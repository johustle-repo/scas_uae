<?php

use App\Http\Controllers\AdoptionApplicationController;
use App\Http\Controllers\AdoptionRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicDogPhotoController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');
Route::get('adopt/dogs/{dog}/photo', PublicDogPhotoController::class)->name('adopt.photo');
Route::post('adopt/applications', [AdoptionApplicationController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('adopt.applications.store');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('can:manage-placements')->group(function (): void {
        Route::get('adoption-requests', [AdoptionRequestController::class, 'index'])->name('adoption-requests.index');
        Route::patch('adoption-requests/{adoptionRequest}', [AdoptionRequestController::class, 'update'])->name('adoption-requests.update');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/dogs.php';
require __DIR__.'/settings.php';
