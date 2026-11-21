<?php

use App\Models\Dog;
use App\Models\User;

test('users can choose to show archived records', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.preferences.update'), ['show_archived_records' => true])
        ->assertSessionHas('success', 'Preferences saved.');

    expect($user->refresh()->show_archived_records)->toBeTrue();
});

test('the preference must be a yes or no value', function () {
    $this->actingAs(User::factory()->create())
        ->patch(route('settings.preferences.update'), ['show_archived_records' => 'sometimes'])
        ->assertSessionHasErrors('show_archived_records');
});

test('the all dogs list includes archived dogs only for users who chose to see them', function (bool $showArchived, int $expected) {
    Dog::factory()->create();
    Dog::factory()->create(['archived_at' => now()]);
    $user = User::factory()->create(['show_archived_records' => $showArchived]);

    $this->actingAs($user)
        ->get(route('dogs.index'))
        ->assertInertia(fn ($page) => $page->has('dogs.data', $expected));
})->with([
    'preference off' => [false, 1],
    'preference on' => [true, 2],
]);
