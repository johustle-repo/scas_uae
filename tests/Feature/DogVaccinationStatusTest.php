<?php

use App\Models\Dog;
use App\Models\DogVaccination;

test('a dose superseded by a later booster does not make the dog overdue', function () {
    $dog = Dog::factory()->create();
    DogVaccination::factory()->for($dog)->create([
        'vaccine_details' => 'Nobivac Rabies',
        'administered_date' => now()->subYears(2),
        'next_due_date' => now()->subYear(),
    ]);
    DogVaccination::factory()->for($dog)->create([
        'vaccine_details' => ' nobivac  rabies ',
        'administered_date' => now()->subYear(),
        'next_due_date' => now()->addMonth(),
    ]);

    expect($dog->vaccinations_up_to_date)->toBeTrue()
        ->and($dog->next_vaccination_date->toDateString())->toBe(now()->addMonth()->toDateString());
});

test('a dog is overdue when the latest dose of any vaccine is past due', function () {
    $dog = Dog::factory()->create();
    DogVaccination::factory()->for($dog)->create([
        'vaccine_details' => 'Nobivac Rabies',
        'administered_date' => now()->subMonth(),
        'next_due_date' => now()->addYear(),
    ]);
    DogVaccination::factory()->for($dog)->create([
        'vaccine_details' => 'Nobivac DHPPi',
        'administered_date' => now()->subYears(2),
        'next_due_date' => now()->subYear(),
    ]);

    expect($dog->vaccinations_up_to_date)->toBeFalse()
        ->and($dog->next_vaccination_date->toDateString())->toBe(now()->addYear()->toDateString());
});

test('a dose due today is not yet overdue', function () {
    $dog = Dog::factory()->create();
    DogVaccination::factory()->for($dog)->create([
        'vaccine_details' => 'Nobivac Rabies',
        'administered_date' => now()->subYear(),
        'next_due_date' => today(),
    ]);

    expect($dog->vaccinations_up_to_date)->toBeTrue()
        ->and($dog->next_vaccination_date->toDateString())->toBe(today()->toDateString());
});
