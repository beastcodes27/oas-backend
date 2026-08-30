<?php

use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\OfficerController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeFeatureController;
use App\Http\Controllers\NectaController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\Staff\ApplicationController as StaffApplicationController;
use App\Http\Controllers\Staff\ApplicationExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::get('gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Route::get('home-features', [HomeFeatureController::class, 'index'])->name('home-features.index');
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
        Route::patch('profile', [AuthController::class, 'updateProfile'])
            ->middleware('auth:sanctum')
            ->name('auth.profile');
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
        Route::get('applications/export', [ApplicationExportController::class, 'download'])->name('staff.applications.export');
    });

    // Administrator endpoints
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin', 'throttle:api'])->group(function () {
        Route::patch('selections/publish', [AdminApplicationController::class, 'publishSelections'])->name('admin.selections.publish');
        Route::patch('settings/window', [AdminApplicationController::class, 'updateWindow'])->name('admin.settings.window');
        Route::patch('school/content', [AdminApplicationController::class, 'updateContent'])->name('admin.school.content');
        Route::patch('school/contact', [AdminApplicationController::class, 'updateContact'])->name('admin.school.contact');
        Route::get('gallery', [App\Http\Controllers\Admin\GalleryController::class, 'index'])->name('admin.gallery.index');
        Route::post('gallery', [App\Http\Controllers\Admin\GalleryController::class, 'store'])->name('admin.gallery.store');
        Route::delete('gallery/{galleryItem}', [App\Http\Controllers\Admin\GalleryController::class, 'destroy'])->name('admin.gallery.destroy');
        Route::get('officers', [OfficerController::class, 'index'])->name('admin.officers.index');
        Route::post('officers', [OfficerController::class, 'store'])->name('admin.officers.store');
        Route::patch('officers/{user}/password', [OfficerController::class, 'resetPassword'])->name('admin.officers.password');
        Route::delete('officers/{user}', [OfficerController::class, 'destroy'])->name('admin.officers.destroy');
        Route::get('home-features', [App\Http\Controllers\Admin\HomeFeatureController::class, 'index'])->name('admin.home-features.index');
        Route::post('home-features', [App\Http\Controllers\Admin\HomeFeatureController::class, 'store'])->name('admin.home-features.store');
        Route::put('home-features/{homeFeature}', [App\Http\Controllers\Admin\HomeFeatureController::class, 'update'])->name('admin.home-features.update');
        Route::delete('home-features/{homeFeature}', [App\Http\Controllers\Admin\HomeFeatureController::class, 'destroy'])->name('admin.home-features.destroy');
    });
});
