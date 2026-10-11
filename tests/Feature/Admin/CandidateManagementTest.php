<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleName;
use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class CandidateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recruiter_suspends_a_candidate_who_can_no_longer_apply(): void
    {
        $recruiter = TalentWorld::recruiter();
        $program = TalentWorld::program();
        $application = TalentWorld::application($program);
        $candidate = $application->candidate;
        $user = $candidate->user;

        $this->actingAs($recruiter)
            ->get(route('admin.candidates.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canManage', true)
                ->where('candidates.data.0.suspended', false));

        $userRole = Role::query()->firstOrCreate(
            ['slug' => RoleName::User],
            ['name' => 'Utilisateur'],
        );
        $user->roles()->syncWithoutDetaching([$userRole->id]);

        $this->actingAs($recruiter)
            ->patch(route('admin.candidates.suspension', $candidate), [
                'reason' => 'Pièces d’identité illisibles.',
            ])
            ->assertRedirect();

        $candidate->refresh();
        $user->refresh();

        $this->assertNotNull($candidate->suspended_at);
        $this->assertSame('Pièces d’identité illisibles.', $candidate->suspension_reason);
        $this->assertTrue($user->hasRole(RoleName::User));
        $this->assertStringContainsString(
            'Pièces d’identité illisibles.',
            (string) $user->notifications()->first()?->data['body'],
        );

        $this->actingAs($recruiter)
            ->get(route('admin.candidates.excluded'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/candidates/excluded')
                ->where('suspended.data.0.reason', 'Pièces d’identité illisibles.'));

        $this->actingAs($user)
            ->post(route('applications.store', $program), $this->payload())
            ->assertForbidden();

        $this->actingAs($recruiter)
            ->patch(route('admin.candidates.suspension', $candidate))
            ->assertRedirect();

        $this->assertNull($candidate->fresh()->suspended_at);

        $other = TalentWorld::program(['name' => 'Autre parcours', 'slug' => 'autre-parcours']);

        $this->actingAs($user->fresh())
            ->post(route('applications.store', $other), $this->payload())
            ->assertRedirect();

        $this->assertSame(2, Application::query()->where('candidate_id', $user->id)->count());
    }

    public function test_a_recruiter_deletes_a_candidate_and_keeps_the_account(): void
    {
        $recruiter = TalentWorld::recruiter();
        $program = TalentWorld::program();
        $application = TalentWorld::application($program);
        $candidate = $application->candidate;
        $user = $candidate->user;
        $role = Role::query()->firstOrCreate(
            ['slug' => RoleName::Candidate],
            ['name' => 'Candidat'],
        );
        $userRole = Role::query()->firstOrCreate(
            ['slug' => RoleName::User],
            ['name' => 'Utilisateur'],
        );
        $user->roles()->syncWithoutDetaching([$role->id, $userRole->id]);

        $this->actingAs($recruiter)
            ->delete(route('admin.candidates.destroy', $candidate), [
                'reason' => 'Dossier frauduleux.',
            ])
            ->assertRedirect(route('admin.candidates.index'));

        $user->refresh();

        $this->assertModelMissing($candidate);
        $this->assertModelExists($user);
        $this->assertSame(0, Application::query()->count());
        $this->assertTrue($user->hasRole(RoleName::User));
        $this->assertFalse($user->hasRole(RoleName::Candidate));
        $this->assertNotNull($user->candidate_removed_at);
        $this->assertSame('Dossier frauduleux.', $user->candidate_removal_reason);
        $this->assertStringContainsString(
            'Dossier frauduleux.',
            (string) $user->notifications()->first()?->data['body'],
        );

        $this->actingAs($user)
            ->post(route('applications.store', $program), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('candidates', ['user_id' => $user->id]);

        $this->actingAs($recruiter)
            ->get(route('admin.candidates.excluded'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('removed.data.0.email', $user->email)
                ->where('removed.data.0.reason', 'Dossier frauduleux.'));
    }

    public function test_a_super_admin_can_view_candidates_without_changing_them(): void
    {
        $program = TalentWorld::program();
        $application = TalentWorld::application($program);
        $superAdmin = User::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['slug' => RoleName::SuperAdmin],
            ['name' => 'Super administrateur'],
        );
        $superAdmin->roles()->attach($role);

        $this->actingAs($superAdmin)
            ->get(route('admin.candidates.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canManage', false));

        $this->actingAs($superAdmin)
            ->patch(route('admin.candidates.suspension', $application->candidate))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->delete(route('admin.candidates.destroy', $application->candidate))
            ->assertForbidden();

        $this->assertModelExists($application->candidate);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'country' => 'Sénégal',
            'city' => 'Dakar',
            'education_level' => 'licence',
            'years_experience' => 0,
            'projects_count' => 0,
            'availability' => 'later',
            'motivation_text' => 'Je veux rejoindre ce programme après la levée de la suspension.',
        ];
    }
}
