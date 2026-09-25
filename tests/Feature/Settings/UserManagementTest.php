<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an admin can view the staff user list', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('settings.users.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('settings/users/index'));
});

test('an admin can create a new staff user', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('settings.users.store'), [
        'name' => 'New Staffer',
        'email' => 'staffer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'records_manager',
    ]);

    $response->assertRedirect(route('settings.users.index'));
    $this->assertDatabaseHas('users', ['email' => 'staffer@example.com']);
});

test('a non-admin cannot view the staff user list', function () {
    $staff = User::factory()->create();

    $response = $this->actingAs($staff)->get(route('settings.users.index'));

    $response->assertForbidden();
});

test('a non-admin cannot create a staff user', function () {
    $staff = User::factory()->create();

    $response = $this->actingAs($staff)->post(route('settings.users.store'), [
        'name' => 'New Staffer',
        'email' => 'staffer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'records_manager',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'staffer@example.com']);
});

test('an unauthenticated user is redirected to login for the staff user list', function () {
    $response = $this->get(route('settings.users.index'));

    $response->assertRedirect(route('login'));
});

test('an unauthenticated user is redirected to login when creating a staff user', function () {
    $response = $this->post(route('settings.users.store'), [
        'name' => 'New Staffer',
        'email' => 'staffer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'records_manager',
    ]);

    $response->assertRedirect(route('login'));
});

test('an admin can change a user\'s details and role', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->put(route('settings.users.update', $user), [
        'name' => 'Renamed User',
        'email' => 'renamed@example.com',
        'role' => UserRole::MedicalStaff->value,
    ]);

    $response->assertRedirect(route('settings.users.index'));
    expect($user->fresh())
        ->name->toBe('Renamed User')
        ->role->toBe(UserRole::MedicalStaff);
});

test('an admin cannot remove their own administrator role', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('settings.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => UserRole::ReadOnly->value,
    ]);

    $response->assertSessionHasErrors('role');
    expect($admin->fresh()->role)->toBe(UserRole::Admin);
});

test('an admin can deactivate and reactivate a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->patch(route('settings.users.status', $user), ['is_active' => false]);
    expect($user->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch(route('settings.users.status', $user), ['is_active' => true]);
    expect($user->fresh()->is_active)->toBeTrue();
});

test('an admin cannot deactivate their own account', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->patch(route('settings.users.status', $admin), ['is_active' => false]);

    $response->assertSessionHasErrors('is_active');
    expect($admin->fresh()->is_active)->toBeTrue();
});

test('only active administrators count towards keeping an administrator', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->inactive()->create();
    User::factory()->create();

    expect(User::otherActiveAdminsExist($admin))->toBeFalse();

    User::factory()->admin()->create();

    expect(User::otherActiveAdminsExist($admin))->toBeTrue();
});

test('an admin can reset a user\'s password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin)->put(route('settings.users.password', $user), [
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect(route('settings.users.index'));
    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('a deactivated user cannot log in', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('a user deactivated during their session is signed out', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('user management changes are recorded in the audit log', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->patch(route('settings.users.status', $user), ['is_active' => false]);

    $log = AuditLog::query()->where('action', 'user.updated')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->user_id->toBe($admin->id)
        ->old_values->toBe(['is_active' => true])
        ->new_values->toBe(['is_active' => false]);
});
