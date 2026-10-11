<?php

namespace Database\Seeders;

use App\Enums\Role as RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminPermissionSeeder::class,
            RecruiterSeeder::class,
            RoleJourneySeeder::class,
            SkillSeeder::class,
            ProgramSeeder::class,
            CourseSeeder::class,
            OfferSeeder::class,
            DemoCandidatesSeeder::class,
        ]);

        // User::factory(10)->create();

        $testUser = User::factory()->create([
            ...User::splitName('Test User'),
            'email' => 'test@example.com',
        ]);
        $userRole = Role::query()->where('slug', RoleName::User)->first();

        if ($userRole instanceof Role) {
            $testUser->roles()->syncWithoutDetaching([$userRole->id]);
        }
    }
}
