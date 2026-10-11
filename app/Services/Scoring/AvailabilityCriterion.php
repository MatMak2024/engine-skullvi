<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;
use App\Models\Application;

class AvailabilityCriterion extends CriterionEvaluator
{
    public function evaluate(Application $application): CriterionResult
    {
        $max = (float) config('scoring.criteria.availability.max');
        $value = $application->availability?->value;
        $points = (float) config('scoring.criteria.availability.points.'.$value, 0);

        $label = match ($value) {
            'immediate' => 'Disponibilité immédiate',
            'one_month' => 'Disponible sous un mois',
            'later' => 'Disponible plus tard',
            default => 'Disponibilité non renseignée',
        };

        return $this->result(
            ScoringCriterion::Availability,
            $points,
            $max,
            $label.' : '.$points.' pts.',
        );
    }
}
