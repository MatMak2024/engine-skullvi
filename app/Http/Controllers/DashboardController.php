<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\AdministrationOverview;
use App\Services\ApplicationService;
use App\Services\SavedOpeningService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AdministrationOverview $overview,
        ApplicationService $applications,
        SavedOpeningService $savedOpenings,
    ): Response {
        $user = $request->user();
        $canSeeAdministration = $user !== null && (
            $user->hasRole(Role::Recruiter) || $user->hasRole(Role::SuperAdmin)
        );
        $isCandidate = (bool) $user?->isCandidate();

        return Inertia::render('dashboard', [
            'overview' => $canSeeAdministration ? $overview->present() : null,
            'applications' => $isCandidate
                ? $applications->forCandidate($user)->take(5)->map(fn ($application) => [
                    'id' => $application->id,
                    'status' => $application->status->value,
                    'submitted_at' => $application->submitted_at,
                    'score' => $application->currentEvaluation?->total_score,
                    'suggested_status' => $application->currentEvaluation?->suggested_status?->value,
                    'program' => [
                        'id' => (string) ($application->offer_id ?? $application->course_id ?? $application->program_id),
                        'name' => $application->targetKind().' · '.$application->targetName(),
                    ],
                ])->values()
                : [],
            'savedOpenings' => $user && ! $canSeeAdministration
                ? $savedOpenings->forDashboard($user)
                : [],
        ]);
    }
}
