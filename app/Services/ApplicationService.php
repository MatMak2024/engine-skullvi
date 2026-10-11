<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\Role as RoleName;
use App\Models\Application;
use App\Models\Course;
use App\Models\Offer;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ApplicationSubmittedNotification;
use App\Notifications\NewApplicationNotification;
use App\Services\Scoring\ScoringCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(private SavedOpeningService $savedOpenings) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function submit(User $user, Program|Course|Offer $target, array $attributes, ?UploadedFile $cv = null, array $documents = []): Application
    {
        $column = $this->targetColumn($target);
        [$errorKey, $label] = $this->targetCopy($target);

        if ($user->isPermanentlyRemovedAsCandidate()) {
            throw ValidationException::withMessages([
                $errorKey => 'Vous ne pouvez plus être candidat. Votre compte utilisateur reste actif.',
            ]);
        }

        if ($user->candidate?->isSuspended()) {
            throw ValidationException::withMessages([
                $errorKey => 'Votre dossier est suspendu. Vous ne pouvez plus postuler.',
            ]);
        }

        if (! $target->is_open) {
            throw ValidationException::withMessages([
                $errorKey => $label.' n\'accepte plus de candidatures.',
            ]);
        }

        if ($this->alreadyApplied($user, $target)) {
            throw ValidationException::withMessages([
                $errorKey => 'Vous avez déjà postulé à '.lcfirst($label).'.',
            ]);
        }

        $useProfileCv = (bool) ($attributes['use_profile_cv'] ?? false);
        $profileCv = $user->candidate?->hasCv() ? $user->candidate->cv_path : null;

        if ($target instanceof Offer && $cv === null && ! ($useProfileCv && $profileCv)) {
            throw ValidationException::withMessages([
                'cv' => 'Le CV est obligatoire pour une offre.',
            ]);
        }

        $this->guardSkills($target, $attributes['skills'] ?? []);

        return DB::transaction(function () use ($user, $target, $attributes, $cv, $documents, $column, $useProfileCv, $profileCv) {
            $candidate = $user->candidate()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $attributes['phone'] ?? null,
                    'country' => $attributes['country'],
                    'city' => $attributes['city'] ?? null,
                    'education_level' => $attributes['education_level'],
                    'field_of_study' => $attributes['field_of_study'] ?? null,
                    'years_experience' => $attributes['years_experience'],
                    'projects_count' => $attributes['projects_count'],
                    'github_url' => $attributes['github_url'] ?? null,
                    'portfolio_url' => $attributes['portfolio_url'] ?? null,
                    'linkedin_url' => $attributes['linkedin_url'] ?? null,
                    'availability' => $attributes['availability'],
                ],
            );

            $cvPath = null;

            if ($cv !== null) {
                $cvPath = $cv->store('cvs', 'local');
                $profilePath = 'cvs/'.Str::uuid().'.pdf';
                Storage::disk('local')->copy($cvPath, $profilePath);

                if ($candidate->cv_path && Storage::disk('local')->exists($candidate->cv_path)) {
                    Storage::disk('local')->delete($candidate->cv_path);
                }

                $candidate->forceFill(['cv_path' => $profilePath])->save();
            } elseif ($useProfileCv && is_string($profileCv)) {
                $cvPath = 'cvs/'.Str::uuid().'.pdf';
                Storage::disk('local')->copy($profileCv, $cvPath);
            }

            $application = $candidate->applications()->create([
                $column => $target->id,
                'education_level' => $attributes['education_level'],
                'field_of_study' => $attributes['field_of_study'] ?? null,
                'years_experience' => $attributes['years_experience'],
                'projects_count' => $attributes['projects_count'],
                'github_url' => $attributes['github_url'] ?? null,
                'portfolio_url' => $attributes['portfolio_url'] ?? null,
                'linkedin_url' => $attributes['linkedin_url'] ?? null,
                'availability' => $attributes['availability'],
                'motivation_text' => $attributes['motivation_text'],
                'cv_path' => $cvPath,
                'status' => ApplicationStatus::Received,
                'submitted_at' => now(),
            ]);

            $skillLevels = $this->skillLevels($attributes['skills'] ?? []);
            $application->skills()->attach($skillLevels);
            $candidate->skills()->syncWithoutDetaching($skillLevels);
            $this->storeDocuments($application, $target, $documents);

            $role = Role::query()->firstOrCreate(
                ['slug' => RoleName::Candidate],
                ['name' => 'Candidat'],
            );
            $user->roles()->syncWithoutDetaching([$role->id]);

            DB::afterCommit(function () use ($application, $user): void {
                $staff = User::query()
                    ->whereHas('roles', fn ($query) => $query->whereIn('slug', [
                        RoleName::Recruiter->value,
                        RoleName::SuperAdmin->value,
                    ]))
                    ->get();

                Notification::send($staff, new NewApplicationNotification($application));
                $user->notify(new ApplicationSubmittedNotification($application));
            });

            $this->savedOpenings->forget($user, $target);

            return $application;
        });
    }

    /**
     * @return Collection<int, Application>
     */
    public function forCandidate(User $user): Collection
    {
        return Application::query()
            ->where('candidate_id', $user->id)
            ->with([
                'program:id,name',
                'course:id,name',
                'offer:id,name',
                'currentEvaluation:id,application_id,total_score,suggested_status,completeness_rate',
            ])
            ->latest('submitted_at')
            ->get();
    }

    /**
     * @param  array<string, UploadedFile|null>  $documents
     */
    private function storeDocuments(Application $application, Program|Course|Offer $target, array $documents): void
    {
        $requested = array_values($target->requested_documents ?? []);

        foreach ($documents as $label => $file) {
            if (! $file instanceof UploadedFile || ! in_array((string) $label, $requested, true)) {
                continue;
            }

            $application->documents()->create([
                'label' => (string) $label,
                'path' => $file->store('documents', 'local'),
            ]);
        }
    }

    /**
     * Path of a stored CV, limited to the private cvs directory.
     */
    public function storedCvPath(Application $application): ?string
    {
        $path = $application->cv_path;

        if (! is_string($path) || ! str_starts_with($path, 'cvs/') || str_contains($path, '..')) {
            return null;
        }

        return Storage::disk('local')->exists($path) ? $path : null;
    }

    public function alreadyApplied(User $user, Program|Course|Offer $target): bool
    {
        return Application::query()
            ->where('candidate_id', $user->id)
            ->where($this->targetColumn($target), $target->id)
            ->exists();
    }

    private function targetColumn(Program|Course|Offer $target): string
    {
        return match (true) {
            $target instanceof Offer => 'offer_id',
            $target instanceof Course => 'course_id',
            default => 'program_id',
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function targetCopy(Program|Course|Offer $target): array
    {
        return match (true) {
            $target instanceof Offer => ['offer', 'Cette offre'],
            $target instanceof Course => ['course', 'Ce cours'],
            default => ['program', 'Ce programme'],
        };
    }

    /**
     * @param  array<int, array{id: string, level: string}>  $skills
     */
    private function guardSkills(Program|Course|Offer $target, array $skills): void
    {
        $catalog = match (true) {
            $target instanceof Offer => ScoringCatalog::fromOffer($target),
            $target instanceof Course => ScoringCatalog::fromCourse($target),
            default => ScoringCatalog::fromProgram($target),
        };
        $allowed = $catalog->skills->pluck('id');
        $given = collect($skills)->pluck('id');

        if ($given->duplicates()->isNotEmpty() || $given->diff($allowed)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'skills' => 'Chaque compétence doit appartenir à ce dossier, une seule fois.',
            ]);
        }
    }

    /**
     * @param  array<int, array{id: string, level: string}>  $skills
     * @return array<string, array{level: string}>
     */
    private function skillLevels(array $skills): array
    {
        return collect($skills)
            ->mapWithKeys(fn (array $skill) => [$skill['id'] => ['level' => $skill['level']]])
            ->all();
    }
}
