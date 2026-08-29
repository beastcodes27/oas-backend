<?php

use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NectaController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\Staff\ApplicationController as StaffApplicationController;
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
        Route::get('applications/{application}/form', [ApplicationController::class, 'form'])->name('applications.form');
        Route::post('necta/lookup', [NectaController::class, 'lookup'])->name('necta.lookup');
    });

    // Staff endpoints — administrators and admission officers
    Route::prefix('staff')->middleware(['auth:sanctum', 'staff', 'throttle:api'])->group(function () {
        Route::get('applications', [StaffApplicationController::class, 'index'])->name('staff.applications.index');
        Route::patch('applications/{application}/status', [StaffApplicationController::class, 'updateStatus'])->name('staff.applications.status');
    });

    // Administrator endpoints
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin', 'throttle:api'])->group(function () {
        Route::patch('selections/publish', [AdminApplicationController::class, 'publishSelections'])->name('admin.selections.publish');
        Route::patch('settings/window', [AdminApplicationController::class, 'updateWindow'])->name('admin.settings.window');
        Route::patch('school/content', [AdminApplicationController::class, 'updateContent'])->name('admin.school.content');
        Route::patch('school/contact', [AdminApplicationController::class, 'updateContact'])->name('admin.school.contact');
    });
});
