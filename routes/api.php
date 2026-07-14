<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

// --- Public: auth & account recovery ---
Route::post('/register', [Api\AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/verify-email', [Api\AuthController::class, 'verifyEmail'])->middleware('throttle:10,1');
Route::post('/resend-code', [Api\AuthController::class, 'resendCode'])->middleware('throttle:5,1');
Route::post('/login', [Api\AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/forgot-password', [Api\AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password', [Api\AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

// --- Authenticated + verified email ---
Route::middleware(['auth:sanctum', 'verified.api'])->group(function () {
    Route::post('/logout', [Api\AuthController::class, 'logout']);
    Route::get('/profile', [Api\ProfileController::class, 'show']);
    Route::patch('/profile', [Api\ProfileController::class, 'update']);
    Route::post('/profile/avatar', [Api\ProfileController::class, 'updateAvatar']);

    // Readable even while the site is locked, so the frontend can show why.
    Route::get('/site-lock', [Api\SiteLockController::class, 'show']);

    // --- Student-facing: blocked while the site is locked (teachers bypass) ---
    Route::middleware('site.unlocked')->group(function () {
        Route::get('/courses', [Api\CourseController::class, 'index']);
        Route::get('/courses/{course}', [Api\CourseController::class, 'show']);
        Route::get('/lessons/{lesson}', [Api\LessonController::class, 'show']);
        Route::get('/challenges/{challenge}', [Api\ChallengeController::class, 'show']);

        Route::post('/challenges/{challenge}/submissions', [Api\SubmissionController::class, 'store'])
            ->middleware('throttle:20,1');
        Route::get('/challenges/{challenge}/submissions', [Api\SubmissionController::class, 'indexForChallenge']);
        Route::get('/submissions/{submission}', [Api\SubmissionController::class, 'show']);

        Route::get('/leaderboard', [Api\LeaderboardController::class, 'index']);
    });

    // --- Teacher only ---
    Route::middleware('teacher')->group(function () {
        Route::put('/site-lock', [Api\SiteLockController::class, 'update']);
        Route::delete('/site-lock', [Api\SiteLockController::class, 'destroy']);

        Route::post('/courses', [Api\Teacher\CourseController::class, 'store']);
        Route::patch('/courses/{course}', [Api\Teacher\CourseController::class, 'update']);
        Route::delete('/courses/{course}', [Api\Teacher\CourseController::class, 'destroy']);

        Route::post('/courses/{course}/lessons', [Api\Teacher\LessonController::class, 'store']);
        Route::patch('/lessons/{lesson}', [Api\Teacher\LessonController::class, 'update']);
        Route::delete('/lessons/{lesson}', [Api\Teacher\LessonController::class, 'destroy']);

        Route::post('/courses/{course}/challenges', [Api\Teacher\ChallengeController::class, 'store']);
        Route::get('/teacher/challenges/{challenge}', [Api\Teacher\ChallengeController::class, 'show']);
        Route::patch('/challenges/{challenge}', [Api\Teacher\ChallengeController::class, 'update']);
        Route::delete('/challenges/{challenge}', [Api\Teacher\ChallengeController::class, 'destroy']);

        Route::post('/challenges/{challenge}/test-cases', [Api\Teacher\TestCaseController::class, 'store']);
        Route::patch('/test-cases/{testCase}', [Api\Teacher\TestCaseController::class, 'update']);
        Route::delete('/test-cases/{testCase}', [Api\Teacher\TestCaseController::class, 'destroy']);

        Route::get('/teacher/students', [Api\Teacher\StudentController::class, 'index']);
        Route::get('/teacher/students/{student}', [Api\Teacher\StudentController::class, 'show']);
    });
});
