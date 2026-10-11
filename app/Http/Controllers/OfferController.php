<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Services\SavedOpeningService;
use App\Support\Pages;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function __construct(private SavedOpeningService $savedOpenings) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Offer::class);

        $offers = Offer::query()
            ->where('is_open', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Offer $offer) => [
                'id' => $offer->id,
                'name' => $offer->name,
                'description' => $offer->description,
                'work_mode' => $offer->work_mode?->value ?? 'remote',
                'work_city' => $offer->work_city,
                'work_country' => $offer->work_country,
            ])
            ->values();

        return Inertia::render('offers/index', [
            'offers' => Pages::of($offers),
        ]);
    }

    public function show(Request $request, Offer $offer): Response
    {
        $this->authorize('view', $offer);
        $offer->load('skills');
        $user = $request->user();

        return Inertia::render('offers/show', [
            'offer' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'description' => $offer->description,
                'is_open' => $offer->is_open,
                'work_mode' => $offer->work_mode?->value ?? 'remote',
                'work_country' => $offer->work_country,
                'work_city' => $offer->work_city,
                'skills' => $offer->skills->map(fn ($skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'is_required' => (bool) $skill->pivot->is_required,
                ])->values(),
            ],
            'saved' => $user ? $this->savedOpenings->isSaved($user, $offer) : false,
        ]);
    }
}
