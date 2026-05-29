<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LeaderboardController;

// Authentication endpoints - these handle user sessions
Route::post('/auth/register',[AuthController::class,'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Users resource - handles user management
Route::post('/users', [UserController::class, 'store']); // Registration
Route::resource('users', UserController::class);
Route::middleware('auth:sanctum')->group(function() {
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);
});

// Games resource - manages game states and actions
Route::middleware('auth:sanctum')->group(function() {
    Route::apiResource('games', GameController::class);
    Route::post('/games/{game}/guesses', [GameController::class, 'makeGuess']);
});

// Leaderboard resource - handles score tracking
Route::get('/leaderboard', [LeaderboardController::class, 'index']);
Route::get('/leaderboard/users/{user}', [LeaderboardController::class, 'show'])
    ->middleware('auth:sanctum');