<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Course;
use App\Models\Offer;
use App\Services\ApplicationReviewService;
use App\Services\ProgramService;
use App\Support\Pages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationReviewService $reviews,
        private ProgramService $programs,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Application::class);

        $applications = Pages::of(
            $this->reviews
                ->ranked(
                    $request->string('program')->toString() ?: null,
                    $request->string('status')->toString() ?: null,
                    $request->string('offer')->toString() ?: null,
                    $request->string('course')->toString() ?: null,
                )
                ->map(fn (Application $application) => $this->reviews->summary($application)),
        );

        return Inertia::render('recruiter/applications/index', [
            'applications' => $applications,
            'programs' => $this->programs->all(),
            'offers' => Offer::query()->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'program' => $request->string('program')->toString(),
                'offer' => $request->string('offer')->toString(),
                'course' => $request->string('course')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'canReview' => $request->user()?->hasRole(Role::Recruiter) ?? false,
        ]);
    }

    public function authorizeExamination(Application $application): RedirectResponse
    {
        $this->authorize('updateStatus', $application);
        $this->reviews->authorizeExamination($application);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Examen autorisé. Le score individuel est calculé.']);

        return back();
    }

    public function show(Request $request, Application $application): Response
    {
        $this->authorize('view', $application);

        return Inertia::render('recruiter/applications/show', [
            'application' => $this->reviews->detail($this->reviews->loadForReview($application)),
            'canReview' => $request->user()?->hasRole(Role::Recruiter) ?? false,
        ]);
    }
}
