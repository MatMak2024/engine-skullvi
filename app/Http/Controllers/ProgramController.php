<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Services\ProgramService;
use App\Services\SavedOpeningService;
use App\Support\Pages;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function __construct(
        private ProgramService $programs,
        private SavedOpeningService $savedOpenings,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Program::class);

        return Inertia::render('programs/index', [
            'programs' => Pages::of($this->programs->open()->map(fn (Program $program) => [
                'id' => $program->id,
                'name' => $program->name,
                'description' => $program->description,
                'courses_count' => $program->courses_count,
                'work_mode' => $program->work_mode?->value ?? 'remote',
                'work_city' => $program->work_city,
                'work_country' => $program->work_country,
            ])->values()),
        ]);
    }

    public function show(Request $request, Program $program): Response
    {
        $this->authorize('view', $program);

        $program = $this->programs->withSkills($program);
        $user = $request->user();

        return Inertia::render('programs/show', [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'slug' => $program->slug,
                'description' => $program->description,
                'is_open' => $program->is_open,
                'work_mode' => $program->work_mode?->value ?? 'remote',
                'work_country' => $program->work_country,
                'work_city' => $program->work_city,
                'skills' => $program->skills->map(fn ($skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'is_required' => (bool) $skill->pivot->is_required,
                ])->values(),
                'courses' => $program->courses->map(fn ($course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'description' => $course->description,
                    'skills' => $course->skills->map(fn ($skill) => [
                        'id' => $skill->id,
                        'name' => $skill->name,
                        'is_required' => (bool) $skill->pivot->is_required,
                    ])->values(),
                ])->values(),
            ],
            'saved' => $user ? $this->savedOpenings->isSaved($user, $program) : false,
        ]);
    }
}
