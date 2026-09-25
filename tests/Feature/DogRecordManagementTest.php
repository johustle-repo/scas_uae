<?php

use App\Enums\DogStatus;
use App\Models\Dog;
use App\Models\DogFosterRecord;
use App\Models\DogVaccination;
use App\Models\User;

test('archived dogs are hidden from the list and dashboard until restored', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create(['name' => 'Oldie']);
    Dog::factory()->create();

    $this->actingAs($user)->patch(route('dogs.archive', $dog))
        ->assertRedirect(route('dogs.show', $dog));

    expect($dog->fresh()->archived_at)->not->toBeNull();

    $this->actingAs($user)->get(route('dogs.index'))
        ->assertInertia(fn ($page) => $page->has('dogs.data', 1));
    $this->actingAs($user)->get(route('dogs.index', ['archived' => 'only']))
        ->assertInertia(fn ($page) => $page->has('dogs.data', 1)->where('dogs.data.0.name', 'Oldie'));
    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.total', 1));

    $this->actingAs($user)->patch(route('dogs.restore', $dog));

    expect($dog->fresh()->archived_at)->toBeNull();
});

test('a treatment record can be edited and deleted', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();
    $vaccination = DogVaccination::factory()->for($dog)->create(['vaccine_details' => 'Rabies']);

    $this->actingAs($user)->put(route('dogs.vaccinations.update', [$dog, $vaccination]), [
        'vaccine_details' => 'Rabies booster',
        'administered_date' => '2026-01-10',
    ])->assertRedirect(route('dogs.show', ['dog' => $dog, 'tab' => 'medical']));

    expect($vaccination->fresh()->vaccine_details)->toBe('Rabies booster');

    $this->actingAs($user)->delete(route('dogs.vaccinations.destroy', [$dog, $vaccination]));

    expect(DogVaccination::find($vaccination->id))->toBeNull();
});

test('a treatment belonging to another dog cannot be edited through this dog', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();
    $otherVaccination = DogVaccination::factory()->create();

    $this->actingAs($user)
        ->put(route('dogs.vaccinations.update', [$dog, $otherVaccination]), ['vaccine_details' => 'Hijacked'])
        ->assertNotFound();
});

test('closing a foster placement returns the dog to SCAS', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->inLocalFoster()->create();
    $foster = DogFosterRecord::factory()->for($dog)->create([
        'foster_type' => 'local',
        'start_date' => '2026-01-01',
        'end_date' => null,
    ]);

    $this->actingAs($user)->patch(route('dogs.foster-records.close', [$dog, $foster]), [
        'end_date' => '2026-03-01',
    ])->assertRedirect(route('dogs.show', ['dog' => $dog, 'tab' => 'foster-history']));

    expect($foster->fresh()->end_date->toDateString())->toBe('2026-03-01')
        ->and($dog->fresh()->current_status)->toBe(DogStatus::AtScas);
});

test('a foster placement cannot end before it started', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();
    $foster = DogFosterRecord::factory()->for($dog)->create(['start_date' => '2026-02-01', 'end_date' => null]);

    $this->actingAs($user)->patch(route('dogs.foster-records.close', [$dog, $foster]), [
        'end_date' => '2026-01-01',
    ])->assertSessionHasErrors('end_date');
});

test('recording a return on an adoption marks the dog for rehoming', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.adoption-records.store', $dog), [
        'adoption_type' => 'local',
        'adopter_name' => 'Smith Family',
        'adoption_date' => '2026-01-15',
    ]);

    expect($dog->fresh()->current_status)->toBe(DogStatus::AdoptedUae);

    $adoption = $dog->adoptionRecords()->sole();

    $this->actingAs($user)->put(route('dogs.adoption-records.update', [$dog, $adoption]), [
        'adoption_type' => 'local',
        'adopter_name' => 'Smith Family',
        'adoption_date' => '2026-01-15',
        'return_date' => '2026-04-01',
        'return_reason' => 'Moving abroad',
    ]);

    expect($dog->fresh()->current_status)->toBe(DogStatus::ReturnedRehoming);
});
