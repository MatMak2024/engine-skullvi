<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;

readonly class CriterionResult
{
    /**
     * @param  list<string>  $flags
     */
    public function __construct(
        public ScoringCriterion $criterion,
        public float $points,
        public float $maxPoints,
        public string $explanation,
        public array $flags = [],
    ) {}
}
