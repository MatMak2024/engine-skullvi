<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;
use App\Models\Application;

class ProofsCriterion extends CriterionEvaluator
{
    public function evaluate(Application $application): CriterionResult
    {
        $max = (float) config('scoring.criteria.proofs.max');
        $github = $this->link(
            $application->github_url,
            (float) config('scoring.criteria.proofs.github'),
            (string) config('scoring.criteria.proofs.hosts.github'),
            'GitHub',
            'invalid_github',
        );
        $portfolio = $this->link(
            $application->portfolio_url,
            (float) config('scoring.criteria.proofs.portfolio'),
            null,
            'Portfolio',
            'invalid_portfolio',
        );
        $linkedin = $this->link(
            $application->linkedin_url,
            (float) config('scoring.criteria.proofs.linkedin'),
            (string) config('scoring.criteria.proofs.hosts.linkedin'),
            'LinkedIn',
            'invalid_linkedin',
        );

        $flags = array_values(array_filter([
            $github['flag'],
            $portfolio['flag'],
            $linkedin['flag'],
        ]));

        $cvPoints = filled($application->cv_path) ? (float) config('scoring.criteria.proofs.cv') : 0.0;
        $qualification = $this->qualifications($application);
        $flags = array_values(array_filter([...$flags, $qualification['flag']]));
        $raw = $github['points'] + $portfolio['points'] + $linkedin['points'] + $cvPoints + $qualification['points'];
        $parts = [
            $github['text'],
            $portfolio['text'],
            $linkedin['text'],
            $cvPoints > 0 ? 'CV joint ('.$this->pointsLabel($cvPoints).').' : 'CV absent.',
            $qualification['text'],
        ];

        if ($raw > $max) {
            $parts[] = 'Plafond '.$this->pointsLabel($max).'.';
        }

        return $this->result(
            ScoringCriterion::Proofs,
            min($max, $raw),
            $max,
            implode(' ', $parts),
            $flags,
        );
    }

    /**
     * @return array{points: float, text: string, flag: ?string}
     */
    private function qualifications(Application $application): array
    {
        $requested = $application->scoringCatalog()->requestedDocuments;
        $max = (float) config('scoring.criteria.proofs.qualifications');

        if ($requested === []) {
            return [
                'points' => 0.0,
                'text' => 'Aucun document de qualification demandé.',
                'flag' => null,
            ];
        }

        $application->loadMissing('documents');
        $provided = $application->documents->pluck('label')->all();
        $matched = count(array_intersect($requested, $provided));
        $points = (count($requested) === 0) ? 0.0 : ($matched / count($requested)) * $max;
        $label = $matched.' document'.($matched > 1 ? 's' : '').' de qualification sur '.count($requested);

        return [
            'points' => $points,
            'text' => $label.' ('.$this->pointsLabel($points).').',
            'flag' => $matched < count($requested) ? 'missing_qualifications' : null,
        ];
    }

    private function pointsLabel(float $points): string
    {
        $shown = rtrim(rtrim(number_format($points, 2, '.', ''), '0'), '.');

        return $shown.' '.((float) $shown > 1 ? 'pts' : 'pt');
    }

    /**
     * @return array{points: float, text: string, flag: ?string}
     */
    private function link(?string $url, float $points, ?string $host, string $label, string $flag): array
    {
        if (blank($url)) {
            return ['points' => 0, 'text' => $label.' absent.', 'flag' => null];
        }

        $valid = $host === null ? $this->validUrl($url) : $this->hostIs($url, $host);

        if (! $valid) {
            return ['points' => 0, 'text' => $label.' invalide.', 'flag' => $flag];
        }

        return ['points' => $points, 'text' => $label.' valide ('.$points.' pts).', 'flag' => null];
    }

    private function validUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function hostIs(string $url, string $expected): bool
    {
        if (! $this->validUrl($url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === $expected || str_ends_with($host, '.'.$expected);
    }
}
