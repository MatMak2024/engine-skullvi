<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCourseRequest;
use App\Models\Course;
use App\Models\Program;
use App\Models\Skill;
use App\Services\Admin\ManageCourseService;
use App\Support\Pages;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(private ManageCourseService $courses) {}

    public function index(): Response
    {
        $this->authorize('viewCatalog', Course::class);

        $courses = Course::query()
            ->with('program:id,name')
            ->withCount('applications')
            ->orderBy('name')
            ->paginate(Pages::PerPage)
            ->through(fn (Course $course) => $this->payload($course));

        return Inertia::render('admin/courses/index', [
            'courses' => $courses,
            'canManage' => request()->user()?->can('create', Course::class) ?? false,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Course::class);

        return Inertia::render('admin/courses/form', [
            'course' => null,
            'skills' => $this->skillOptions(),
            'programs' => $this->programOptions(),
        ]);
    }

    public function store(SaveCourseRequest $request): RedirectResponse
    {
        $course = $this->courses->save($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cours enregistré.']);

        return to_route('admin.courses.edit', $course);
    }

    public function edit(Course $course): Response
    {
        $this->authorize('update', $course);
        $course->load('skills');

        return Inertia::render('admin/courses/form', [
            'course' => $this->payload($course),
            'skills' => $this->skillOptions($course->skills->pluck('id')->all()),
            'programs' => $this->programOptions(),
        ]);
    }

    public function update(SaveCourseRequest $request, Course $course): RedirectResponse
    {
        $this->courses->save($request->validated(), $course);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cours enregistré.']);

        return to_route('admin.courses.edit', $course);
    }

    public function suspend(Course $course): RedirectResponse
    {
        $this->authorize('update', $course);
        $suspended = $this->courses->toggleSuspension($course);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $suspended ? 'Cours suspendu.' : 'Suspension levée.',
        ]);

        return back();
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);
        $this->courses->delete($course);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cours supprimé.']);

        return to_route('admin.courses.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Course $course): array
    {
        $course->loadMissing(['skills', 'program:id,name']);

        return [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'is_open' => $course->is_open,
            'program_id' => $course->program_id,
            'program_name' => $course->program?->name,
            'requested_documents' => $course->requested_documents ?? [],
            'applications_count' => $course->applications_count ?? $course->applications()->count(),
            'required_skills' => $course->skills
                ->filter(fn (Skill $skill) => (bool) $skill->pivot->is_required)
                ->pluck('id')
                ->values(),
            'bonus_skills' => $course->skills
                ->filter(fn (Skill $skill) => ! (bool) $skill->pivot->is_required)
                ->pluck('id')
                ->values(),
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function programOptions(): array
    {
        return Program::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Program $program) => [
                'id' => $program->id,
                'name' => $program->name,
            ])
            ->all();
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
