<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;
use App\Models\Application;

class ExperienceCriterion extends CriterionEvaluator
{
    public function evaluate(Application $application): CriterionResult
    {
        $max = (float) config('scoring.criteria.experience.max');
        $perYear = (float) config('scoring.criteria.experience.points_per_year');
        $yearCap = (int) config('scoring.criteria.experience.year_cap');
        $perProject = (float) config('scoring.criteria.experience.points_per_project');
        $projectCap = (int) config('scoring.criteria.experience.project_cap');

        $years = max(0, (int) $application->years_experience);
        $projects = max(0, (int) $application->projects_count);
        $countedYears = min($years, $yearCap);
        $countedProjects = min($projects, $projectCap);
        $yearPoints = $countedYears * $perYear;
        $projectPoints = $countedProjects * $perProject;

        $yearLabel = $years > $yearCap
            ? $years.' ans plafonnés à '.$yearCap
            : $countedYears.' '.($countedYears > 1 ? 'ans' : 'an');
        $projectLabel = $projects > $projectCap
            ? $projects.' projets plafonnés à '.$projectCap
            : $countedProjects.' '.($countedProjects > 1 ? 'projets' : 'projet');

        return $this->result(
            ScoringCriterion::Experience,
            $yearPoints + $projectPoints,
            $max,
            $yearLabel.' ('.$yearPoints.' pts) et '.$projectLabel.' ('.$projectPoints.' pts).',
        );
    }
}
