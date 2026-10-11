<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;
use App\Models\Application;

abstract class CriterionEvaluator
{
    abstract public function evaluate(Application $application): CriterionResult;

    /**
     * @param  list<string>  $flags
     */
    protected function result(
        ScoringCriterion $criterion,
        float $points,
        float $max,
        string $explanation,
        array $flags = [],
    ): CriterionResult {
        return new CriterionResult(
            $criterion,
            round(min($max, max(0, $points)), 2),
            $max,
            $explanation,
            $flags,
        );
    }
}
