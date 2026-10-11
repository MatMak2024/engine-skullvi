<?php

namespace App\Services\Scoring;

use App\Enums\WorkMode;
use App\Models\Course;
use App\Models\Offer;
use App\Models\Program;
use App\Models\Skill;
use Illuminate\Support\Collection;

class ScoringCatalog
{
    /**
     * @param  Collection<int, Skill>  $skills
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public bool $isOpen,
        public Collection $skills,
        public WorkMode $workMode,
        public ?string $workCountry,
        public ?string $workCity,
        public array $requestedDocuments = [],
    ) {}

    public static function fromProgram(Program $program): self
    {
        $program->loadMissing(['skills', 'courses.skills']);

        $courseSkills = self::mergeSkills($program->courses);
        $description = collect([$program->description])
            ->merge($program->courses->pluck('name'))
            ->merge($program->courses->pluck('description'))
            ->filter()
            ->implode(' ');

        return new self(
            $program->name,
            $description !== '' ? $description : null,
            (bool) $program->is_open,
            $courseSkills->isNotEmpty() ? $courseSkills : $program->skills,
            $program->work_mode ?? WorkMode::Remote,
            $program->work_country,
            $program->work_city,
            array_values($program->requested_documents ?? []),
        );
    }

    public static function fromOffer(Offer $offer): self
    {
        $offer->loadMissing('skills');

        return new self(
            $offer->name,
            $offer->description,
            (bool) $offer->is_open,
            $offer->skills,
            $offer->work_mode ?? WorkMode::Remote,
            $offer->work_country,
            $offer->work_city,
            array_values($offer->requested_documents ?? []),
        );
    }

    public static function fromCourse(Course $course): self
    {
        $course->loadMissing(['skills', 'program']);
        $program = $course->program;

        return new self(
            $course->name,
            $course->description,
            (bool) $course->is_open,
            $course->skills,
            $program?->work_mode ?? WorkMode::Remote,
            $program?->work_country,
            $program?->work_city,
            array_values($course->requested_documents ?? []),
        );
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @return Collection<int, Skill>
     */
    private static function mergeSkills(Collection $courses): Collection
    {
        $merged = collect();

        foreach ($courses as $course) {
            foreach ($course->skills as $skill) {
                $existing = $merged->get($skill->id);

                if ($existing === null) {
                    $merged->put($skill->id, $skill);

                    continue;
                }

                if ($skill->pivot->is_required) {
                    $existing->pivot->is_required = true;
                }
            }
        }

        return $merged->values();
    }
}
