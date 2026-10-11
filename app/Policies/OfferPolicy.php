<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Offer;
use App\Models\User;
use App\Policies\Concerns\GuardsCandidacy;

class OfferPolicy
{
    use GuardsCandidacy;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Offer $offer): bool
    {
        return $offer->is_open
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

    public function update(User $user, Offer $offer): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function delete(User $user, Offer $offer): bool
    {
        return $user->hasRole(Role::Recruiter);
    }

    public function apply(User $user, Offer $offer): bool
    {
        return $offer->is_open && $this->mayApply($user);
    }
}
