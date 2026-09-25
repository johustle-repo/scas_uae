<?php

use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\AvatarController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SessionController;
use App\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('settings.password.update');
    Route::post('settings/avatar', [AvatarController::class, 'store'])->name('settings.avatar.store');
    Route::delete('settings/avatar', [AvatarController::class, 'destroy'])->name('settings.avatar.destroy');
    Route::get('users/{user}/avatar', [AvatarController::class, 'show'])->name('users.avatar');
    Route::delete('settings/sessions', [SessionController::class, 'destroy'])
        ->middleware('throttle:6,1')
        ->name('settings.sessions.destroy');

    Route::middleware('can:manage-users')->group(function (): void {
        Route::get('settings/users', [UserController::class, 'index'])->name('settings.users.index');
        Route::post('settings/users', [UserController::class, 'store'])->name('settings.users.store');
        Route::put('settings/users/{user}', [UserController::class, 'update'])->name('settings.users.update');
        Route::patch('settings/users/{user}/status', [UserController::class, 'updateStatus'])->name('settings.users.status');
        Route::put('settings/users/{user}/password', [UserController::class, 'resetPassword'])->name('settings.users.password');
    });

    Route::get('settings/logs', [AuditLogController::class, 'index'])
        ->middleware('can:view-audit-log')
        ->name('settings.logs.index');
});
