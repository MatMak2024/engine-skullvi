<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveOfferRequest;
use App\Models\Offer;
use App\Models\Skill;
use App\Services\Admin\ManageOfferService;
use App\Support\Pages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function __construct(private ManageOfferService $offers) {}

    public function index(): Response
    {
        $this->authorize('viewCatalog', Offer::class);

        $offers = Offer::query()
            ->withCount('applications')
            ->with('skills')
            ->orderBy('name')
            ->paginate(Pages::PerPage)
            ->through(fn (Offer $offer) => $this->payload($offer));

        return Inertia::render('admin/offers/index', [
            'offers' => $offers,
            'canManage' => request()->user()?->can('create', Offer::class) ?? false,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Offer::class);

        return Inertia::render('admin/offers/form', [
            'offer' => null,
            'skills' => $this->skillOptions(),
        ]);
    }

    public function store(SaveOfferRequest $request): RedirectResponse
    {
        $offer = $this->offers->save($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Offre enregistrée.']);

        return to_route('admin.offers.edit', $offer);
    }

    public function edit(Offer $offer): Response
    {
        $this->authorize('update', $offer);
        $offer->load('skills');

        return Inertia::render('admin/offers/form', [
            'offer' => $this->payload($offer),
            'skills' => $this->skillOptions($offer->skills->pluck('id')->all()),
        ]);
    }

    public function update(SaveOfferRequest $request, Offer $offer): RedirectResponse
    {
        $this->offers->save($request->validated(), $offer);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Offre enregistrée.']);

        return to_route('admin.offers.edit', $offer);
    }

    public function suspend(Offer $offer): RedirectResponse
    {
        $this->authorize('update', $offer);
        $suspended = $this->offers->toggleSuspension($offer);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $suspended ? 'Offre suspendue.' : 'Suspension levée.',
        ]);

        return back();
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $this->authorize('delete', $offer);
        $this->offers->delete($offer);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Offre supprimée.']);

        return to_route('admin.offers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Offer $offer): array
    {
        $offer->loadMissing('skills');

        return [
            'id' => $offer->id,
            'name' => $offer->name,
            'description' => $offer->description,
            'is_open' => $offer->is_open,
            'work_mode' => $offer->work_mode?->value ?? 'remote',
            'work_country' => $offer->work_country,
            'work_city' => $offer->work_city,
            'requested_documents' => $offer->requested_documents ?? [],
            'applications_count' => $offer->applications_count ?? $offer->applications()->count(),
            'required_skills' => $offer->skills
                ->filter(fn (Skill $skill) => (bool) $skill->pivot->is_required)
                ->pluck('id')
                ->values(),
            'bonus_skills' => $offer->skills
                ->filter(fn (Skill $skill) => ! (bool) $skill->pivot->is_required)
                ->pluck('id')
                ->values(),
            'skills' => $offer->skills->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'is_required' => (bool) $skill->pivot->is_required,
            ])->values(),
        ];
    }

    /**
     * @param  list<string>  $includeIds
     * @return list<array{id: string, name: string, category: string|null}>
     */
    private function skillOptions(array $includeIds = []): array
    {
        return Skill::query()
            ->with('categories')
            ->selectable($includeIds)
            ->orderBy('name')
            ->get()
            ->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->categories->first()?->name,
            ])
            ->values()
            ->all();
    }
}
