<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeamController as ApiTeamController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth');

    Route::middleware('auth')->group(function () {
        Route::get('/teams', [ApiTeamController::class, 'index']);
        Route::post('/teams', [ApiTeamController::class, 'store']);
        Route::get('/teams/{team}/members', [ApiTeamController::class, 'members']);
        Route::post('/teams/{team}/members', [ApiTeamController::class, 'addMember']);
        Route::patch('/teams/{team}/members/{user}', [ApiTeamController::class, 'updateMember']);
        Route::delete('/teams/{team}/members/{user}', [ApiTeamController::class, 'removeMember']);
    });
});
