<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;
use App\Models\Application;

class MotivationCriterion extends CriterionEvaluator
{
    /**
     * @var list<string>
     */
    private array $stopWords = [
        'avec', 'dans', 'pour', 'cette', 'cet', 'ces', 'des', 'les', 'une', 'sur',
        'par', 'aux', 'que', 'qui', 'dont', 'est', 'sont', 'son', 'ses', 'leur',
        'plus', 'tout', 'tous', 'mais', 'donc', 'comme', 'chez', 'vers', 'entre',
    ];

    public function evaluate(Application $application): CriterionResult
    {
        $max = (float) config('scoring.criteria.motivation.max');
        $keywordMax = (float) config('scoring.criteria.motivation.keyword_max');
        $shortBelow = (int) config('scoring.criteria.motivation.short_below');
        $text = trim((string) $application->motivation_text);
        $length = mb_strlen($text);
        $lengthPoints = $this->lengthPoints($length);
        $keywords = $this->keywords($application);
        $matched = $this->matchedKeywords($text, $keywords);
        $keywordPoints = $keywords === []
            ? 0
            : ($matched / count($keywords)) * $keywordMax;

        $flags = $length < $shortBelow ? ['short_motivation'] : [];

        return $this->result(
            ScoringCriterion::Motivation,
            $lengthPoints + $keywordPoints,
            $max,
            'Lettre de motivation : '.$length.' caractères ('.$lengthPoints.' pts), '
                .$matched.' mot-clé sur '.count($keywords).' ('.round($keywordPoints, 2).' pts).',
            $flags,
        );
    }

    private function lengthPoints(int $length): float
    {
        foreach (config('scoring.criteria.motivation.length_bands') as $band) {
            if ($length >= $band['min']) {
                return (float) $band['points'];
            }
        }

        return 0;
    }

    /**
     * @return list<string>
     */
    private function keywords(Application $application): array
    {
        $catalog = $application->scoringCatalog();
        $source = mb_strtolower(trim($catalog->name.' '.($catalog->description ?? '')));
        $source = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $source) ?? '';
        $words = preg_split('/\s+/u', trim($source)) ?: [];

        $keywords = [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 4 || in_array($word, $this->stopWords, true)) {
                continue;
            }

            $keywords[$word] = $word;
        }

        return array_values($keywords);
    }

    /**
     * @param  list<string>  $keywords
     */
    private function matchedKeywords(string $text, array $keywords): int
    {
        $haystack = mb_strtolower($text);
        $matched = 0;

        foreach ($keywords as $keyword) {
            $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($keyword, '/').'(?![\p{L}\p{N}])/u';

            if (preg_match($pattern, $haystack) === 1) {
                $matched++;
            }
        }

        return $matched;
    }
}
