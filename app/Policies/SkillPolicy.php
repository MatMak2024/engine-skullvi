<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Skill;
use App\Models\User;

class SkillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Recruiter) || $user->hasRole(Role::SuperAdmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function update(User $user, Skill $skill): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function delete(User $user, Skill $skill): bool
    {
        return $user->hasRole(Role::Recruiter);
    }
}
