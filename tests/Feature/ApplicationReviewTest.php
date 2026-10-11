<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ScoringCriterion;
use App\Models\Application;
use App\Services\ApplicationReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class ApplicationReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_uses_score_then_proofs_then_submission_date(): void
    {
        $program = TalentWorld::program();
        $higher = $this->scored($program, 80, 0, now()->subDay());
        $early = $this->scored($program, 65, 8, now()->subDays(3));
        $late = $this->scored($program, 65, 8, now()->subDay());
        $fewerProofs = $this->scored($program, 65, 2, now()->subDays(2));

        $ranked = app(ApplicationReviewService::class)->ranked()->pluck('id')->all();

        $this->assertSame([
            $higher->id,
            $early->id,
            $late->id,
            $fewerProofs->id,
        ], $ranked);
    }

    public function test_ranking_can_filter_by_program_and_status(): void
    {
        $program = TalentWorld::program();
        $other = TalentWorld::program(['name' => 'Autre', 'slug' => 'autre']);
        $kept = $this->scored($program, 60, 0, now());
        $this->scored($other, 90, 0, now());
        $kept->update(['status' => ApplicationStatus::Accepted]);

        $ranked = app(ApplicationReviewService::class)->ranked($program->id, ApplicationStatus::Accepted->value);

        $this->assertCount(1, $ranked);
        $this->assertSame($kept->id, $ranked->first()->id);
    }

    private function scored($program, int $total, float $proofs, $submittedAt): Application
    {
        $application = TalentWorld::application($program, [
            'submitted_at' => $submittedAt,
        ]);

        $evaluation = $application->evaluations()->create([
            'rubric_version' => 'v1',
            'total_score' => $total,
            'completeness_rate' => 80,
            'suggested_status' => ApplicationStatus::ToReview,
            'is_current' => true,
            'computed_at' => now(),
        ]);

        $evaluation->scores()->create([
            'criterion' => ScoringCriterion::Proofs,
            'points' => $proofs,
            'max_points' => 15,
            'explanation' => 'Preuves de test.',
        ]);

        return $application;
    }
}
