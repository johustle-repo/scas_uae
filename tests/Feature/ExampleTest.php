<?php

use App\Enums\DogStatus;
use App\Enums\ImportFlagType;
use App\Models\Dog;
use App\Models\DogIdentification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests see the adoption landing page with adoptable dogs only', function () {
    $available = Dog::factory()->atScas()->create(['name' => 'Biscuit']);
    Dog::factory()->create(['name' => 'Rehome', 'current_status' => DogStatus::ReturnedRehoming]);
    Dog::factory()->inLocalFoster()->create(['name' => 'Fostered']);
    Dog::factory()->adoptedInUae()->create(['name' => 'Adopted']);
    Dog::factory()->atScas()->create(['name' => 'Archived', 'archived_at' => now()]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->has('adoptableDogs', 2)
            ->where('stats.available', 2)
            ->where('stats.adopted', 1)
            ->where('stats.inFoster', 1)
            ->where('auth.user', null));
});

test('the landing page never exposes private dog details', function () {
    $dog = Dog::factory()->atScas()->create();
    DogIdentification::factory()->for($dog)->create(['microchip_number' => '900000000000999']);

    $response = $this->get(route('home'))->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->has('adoptableDogs.0', fn ($publicDog) => $publicDog
            ->where('id', $dog->id)
            ->missing('scas_id')
            ->missing('current_location')
            ->missing('identification')
            ->missing('medical_profile')
            ->etc()));
    expect($response->getContent())->not->toContain('900000000000999');
});

test('signed-in users also see the landing page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('welcome')
            ->where('auth.user.id', $user->id));
});

test('photos of listed dogs are public but other dog photos are not', function () {
    Storage::fake('local');
    $path = UploadedFile::fake()->image('dog.jpg')->store('dog-photos', 'local');
    $adoptable = Dog::factory()->atScas()->create(['photo_path' => $path]);
    $fostered = Dog::factory()->inLocalFoster()->create(['photo_path' => $path]);

    $this->get(route('adopt.photo', $adoptable))->assertOk();
    $this->get(route('adopt.photo', $fostered))->assertNotFound();
});

test('imported dogs with an unconfirmed placement are not advertised', function () {
    $flagged = Dog::factory()->atScas()->create();
    $flagged->importFlags()->create([
        'flag_type' => ImportFlagType::PossiblePlacementUnconfirmed,
        'message' => 'Contact details listed in legacy sheet.',
    ]);
    Dog::factory()->atScas()->create();

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('adoptableDogs', 1)
            ->where('stats.available', 1));
});
