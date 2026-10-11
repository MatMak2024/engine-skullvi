<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ScoringCriterion;
use App\Models\Application;
use App\Models\Course;
use App\Models\Offer;
use App\Models\Program;
use App\Models\Skill;
use App\Services\Scoring\ScoringCatalog;
use Illuminate\Support\Collection;

class ScoreExamination
{
    public function __construct(private ApplicationReviewService $reviews) {}

    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        $ranked = $this->reviews->ranked()->filter(
            fn (Application $application) => $application->examination_authorized_at !== null,
        );

        return [
            'rubric_version' => config('scoring.rubric_version'),
            'thresholds' => [
                'priority' => (int) config('scoring.thresholds.priority'),
                'to_review' => (int) config('scoring.thresholds.to_review'),
            ],
            'criteria' => collect(ScoringCriterion::cases())->map(fn (ScoringCriterion $criterion) => [
                'key' => $criterion->value,
                'max' => (float) config('scoring.criteria.'.$criterion->value.'.max'),
            ])->values()->all(),
            'offers' => $this->boards(
                Offer::query()->with('skills')->orderBy('name')->get(),
                $ranked->filter(fn (Application $application) => $application->offer_id !== null)->groupBy('offer_id'),
                fn (Offer $offer) => ScoringCatalog::fromOffer($offer),
            ),
            'programs' => $this->boards(
                Program::query()->with(['skills', 'courses.skills'])->orderBy('name')->get(),
                $ranked->filter(
                    fn (Application $application) => $application->program_id !== null
                        && $application->course_id === null
                        && $application->offer_id === null,
                )->groupBy('program_id'),
                fn (Program $program) => ScoringCatalog::fromProgram($program),
            ),
            'courses' => $this->boards(
                Course::query()->with(['skills', 'program'])->orderBy('name')->get(),
                $ranked->filter(
                    fn (Application $application) => $application->course_id !== null && $application->offer_id === null,
                )->groupBy('course_id'),
                fn (Course $course) => ScoringCatalog::fromCourse($course),
                true,
            ),
        ];
    }

    /**
     * @param  Collection<int, Course|Offer|Program>  $targets
     * @param  Collection<string, Collection<int, Application>>  $grouped
     * @return list<array<string, mixed>>
     */
    private function boards(Collection $targets, Collection $grouped, callable $catalogFor, bool $withProgram = false): array
    {
        return $targets->map(function (Course|Offer|Program $target) use ($grouped, $catalogFor, $withProgram) {
            /** @var ScoringCatalog $catalog */
            $catalog = $catalogFor($target);
            /** @var Collection<int, Application> $applications */
            $applications = $grouped->get($target->id, collect());

            return [
                'id' => $target->id,
                'name' => $target->name,
                'open' => (bool) $target->is_open,
                'program_name' => $withProgram && $target instanceof Course ? $target->program?->name : null,
                'work_mode' => $catalog->workMode->value,
                'work_country' => $catalog->workCountry,
                'work_city' => $catalog->workCity,
                'skills' => $catalog->skills
                    ->sortBy(fn (Skill $skill) => sprintf('%d-%s', ($skill->pivot->is_required ?? true) ? 0 : 1, mb_strtolower($skill->name)))
                    ->map(fn (Skill $skill) => [
                        'name' => $skill->name,
                        'required' => (bool) ($skill->pivot->is_required ?? true),
                    ])
                    ->values()
                    ->all(),
                'summary' => $this->summary($applications),
                'items' => $applications->values()->map(
                    fn (Application $application, int $index) => $this->item($application, $index + 1),
                )->all(),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return array<string, int|null>
     */
    private function summary(Collection $applications): array
    {
        $count = fn (ApplicationStatus $status) => $applications
            ->filter(fn (Application $application) => $application->currentEvaluation?->suggested_status === $status)
            ->count();

        return [
            'count' => $applications->count(),
            'priority' => $count(ApplicationStatus::Priority),
            'to_review' => $count(ApplicationStatus::ToReview),
            'low_fit' => $count(ApplicationStatus::LowFit),
            'incomplete' => $count(ApplicationStatus::Incomplete),
            'ineligible' => $count(ApplicationStatus::Ineligible),
            'unscored' => $applications->filter(fn (Application $application) => $application->currentEvaluation === null)->count(),
            'best' => $applications->max(fn (Application $application) => $application->currentEvaluation?->total_score),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(Application $application, int $rank): array
    {
        $evaluation = $application->currentEvaluation;
        $scores = $evaluation?->scores->keyBy(fn ($score) => $score->criterion->value) ?? collect();

        return [
            'rank' => $rank,
            'id' => $application->id,
            'name' => $application->candidate->user->displayName(),
            'email' => $application->candidate->user->email,
            'city' => $application->candidate->city,
            'country' => $application->candidate->country,
            'href' => route('admin.applications.show', $application),
            'score' => $evaluation?->total_score,
            'completeness_rate' => $evaluation?->completeness_rate,
            'suggested_status' => $evaluation?->suggested_status?->value,
            'status' => $application->status->value,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'flags' => collect($evaluation?->flags ?? [])->map(fn (string $flag) => $this->flagLabel($flag))->values()->all(),
            'scores' => collect(ScoringCriterion::cases())->map(function (ScoringCriterion $criterion) use ($scores) {
                $score = $scores->get($criterion->value);

                return [
                    'criterion' => $criterion->value,
                    'points' => $score !== null ? (float) $score->points : null,
                    'max_points' => $score !== null ? (float) $score->max_points : (float) config('scoring.criteria.'.$criterion->value.'.max'),
                    'explanation' => $score?->explanation,
                ];
            })->values()->all(),
        ];
    }

    private function flagLabel(string $flag): string
    {
        return match ($flag) {
            'short_motivation' => 'Motivation courte',
            'no_skills' => 'Aucune compétence déclarée',
            'invalid_github' => 'GitHub invalide',
            'invalid_portfolio' => 'Portfolio invalide',
            'invalid_linkedin' => 'LinkedIn invalide',
            default => $flag,
        };
    }
}
