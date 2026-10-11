<?php

namespace App\Services\Admin;

use App\Enums\WorkMode;
use App\Models\Offer;
use App\Models\Skill;
use App\Support\RequestedDocuments;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageOfferService
{
    /**
     * @param  array{name: string, description: ?string, is_open: bool, work_mode: string, work_country: ?string, work_city: ?string, required_skills: list<string>, bonus_skills: list<string>}  $data
     */
    public function save(array $data, ?Offer $offer = null): Offer
    {
        return DB::transaction(function () use ($data, $offer) {
            $offer ??= new Offer;
            $nameChanged = ! $offer->exists || $offer->name !== $data['name'];
            $mode = WorkMode::from($data['work_mode']);
            $onSite = $mode !== WorkMode::Remote;

            $offer->fill([
                'name' => $data['name'],
                'description' => ($data['description'] ?? null) ?: null,
                'is_open' => $data['is_open'],
                'work_mode' => $mode,
                'work_country' => $onSite ? (($data['work_country'] ?? null) ?: null) : null,
                'work_city' => $onSite ? (($data['work_city'] ?? null) ?: null) : null,
                'requested_documents' => RequestedDocuments::fromText($data['requested_documents'] ?? null),
                'slug' => $nameChanged
                    ? $this->uniqueSlug($data['name'], $offer->getKey())
                    : $offer->slug,
            ]);
            $offer->save();

            $sync = [];

            foreach ($data['required_skills'] as $skillId) {
                $sync[$skillId] = ['is_required' => true];
            }

            foreach ($data['bonus_skills'] as $skillId) {
                $sync[$skillId] = ['is_required' => false];
            }

            Skill::ensureSelectable(
                array_keys($sync),
                $offer->exists ? $offer->skills()->pluck('skills.id')->all() : [],
            );
            $offer->skills()->sync($sync);

            return $offer;
        });
    }

    public function toggleSuspension(Offer $offer): bool
    {
        $offer->forceFill([
            'is_open' => ! $offer->is_open,
        ])->save();

        return ! $offer->is_open;
    }

    public function delete(Offer $offer): void
    {
        if ($offer->applications()->exists()) {
            throw ValidationException::withMessages([
                'offer' => 'Cette offre a des candidatures. Fermez-la au lieu de la supprimer.',
            ]);
        }

        $offer->delete();
    }

    private function uniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'offre';
        $slug = $base;
        $suffix = 2;

        while (
            Offer::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
