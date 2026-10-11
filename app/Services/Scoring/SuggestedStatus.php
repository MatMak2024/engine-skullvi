<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationStatus;

class SuggestedStatus
{
    public function from(float $total, bool $programOpen, bool $complete): ApplicationStatus
    {
        if (! $programOpen) {
            return ApplicationStatus::Ineligible;
        }

        if (! $complete) {
            return ApplicationStatus::Incomplete;
        }

        $priority = (float) config('scoring.thresholds.priority');
        $review = (float) config('scoring.thresholds.to_review');

        if ($total >= $priority) {
            return ApplicationStatus::Priority;
        }

        if ($total >= $review) {
            return ApplicationStatus::ToReview;
        }

        return ApplicationStatus::LowFit;
    }
}
