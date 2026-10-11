<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewNoteRequest;
use App\Models\Application;
use App\Services\ApplicationReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewNoteController extends Controller
{
    public function __construct(private ApplicationReviewService $reviews) {}

    public function store(StoreReviewNoteRequest $request, Application $application): RedirectResponse
    {
        $this->reviews->addNote(
            $application,
            $request->user(),
            $request->validated('note'),
        );

        return to_route('recruiter.applications.show', $application);
    }
}
