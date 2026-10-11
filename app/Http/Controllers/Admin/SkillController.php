<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSkillRequest;
use App\Models\Category;
use App\Models\Skill;
use App\Services\Admin\ManageSkillService;
use App\Support\Pages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    public function __construct(private ManageSkillService $skills) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Skill::class);

        return Inertia::render('admin/skills/index', [
            'canManage' => request()->user()?->can('create', Skill::class) ?? false,
            'skills' => Skill::query()
                ->with('categories')
                ->withCount(['programs', 'courses', 'offers', 'applications'])
                ->orderBy('name')
                ->paginate(Pages::PerPage)
                ->through(fn (Skill $skill) => $this->payload($skill)),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Skill::class);

        return Inertia::render('admin/skills/form', [
            'skill' => null,
            'categories' => $this->categories(),
        ]);
    }

    public function store(SaveSkillRequest $request): RedirectResponse
    {
        $skill = $this->skills->save($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compétence enregistrée.']);

        return to_route('admin.skills.edit', $skill);
    }

    public function edit(Skill $skill): Response
    {
        $this->authorize('update', $skill);

        return Inertia::render('admin/skills/form', [
            'skill' => $this->payload($skill),
            'categories' => $this->categories(),
        ]);
    }

    public function update(SaveSkillRequest $request, Skill $skill): RedirectResponse
    {
        $this->skills->save($request->validated(), $skill);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compétence enregistrée.']);

        return to_route('admin.skills.edit', $skill);
    }

    public function suspend(Skill $skill): RedirectResponse
    {
        $this->authorize('update', $skill);
        $suspended = $this->skills->toggleSuspension($skill);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $suspended ? 'Compétence suspendue.' : 'Suspension levée.',
        ]);

        return back();
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $this->authorize('delete', $skill);
        $this->skills->delete($skill);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compétence supprimée.']);

        return to_route('admin.skills.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Skill $skill): array
    {
        $skill->loadMissing('categories');

        return [
            'id' => $skill->id,
            'name' => $skill->name,
            'is_open' => $skill->is_open,
            'category_id' => $skill->categories->first()?->id,
            'category' => $skill->categories->first()?->name,
            'programs_count' => $skill->programs_count ?? $skill->programs()->count(),
            'courses_count' => $skill->courses_count ?? $skill->courses()->count(),
            'offers_count' => $skill->offers_count ?? $skill->offers()->count(),
            'applications_count' => $skill->applications_count ?? $skill->applications()->count(),
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function categories(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }
}
