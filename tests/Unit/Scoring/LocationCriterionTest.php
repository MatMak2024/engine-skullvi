<?php

namespace Tests\Unit\Scoring;

use App\Services\Scoring\LocationCriterion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class LocationCriterionTest extends TestCase
{
    use RefreshDatabase;

    public function test_remote_work_rewards_a_declared_place_without_matching_it(): void
    {
        $program = TalentWorld::program(['work_mode' => 'remote']);
        $application = TalentWorld::application($program, [
            'country' => 'France',
            'city' => 'Paris',
        ]);
        $application->candidate->user->forceFill([
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'address' => '12 avenue Cheikh Anta Diop',
        ])->save();

        $score = app(LocationCriterion::class)->evaluate($application);

        $this->assertSame(5.0, $score->points);
        $this->assertSame(5.0, $score->maxPoints);
        $this->assertStringContainsString('et l’adresse sont renseignés', $score->explanation);
    }

    public function test_remote_work_scores_the_country_alone(): void
    {
        $application = TalentWorld::application(TalentWorld::program(), [
            'city' => null,
        ]);

        $score = app(LocationCriterion::class)->evaluate($application);

        $this->assertSame(3.0, $score->points);
    }

    public function test_remote_work_without_a_place_scores_zero(): void
    {
        $application = TalentWorld::application(TalentWorld::program(), [
            'country' => '',
            'city' => null,
        ]);

        $score = app(LocationCriterion::class)->evaluate($application);

        $this->assertSame(0.0, $score->points);
    }

    public function test_onsite_work_scores_a_matching_place_and_ignores_another_country(): void
    {
        $program = TalentWorld::program([
            'work_mode' => 'onsite',
            'work_country' => 'senegal',
            'work_city' => 'Dakar',
        ]);
        $matching = TalentWorld::application($program);
        $matching->candidate->user->forceFill([
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'zip' => '12500',
        ])->save();

        $elsewhere = TalentWorld::application($program, [
            'country' => 'Togo',
            'city' => 'Lomé',
        ]);

        $criterion = app(LocationCriterion::class);

        $this->assertSame(5.0, $criterion->evaluate($matching)->points);
        $this->assertSame(0.0, $criterion->evaluate($elsewhere)->points);
    }

    public function test_onsite_work_gives_partial_points_when_only_the_country_matches(): void
    {
        $program = TalentWorld::program([
            'work_mode' => 'onsite',
            'work_country' => 'Togo',
            'work_city' => 'Lomé',
        ]);
        $application = TalentWorld::application($program, [
            'country' => 'Togo',
            'city' => 'Kara',
        ]);

        $score = app(LocationCriterion::class)->evaluate($application);

        $this->assertSame(2.0, $score->points);
        $this->assertStringContainsString('pas la ville', $score->explanation);
    }

    public function test_the_account_place_overrides_the_candidate_profile(): void
    {
        $program = TalentWorld::program([
            'work_mode' => 'onsite',
            'work_country' => 'Togo',
            'work_city' => 'Lomé',
        ]);
        $application = TalentWorld::application($program, [
            'country' => 'Sénégal',
            'city' => 'Dakar',
        ]);
        $application->candidate->user->forceFill([
            'country' => 'Togo',
            'city' => 'Lomé',
        ])->save();

        $score = app(LocationCriterion::class)->evaluate($application);

        $this->assertSame(4.0, $score->points);
    }

    public function test_hybrid_work_keeps_the_country_points_and_adds_a_matching_city(): void
    {
        $program = TalentWorld::program([
            'work_mode' => 'hybrid',
            'work_country' => 'Togo',
            'work_city' => 'Lomé',
        ]);
        $away = TalentWorld::application($program, [
            'country' => 'Sénégal',
            'city' => 'Dakar',
        ]);
        $local = TalentWorld::application($program, [
            'country' => 'Togo',
            'city' => 'Lomé',
        ]);
        $local->candidate->user->forceFill([
            'address' => 'Quartier administratif',
        ])->save();

        $criterion = app(LocationCriterion::class);

        $this->assertSame(3.0, $criterion->evaluate($away)->points);
        $this->assertSame(5.0, $criterion->evaluate($local)->points);
    }

    public function test_an_onsite_offer_without_a_country_uses_the_declared_place(): void
    {
        $program = TalentWorld::program([
            'work_mode' => 'onsite',
            'work_country' => null,
            'work_city' => null,
        ]);
        $application = TalentWorld::application($program);

        $score = app(LocationCriterion::class)->evaluate($application);

        $this->assertSame(4.0, $score->points);
    }
}
