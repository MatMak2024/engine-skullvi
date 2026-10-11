<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\ReviewNote;
use App\Models\User;

class ReviewNotePolicy
{
    public function view(User $user, ReviewNote $note): bool
    {
        return $note->application->candidate_id === $user->id
            || $user->hasPermission('application:view');
    }

    public function create(User $user, Application $application): bool
    {
        return $user->hasPermission('review-note:create');
    }
}
