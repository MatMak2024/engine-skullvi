<?php

namespace App\Enums;

enum ScoringCriterion: string
{
    case Skills = 'skills';
    case Experience = 'experience';
    case Education = 'education';
    case Proofs = 'proofs';
    case Motivation = 'motivation';
    case Availability = 'availability';
    case Location = 'location';
}
