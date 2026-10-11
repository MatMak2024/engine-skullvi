<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait GuardsCandidacy
{
    private function mayApply(User $user): bool
    {
        return ! $user->isPermanentlyRemovedAsCandidate()
            && ! ($user->candidate?->isSuspended() ?? false);
    }
}
