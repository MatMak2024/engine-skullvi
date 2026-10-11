<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Received = 'received';
    case Incomplete = 'incomplete';
    case Ineligible = 'ineligible';
    case Priority = 'priority';
    case ToReview = 'to_review';
    case LowFit = 'low_fit';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
