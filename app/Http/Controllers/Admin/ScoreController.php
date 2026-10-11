<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\ScoreExamination;
use Inertia\Inertia;
use Inertia\Response;

class ScoreController extends Controller
{
    public function __invoke(ScoreExamination $examination): Response
    {
        $this->authorize('viewAny', Application::class);

        return Inertia::render('admin/scores/index', [
            'examination' => $examination->present(),
        ]);
    }
}
