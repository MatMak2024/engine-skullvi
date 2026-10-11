<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Course;
use App\Models\Evaluation;
use App\Models\Offer;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdministrationOverview
{
    /**
     * @return array{
     *     counts: list<array{label: string, value: int}>,
     *     statuses: list<array{label: string, value: int}>,
     *     scores: list<array{label: string, value: int}>,
     *     months: list<array{label: string, value: int}>
     * }
     */
    public function present(): array
    {
        return [
            'counts' => [
                ['label' => 'Utilisateurs', 'value' => User::query()->count()],
                ['label' => 'Candidats', 'value' => Candidate::query()->count()],
                ['label' => 'Candidatures', 'value' => Application::query()->count()],
                ['label' => 'Programmes', 'value' => Program::query()->count()],
                ['label' => 'Cours', 'value' => Course::query()->count()],
                ['label' => 'Offres', 'value' => Offer::query()->count()],
            ],
            'statuses' => $this->statuses(),
            'scores' => $this->scores(),
            'months' => $this->months(),
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function statuses(): array
    {
        $counts = Application::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(ApplicationStatus::cases())
            ->map(fn (ApplicationStatus $status) => [
                'label' => $this->statusLabel($status),
                'value' => (int) ($counts[$status->value] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function scores(): array
    {
        $scores = Evaluation::query()
            ->where('is_current', true)
            ->pluck('total_score');

        $priority = $scores->filter(fn ($score) => (float) $score >= 70)->count();
        $review = $scores->filter(fn ($score) => (float) $score >= 50 && (float) $score < 70)->count();
        $low = $scores->filter(fn ($score) => (float) $score < 50)->count();
        $missing = max(0, Application::query()->count() - $scores->count());

        return [
            ['label' => 'Prioritaire', 'value' => $priority],
            ['label' => 'À examiner', 'value' => $review],
            ['label' => 'Adéquation faible', 'value' => $low],
            ['label' => 'Sans score', 'value' => $missing],
        ];
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function months(): array
    {
        $labels = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
        $start = now()->startOfMonth()->subMonths(5);

        $counts = Application::query()
            ->where('submitted_at', '>=', $start)
            ->pluck('submitted_at')
            ->countBy(fn ($date) => Carbon::parse($date)->format('Y-m'));

        return collect(range(0, 5))
            ->map(function (int $offset) use ($start, $labels, $counts) {
                $month = $start->copy()->addMonths($offset);

                return [
                    'label' => $labels[(int) $month->format('n') - 1],
                    'value' => (int) ($counts[$month->format('Y-m')] ?? 0),
                ];
            })
            ->all();
    }

    private function statusLabel(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::Received => 'Reçue',
            ApplicationStatus::Incomplete => 'Incomplète',
            ApplicationStatus::Ineligible => 'Non éligible',
            ApplicationStatus::Priority => 'Prioritaire',
            ApplicationStatus::ToReview => 'À examiner',
            ApplicationStatus::LowFit => 'Adéquation faible',
            ApplicationStatus::Accepted => 'Acceptée',
            ApplicationStatus::Rejected => 'Refusée',
        };
    }
}
