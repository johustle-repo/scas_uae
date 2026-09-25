<?php

use App\Models\Dog;
use App\Models\DogFosterRecord;
use App\Models\DogIdentification;
use App\Models\DogMedicalProfile;
use App\Models\DogVaccination;
use App\Models\User;

test('a dog profile renders with all of its related records', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();
    DogMedicalProfile::factory()->for($dog)->create();
    DogIdentification::factory()->for($dog)->create(['microchip_number' => '900000000000123']);
    DogVaccination::factory()->for($dog)->create(['vaccine_details' => 'Nobivac Rabies']);
    DogFosterRecord::factory()->for($dog)->create(['foster_family_name' => 'Jones Family']);

    $response = $this->actingAs($user)->get(route('dogs.show', $dog));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dogs/show')
        ->where('dog.id', $dog->id)
        ->where('dog.medical_profile.id', $dog->medicalProfile->id)
        ->where('dog.identification.microchip_number', '900000000000123')
        ->has('dog.vaccinations', 1)
        ->has('dog.foster_records', 1));
});

test('viewing a non-existent dog returns a 404', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dogs/999999');

    $response->assertNotFound();
});
