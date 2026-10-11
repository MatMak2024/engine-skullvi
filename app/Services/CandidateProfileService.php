<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CandidateProfileService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function save(User $user, array $attributes, ?UploadedFile $cv = null): Candidate
    {
        if ($user->isPermanentlyRemovedAsCandidate()) {
            throw ValidationException::withMessages([
                'profile' => 'Vous ne pouvez plus constituer de profil candidat.',
            ]);
        }

        if ($user->candidate?->isSuspended()) {
            throw ValidationException::withMessages([
                'profile' => 'Votre dossier est suspendu. Vous ne pouvez plus modifier ce profil.',
            ]);
        }

        $this->guardSkills($attributes['skills'] ?? []);

        return DB::transaction(function () use ($user, $attributes, $cv) {
            $country = $attributes['country']
                ?? $user->candidate?->country
                ?? $user->country
                ?? 'Non précisé';

            $candidate = $user->candidate()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $attributes['phone'] ?? $user->phone,
                    'country' => $country,
                    'city' => $attributes['city'] ?? $user->city,
                    'education_level' => $attributes['education_level'] ?? null,
                    'field_of_study' => $attributes['field_of_study'] ?? null,
                    'years_experience' => $attributes['years_experience'] ?? 0,
                    'projects_count' => $attributes['projects_count'] ?? 0,
                    'github_url' => $attributes['github_url'] ?? null,
                    'portfolio_url' => $attributes['portfolio_url'] ?? null,
                    'linkedin_url' => $attributes['linkedin_url'] ?? null,
                    'availability' => $attributes['availability'] ?? null,
                    'interests' => $this->normalizeList($attributes['interests'] ?? []),
                    'other_skills' => $this->normalizeOtherSkills($attributes['other_skills'] ?? []),
                ],
            );

            if ($cv !== null) {
                if ($candidate->cv_path && Storage::disk('local')->exists($candidate->cv_path)) {
                    Storage::disk('local')->delete($candidate->cv_path);
                }

                $candidate->forceFill([
                    'cv_path' => $cv->store('cvs', 'local'),
                ])->save();
            }

            $candidate->skills()->sync($this->skillLevels($attributes['skills'] ?? []));
            $this->syncEntries($candidate->diplomas(), $attributes['diplomas'] ?? [], [
                'title', 'institution', 'year', 'description',
            ]);
            $this->syncEntries($candidate->trainings(), $attributes['trainings'] ?? [], [
                'title', 'organization', 'year', 'description',
            ]);

            return $candidate->fresh(['skills', 'diplomas', 'trainings']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function present(?Candidate $candidate): ?array
    {
        if ($candidate === null) {
            return null;
        }

        $candidate->loadMissing(['skills', 'diplomas', 'trainings']);

        return [
            'phone' => $candidate->phone,
            'country' => $candidate->country,
            'city' => $candidate->city,
            'has_cv' => $candidate->hasCv(),
            'education_level' => $candidate->education_level?->value,
            'field_of_study' => $candidate->field_of_study,
            'years_experience' => $candidate->years_experience,
            'projects_count' => $candidate->projects_count,
            'github_url' => $candidate->github_url,
            'portfolio_url' => $candidate->portfolio_url,
            'linkedin_url' => $candidate->linkedin_url,
            'availability' => $candidate->availability?->value,
            'interests' => $candidate->interests ?? [],
            'other_skills' => $candidate->other_skills ?? [],
            'skills' => $candidate->skills->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'level' => $skill->pivot->level,
            ])->values()->all(),
            'diplomas' => $candidate->diplomas->map(fn ($diploma) => [
                'id' => $diploma->id,
                'title' => $diploma->title,
                'institution' => $diploma->institution,
                'year' => $diploma->year,
                'description' => $diploma->description,
            ])->values()->all(),
            'trainings' => $candidate->trainings->map(fn ($training) => [
                'id' => $training->id,
                'title' => $training->title,
                'organization' => $training->organization,
                'year' => $training->year,
                'description' => $training->description,
            ])->values()->all(),
        ];
    }

    public function storedCvPath(Candidate $candidate): ?string
    {
        return $candidate->hasCv() ? $candidate->cv_path : null;
    }

    /**
     * @param  list<array{id: string, level: string}>  $skills
     */
    private function guardSkills(array $skills): void
    {
        $ids = collect($skills)->pluck('id');
        $open = Skill::query()->where('is_open', true)->whereIn('id', $ids)->pluck('id');

        if ($ids->duplicates()->isNotEmpty() || $ids->diff($open)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'skills' => 'Chaque compétence doit être ouverte et n’apparaître qu’une seule fois.',
            ]);
        }
    }

    /**
     * @param  list<array{id: string, level: string}>  $skills
     * @return array<string, array{level: string}>
     */
    private function skillLevels(array $skills): array
    {
        return collect($skills)
            ->mapWithKeys(fn (array $skill) => [$skill['id'] => ['level' => $skill['level']]])
            ->all();
    }

    /**
     * @param  list<string|array{label?: string}>  $items
     * @return list<string>
     */
    private function normalizeList(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if (is_string($item)) {
                    return trim($item);
                }

                return trim((string) ($item['label'] ?? ''));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<array{name?: string, level?: string}>  $skills
     * @return list<array{name: string, level: string}>
     */
    private function normalizeOtherSkills(array $skills): array
    {
        return collect($skills)
            ->map(function (array $skill) {
                $name = trim((string) ($skill['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'level' => (string) ($skill['level'] ?? 'beginner'),
                ];
            })
            ->filter()
            ->unique('name')
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Relations\HasMany<\Illuminate\Database\Eloquent\Model, Candidate>  $relation
     * @param  list<array<string, mixed>>  $entries
     * @param  list<string>  $columns
     */
    private function syncEntries($relation, array $entries, array $columns): void
    {
        $relation->delete();

        foreach ($entries as $entry) {
            $title = trim((string) ($entry['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $payload = ['title' => $title];

            foreach ($columns as $column) {
                if ($column === 'title') {
                    continue;
                }

                $value = $entry[$column] ?? null;
                $payload[$column] = is_string($value) ? trim($value) ?: null : $value;
            }

            $relation->create($payload);
        }
    }
}
