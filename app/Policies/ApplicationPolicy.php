<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Recruiter)
            || $user->hasRole(Role::SuperAdmin)
            || $user->hasPermission('application:view');
    }

    public function view(User $user, Application $application): bool
    {
        return $this->viewOwn($user, $application)
            || $user->hasRole(Role::Recruiter)
            || $user->hasRole(Role::SuperAdmin)
            || $user->hasPermission('application:view');
    }

    public function viewOwn(User $user, Application $application): bool
    {
        return $application->candidate_id === $user->id;
    }

    public function updateStatus(User $user, Application $application): bool
    {
        return $user->hasPermission('application:update-status');
    }
}
