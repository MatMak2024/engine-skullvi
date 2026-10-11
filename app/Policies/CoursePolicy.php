<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Course;
use App\Models\User;
use App\Policies\Concerns\GuardsCandidacy;

class CoursePolicy
{
    use GuardsCandidacy;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Course $course): bool
    {
        return $course->is_open
            || ($user?->hasRole(Role::Recruiter) ?? false)
            || ($user?->hasRole(Role::SuperAdmin) ?? false);
    }

    public function viewCatalog(User $user): bool
    {
        return $user->hasRole(Role::Recruiter) || $user->hasRole(Role::SuperAdmin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function apply(User $user, Course $course): bool
    {
        return $course->is_open && $this->mayApply($user);
    }
}
