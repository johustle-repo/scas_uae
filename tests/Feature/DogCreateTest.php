<?php

use App\Enums\DogStatus;
use App\Enums\PhotoKind;
use App\Models\Dog;
use App\Models\DogIdentification;
use App\Models\DogMedicalProfile;
use App\Models\DogRescueIntake;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the create dog form can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dogs.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('dogs/create'));
});

test('a dog can be created with an auto-generated scas id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('dogs.store'), [
        'name' => 'Rex',
        'species' => 'canine',
        'breed' => 'German Shepherd',
        'gender' => 'male',
        'size' => 'large',
    ]);

    $dog = Dog::sole();

    $response->assertRedirect(route('dogs.show', $dog));
    expect($dog->scas_id)->toBe('SCAS-001');
    expect($dog->name)->toBe('Rex');
});

test('creating a dog requires the core fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('dogs.store'), []);

    $response->assertSessionHasErrors(['name', 'species', 'breed', 'gender', 'size']);
    expect(Dog::count())->toBe(0);
});

test('creating a dog with nested details creates the related records', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dogs.store'), [
        'name' => 'Luna',
        'species' => 'canine',
        'breed' => 'Saluki Mix',
        'gender' => 'female',
        'size' => 'medium',
        'rescue_intake' => [
            'rescue_location' => 'Sharjah Industrial Area',
            'initial_condition' => 'Underweight, dehydrated',
        ],
        'medical' => [
            'spayed_neutered' => true,
            'veterinary_clinic' => 'Al Ain Vet Clinic',
        ],
        'identification' => [
            'microchip_number' => '900000000000000',
        ],
    ]);

    $dog = Dog::sole();

    expect(DogRescueIntake::where('dog_id', $dog->id)->exists())->toBeTrue();
    expect(DogMedicalProfile::where('dog_id', $dog->id)->exists())->toBeTrue();
    expect(DogIdentification::where('dog_id', $dog->id)->where('microchip_number', '900000000000000')->exists())->toBeTrue();
});

test('a new dog can be registered with a profile photo and starting placement', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dogs.store'), [
        'name' => 'Nala',
        'species' => 'canine',
        'breed' => 'Saluki Mix',
        'gender' => 'female',
        'size' => 'medium',
        'current_status' => DogStatus::LocalFoster->value,
        'current_location' => 'Dubai',
        'photo' => UploadedFile::fake()->image('nala.jpg'),
    ])->assertSessionHasNoErrors();

    $dog = Dog::sole();

    expect($dog->current_status)->toBe(DogStatus::LocalFoster)
        ->and($dog->current_location)->toBe('Dubai')
        ->and($dog->photos()->sole()->kind)->toBe(PhotoKind::Profile)
        ->and($dog->photo_path)->toBe($dog->photos()->sole()->file_path);
    Storage::disk('local')->assertExists($dog->photo_path);
});

test('new dogs default to being at SCAS', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dogs.store'), [
        'name' => 'Rex',
        'species' => 'canine',
        'breed' => 'Husky',
        'gender' => 'male',
        'size' => 'large',
    ]);

    expect(Dog::sole()->current_status)->toBe(DogStatus::AtScas);
});

test('a date of birth in the future is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dogs.store'), [
        'name' => 'Rex',
        'species' => 'canine',
        'breed' => 'Husky',
        'gender' => 'male',
        'size' => 'large',
        'date_of_birth' => now()->addYear()->toDateString(),
    ])->assertSessionHasErrors('date_of_birth');
});

test('a photo path cannot be set directly through the form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dogs.store'), [
        'name' => 'Rex',
        'species' => 'canine',
        'breed' => 'Husky',
        'gender' => 'male',
        'size' => 'large',
        'photo_path' => 'dog-documents/1/secret.pdf',
    ]);

    expect(Dog::sole()->photo_path)->toBeNull();
});
