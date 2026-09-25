<?php

use App\Enums\UserRole;
use App\Models\Dog;
use App\Models\User;

test('read-only users can view dogs but not change them', function () {
    $user = User::factory()->readOnly()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->get(route('dogs.index'))->assertOk();
    $this->actingAs($user)->get(route('dogs.show', $dog))->assertOk();

    $this->actingAs($user)->get(route('dogs.create'))->assertForbidden();
    $this->actingAs($user)->put(route('dogs.update', $dog), ['name' => 'Changed'])->assertForbidden();
    $this->actingAs($user)->patch(route('dogs.medical.update', $dog), ['medications' => 'None'])->assertForbidden();
    $this->actingAs($user)->post(route('dogs.foster-records.store', $dog), [])->assertForbidden();
    $this->actingAs($user)->patch(route('dogs.archive', $dog))->assertForbidden();
});

test('medical staff can manage medical records but not placements or profiles', function () {
    $user = User::factory()->role(UserRole::MedicalStaff)->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)
        ->post(route('dogs.vaccinations.store', $dog), ['vaccine_details' => 'Rabies'])
        ->assertRedirect();

    expect($dog->vaccinations()->count())->toBe(1);

    $this->actingAs($user)->post(route('dogs.foster-records.store', $dog), [])->assertForbidden();
    $this->actingAs($user)->get(route('dogs.edit', $dog))->assertForbidden();
});

test('placement coordinators can manage placements but not medical records', function () {
    $user = User::factory()->role(UserRole::PlacementCoordinator)->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.foster-records.store', $dog), [
        'foster_type' => 'local',
        'foster_family_name' => 'Jones Family',
    ])->assertRedirect();

    expect($dog->fosterRecords()->count())->toBe(1);

    $this->actingAs($user)->patch(route('dogs.medical.update', $dog), ['medications' => 'None'])->assertForbidden();
});

test('only administrators can manage users and view the audit log', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();

    $status = $allowed ? 200 : 403;

    $this->actingAs($user)->get(route('settings.users.index'))->assertStatus($status);
    $this->actingAs($user)->get(route('settings.logs.index'))->assertStatus($status);
})->with([
    'administrator' => [UserRole::Admin, true],
    'records manager' => [UserRole::RecordsManager, false],
    'medical staff' => [UserRole::MedicalStaff, false],
    'placement coordinator' => [UserRole::PlacementCoordinator, false],
    'read-only' => [UserRole::ReadOnly, false],
]);

test('the signed-in user\'s permissions are shared with the frontend', function () {
    $user = User::factory()->role(UserRole::MedicalStaff)->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.permissions', ['manage-medical', 'manage-documents'])
            ->where('auth.roleLabel', 'Medical / Veterinary Staff'));
});
