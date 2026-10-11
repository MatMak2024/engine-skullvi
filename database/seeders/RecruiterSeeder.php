<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecruiterSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'recruiter@talent-engine.test')->first();

        if (! $user instanceof User) {
            $user = User::factory()->create([
                ...User::splitName('Recruteur démo'),
                'email' => 'recruiter@talent-engine.test',
            ]);
        }

        $role = Role::query()->where('slug', RoleName::User)->first();

        if ($role instanceof Role && ! $user->roles()->whereKey($role->id)->exists()) {
            $user->roles()->attach($role);
        }
    }
}
