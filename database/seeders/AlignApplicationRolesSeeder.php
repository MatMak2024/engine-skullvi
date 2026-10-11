<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlignApplicationRolesSeeder extends Seeder
{
    /**
     * Replace owner, member and recruiter with the four application roles.
     */
    public function run(): void
    {
        $now = now();

        $permissionNames = [
            'application:view',
            'application:update-status',
            'review-note:create',
            'program:manage',
            'skill:manage',
            'user:manage',
        ];

        $permissionIds = [];

        foreach ($permissionNames as $name) {
            $id = DB::table('permissions')->where('name', $name)->value('id');

            if ($id === null) {
                $id = (string) Str::uuid();
                DB::table('permissions')->insert([
                    'id' => $id,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $permissionIds[$name] = $id;
        }

        $rolePermissions = [
            'super_admin' => ['Super administrateur', ['user:manage', 'application:view']],
            'recruiter' => ['Recruteur', ['program:manage', 'skill:manage', 'application:view', 'application:update-status', 'review-note:create']],
            'user' => ['Utilisateur', []],
            'candidate' => ['Candidat', []],
        ];

        $roleIds = [];

        foreach ($rolePermissions as $slug => [$name, $permissions]) {
            $roleId = DB::table('roles')->where('slug', $slug)->value('id');

            if ($roleId === null) {
                $roleId = (string) Str::uuid();
                DB::table('roles')->insert([
                    'id' => $roleId,
                    'name' => $name,
                    'slug' => $slug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('roles')->where('id', $roleId)->update([
                    'name' => $name,
                    'updated_at' => $now,
                ]);
            }

            DB::table('permission_role')->where('role_id', $roleId)->delete();

            foreach ($permissions as $permission) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permissionIds[$permission],
                    'role_id' => $roleId,
                ]);
            }

            $roleIds[$slug] = $roleId;
        }

        $this->replaceRoles('owner@talent-engine.test', [$roleIds['super_admin']]);
        $this->replaceRoles('admin@talent-engine.test', [$roleIds['recruiter']]);

        $candidateUserIds = DB::table('candidates')->pluck('user_id');

        foreach ($candidateUserIds as $userId) {
            $this->attach($userId, $roleIds['candidate']);
        }

        $staffIds = DB::table('users')
            ->whereIn('email', ['owner@talent-engine.test', 'admin@talent-engine.test'])
            ->pluck('id');

        $plainUserIds = DB::table('users')
            ->whereNotIn('id', $staffIds->merge($candidateUserIds)->unique()->all())
            ->pluck('id');

        foreach ($plainUserIds as $userId) {
            DB::table('role_user')->where('user_id', $userId)->delete();
            $this->attach($userId, $roleIds['user']);
        }

        $removed = DB::table('roles')->whereIn('slug', ['owner', 'member', 'recruiter'])->pluck('id');
        DB::table('role_user')->whereIn('role_id', $removed)->delete();
        DB::table('roles')->whereIn('id', $removed)->delete();
    }

    /**
     * @param  list<string>  $roleIds
     */
    private function replaceRoles(string $email, array $roleIds): void
    {
        $userId = DB::table('users')->where('email', $email)->value('id');

        if ($userId === null) {
            return;
        }

        DB::table('role_user')->where('user_id', $userId)->delete();

        foreach ($roleIds as $roleId) {
            $this->attach($userId, $roleId);
        }
    }

    private function attach(string $userId, string $roleId): void
    {
        $exists = DB::table('role_user')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->exists();

        if (! $exists) {
            DB::table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
        }
    }
}
