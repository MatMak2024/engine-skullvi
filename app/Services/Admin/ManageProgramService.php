<?php

namespace App\Services\Admin;

use App\Enums\WorkMode;
use App\Models\Course;
use App\Models\Program;
use App\Models\Skill;
use App\Support\RequestedDocuments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageProgramService
{
    /**
     * @param  array{name: string, description: ?string, is_open: bool, work_mode: string, work_country: ?string, work_city: ?string, required_skills: list<string>, bonus_skills: list<string>, course_ids?: list<string>}  $data
     */
    public function save(array $data, ?Program $program = null): Program
    {
        return DB::transaction(function () use ($data, $program) {
            $program ??= new Program;
            $nameChanged = ! $program->exists || $program->name !== $data['name'];

            $mode = WorkMode::from($data['work_mode']);
            $onSite = $mode !== WorkMode::Remote;

            $program->fill([
                'name' => $data['name'],
                'description' => ($data['description'] ?? null) ?: null,
                'is_open' => $data['is_open'],
                'work_mode' => $mode,
                'work_country' => $onSite ? (($data['work_country'] ?? null) ?: null) : null,
                'work_city' => $onSite ? (($data['work_city'] ?? null) ?: null) : null,
                'requested_documents' => RequestedDocuments::fromText($data['requested_documents'] ?? null),
                'slug' => $nameChanged
                    ? $this->uniqueSlug($data['name'], $program->getKey())
                    : $program->slug,
            ]);
            $program->save();

            $sync = [];

            foreach ($data['required_skills'] as $skillId) {
                $sync[$skillId] = ['is_required' => true];
            }

            foreach ($data['bonus_skills'] as $skillId) {
                $sync[$skillId] = ['is_required' => false];
            }

            Skill::ensureSelectable(
                array_keys($sync),
                $program->exists ? $program->skills()->pluck('skills.id')->all() : [],
            );
            $program->skills()->sync($sync);
            $this->syncCourses($program, $data['course_ids'] ?? []);

            return $program;
        });
    }

    /**
     * @param  list<string>  $courseIds
     */
    private function syncCourses(Program $program, array $courseIds): void
    {
        Course::query()
            ->where('program_id', $program->id)
            ->when($courseIds !== [], fn ($query) => $query->whereNotIn('id', $courseIds))
            ->update(['program_id' => null]);

        if ($courseIds === []) {
            return;
        }

        Course::query()
            ->whereIn('id', $courseIds)
            ->where(fn ($query) => $query
                ->whereNull('program_id')
                ->orWhere('program_id', $program->id))
            ->update(['program_id' => $program->id]);
    }

    public function toggleSuspension(Program $program): bool
    {
        $program->forceFill([
            'is_open' => ! $program->is_open,
        ])->save();

        return ! $program->is_open;
    }

    public function delete(Program $program): void
    {
        if ($program->applications()->exists() || $program->courses()->exists()) {
            throw ValidationException::withMessages([
                'program' => 'Ce programme a des cours ou des candidatures. Fermez-le au lieu de le supprimer.',
            ]);
        }

        $program->delete();
    }

    private function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'programme';
        $slug = $base;
        $suffix = 2;

        while (
            Program::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
