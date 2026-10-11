<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\Availability;
use App\Enums\EducationLevel;
use App\Models\Application;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class TalentRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_public_catalog(): void
    {
        $this->get(route('programs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('programs/index'));

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('courses/index')
                ->has('courses')
                ->has('programs'));

        $this->get(route('offers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('offers/index'));
    }

    public function test_a_candidate_can_open_programs_and_not_the_recruiter_space(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('programs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('programs/index'));

        $this->actingAs($user)
            ->get(route('recruiter.applications.index'))
            ->assertForbidden();
    }

    public function test_a_recruiter_can_open_the_ranked_list(): void
    {
        $user = TalentWorld::recruiter(['application:view']);

        $this->actingAs($user)
            ->get(route('recruiter.applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('recruiter/applications/index'));
    }

    public function test_applying_creates_a_candidate_and_blocks_a_second_application(): void
    {
        $user = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs',
            'is_open' => true,
        ]);

        $payload = [
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'education_level' => EducationLevel::Licence->value,
            'years_experience' => 2,
            'projects_count' => 1,
            'availability' => Availability::Immediate->value,
            'motivation_text' => 'Je veux rejoindre le programme.',
        ];

        $this->actingAs($user)
            ->post(route('applications.store', $program), $payload)
            ->assertRedirect();

        $application = Application::query()->first();

        $this->assertNotNull($application);
        $this->assertSame($user->id, $application->candidate_id);
        $this->assertSame(ApplicationStatus::Received, $application->status);
        $this->assertTrue($user->fresh()->isCandidate());

        $this->actingAs($user)
            ->get(route('applications.show', $application))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('applications.store', $program), $payload)
            ->assertSessionHasErrors('program');
    }

    public function test_a_candidate_cannot_view_someone_elses_application(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $program = Program::query()->create([
            'name' => 'Développeurs',
            'slug' => 'developpeurs',
            'is_open' => true,
        ]);

        $candidate = $owner->candidate()->create(['country' => 'Sénégal']);
        $application = $candidate->applications()->create([
            'program_id' => $program->id,
            'education_level' => EducationLevel::Bac,
            'availability' => Availability::Later,
            'motivation_text' => 'Motivation.',
            'status' => ApplicationStatus::Received,
            'submitted_at' => now(),
        ]);

        $this->actingAs($other)
            ->get(route('applications.show', $application))
            ->assertForbidden();
    }
}
