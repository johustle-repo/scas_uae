<?php

use App\Enums\DogStatus;
use App\Enums\Gender;
use App\Enums\Size;
use App\Models\Dog;
use App\Models\User;

test('authenticated users can view the dog list', function () {
    $user = User::factory()->create();
    Dog::factory()->count(3)->create();

    $response = $this->actingAs($user)->get(route('dogs.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dogs/index')
        ->has('dogs.data', 3));
});

test('dogs can be filtered by current status', function () {
    $user = User::factory()->create();
    Dog::factory()->atScas()->count(2)->create();
    Dog::factory()->inLocalFoster()->count(3)->create();

    $response = $this->actingAs($user)->get(route('dogs.index', ['status' => DogStatus::LocalFoster->value]));

    $response->assertInertia(fn ($page) => $page->has('dogs.data', 3));
});

test('dogs can be filtered by gender', function () {
    $user = User::factory()->create();
    Dog::factory()->count(2)->create(['gender' => Gender::Male]);
    Dog::factory()->count(3)->create(['gender' => Gender::Female]);

    $response = $this->actingAs($user)->get(route('dogs.index', ['gender' => Gender::Female->value]));

    $response->assertInertia(fn ($page) => $page->has('dogs.data', 3));
});

test('dogs can be filtered by size', function () {
    $user = User::factory()->create();
    Dog::factory()->count(2)->create(['size' => Size::Small]);
    Dog::factory()->count(1)->create(['size' => Size::Giant]);

    $response = $this->actingAs($user)->get(route('dogs.index', ['size' => Size::Giant->value]));

    $response->assertInertia(fn ($page) => $page->has('dogs.data', 1));
});

test('dogs can be searched by name', function () {
    $user = User::factory()->create();
    Dog::factory()->create(['name' => 'Rex']);
    Dog::factory()->create(['name' => 'Bella']);

    $response = $this->actingAs($user)->get(route('dogs.index', ['q' => 'Rex']));

    $response->assertInertia(fn ($page) => $page
        ->has('dogs.data', 1)
        ->where('dogs.data.0.name', 'Rex'));
});

test('the dog list is paginated', function () {
    $user = User::factory()->create();
    Dog::factory()->count(20)->create();

    $response = $this->actingAs($user)->get(route('dogs.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('dogs.data', 15)
        ->where('dogs.total', 20));
});

test('dogs can be filtered by several statuses at once', function () {
    $user = User::factory()->create();
    Dog::factory()->atScas()->count(2)->create();
    Dog::factory()->inLocalFoster()->count(3)->create();
    Dog::factory()->count(1)->create(['current_status' => DogStatus::Memorial]);

    $response = $this->actingAs($user)->get(route('dogs.index', [
        'status' => [DogStatus::AtScas->value, DogStatus::LocalFoster->value],
    ]));

    $response->assertInertia(fn ($page) => $page->has('dogs.data', 5));
});

test('unknown status filters are ignored', function () {
    $user = User::factory()->create();
    Dog::factory()->count(2)->create();

    $response = $this->actingAs($user)->get(route('dogs.index', ['status' => 'not-a-status']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('dogs.data', 2));
});

test('dogs can be filtered by several genders and sizes', function () {
    $user = User::factory()->create();
    Dog::factory()->create(['gender' => Gender::Male, 'size' => Size::Small]);
    Dog::factory()->create(['gender' => Gender::Female, 'size' => Size::Medium]);
    Dog::factory()->create(['gender' => Gender::Female, 'size' => Size::Giant]);

    $response = $this->actingAs($user)->get(route('dogs.index', [
        'gender' => [Gender::Male->value, Gender::Female->value],
        'size' => [Size::Small->value, Size::Medium->value],
    ]));

    $response->assertInertia(fn ($page) => $page->has('dogs.data', 2));
});

test('dogs can be filtered by age range', function () {
    $user = User::factory()->create();
    Dog::factory()->create(['name' => 'Puppy', 'date_of_birth' => now()->subMonths(6)]);
    Dog::factory()->create(['name' => 'Adult', 'date_of_birth' => now()->subYears(3)->subMonth()]);
    Dog::factory()->create(['name' => 'Senior', 'date_of_birth' => now()->subYears(10)]);

    $response = $this->actingAs($user)->get(route('dogs.index', ['age_from' => 2, 'age_to' => 5]));

    $response->assertInertia(fn ($page) => $page
        ->has('dogs.data', 1)
        ->where('dogs.data.0.name', 'Adult'));
});

test('dogs needing review can be listed', function () {
    $user = User::factory()->create();
    $complete = Dog::factory()->create(['name' => 'Complete']);
    $complete->identification()->create(['microchip_number' => '985000000000001']);
    Dog::factory()->create(['name' => 'No Breed', 'breed' => null]);
    Dog::factory()->create(['name' => 'No Chip']);

    $response = $this->actingAs($user)->get(route('dogs.index', ['needs_review' => 1]));

    $response->assertInertia(fn ($page) => $page
        ->has('dogs.data', 2)
        ->where('filters.needs_review', '1'));
});
