<?php

use App\Models\AuditLog;
use App\Models\Dog;
use App\Models\User;
use App\Services\AuditLogger;

test('creating and updating a dog is recorded with the changed values', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $dog = Dog::factory()->create(['name' => 'Tessa', 'current_location' => 'Dubai']);
    $dog->update(['current_location' => 'Abu Dhabi']);

    $created = AuditLog::query()->where('action', 'dog.created')->sole();
    $updated = AuditLog::query()->where('action', 'dog.updated')->sole();

    expect($created)
        ->user_id->toBe($user->id)
        ->dog_id->toBe($dog->id)
        ->description->toBe("Created dog Tessa ({$dog->scas_id})");

    expect($updated)
        ->old_values->toBe(['current_location' => 'Dubai'])
        ->new_values->toBe(['current_location' => 'Abu Dhabi']);
});

test('changes to related records appear in the dog\'s history', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create();

    $this->actingAs($user)->post(route('dogs.vaccinations.store', $dog), ['vaccine_details' => 'Rabies']);

    $log = AuditLog::query()->where('action', 'vaccination.created')->sole();

    expect($log->dog_id)->toBe($dog->id)
        ->and($log->description)->toContain($dog->name);

    $this->actingAs($user)->get(route('dogs.show', $dog))
        ->assertInertia(fn ($page) => $page
            ->missing('history')
            ->loadDeferredProps(fn ($reload) => $reload->has('history', 2)));
});

test('logins and logouts are recorded', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('logout'));

    expect(AuditLog::query()->where('user_id', $user->id)->pluck('action')->all())
        ->toContain('auth.login', 'auth.logout');
});

test('passwords are never written to the audit log', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->put(route('settings.users.password', $user), [
        'password' => 'secret-password-1',
        'password_confirmation' => 'secret-password-1',
    ]);

    $log = AuditLog::query()->where('action', 'user.updated')->latest('id')->first();

    expect($log->new_values)->toBe(['password' => '(changed)'])
        ->and(json_encode($log->old_values))->not->toContain('$2y$');
});

test('auditing can be paused for bulk operations', function () {
    AuditLogger::withoutAuditing(fn () => Dog::factory()->count(3)->create());

    expect(AuditLog::query()->count())->toBe(0);
});

test('administrators can browse and filter the audit log', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    Dog::factory()->create();
    AuditLogger::record('auth.login', 'Someone logged in');

    // Creating the admin, the dog and the login entry: three entries in total.
    $this->get(route('settings.logs.index'))
        ->assertInertia(fn ($page) => $page
            ->component('settings/logs/index')
            ->has('logs.data', 3));

    $this->get(route('settings.logs.index', ['area' => 'dog']))
        ->assertInertia(fn ($page) => $page->has('logs.data', 1)->where('logs.data.0.action', 'dog.created'));

    $this->get(route('settings.logs.index', ['area' => 'auth']))
        ->assertInertia(fn ($page) => $page->has('logs.data', 1)->where('logs.data.0.action', 'auth.login'));
});
