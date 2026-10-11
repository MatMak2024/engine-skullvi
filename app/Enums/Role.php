<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Recruiter = 'recruiter';
    case User = 'user';
    case Candidate = 'candidate';
}
