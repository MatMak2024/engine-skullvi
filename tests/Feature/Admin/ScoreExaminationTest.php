<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class ScoreExaminationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.scores.index'))->assertRedirect(route('login'));
    }

    public function test_a_simple_user_cannot_open_the_scores(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'user')->firstOrFail());

        $this->actingAs($user)->get(route('admin.scores.index'))->assertForbidden();
    }

    public function test_a_recruiter_sees_the_ranking_of_a_program(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $program = TalentWorld::program();
        $application = TalentWorld::application($program);
        $evaluation = $application->evaluations()->create([
            'rubric_version' => 'v2',
            'total_score' => 65,
            'completeness_rate' => 80,
            'suggested_status' => 'to_review',
            'flags' => ['short_motivation'],
            'is_current' => true,
            'computed_at' => now(),
        ]);
        $application->forceFill(['examination_authorized_at' => now()])->save();
        $evaluation->scores()->create([
            'criterion' => 'skills',
            'points' => 24,
            'max_points' => 30,
            'explanation' => 'Compétences requises couvertes.',
        ]);

        $recruiter = User::factory()->create();
        $recruiter->roles()->attach(Role::query()->where('slug', 'recruiter')->firstOrFail());

        $this->actingAs($recruiter)
            ->get(route('admin.scores.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/scores/index')
                ->where('examination.rubric_version', 'v2')
                ->where('examination.thresholds.priority', 70)
                ->where('examination.programs.0.name', 'Dev')
                ->where('examination.programs.0.items.0.rank', 1)
                ->where('examination.programs.0.items.0.score', 65)
                ->where('examination.programs.0.items.0.suggested_status', 'to_review')
                ->where('examination.programs.0.items.0.status', 'received')
                ->where('examination.programs.0.items.0.flags.0', 'Motivation courte')
                ->where('examination.programs.0.items.0.scores.0.criterion', 'skills')
                ->where('examination.programs.0.items.0.scores.0.points', 24)
                ->where('examination.programs.0.items.0.scores.0.explanation', 'Compétences requises couvertes.')
                ->has('examination.programs.0.items.0.scores', 7),
            );
    }
}
