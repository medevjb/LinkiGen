<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CareerGoalController;
use App\Http\Controllers\Api\CareerProfileController;
use App\Http\Controllers\Api\ProfileImportController;
use App\Http\Controllers\Api\WeeklyPlanController;
use Illuminate\Support\Facades\Route;

// --- identity/account-access ---------------------------------------------

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-email/otp', [AuthController::class, 'verifyEmailOtp']);
    Route::post('/verify-email/resend', [AuthController::class, 'resendVerification']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // --- career/profile-import ---
    Route::post('/profile/import', [ProfileImportController::class, 'import']);
    Route::post('/profile/import/manual', [ProfileImportController::class, 'manual']);
    Route::get('/profile/draft', [ProfileImportController::class, 'draft']);

    // --- career/career-profile ---
    Route::get('/profile', [CareerProfileController::class, 'show']);
    Route::patch('/profile', [CareerProfileController::class, 'update']);
    Route::post('/profile/confirm', [CareerProfileController::class, 'confirm']);
    Route::post('/profile/experience', [CareerProfileController::class, 'storeExperience']);
    Route::delete('/profile/experience/{id}', [CareerProfileController::class, 'deleteExperience']);
    Route::post('/profile/education', [CareerProfileController::class, 'storeEducation']);
    Route::delete('/profile/education/{id}', [CareerProfileController::class, 'deleteEducation']);
    Route::put('/profile/skills', [CareerProfileController::class, 'syncSkills']);

    // --- career/goals-and-analysis ---
    Route::get('/goals', [CareerGoalController::class, 'show']);
    Route::put('/goals', [CareerGoalController::class, 'store']);

    // --- planning/weekly-plan ---
    Route::get('/plans/current', [WeeklyPlanController::class, 'current']);
    Route::post('/plans', [WeeklyPlanController::class, 'regenerate']);
    Route::patch('/tasks/{id}', [WeeklyPlanController::class, 'updateTask']);
});
