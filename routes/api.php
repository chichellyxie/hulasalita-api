<?php

use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminLanguageController;
use App\Http\Controllers\Api\V1\Admin\AdminPuzzleController;
use App\Http\Controllers\Api\V1\Admin\AdminSchoolController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\GuessController;
use App\Http\Controllers\Api\V1\LanguageController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\PuzzleController;
use App\Http\Controllers\Api\V1\SchoolController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HulaSalita API — every path starts with /api/v1
|--------------------------------------------------------------------------
| Public            → register, login, list schools
| Any logged-in user → me, logout, languages, categories
| Student            → upload school ID (pending/rejected only)
| Verified student   → games, guesses, stats, leaderboard
| Admin              → verification queue, content, users, reports
*/

Route::prefix('v1')->group(function () {

    // ---------- Public ----------
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('schools', [SchoolController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {

        // ---------- Any logged-in user ----------
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('me', [AuthController::class, 'updateMe']);
        Route::get('languages', [LanguageController::class, 'index']);
        Route::get('categories', [CategoryController::class, 'index']);

        // ---------- Pending / rejected student ----------
        Route::post('verification', [VerificationController::class, 'store'])->middleware('role:student');

        // ---------- Verified student ----------
        Route::middleware('verified.student')->group(function () {
            Route::get('puzzles/daily', [PuzzleController::class, 'daily']);
            Route::get('puzzles', [PuzzleController::class, 'index']);
            Route::get('puzzles/{puzzle}', [PuzzleController::class, 'show'])->whereNumber('puzzle');
            Route::post('puzzles/{puzzle}/guess', [GuessController::class, 'store'])
                ->whereNumber('puzzle')
                ->middleware('throttle:30,1');
            Route::get('me/stats', [StatsController::class, 'show']);
            Route::get('leaderboard', [LeaderboardController::class, 'index']);
        });

        // ---------- Admin ----------
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('verifications', [AdminVerificationController::class, 'index']);
            Route::get('verifications/{verification}/document', [AdminVerificationController::class, 'document']);
            Route::patch('verifications/{verification}', [AdminVerificationController::class, 'decide']);

            Route::apiResource('puzzles', AdminPuzzleController::class);
            Route::apiResource('schools', AdminSchoolController::class);
            Route::apiResource('categories', AdminCategoryController::class)->except('show');
            Route::apiResource('languages', AdminLanguageController::class)->except('show');

            Route::get('users', [AdminUserController::class, 'index']);
            Route::patch('users/{user}', [AdminUserController::class, 'update']);

            Route::get('reports', [ReportController::class, 'index']);
        });
    });
});
