<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\StoreCourseApplicationRequest;
use App\Http\Requests\StoreOfferApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Course;
use App\Models\Offer;
use App\Models\Program;
use App\Services\ApplicationService;
use App\Services\CandidateProfileService;
use App\Services\Scoring\ScoringCatalog;
use App\Support\Pages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $applications,
        private CandidateProfileService $profiles,
    ) {}

    public function index(Request $request): Response
    {
        $applications = Pages::of(
            $this->applications
                ->forCandidate($request->user())
                ->map(fn (Application $application) => [
                    'id' => $application->id,
                    'status' => $application->status->value,
                    'submitted_at' => $application->submitted_at,
                    'score' => $application->currentEvaluation?->total_score,
                    'suggested_status' => $application->currentEvaluation?->suggested_status?->value,
                    'program' => [
                        'id' => (string) ($application->offer_id ?? $application->course_id ?? $application->program_id),
                        'name' => $application->targetKind().' · '.$application->targetName(),
                    ],
                ]),
        );

        return Inertia::render('applications/index', [
            'applications' => $applications,
        ]);
    }

    public function create(Request $request, Program $program): Response
    {
        $this->authorize('apply', $program);

        $catalog = ScoringCatalog::fromProgram($program);

        return Inertia::render('applications/create', [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'is_open' => $program->is_open,
                'skills' => $this->skillPayload($catalog->skills),
                'requested_documents' => $catalog->requestedDocuments,
            ],
            'candidate' => $this->candidatePayload($request),
        ]);
    }

    public function createForCourse(Request $request, Course $course): Response
    {
        $this->authorize('apply', $course);
        $course->load('skills');

        return Inertia::render('applications/create', [
            'program' => $this->openingFromCourse($course),
            'submitUrl' => route('courses.apply.store', $course),
            'kind' => 'course',
            'candidate' => $this->candidatePayload($request),
        ]);
    }

    public function storeForCourse(StoreCourseApplicationRequest $request, Course $course): RedirectResponse
    {
        $application = $this->applications->submit(
            $request->user(),
            $course,
            $request->safe()->except(['cv', 'documents']),
            $request->file('cv'),
            $request->file('documents', []) ?? [],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Candidature envoyée.']);

        return to_route('applications.show', $application);
    }

    public function createForOffer(Request $request, Offer $offer): Response
    {
        $this->authorize('apply', $offer);
        $offer->load('skills');

        return Inertia::render('applications/create', [
            'program' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'is_open' => $offer->is_open,
                'skills' => $this->skillPayload($offer->skills),
                'requested_documents' => array_values($offer->requested_documents ?? []),
            ],
            'submitUrl' => route('offers.apply.store', $offer),
            'kind' => 'offer',
            'candidate' => $this->candidatePayload($request),
        ]);
    }

    public function storeForOffer(StoreOfferApplicationRequest $request, Offer $offer): RedirectResponse
    {
        $application = $this->applications->submit(
            $request->user(),
            $offer,
            $request->safe()->except(['cv', 'documents']),
            $request->file('cv'),
            $request->file('documents', []) ?? [],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Candidature envoyée.']);

        return to_route('applications.show', $application);
    }

    public function store(StoreApplicationRequest $request, Program $program): RedirectResponse
    {
        $application = $this->applications->submit(
            $request->user(),
            $program,
            $request->safe()->except(['cv', 'documents']),
            $request->file('cv'),
            $request->file('documents', []) ?? [],
        );

        return to_route('applications.show', $application);
    }

    public function show(Application $application): Response
    {
        $this->authorize('viewOwn', $application);

        $application->load([
            'program:id,name',
            'course:id,name',
            'offer:id,name',
            'currentEvaluation:id,application_id,total_score,suggested_status,completeness_rate',
        ]);

        return Inertia::render('applications/show', [
            'application' => [
                'id' => $application->id,
                'status' => $application->status->value,
                'submitted_at' => $application->submitted_at,
                'education_level' => $application->education_level->value,
                'availability' => $application->availability->value,
                'motivation_text' => $application->motivation_text,
                'cv_url' => filled($application->cv_path) ? route('applications.cv', $application) : null,
                'score' => $application->currentEvaluation?->total_score,
                'suggested_status' => $application->currentEvaluation?->suggested_status?->value,
                'program' => [
                    'id' => (string) ($application->offer_id ?? $application->course_id ?? $application->program_id),
                    'name' => $application->targetKind().' · '.$application->targetName(),
                ],
            ],
        ]);
    }

    /**
     * @return array{id: string, name: string, is_open: bool, skills: Collection<int, array{id: string, name: string, is_required: bool}>}
     */
    private function openingFromCourse(Course $course): array
    {
        return [
            'id' => $course->id,
            'name' => $course->name,
            'is_open' => $course->is_open,
            'skills' => $this->skillPayload($course->skills),
            'requested_documents' => array_values($course->requested_documents ?? []),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $skills
     * @return Collection<int, array{id: string, name: string, is_required: bool}>
     */
    private function skillPayload(Collection $skills): Collection
    {
        return $skills->map(fn ($skill) => [
            'id' => $skill->id,
            'name' => $skill->name,
            'is_required' => (bool) $skill->pivot->is_required,
        ])->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function candidatePayload(Request $request): ?array
    {
        $user = $request->user();
        $presented = $this->profiles->present($user?->candidate);

        if ($presented !== null) {
            return $presented;
        }

        if ($user === null || (blank($user->phone) && blank($user->country) && blank($user->city))) {
            return null;
        }

        return [
            'phone' => $user->phone,
            'country' => (string) $user->country,
            'city' => $user->city,
            'has_cv' => false,
            'education_level' => null,
            'field_of_study' => null,
            'years_experience' => 0,
            'projects_count' => 0,
            'github_url' => null,
            'portfolio_url' => null,
            'linkedin_url' => null,
            'availability' => null,
            'interests' => [],
            'other_skills' => [],
            'skills' => [],
            'diplomas' => [],
            'trainings' => [],
        ];
    }

    public function cv(Application $application): StreamedResponse
    {
        $this->authorize('view', $application);

        $path = $this->applications->storedCvPath($application);
        abort_unless($path !== null, 404);

        return Storage::disk('local')->download($path, 'cv.pdf');
    }

    public function document(Application $application, ApplicationDocument $document): StreamedResponse
    {
        $this->authorize('view', $application);
        abort_unless($document->application_id === $application->id, 404);

        $path = $document->path;
        abort_unless(is_string($path) && str_starts_with($path, 'documents/') && ! str_contains($path, '..'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $document->label.'.pdf');
    }
}
