<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application roles and their permissions.
     */
    public function run(): void
    {
        $now = now();

        $permissions = collect([
            'application:view',
            'application:update-status',
            'review-note:create',
            'program:manage',
            'skill:manage',
            'user:manage',
        ])->mapWithKeys(function (string $name) use ($now) {
            $id = (string) Str::uuid();

            DB::table('permissions')->insert([
                'id' => $id,
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [$name => $id];
        });

        $roles = [
            'super_admin' => 'Super administrateur',
            'recruiter' => 'Recruteur',
            'user' => 'Utilisateur',
            'candidate' => 'Candidat',
        ];

        $rolePermissions = [
            'super_admin' => [
                'user:manage',
                'application:view',
            ],
            'recruiter' => [
                'program:manage',
                'skill:manage',
                'application:view',
                'application:update-status',
                'review-note:create',
            ],
            'user' => [],
            'candidate' => [],
        ];

        foreach ($roles as $slug => $name) {
            $roleId = (string) Str::uuid();

            DB::table('roles')->insert([
                'id' => $roleId,
                'name' => $name,
                'slug' => $slug,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($rolePermissions[$slug] as $permission) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissions[$permission],
                    'role_id' => $roleId,
                ]);
            }
        }
    }
}
