<?php

namespace App\Services\Scoring;

use App\Models\Application;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    public function __construct(
        private SkillsCriterion $skills,
        private ExperienceCriterion $experience,
        private EducationCriterion $education,
        private ProofsCriterion $proofs,
        private MotivationCriterion $motivation,
        private AvailabilityCriterion $availability,
        private LocationCriterion $location,
        private SuggestedStatus $suggestedStatus,
    ) {}

    /**
     * Score the application and keep the new evaluation as the current one.
     */
    public function score(Application $application): Evaluation
    {
        $application->load([
            'program.skills',
            'program.courses.skills',
            'offer.skills',
            'course.skills',
            'course.program',
            'skills',
            'documents',
            'candidate.user',
        ]);

        $results = [
            $this->skills->evaluate($application),
            $this->experience->evaluate($application),
            $this->education->evaluate($application),
            $this->proofs->evaluate($application),
            $this->motivation->evaluate($application),
            $this->availability->evaluate($application),
            $this->location->evaluate($application),
        ];

        $total = (int) round(min(100, array_sum(array_map(
            fn (CriterionResult $result) => $result->points,
            $results,
        ))));

        $flags = array_values(array_unique(array_merge(...array_map(
            fn (CriterionResult $result) => $result->flags,
            $results,
        ))));

        $suggested = $this->suggestedStatus->from(
            $total,
            $application->scoringCatalog()->isOpen,
            $this->isComplete($application),
        );

        return DB::transaction(function () use ($application, $results, $total, $flags, $suggested) {
            $application->evaluations()->where('is_current', true)->update(['is_current' => false]);

            $evaluation = $application->evaluations()->create([
                'rubric_version' => config('scoring.rubric_version'),
                'total_score' => $total,
                'completeness_rate' => $this->completeness($application),
                'suggested_status' => $suggested,
                'flags' => $flags === [] ? null : $flags,
                'is_current' => true,
                'computed_at' => now(),
            ]);

            foreach ($results as $result) {
                $evaluation->scores()->create([
                    'criterion' => $result->criterion,
                    'points' => $result->points,
                    'max_points' => $result->maxPoints,
                    'explanation' => $result->explanation,
                ]);
            }

            return $evaluation->load('scores');
        });
    }

    /**
     * Recalculate every application, optionally limited to one program.
     */
    public function rescoreAll(?string $programId = null): int
    {
        $count = 0;

        Application::query()
            ->when(filled($programId), fn ($query) => $query->where('program_id', $programId))
            ->orderBy('id')
            ->chunkById(50, function ($applications) use (&$count) {
                foreach ($applications as $application) {
                    $this->score($application);
                    $count++;
                }
            });

        return $count;
    }

    private function isComplete(Application $application): bool
    {
        return filled($application->motivation_text)
            && $application->education_level !== null
            && $application->availability !== null
            && filled($application->candidate?->country);
    }

    private function documentsComplete(Application $application): bool
    {
        $requested = $application->scoringCatalog()->requestedDocuments;

        if ($requested === []) {
            return true;
        }

        $provided = $application->documents->pluck('label');

        foreach ($requested as $label) {
            if (! $provided->contains($label)) {
                return false;
            }
        }

        return true;
    }

    private function completeness(Application $application): int
    {
        $filled = [
            filled($application->education_level),
            filled($application->field_of_study),
            $application->years_experience !== null,
            $application->projects_count !== null,
            filled($application->github_url),
            filled($application->portfolio_url),
            filled($application->linkedin_url),
            filled($application->availability),
            filled($application->motivation_text),
            filled($application->cv_path),
            $this->documentsComplete($application),
            filled($application->candidate?->phone),
            filled($application->candidate?->country),
            filled($application->candidate?->city),
            $application->skills->isNotEmpty(),
        ];

        return (int) round(100 * count(array_filter($filled)) / count($filled));
    }
}
