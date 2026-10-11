<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Offer;
use App\Models\Program;
use App\Services\SavedOpeningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SavedOpeningController extends Controller
{
    public function __construct(private SavedOpeningService $saved) {}

    public function storeProgram(Request $request, Program $program): RedirectResponse
    {
        $this->saved->save($request->user(), $program);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Programme enregistré pour plus tard.']);

        return back();
    }

    public function destroyProgram(Request $request, Program $program): RedirectResponse
    {
        $this->saved->forget($request->user(), $program);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Programme retiré de vos enregistrements.']);

        return back();
    }

    public function storeCourse(Request $request, Course $course): RedirectResponse
    {
        $this->saved->save($request->user(), $course);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cours enregistré pour plus tard.']);

        return back();
    }

    public function destroyCourse(Request $request, Course $course): RedirectResponse
    {
        $this->saved->forget($request->user(), $course);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cours retiré de vos enregistrements.']);

        return back();
    }

    public function storeOffer(Request $request, Offer $offer): RedirectResponse
    {
        $this->saved->save($request->user(), $offer);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Offre enregistrée pour plus tard.']);

        return back();
    }

    public function destroyOffer(Request $request, Offer $offer): RedirectResponse
    {
        $this->saved->forget($request->user(), $offer);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Offre retirée de vos enregistrements.']);

        return back();
    }
}
