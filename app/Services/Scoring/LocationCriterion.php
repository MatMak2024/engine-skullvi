<?php

namespace App\Services\Scoring;

use App\Enums\ScoringCriterion;
use App\Enums\WorkMode;
use App\Models\Application;
use Illuminate\Support\Str;

class LocationCriterion extends CriterionEvaluator
{
    public function evaluate(Application $application): CriterionResult
    {
        $application->loadMissing(['program', 'candidate.user']);

        $catalog = $application->scoringCatalog();
        $max = (float) config('scoring.criteria.location.max');
        $mode = $catalog->workMode;
        $place = $this->place($application);

        $scored = match ($mode) {
            WorkMode::Onsite => $this->onsite($catalog, $place),
            WorkMode::Hybrid => $this->hybrid($catalog, $place),
            WorkMode::Remote => $this->declared($place, $mode->label()),
        };

        return $this->result(
            ScoringCriterion::Location,
            $scored['points'],
            $max,
            $scored['explanation'],
        );
    }

    /**
     * @return array{country: string, city: string, precise: bool}
     */
    private function place(Application $application): array
    {
        $user = $application->candidate?->user;
        $candidate = $application->candidate;

        $city = filled($user?->city)
            ? $user->city
            : (filled($user?->state) ? $user->state : $candidate?->city);

        return [
            'country' => $this->normalize(filled($user?->country) ? $user->country : $candidate?->country),
            'city' => $this->normalize($city),
            'precise' => filled($user?->address) || filled($user?->zip),
        ];
    }

    /**
     * @param  array{country: string, city: string, precise: bool}  $place
     * @return array{points: float, explanation: string}
     */
    private function declared(array $place, string $intro): array
    {
        $country = $place['country'] !== '' ? $this->weight('declared.country') : 0.0;
        $city = $place['city'] !== '' ? $this->weight('declared.city') : 0.0;
        $precise = $place['precise'] ? $this->weight('declared.precise') : 0.0;
        $points = $country + $city + $precise;

        $labels = array_values(array_filter([
            $country > 0 ? 'le pays' : null,
            $city > 0 ? 'la ville' : null,
            $precise > 0 ? 'l’adresse' : null,
        ]));

        if ($labels === []) {
            $detail = 'localisation non renseignée';
        } elseif (count($labels) > 1) {
            $last = array_pop($labels);
            $detail = implode(', ', $labels).' et '.$last.' sont renseignés';
        } else {
            $detail = match ($labels[0]) {
                'la ville' => 'la ville est renseignée',
                'l’adresse' => 'l’adresse est renseignée',
                default => 'le pays est renseigné',
            };
        }

        return [
            'points' => $points,
            'explanation' => $intro.' : '.$detail.' ('.$this->pointsLabel($points).').',
        ];
    }

    /**
     * @param  array{country: string, city: string, precise: bool}  $place
     * @return array{points: float, explanation: string}
     */
    private function onsite(ScoringCatalog $catalog, array $place): array
    {
        $workCountry = $this->normalize($catalog->workCountry);

        if ($workCountry === '') {
            return $this->declared($place, 'Sur place, lieu de l’offre non précisé');
        }

        $workCity = $this->normalize($catalog->workCity);
        $countryPoints = $place['country'] !== '' && $place['country'] === $workCountry
            ? $this->weight('onsite.country')
            : 0.0;
        $cityMatch = $workCity !== '' && $place['city'] !== '' && $place['city'] === $workCity;
        $cityPoints = $countryPoints > 0 && $cityMatch ? $this->weight('onsite.city') : 0.0;
        $precisePoints = $cityPoints > 0 && $place['precise'] ? $this->weight('onsite.precise') : 0.0;
        $points = $countryPoints + $cityPoints + $precisePoints;
        $where = $this->where($catalog);

        if ($countryPoints === 0.0) {
            $detail = 'la localisation ne correspond pas';
        } elseif ($workCity === '') {
            $detail = 'le pays correspond';
        } elseif ($cityPoints === 0.0) {
            $detail = 'le pays correspond, pas la ville';
        } elseif ($precisePoints === 0.0) {
            $detail = 'le pays et la ville correspondent';
        } else {
            $detail = 'le pays, la ville et l’adresse correspondent';
        }

        return [
            'points' => $points,
            'explanation' => 'Sur place'.$where.' : '.$detail.' ('.$this->pointsLabel($points).').',
        ];
    }

    /**
     * @param  array{country: string, city: string, precise: bool}  $place
     * @return array{points: float, explanation: string}
     */
    private function hybrid(ScoringCatalog $catalog, array $place): array
    {
        $workCity = $this->normalize($catalog->workCity);
        $country = $place['country'] !== '' ? $this->weight('hybrid.country') : 0.0;
        $cityMatches = $workCity === ''
            ? $place['city'] !== ''
            : $place['city'] !== '' && $place['city'] === $workCity;
        $city = $cityMatches ? $this->weight('hybrid.city') : 0.0;
        $precise = $city > 0 && $place['precise'] ? $this->weight('hybrid.precise') : 0.0;
        $points = $country + $city + $precise;
        $where = $this->where($catalog);

        if ($country === 0.0) {
            $detail = 'localisation non renseignée';
        } elseif ($workCity !== '' && $city === 0.0) {
            $detail = 'le pays est renseigné, la ville ne correspond pas au lieu de l’offre';
        } elseif ($precise > 0) {
            $detail = 'le pays, la ville et l’adresse sont pris en compte';
        } elseif ($city > 0) {
            $detail = 'le pays et la ville sont pris en compte';
        } else {
            $detail = 'le pays est renseigné';
        }

        return [
            'points' => $points,
            'explanation' => 'Hybride'.$where.' : '.$detail.' ('.$this->pointsLabel($points).').',
        ];
    }

    private function weight(string $key): float
    {
        return (float) config('scoring.criteria.location.'.$key);
    }

    private function where(ScoringCatalog $catalog): string
    {
        $place = implode(', ', array_filter([
            trim((string) $catalog->workCity),
            trim((string) $catalog->workCountry),
        ]));

        return $place === '' ? '' : ' à '.$place;
    }

    private function normalize(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return mb_strtolower(Str::ascii($value));
    }

    private function pointsLabel(float $points): string
    {
        $shown = rtrim(rtrim(number_format($points, 2, '.', ''), '0'), '.');

        return $shown.' '.((float) $shown > 1 ? 'points' : 'point');
    }
}
