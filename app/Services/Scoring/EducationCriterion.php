<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;
use App\Models\Application;

class EducationCriterion extends CriterionEvaluator
{
    public function evaluate(Application $application): CriterionResult
    {
        $max = (float) config('scoring.criteria.education.max');
        $level = $application->education_level?->value;
        $points = (float) config('scoring.criteria.education.points.'.$level, 0);

        $label = match ($level) {
            'none' => 'Sans diplôme',
            'bac' => 'Bac',
            'licence' => 'Licence',
            'master' => 'Master',
            'other' => 'Autre formation',
            default => 'Niveau non renseigné',
        };

        return $this->result(
            ScoringCriterion::Education,
            $points,
            $max,
            $label.' : '.$points.' pts.',
        );
    }
}
