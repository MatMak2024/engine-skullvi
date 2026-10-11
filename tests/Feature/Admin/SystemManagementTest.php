<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleName;
use App\Enums\WorkMode;
use App\Models\Category;
use App\Models\Program;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TalentWorld;
use Tests\TestCase;

class SystemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_redirected_from_the_admin_catalog(): void
    {
        $this->get(route('admin.programs.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_admin_role_cannot_manage_the_system(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.programs.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.skills.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_an_admin_can_create_a_program_and_a_skill(): void
    {
        $admin = $this->admin();
        $skill = Skill::query()->create(['name' => 'Quiz']);

        $this->actingAs($admin)
            ->post(route('admin.skills.store'), [
                'name' => 'Raisonnement structuré',
                'category' => 'Lycée',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('skills', ['name' => 'Raisonnement structuré']);
        $this->assertDatabaseHas('categories', ['name' => 'Lycée']);

        $category = Category::query()->where('name', 'Lycée')->first();
        $created = Skill::query()->where('name', 'Raisonnement structuré')->first();

        $this->assertNotNull($category);
        $this->assertNotNull($created);
        $this->assertTrue($created->categories()->whereKey($category->id)->exists());

        $this->actingAs($admin)
            ->post(route('admin.skills.store'), [
                'name' => 'Autre raisonnement',
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $linked = Skill::query()->where('name', 'Autre raisonnement')->first();

        $this->assertNotNull($linked);
        $this->assertTrue($linked->categories()->whereKey($category->id)->exists());

        $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'name' => 'Forum de jeux',
                'description' => 'Réviser en jouant.',
                'is_open' => '1',
                'required_skills' => [$skill->id],
            ])
            ->assertRedirect();

        $program = Program::query()->where('name', 'Forum de jeux')->first();

        $this->assertNotNull($program);
        $this->assertTrue($program->is_open);
        $this->assertSame(WorkMode::Remote, $program->work_mode);
        $this->assertTrue($program->skills()->whereKey($skill->id)->wherePivot('is_required', true)->exists());
    }

    public function test_an_onsite_program_requires_a_place(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'name' => 'Atelier Lomé',
                'is_open' => '1',
                'work_mode' => 'onsite',
            ])
            ->assertSessionHasErrors(['work_country', 'work_city']);

        $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'name' => 'Atelier Lomé',
                'is_open' => '1',
                'work_mode' => 'onsite',
                'work_country' => 'Togo',
                'work_city' => 'Lomé',
            ])
            ->assertRedirect();

        $program = Program::query()->where('name', 'Atelier Lomé')->first();

        $this->assertNotNull($program);
        $this->assertSame(WorkMode::Onsite, $program->work_mode);
        $this->assertSame('Togo', $program->work_country);
        $this->assertSame('Lomé', $program->work_city);
    }

    public function test_an_admin_assigns_a_role_and_keeps_their_own(): void
    {
        $admin = $this->admin();
        $account = User::factory()->create();
        $superAdmin = $this->superAdmin();
        Role::query()->firstOrCreate(
            ['slug' => RoleName::Recruiter],
            ['name' => 'Recruteur'],
        );

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('roleOptions.1.value', 'recruiter')
                ->where('roleOptions.1.label', 'Recruteur'),
            );

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $account), [
                'roles' => [RoleName::Recruiter->value],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($account->fresh()->hasRole(RoleName::Recruiter));

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $superAdmin), [
                'roles' => [],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertTrue($superAdmin->fresh()->hasRole(RoleName::SuperAdmin));
        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_a_super_admin_views_applications_and_an_admin_can_decide(): void
    {
        $admin = $this->admin();
        $superAdmin = $this->superAdmin();
        $program = TalentWorld::program();
        $application = TalentWorld::application($program);
        $candidateName = $application->candidate->user->displayName();

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('recruiter/applications/index')
                ->where('canReview', true)
                ->where('applications.data.0.candidate.name', $candidateName));

        $this->actingAs($superAdmin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('recruiter/applications/show')
                ->where('canReview', false));

        $this->actingAs($superAdmin)
            ->get(route('admin.candidates.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/candidates/index')
                ->where('candidates.data.0.name', $candidateName));

        $this->actingAs($superAdmin)
            ->post(route('admin.programs.store'), [
                'name' => 'Hors périmètre',
                'is_open' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->patch(route('recruiter.applications.update-status', $application), [
                'status' => 'accepted',
            ])
            ->assertForbidden();

        $member = User::factory()->create();
        $this->actingAs($member)->get(route('admin.applications.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.candidates.index'))->assertForbidden();
    }

    public function test_an_admin_cannot_delete_a_program_that_has_applications(): void
    {
        $admin = $this->admin();
        $program = TalentWorld::program();
        TalentWorld::application($program);

        $this->actingAs($admin)
            ->delete(route('admin.programs.destroy', $program))
            ->assertSessionHasErrors('program');

        $this->assertModelExists($program);
    }

    public function test_program_list_pages_ten_items_at_a_time(): void
    {
        $admin = $this->admin();

        foreach (range(1, 11) as $number) {
            TalentWorld::program([
                'name' => sprintf('Programme %02d', $number),
                'slug' => sprintf('programme-%02d', $number),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.programs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('programs.per_page', 10)
                ->where('programs.total', 11)
                ->where('programs.current_page', 1)
                ->has('programs.data', 10)
                ->where('programs.data.0.name', 'Programme 01'));

        $this->actingAs($admin)
            ->get(route('admin.programs.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('programs.current_page', 2)
                ->has('programs.data', 1)
                ->where('programs.data.0.name', 'Programme 11'));
    }

    private function admin(): User
    {
        return $this->accountWithRole(RoleName::Recruiter, 'Recruteur');
    }

    private function superAdmin(): User
    {
        return $this->accountWithRole(RoleName::SuperAdmin, 'Super administrateur');
    }

    private function accountWithRole(RoleName $slug, string $name): User
    {
        $user = User::factory()->create();
        $role = Role::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name],
        );
        $user->roles()->attach($role);

        return $user;
    }
}
