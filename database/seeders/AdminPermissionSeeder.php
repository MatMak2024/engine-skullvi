<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $userManage = Permission::query()->firstOrCreate(['name' => 'user:manage']);
        $admin = Role::query()->where('slug', 'recruiter')->first();
        $superAdmin = Role::query()->where('slug', 'super_admin')->first();

        if ($admin instanceof Role) {
            $admin->permissions()->detach($userManage->id);
        }

        if ($superAdmin instanceof Role) {
            $superAdmin->permissions()->syncWithoutDetaching([$userManage->id]);
        }
    }
}
