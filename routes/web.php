<?php

use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::inertia('/a-propos', 'about')->name('about');

Route::get('definir-mot-de-passe/{user}', [SetPasswordController::class, 'create'])
    ->middleware('signed')
    ->name('password.set');

Route::post('definir-mot-de-passe/{user}', [SetPasswordController::class, 'store'])
    ->middleware('signed')
    ->name('password.set.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/talent.php';
require __DIR__.'/admin.php';
