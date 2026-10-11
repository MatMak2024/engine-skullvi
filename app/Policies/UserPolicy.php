<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }

    public function update(User $user, User $account): bool
    {
        return $user->hasRole(Role::SuperAdmin);
    }
}
