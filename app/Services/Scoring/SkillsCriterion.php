<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;
use App\Models\Application;
use App\Models\Skill;
use Illuminate\Support\Collection;

class SkillsCriterion extends CriterionEvaluator
{
    public function evaluate(Application $application): CriterionResult
    {
        $max = (float) config('scoring.criteria.skills.max');
        $requiredMax = (float) config('scoring.criteria.skills.required_max');
        $bonusMax = (float) config('scoring.criteria.skills.bonus_max');

        $declared = $application->skills->keyBy('id');
        $programSkills = $application->scoringCatalog()->skills;
        $required = $programSkills->filter(fn ($skill) => (bool) $skill->pivot->is_required)->values();
        $bonus = $programSkills->filter(fn ($skill) => ! (bool) $skill->pivot->is_required)->values();

        $requiredMatched = $this->matched($required, $declared);
        $bonusMatched = $this->matched($bonus, $declared);

        $requiredPoints = $required->isEmpty()
            ? 0
            : ($requiredMatched['weight'] / $required->count()) * $requiredMax;
        $bonusPoints = $bonus->isEmpty()
            ? 0
            : ($bonusMatched['weight'] / $bonus->count()) * $bonusMax;

        $flags = $application->skills->isEmpty() ? ['no_skills'] : [];

        return $this->result(
            ScoringCriterion::Skills,
            $requiredPoints + $bonusPoints,
            $max,
            $this->sentence($requiredMatched['count'], $required->count(), 'requise', 'requises')
                .' '
                .$this->sentence($bonusMatched['count'], $bonus->count(), 'bonus', 'bonus'),
            $flags,
        );
    }

    /**
     * @param  Collection<int, Skill>  $skills
     * @param  Collection<string, Skill>  $declared
     * @return array{count: int, weight: float}
     */
    private function matched(Collection $skills, Collection $declared): array
    {
        $count = 0;
        $weight = 0.0;

        foreach ($skills as $skill) {
            $level = $declared->get($skill->id)?->pivot->level;

            if ($level === null) {
                continue;
            }

            $count++;
            $weight += (float) config('scoring.levels.'.$level, 0);
        }

        return ['count' => $count, 'weight' => $weight];
    }

    private function sentence(int $matched, int $total, string $singular, string $plural): string
    {
        if ($total === 0) {
            return 'Aucune compétence '.$singular.'.';
        }

        $label = $matched > 1 ? 'compétences '.$plural : 'compétence '.$singular;

        return $matched.' '.$label.' sur '.$total.'.';
    }
}
