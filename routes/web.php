<?php

use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::post('/teams/{team}/projects', [TeamController::class, 'storeProject'])->name('teams.projects.store');
    Route::post('/teams/{team}/projects/{project}/tasks', [TeamController::class, 'storeTask'])->name('teams.projects.tasks.store');
});

require __DIR__.'/settings.php';
