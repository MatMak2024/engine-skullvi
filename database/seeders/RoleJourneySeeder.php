<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleJourneySeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->user('Awa Mensah', 'owner@talent-engine.test');
        $admin = $this->user('Kofi Agbeko', 'admin@talent-engine.test');
        $member = $this->user('Aïcha Sow', 'member@talent-engine.test');
        $this->user('Jean Morel', 'user@talent-engine.test');

        $plainUser = User::query()->where('email', 'user@talent-engine.test')->first();

        $this->assignRole($owner, RoleName::SuperAdmin);
        $this->assignRole($admin, RoleName::Recruiter);
        $this->assignRole($member, RoleName::User);

        if ($plainUser instanceof User) {
            $this->assignRole($plainUser, RoleName::User);
        }
    }

    private function user(string $name, string $email): User
    {
        $existing = User::query()->where('email', $email)->first();

        if ($existing instanceof User) {
            return $existing;
        }

        return User::factory()->create([
            ...User::splitName($name),
            'email' => $email,
        ]);
    }

    private function assignRole(User $user, RoleName $roleName): void
    {
        $role = Role::query()->where('slug', $roleName)->firstOrFail();

        if (! $user->roles()->whereKey($role->id)->exists()) {
            $user->roles()->attach($role);
        }
    }
}
