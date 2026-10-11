<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Recruiter) || $user->hasRole(Role::SuperAdmin);
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return $user->hasRole(Role::Recruiter)
            || $user->hasRole(Role::SuperAdmin)
            || $candidate->user_id === $user->id;
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return $candidate->user_id === $user->id;
    }

    public function suspend(User $user, Candidate $candidate): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return $user->hasRole(Role::Recruiter);
    }
}
