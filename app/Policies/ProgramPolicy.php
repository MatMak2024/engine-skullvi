<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Program;
use App\Models\User;
use App\Policies\Concerns\GuardsCandidacy;

class ProgramPolicy
{
    use GuardsCandidacy;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Program $program): bool
    {
        return $program->is_open
            || ($user?->hasRole(Role::Recruiter) ?? false)
            || ($user?->hasRole(Role::SuperAdmin) ?? false)
            || ($user?->hasPermission('application:view') ?? false);
    }

    public function viewCatalog(User $user): bool
    {
        return $user->hasRole(Role::Recruiter) || $user->hasRole(Role::SuperAdmin);
    }

    public function manage(User $user): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function update(User $user, Program $program): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function apply(User $user, Program $program): bool
    {
        return $program->is_open && $this->mayApply($user);
    }
}
