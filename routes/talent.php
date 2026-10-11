<?php

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RecruiterApplicationController;
use App\Http\Controllers\ReviewNoteController;
use App\Http\Controllers\SavedOpeningController;
use Illuminate\Support\Facades\Route;

Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
Route::get('programs/{program}', [ProgramController::class, 'show'])->name('programs.show');
Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
Route::get('offers/{offer}', [OfferController::class, 'show'])->name('offers.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('profile', [CandidateProfileController::class, 'edit'])->name('candidate-profile.edit');
    Route::post('profile', [CandidateProfileController::class, 'update'])->name('candidate-profile.update');
    Route::get('profile/cv', [CandidateProfileController::class, 'cv'])->name('candidate-profile.cv');

    Route::post('programs/{program}/save', [SavedOpeningController::class, 'storeProgram'])->name('programs.save');
    Route::delete('programs/{program}/save', [SavedOpeningController::class, 'destroyProgram'])->name('programs.unsave');
    Route::post('courses/{course}/save', [SavedOpeningController::class, 'storeCourse'])->name('courses.save');
    Route::delete('courses/{course}/save', [SavedOpeningController::class, 'destroyCourse'])->name('courses.unsave');
    Route::post('offers/{offer}/save', [SavedOpeningController::class, 'storeOffer'])->name('offers.save');
    Route::delete('offers/{offer}/save', [SavedOpeningController::class, 'destroyOffer'])->name('offers.unsave');

    Route::get('courses/{course}/apply', [ApplicationController::class, 'createForCourse'])->name('courses.apply');
    Route::post('courses/{course}/apply', [ApplicationController::class, 'storeForCourse'])
        ->middleware('throttle:applications')
        ->name('courses.apply.store');

    Route::get('offers/{offer}/apply', [ApplicationController::class, 'createForOffer'])->name('offers.apply');
    Route::post('offers/{offer}/apply', [ApplicationController::class, 'storeForOffer'])
        ->middleware('throttle:applications')
        ->name('offers.apply.store');

    Route::get('programs/{program}/apply', [ApplicationController::class, 'create'])
        ->middleware('program.open')
        ->name('applications.create');
    Route::post('programs/{program}/apply', [ApplicationController::class, 'store'])
        ->middleware(['program.open', 'throttle:applications'])
        ->name('applications.store');

    Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::get('applications/{application}/cv', [ApplicationController::class, 'cv'])->name('applications.cv');
    Route::get('applications/{application}/documents/{document}', [ApplicationController::class, 'document'])->name('applications.document');
});

Route::middleware(['auth', 'verified', 'role:recruiter,super_admin'])
    ->prefix('recruiter')
    ->name('recruiter.')
    ->group(function () {
        Route::middleware('permission:application:view')->group(function () {
            Route::get('applications', [RecruiterApplicationController::class, 'index'])->name('applications.index');
            Route::get('applications/{application}', [RecruiterApplicationController::class, 'show'])->name('applications.show');
        });

        Route::patch('applications/{application}/status', [RecruiterApplicationController::class, 'updateStatus'])
            ->middleware(['role:recruiter', 'permission:application:update-status'])
            ->name('applications.update-status');
        Route::post('applications/{application}/notes', [ReviewNoteController::class, 'store'])
            ->middleware(['role:recruiter', 'permission:review-note:create'])
            ->name('review-notes.store');
    });
