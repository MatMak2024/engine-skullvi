<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use App\Services\ProgramService;
use App\Services\SavedOpeningService;
use App\Support\Pages;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(private SavedOpeningService $savedOpenings) {}

    public function index(ProgramService $programs): Response
    {
        $this->authorize('viewAny', Course::class);

        $courses = Course::query()
            ->where('is_open', true)
            ->with('program:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'program' => $course->program ? [
                    'id' => $course->program->id,
                    'name' => $course->program->name,
                ] : null,
            ])
            ->values();

        return Inertia::render('courses/index', [
            'courses' => Pages::of($courses, 'courses_page'),
            'programs' => Pages::of($programs->open()->map(fn (Program $program) => [
                'id' => $program->id,
                'name' => $program->name,
                'description' => $program->description,
                'courses_count' => $program->courses_count,
                'work_mode' => $program->work_mode?->value ?? 'remote',
                'work_city' => $program->work_city,
                'work_country' => $program->work_country,
            ])->values(), 'programs_page'),
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        $this->authorize('view', $course);
        $course->load(['skills', 'program:id,name']);
        $user = $request->user();

        return Inertia::render('courses/show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'is_open' => $course->is_open,
                'program' => $course->program ? [
                    'id' => $course->program->id,
                    'name' => $course->program->name,
                ] : null,
                'skills' => $course->skills->map(fn ($skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'is_required' => (bool) $skill->pivot->is_required,
                ])->values(),
            ],
            'saved' => $user ? $this->savedOpenings->isSaved($user, $course) : false,
        ]);
    }
}
