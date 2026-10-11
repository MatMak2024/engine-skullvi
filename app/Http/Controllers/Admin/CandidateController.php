<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\User;
use App\Services\Admin\ManageCandidateService;
use App\Support\Pages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function __construct(private ManageCandidateService $candidates) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Candidate::class);

        $candidates = Candidate::query()
            ->with('user:id,first_name,name,email')
            ->withCount('applications')
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'candidates.user_id')
                    ->limit(1),
            )
            ->paginate(Pages::PerPage)
            ->through(fn (Candidate $candidate) => [
                'id' => $candidate->user_id,
                'name' => $candidate->user->displayName(),
                'email' => $candidate->user->email,
                'phone' => $candidate->phone,
                'country' => $candidate->country,
                'city' => $candidate->city,
                'applications_count' => $candidate->applications_count,
                'suspended' => $candidate->isSuspended(),
            ]);

        return Inertia::render('admin/candidates/index', [
            'candidates' => $candidates,
            'canManage' => request()->user()?->hasRole(Role::Recruiter) ?? false,
        ]);
    }

    public function excluded(): Response
    {
        $this->authorize('viewAny', Candidate::class);

        $suspended = Pages::of(
            Candidate::query()
                ->whereNotNull('suspended_at')
                ->with('user:id,first_name,name,email')
                ->orderByDesc('suspended_at')
                ->get()
                ->map(fn (Candidate $candidate) => [
                    'id' => $candidate->user_id,
                    'name' => $candidate->user->displayName(),
                    'email' => $candidate->user->email,
                    'reason' => $candidate->suspension_reason,
                    'at' => $candidate->suspended_at,
                ]),
            'suspended_page',
        );

        $removed = Pages::of(
            User::query()
                ->whereNotNull('candidate_removed_at')
                ->orderByDesc('candidate_removed_at')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->displayName(),
                    'email' => $user->email,
                    'reason' => $user->candidate_removal_reason,
                    'at' => $user->candidate_removed_at,
                ]),
            'removed_page',
        );

        return Inertia::render('admin/candidates/excluded', [
            'suspended' => $suspended,
            'removed' => $removed,
            'canManage' => request()->user()?->hasRole(Role::Recruiter) ?? false,
        ]);
    }

    public function suspend(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('suspend', $candidate);

        if ($candidate->isSuspended()) {
            $this->candidates->lift($candidate);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Suspension levée.',
            ]);

            return back();
        }

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Indiquez la situation à résoudre.',
        ])['reason'];

        $this->candidates->suspend($candidate, $reason);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Candidat suspendu. Il est notifié.',
        ]);

        return back();
    }

    public function destroy(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('delete', $candidate);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Indiquez pourquoi ce candidat est retiré.',
        ])['reason'];

        $this->candidates->delete($candidate, $reason);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Candidat retiré. Le compte utilisateur reste.',
        ]);

        return to_route('admin.candidates.index');
    }
}
