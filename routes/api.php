<?php

use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SchoolController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::get('applications/status/{reference}', [ApplicationController::class, 'track'])
        ->middleware('throttle:api')
        ->name('applications.track');

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:auth')
            ->name('auth.register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth')
            ->name('auth.login');
        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])
            ->middleware('auth:sanctum')
            ->name('auth.me');
    });

    // Authenticated applicant endpoints
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::post('applications', [ApplicationController::class, 'store'])->name('applications.store');
    });

    // Administrator endpoints
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin', 'throttle:api'])->group(function () {
        Route::get('applications', [AdminApplicationController::class, 'index'])->name('admin.applications.index');
        Route::patch('applications/{application}/status', [AdminApplicationController::class, 'updateStatus'])->name('admin.applications.status');
        Route::patch('settings/window', [AdminApplicationController::class, 'updateWindow'])->name('admin.settings.window');
    });
});
