<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ScoreController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('role:recruiter,super_admin')->group(function () {
            Route::get('scores', ScoreController::class)->name('scores.index');
            Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
            Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
            Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
            Route::get('skills', [SkillController::class, 'index'])->name('skills.index');
            Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
            Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
            Route::get('candidates', [CandidateController::class, 'index'])->name('candidates.index');
            Route::get('candidates/excluded', [CandidateController::class, 'excluded'])->name('candidates.excluded');
        });

        Route::middleware('role:recruiter')->group(function () {
            Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
            Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
            Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
            Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
            Route::patch('courses/{course}/suspension', [CourseController::class, 'suspend'])->name('courses.suspension');
            Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

            Route::get('offers/create', [OfferController::class, 'create'])->name('offers.create');
            Route::post('offers', [OfferController::class, 'store'])->name('offers.store');
            Route::get('offers/{offer}/edit', [OfferController::class, 'edit'])->name('offers.edit');
            Route::put('offers/{offer}', [OfferController::class, 'update'])->name('offers.update');
            Route::patch('offers/{offer}/suspension', [OfferController::class, 'suspend'])->name('offers.suspension');
            Route::delete('offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');

            Route::get('programs/create', [ProgramController::class, 'create'])->name('programs.create');
            Route::post('programs', [ProgramController::class, 'store'])->name('programs.store');
            Route::get('programs/{program}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
            Route::put('programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
            Route::patch('programs/{program}/suspension', [ProgramController::class, 'suspend'])->name('programs.suspension');
            Route::delete('programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');

            Route::get('skills/create', [SkillController::class, 'create'])->name('skills.create');
            Route::post('skills', [SkillController::class, 'store'])->name('skills.store');
            Route::get('skills/{skill}/edit', [SkillController::class, 'edit'])->name('skills.edit');
            Route::put('skills/{skill}', [SkillController::class, 'update'])->name('skills.update');
            Route::patch('skills/{skill}/suspension', [SkillController::class, 'suspend'])->name('skills.suspension');
            Route::delete('skills/{skill}', [SkillController::class, 'destroy'])->name('skills.destroy');

            Route::patch('applications/{application}/examination', [ApplicationController::class, 'authorizeExamination'])->name('applications.examination');
            Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
            Route::patch('candidates/{candidate}/suspension', [CandidateController::class, 'suspend'])->name('candidates.suspension');
            Route::delete('candidates/{candidate}', [CandidateController::class, 'destroy'])->name('candidates.destroy');
        });

        Route::middleware('role:super_admin')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        });
    });
