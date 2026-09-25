<?php

use App\Enums\AdoptionApplicationStatus;
use App\Enums\UserRole;
use App\Models\AdoptionApplication;
use App\Models\Dog;
use App\Models\User;

/**
 * @return array<string, mixed>
 */
function adoptionPayload(Dog $dog, array $overrides = []): array
{
    return [
        'dog_id' => $dog->id,
        'full_name' => 'Layla Hassan',
        'email' => 'layla@example.com',
        'phone' => '+971 50 123 4567',
        'city' => 'Dubai',
        'home_type' => 'villa',
        'has_garden' => '1',
        'other_pets' => 'One cat',
        'message' => 'We have a big garden and work from home, so she would have company all day.',
        'agree' => '1',
        ...$overrides,
    ];
}

test('a visitor can apply to adopt a listed dog', function () {
    $dog = Dog::factory()->atScas()->create();

    $this->post(route('adopt.applications.store'), adoptionPayload($dog))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $application = AdoptionApplication::query()->sole();
    expect($application)
        ->dog_id->toBe($dog->id)
        ->full_name->toBe('Layla Hassan')
        ->has_garden->toBeTrue()
        ->has_children->toBeFalse()
        ->status->toBe(AdoptionApplicationStatus::New);
});

test('applications for dogs that are not up for adoption are rejected', function () {
    $dog = Dog::factory()->adoptedInUae()->create();

    $this->post(route('adopt.applications.store'), adoptionPayload($dog))
        ->assertSessionHasErrors('dog_id');

    expect(AdoptionApplication::count())->toBe(0);
});

test('an application needs contact details, consent and a message', function () {
    $dog = Dog::factory()->atScas()->create();

    $this->post(route('adopt.applications.store'), adoptionPayload($dog, [
        'email' => 'not-an-email',
        'message' => 'Too short',
        'agree' => null,
    ]))->assertSessionHasErrors(['email', 'message', 'agree']);
});

test('submissions that fill the hidden honeypot field are rejected', function () {
    $dog = Dog::factory()->atScas()->create();

    $this->post(route('adopt.applications.store'), adoptionPayload($dog, ['website' => 'http://spam.example']))
        ->assertSessionHasErrors('website');

    expect(AdoptionApplication::count())->toBe(0);
});

test('placement staff can review adoption requests', function () {
    $staff = User::factory()->role(UserRole::PlacementCoordinator)->create();
    AdoptionApplication::factory()->count(2)->create();

    $this->actingAs($staff)
        ->get(route('adoption-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('adoption-requests/index')
            ->has('requests.data', 2)
            ->where('statusCounts.new', 2)
            ->where('newAdoptionRequests', 2));
});

test('staff without placement access cannot see adoption requests', function () {
    $this->actingAs(User::factory()->readOnly()->create())
        ->get(route('adoption-requests.index'))
        ->assertForbidden();
});

test('changing the status records who reviewed the request', function () {
    $staff = User::factory()->role(UserRole::PlacementCoordinator)->create();
    $application = AdoptionApplication::factory()->create();

    $this->actingAs($staff)
        ->patch(route('adoption-requests.update', $application), [
            'status' => 'contacted',
            'staff_notes' => 'Called, home visit on Saturday.',
        ])
        ->assertSessionHasNoErrors();

    $application->refresh();
    expect($application)
        ->status->toBe(AdoptionApplicationStatus::Contacted)
        ->staff_notes->toBe('Called, home visit on Saturday.')
        ->reviewed_by->toBe($staff->id)
        ->reviewed_at->not->toBeNull();
});
