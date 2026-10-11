<?php

namespace App\Services;

use App\Enums\OpeningKind;
use App\Models\Course;
use App\Models\Offer;
use App\Models\Program;
use App\Models\SavedOpening;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SavedOpeningService
{
    public function save(User $user, Program|Course|Offer $target): SavedOpening
    {
        $this->guardCanSave($user);

        return SavedOpening::query()->firstOrCreate([
            'user_id' => $user->id,
            'kind' => $this->kind($target)->value,
            'target_id' => $target->id,
        ]);
    }

    public function forget(User $user, Program|Course|Offer $target): void
    {
        SavedOpening::query()
            ->where('user_id', $user->id)
            ->where('kind', $this->kind($target)->value)
            ->where('target_id', $target->id)
            ->delete();
    }

    public function isSaved(User $user, Program|Course|Offer $target): bool
    {
        return SavedOpening::query()
            ->where('user_id', $user->id)
            ->where('kind', $this->kind($target)->value)
            ->where('target_id', $target->id)
            ->exists();
    }

    /**
     * @return Collection<int, array{id: string, kind: string, kind_label: string, target_id: string, name: string, is_open: bool, href: string, apply_href: string, unsave_url: string}>
     */
    public function forDashboard(User $user): Collection
    {
        return SavedOpening::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function (SavedOpening $saved) {
                $target = $this->resolve($saved);

                if ($target === null) {
                    $saved->delete();

                    return null;
                }

                $kind = $saved->kind;

                return [
                    'id' => $saved->id,
                    'kind' => $kind->value,
                    'kind_label' => $kind->label(),
                    'target_id' => $saved->target_id,
                    'name' => $target->name,
                    'is_open' => (bool) $target->is_open,
                    'href' => match ($kind) {
                        OpeningKind::Program => route('programs.show', $target),
                        OpeningKind::Course => route('courses.show', $target),
                        OpeningKind::Offer => route('offers.show', $target),
                    },
                    'apply_href' => match ($kind) {
                        OpeningKind::Program => route('applications.create', $target),
                        OpeningKind::Course => route('courses.apply', $target),
                        OpeningKind::Offer => route('offers.apply', $target),
                    },
                    'unsave_url' => match ($kind) {
                        OpeningKind::Program => route('programs.unsave', $target),
                        OpeningKind::Course => route('courses.unsave', $target),
                        OpeningKind::Offer => route('offers.unsave', $target),
                    },
                ];
            })
            ->filter()
            ->values();
    }

    private function guardCanSave(User $user): void
    {
        if ($user->isPermanentlyRemovedAsCandidate()) {
            throw ValidationException::withMessages([
                'opening' => 'Vous ne pouvez plus enregistrer de dossier. Votre compte utilisateur reste actif.',
            ]);
        }

        if ($user->candidate?->isSuspended()) {
            throw ValidationException::withMessages([
                'opening' => 'Votre dossier est suspendu. Vous ne pouvez plus enregistrer pour postuler plus tard.',
            ]);
        }
    }

    private function kind(Program|Course|Offer $target): OpeningKind
    {
        return match (true) {
            $target instanceof Offer => OpeningKind::Offer,
            $target instanceof Course => OpeningKind::Course,
            default => OpeningKind::Program,
        };
    }

    private function resolve(SavedOpening $saved): Program|Course|Offer|null
    {
        return match ($saved->kind) {
            OpeningKind::Program => Program::query()->find($saved->target_id),
            OpeningKind::Course => Course::query()->find($saved->target_id),
            OpeningKind::Offer => Offer::query()->find($saved->target_id),
        };
    }
}
