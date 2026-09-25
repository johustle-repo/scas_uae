<?php

use App\Http\Controllers\DogController;
use App\Http\Controllers\Dogs\AdoptionRecordController;
use App\Http\Controllers\Dogs\ArchiveController;
use App\Http\Controllers\Dogs\DocumentController;
use App\Http\Controllers\Dogs\FosterRecordController;
use App\Http\Controllers\Dogs\IdentificationController;
use App\Http\Controllers\Dogs\MedicalProfileController;
use App\Http\Controllers\Dogs\PhotoController;
use App\Http\Controllers\Dogs\RescueIntakeController;
use App\Http\Controllers\Dogs\VaccinationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('dogs', [DogController::class, 'index'])->name('dogs.index');

    Route::middleware('can:manage-dogs')->group(function (): void {
        Route::get('dogs/create', [DogController::class, 'create'])->name('dogs.create');
        Route::post('dogs', [DogController::class, 'store'])->name('dogs.store');
        Route::get('dogs/{dog}/edit', [DogController::class, 'edit'])->name('dogs.edit');
        Route::put('dogs/{dog}', [DogController::class, 'update'])->name('dogs.update');
        Route::patch('dogs/{dog}/identification', [IdentificationController::class, 'update'])->name('dogs.identification.update');
        Route::patch('dogs/{dog}/rescue-intake', [RescueIntakeController::class, 'update'])->name('dogs.rescue-intake.update');
        Route::patch('dogs/{dog}/archive', [ArchiveController::class, 'store'])->name('dogs.archive');
        Route::patch('dogs/{dog}/restore', [ArchiveController::class, 'destroy'])->name('dogs.restore');
    });

    Route::get('dogs/{dog}', [DogController::class, 'show'])->name('dogs.show');
    Route::get('dogs/{dog}/photo', [PhotoController::class, 'profile'])->name('dogs.photo');

    Route::scopeBindings()->group(function (): void {
        Route::middleware('can:manage-medical')->group(function (): void {
            Route::patch('dogs/{dog}/medical', [MedicalProfileController::class, 'update'])->name('dogs.medical.update');
            Route::post('dogs/{dog}/vaccinations', [VaccinationController::class, 'store'])->name('dogs.vaccinations.store');
            Route::put('dogs/{dog}/vaccinations/{vaccination}', [VaccinationController::class, 'update'])->name('dogs.vaccinations.update');
            Route::delete('dogs/{dog}/vaccinations/{vaccination}', [VaccinationController::class, 'destroy'])->name('dogs.vaccinations.destroy');
        });

        Route::middleware('can:manage-placements')->group(function (): void {
            Route::post('dogs/{dog}/foster-records', [FosterRecordController::class, 'store'])->name('dogs.foster-records.store');
            Route::put('dogs/{dog}/foster-records/{fosterRecord}', [FosterRecordController::class, 'update'])->name('dogs.foster-records.update');
            Route::patch('dogs/{dog}/foster-records/{fosterRecord}/close', [FosterRecordController::class, 'close'])->name('dogs.foster-records.close');
            Route::post('dogs/{dog}/adoption-records', [AdoptionRecordController::class, 'store'])->name('dogs.adoption-records.store');
            Route::put('dogs/{dog}/adoption-records/{adoptionRecord}', [AdoptionRecordController::class, 'update'])->name('dogs.adoption-records.update');
        });

        Route::get('dogs/{dog}/documents/{document}', [DocumentController::class, 'show'])->name('dogs.documents.show');
        Route::get('dogs/{dog}/documents/{document}/download', [DocumentController::class, 'download'])->name('dogs.documents.download');
        Route::get('dogs/{dog}/photos/{photo}', [PhotoController::class, 'show'])->name('dogs.photos.show');

        Route::middleware('can:manage-documents')->group(function (): void {
            Route::post('dogs/{dog}/documents', [DocumentController::class, 'store'])->name('dogs.documents.store');
            Route::post('dogs/{dog}/documents/{document}/replace', [DocumentController::class, 'replace'])->name('dogs.documents.replace');
            Route::delete('dogs/{dog}/documents/{document}', [DocumentController::class, 'destroy'])->name('dogs.documents.destroy');

            Route::post('dogs/{dog}/photos', [PhotoController::class, 'store'])->name('dogs.photos.store');
            Route::patch('dogs/{dog}/photos/{photo}/profile', [PhotoController::class, 'makeProfile'])->name('dogs.photos.profile');
            Route::delete('dogs/{dog}/photos/{photo}', [PhotoController::class, 'destroy'])->name('dogs.photos.destroy');
        });
    });
});
