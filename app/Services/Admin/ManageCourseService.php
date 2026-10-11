<?php

namespace App\Services\Admin;

use App\Models\Course;
use App\Models\Skill;
use App\Support\RequestedDocuments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageCourseService
{
    /**
     * @param  array{name: string, description: ?string, is_open: bool, program_id?: ?string, required_skills: list<string>, bonus_skills: list<string>}  $data
     */
    public function save(array $data, ?Course $course = null): Course
    {
        return DB::transaction(function () use ($data, $course) {
            $course ??= new Course;
            $nameChanged = ! $course->exists || $course->name !== $data['name'];

            $course->fill([
                'name' => $data['name'],
                'description' => ($data['description'] ?? null) ?: null,
                'is_open' => $data['is_open'],
                'program_id' => ($data['program_id'] ?? null) ?: null,
                'requested_documents' => RequestedDocuments::fromText($data['requested_documents'] ?? null),
                'slug' => $nameChanged
                    ? $this->uniqueSlug($data['name'], $course->getKey())
                    : $course->slug,
            ]);
            $course->save();

            $sync = [];

            foreach ($data['required_skills'] as $skillId) {
                $sync[$skillId] = ['is_required' => true];
            }

            foreach ($data['bonus_skills'] as $skillId) {
                $sync[$skillId] = ['is_required' => false];
            }

            Skill::ensureSelectable(
                array_keys($sync),
                $course->exists ? $course->skills()->pluck('skills.id')->all() : [],
            );
            $course->skills()->sync($sync);

            return $course;
        });
    }

    public function toggleSuspension(Course $course): bool
    {
        $course->forceFill([
            'is_open' => ! $course->is_open,
        ])->save();

        return ! $course->is_open;
    }

    public function delete(Course $course): void
    {
        if ($course->applications()->exists()) {
            throw ValidationException::withMessages([
                'course' => 'Ce cours a des candidatures. Fermez-le au lieu de le supprimer.',
            ]);
        }

        $course->delete();
    }

    private function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'cours';
        $slug = $base;
        $suffix = 2;

        while (
            Course::query()
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
