<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCandidateProfileRequest;
use App\Models\Skill;
use App\Services\CandidateProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateProfileController extends Controller
{
    public function __construct(private CandidateProfileService $profiles) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('candidate/profile', [
            'account' => [
                'first_name' => $user->first_name,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'city' => $user->city,
                'state' => $user->state,
                'zip' => $user->zip,
                'country' => $user->country,
            ],
            'candidate' => $this->profiles->present($user->candidate),
            'skillOptions' => Skill::query()
                ->where('is_open', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Skill $skill) => [
                    'id' => $skill->id,
                    'name' => $skill->name,
                ])
                ->values(),
        ]);
    }

    public function update(UpdateCandidateProfileRequest $request): RedirectResponse
    {
        $this->profiles->save(
            $request->user(),
            $request->safe()->except('cv'),
            $request->file('cv'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil professionnel enregistré.']);

        return to_route('candidate-profile.edit');
    }

    public function cv(Request $request): StreamedResponse
    {
        $candidate = $request->user()->candidate;
        abort_unless($candidate !== null, 404);

        $path = $this->profiles->storedCvPath($candidate);
        abort_unless($path !== null, 404);

        return Storage::disk('local')->download($path, 'cv.pdf');
    }
}
