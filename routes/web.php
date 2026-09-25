<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicDogPhotoController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');
Route::get('adopt/dogs/{dog}/photo', PublicDogPhotoController::class)->name('adopt.photo');

Route::middleware('auth')->get('/dashboard', DashboardController::class)->name('dashboard');

require __DIR__.'/auth.php';
require __DIR__.'/dogs.php';
require __DIR__.'/settings.php';
