<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\Role as RoleName;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class TalentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recruiter_without_the_permission_cannot_open_the_list(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'Recruteur',
            'slug' => RoleName::Recruiter,
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->get(route('recruiter.applications.index'))
            ->assertForbidden();
    }

    public function test_a_recruiter_can_change_status_and_add_a_note(): void
    {
        $recruiter = TalentWorld::recruiter();
        $program = TalentWorld::program();
        $application = TalentWorld::application($program);

        $this->actingAs($recruiter)
            ->patch(route('recruiter.applications.update-status', $application), [
                'status' => ApplicationStatus::Accepted->value,
            ])
            ->assertRedirect(route('recruiter.applications.show', $application));

        $this->assertSame(ApplicationStatus::Accepted, $application->fresh()->status);

        $this->actingAs($recruiter)
            ->post(route('recruiter.review-notes.store', $application), [
                'note' => 'Profil à rencontrer.',
            ])
            ->assertRedirect(route('recruiter.applications.show', $application));

        $this->assertDatabaseHas('review_notes', [
            'application_id' => $application->id,
            'user_id' => $recruiter->id,
            'note' => 'Profil à rencontrer.',
        ]);
    }

    public function test_a_candidate_cannot_change_status_or_comment(): void
    {
        $program = TalentWorld::program();
        $application = TalentWorld::application($program);
        $candidate = $application->candidate->user;

        $this->actingAs($candidate)
            ->patch(route('recruiter.applications.update-status', $application), [
                'status' => ApplicationStatus::Accepted->value,
            ])
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('recruiter.review-notes.store', $application), [
                'note' => 'Note interdite.',
            ])
            ->assertForbidden();
    }

    public function test_a_user_can_create_a_professional_profile_and_a_removed_account_cannot(): void
    {
        $user = User::factory()->create(['country' => 'Mali']);

        $this->actingAs($user)
            ->post(route('candidate-profile.update'), [
                'country' => 'Sénégal',
                'city' => 'Dakar',
            ])
            ->assertRedirect(route('candidate-profile.edit'));

        $this->assertSame('Sénégal', $user->candidate()->first()->country);
        $this->assertSame('Dakar', $user->candidate()->first()->city);

        $user->forceFill(['candidate_removed_at' => now()])->save();

        $this->actingAs($user->fresh())
            ->post(route('candidate-profile.update'), [
                'country' => 'Togo',
                'city' => 'Lomé',
            ])
            ->assertForbidden();

        $this->assertSame('Sénégal', $user->candidate()->first()->country);
    }

    public function test_a_candidate_can_open_an_open_program_and_not_a_closed_one(): void
    {
        $user = User::factory()->create();
        $open = Program::query()->create([
            'name' => 'Ouvert',
            'slug' => 'ouvert',
            'is_open' => true,
        ]);
        $closed = Program::query()->create([
            'name' => 'Fermé',
            'slug' => 'ferme',
            'is_open' => false,
        ]);

        $this->actingAs($user)
            ->get(route('programs.show', $open))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('programs/show'));

        $this->actingAs($user)
            ->get(route('programs.show', $closed))
            ->assertForbidden();
    }
}
