<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeamController as ApiTeamController;
use App\Http\Controllers\Api\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth');

    Route::middleware('auth')->group(function () {
        Route::get('/teams', [ApiTeamController::class, 'index']);
        Route::post('/teams', [ApiTeamController::class, 'store']);
        Route::get('/teams/{team}/members', [TeamMemberController::class, 'index']);
        Route::post('/teams/{team}/members', [TeamMemberController::class, 'store']);
        Route::patch('/teams/{team}/members/{member}', [TeamMemberController::class, 'update']);
        Route::delete('/teams/{team}/members/{member}', [TeamMemberController::class, 'destroy']);
    });
});
