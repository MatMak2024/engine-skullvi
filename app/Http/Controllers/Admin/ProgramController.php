<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProgramRequest;
use App\Models\Course;
use App\Models\Program;
use App\Models\Skill;
use App\Services\Admin\ManageProgramService;
use App\Support\Pages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function __construct(private ManageProgramService $programs) {}

    public function index(): Response
    {
        $this->authorize('viewCatalog', Program::class);

        $programs = Program::query()
            ->withCount(['applications', 'courses'])
            ->with('skills')
            ->orderBy('name')
            ->paginate(Pages::PerPage)
            ->through(fn (Program $program) => $this->payload($program));

        return Inertia::render('admin/programs/index', [
            'programs' => $programs,
            'canManage' => request()->user()?->can('create', Program::class) ?? false,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Program::class);

        return Inertia::render('admin/programs/form', [
            'program' => null,
            'skills' => $this->skillOptions(),
            'courses' => $this->courseOptions(),
        ]);
    }

    public function store(SaveProgramRequest $request): RedirectResponse
    {
        $program = $this->programs->save($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Programme enregistré.']);

        return to_route('admin.programs.edit', $program);
    }

    public function edit(Program $program): Response
    {
        $this->authorize('update', $program);

        $program->load('skills');

        return Inertia::render('admin/programs/form', [
            'program' => [
                ...$this->payload($program),
                'course_ids' => $program->courses()->pluck('id')->all(),
            ],
            'skills' => $this->skillOptions($program->skills->pluck('id')->all()),
            'courses' => $this->courseOptions($program),
        ]);
    }

    public function update(SaveProgramRequest $request, Program $program): RedirectResponse
    {
        $this->programs->save($request->validated(), $program);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Programme enregistré.']);

        return to_route('admin.programs.edit', $program);
    }

    public function suspend(Program $program): RedirectResponse
    {
        $this->authorize('update', $program);
        $suspended = $this->programs->toggleSuspension($program);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $suspended ? 'Programme suspendu.' : 'Suspension levée.',
        ]);

        return back();
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->authorize('delete', $program);
        $this->programs->delete($program);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Programme supprimé.']);

        return to_route('admin.programs.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Program $program): array
    {
        $program->loadMissing('skills');

        return [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'is_open' => $program->is_open,
            'courses_count' => $program->courses_count ?? $program->courses()->count(),
            'work_mode' => $program->work_mode?->value ?? 'remote',
            'work_country' => $program->work_country,
            'work_city' => $program->work_city,
            'requested_documents' => $program->requested_documents ?? [],
            'applications_count' => $program->applications_count ?? $program->applications()->count(),
            'required_skills' => $program->skills
                ->filter(fn (Skill $skill) => (bool) $skill->pivot->is_required)
                ->pluck('id')
                ->values(),
            'bonus_skills' => $program->skills
                ->filter(fn (Skill $skill) => ! (bool) $skill->pivot->is_required)
                ->pluck('id')
                ->values(),
            'skills' => $program->skills->map(fn (Skill $skill) => [
                'id' => $skill->id,
                'name' => $skill->name,
                'is_required' => (bool) $skill->pivot->is_required,
            ])->values(),
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function courseOptions(?Program $program = null): array
    {
        return Course::query()
            ->where(function ($query) use ($program): void {
                $query->whereNull('program_id');

                if ($program instanceof Program) {
                    $query->orWhere('program_id', $program->id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'name' => $course->name,
            ])
            ->all();
    }

    /**
     * @param  list<string>  $includeIds
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
