<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Enums\Role as RoleName;
use App\Models\Application;
use App\Models\Evaluation;
use App\Models\Program;
use App\Models\User;
use App\Notifications\ApplicationSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class ApplicationScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_submission_is_scored_without_changing_the_human_status(): void
    {
        $recruiter = TalentWorld::recruiter();
        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs',
            'description' => 'laravel react',
            'is_open' => true,
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $program), $this->payload())
            ->assertRedirect();

        $application = Application::query()->first();

        $this->assertNotNull($application);
        $this->assertSame(ApplicationStatus::Received, $application->status);
        $this->assertNull($application->currentEvaluation);
        $this->assertNull($application->examination_authorized_at);
        $this->assertTrue($user->fresh()->hasRole(RoleName::Candidate));
        $this->assertSame(1, $recruiter->notifications()->count());
        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame(
            ApplicationSubmittedNotification::class,
            $user->notifications()->first()->type,
        );

        $this->actingAs($recruiter)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('applications.data.0.examined', false)
                ->where('applications.data.0.score', null));

        $this->actingAs($recruiter)
            ->patch(route('admin.applications.examination', $application))
            ->assertRedirect();

        $application->refresh();

        $this->assertNotNull($application->examination_authorized_at);
        $this->assertNotNull($application->currentEvaluation);
        $this->assertSame('v2', $application->currentEvaluation->rubric_version);
        $this->assertSame(1, Evaluation::query()->count());

        $this->actingAs($recruiter)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('applications.data.0.examined', true)
                ->where('applications.data.0.score', $application->currentEvaluation->total_score));
    }

    public function test_an_invalid_submission_is_rejected_in_french(): void
    {
        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs',
            'is_open' => true,
        ]);

        $this->actingAs($user)
            ->post(route('applications.store', $program), [
                'education_level' => EducationLevel::Licence->value,
                'years_experience' => 1,
                'projects_count' => 1,
                'availability' => Availability::Immediate->value,
                'motivation_text' => 'Je veux rejoindre le programme.',
            ])
            ->assertSessionHasErrors([
                'country' => 'Le champ pays est obligatoire.',
            ]);

        $this->assertSame(0, Application::query()->count());
        $this->assertFalse($user->fresh()->isCandidate());
    }

    public function test_a_closed_program_cannot_be_applied_to(): void
    {
        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Fermé',
            'slug' => 'ferme',
            'is_open' => false,
        ]);

        $this->actingAs($user)
            ->get(route('applications.create', $program))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('applications.store', $program), $this->payload())
            ->assertForbidden();

        $this->assertSame(0, Application::query()->count());
    }

    public function test_demo_seed_creates_a_recruiter_programs_and_scored_applications(): void
    {
        $this->seed();

        $recruiter = User::query()->where('email', 'recruiter@talent-engine.test')->first();

        $this->assertNotNull($recruiter);
        $this->assertTrue($recruiter->hasRole(RoleName::User));
        $this->assertFalse($recruiter->hasPermission('application:view'));

        $superAdmin = User::query()->where('email', 'owner@talent-engine.test')->first();
        $admin = User::query()->where('email', 'admin@talent-engine.test')->first();
        $candidate = User::query()->where('email', 'amina@talent-engine.test')->first();

        $this->assertTrue($superAdmin?->hasRole(RoleName::SuperAdmin));
        $this->assertTrue($superAdmin?->hasPermission('user:manage'));
        $this->assertFalse($superAdmin?->hasPermission('program:manage'));
        $this->assertTrue($admin?->hasRole(RoleName::Recruiter));
        $this->assertTrue($admin?->hasPermission('program:manage'));
        $this->assertFalse($admin?->hasPermission('user:manage'));
        $this->assertTrue($candidate?->hasRole(RoleName::Candidate));
        $this->assertTrue(Program::query()->where('slug', 'intelligence-artificielle')->where('is_open', true)->exists());
        $this->assertTrue(Program::query()->where('slug', 'svt-5e-togo')->where('is_open', true)->exists());
        $this->assertSame(12, Program::query()->count());
        $this->assertSame(3, Application::query()->count());
        $this->assertSame(3, Evaluation::query()->where('is_current', true)->count());

        $scores = Application::query()
            ->with(['currentEvaluation', 'candidate.user'])
            ->get()
            ->mapWithKeys(fn (Application $application) => [
                $application->candidate->user->email => $application->currentEvaluation->total_score,
            ]);

        $this->assertGreaterThan($scores['luc@talent-engine.test'], $scores['amina@talent-engine.test']);
        $this->assertGreaterThan($scores['sara@talent-engine.test'], $scores['luc@talent-engine.test']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'education_level' => EducationLevel::Licence->value,
            'years_experience' => 2,
            'projects_count' => 1,
            'availability' => Availability::Immediate->value,
            'motivation_text' => 'Je veux rejoindre le programme.',
        ];
    }
}
