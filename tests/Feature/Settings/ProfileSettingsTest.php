<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('the settings page shows the user recent activity', function () {
    $user = User::factory()->create();
    AuditLog::create(['user_id' => $user->id, 'action' => 'dog.updated', 'description' => 'Updated dog Rex']);
    AuditLog::create(['user_id' => User::factory()->create()->id, 'action' => 'dog.updated', 'description' => 'Someone else']);

    $this->actingAs($user)
        ->get(route('settings.profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/profile')
            ->has('recentActivity', 1)
            ->where('recentActivity.0.description', 'Updated dog Rex')
            ->has('sessions'));
});

test('a user can update their name and email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.profile.update'), ['name' => 'New Name', 'email' => 'new@scas.ae'])
        ->assertRedirect(route('settings.profile.edit'))
        ->assertSessionHas('success', 'Profile updated.');

    expect($user->fresh())
        ->name->toBe('New Name')
        ->email->toBe('new@scas.ae');
});

test('the profile form no longer changes the password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('settings.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'Brand-new-pass1!',
        'password_confirmation' => 'Brand-new-pass1!',
    ]);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('a user can change their password with the correct current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('settings.password.update'), [
            'current_password' => 'password',
            'password' => 'Brand-new-pass1!',
            'password_confirmation' => 'Brand-new-pass1!',
        ])
        ->assertRedirect(route('settings.profile.edit'))
        ->assertSessionHasNoErrors();

    expect(Hash::check('Brand-new-pass1!', $user->fresh()->password))->toBeTrue();
    expect(AuditLog::query()->where('action', 'user.password_changed')->where('user_id', $user->id)->exists())->toBeTrue();
});

test('changing the password requires the correct current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('settings.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'Brand-new-pass1!',
            'password_confirmation' => 'Brand-new-pass1!',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('a user can log out their other sessions but keeps the current one', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    DB::table('sessions')->insert([
        ['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp],
        ['id' => 'someone-else', 'user_id' => $otherUser->id, 'payload' => '', 'last_activity' => now()->timestamp],
    ]);
    $originalRememberToken = $user->remember_token;

    $this->actingAs($user)
        ->delete(route('settings.sessions.destroy'), ['password' => 'password'])
        ->assertRedirect(route('settings.profile.edit'))
        ->assertSessionHas('success', 'Logged out of 1 other session.');

    expect(DB::table('sessions')->where('id', 'other-device')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'someone-else')->exists())->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($originalRememberToken);
});

test('logging out other sessions requires the correct password', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp]);

    $this->actingAs($user)
        ->delete(route('settings.sessions.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect(DB::table('sessions')->where('id', 'other-device')->exists())->toBeTrue();
});

test('a user can upload a profile photo that replaces the previous one', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.avatar.store'), ['avatar' => UploadedFile::fake()->image('first.jpg', 200, 200)])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Profile photo updated.');

    $firstPath = $user->fresh()->avatar_path;
    Storage::disk('local')->assertExists($firstPath);

    $this->actingAs($user)
        ->post(route('settings.avatar.store'), ['avatar' => UploadedFile::fake()->image('second.png', 200, 200)]);

    $user->refresh();
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($user->avatar_path);
    expect($user->avatar_url)->toStartWith("/users/{$user->id}/avatar");
});

test('profile photos must be images within the size limit', function (UploadedFile $file) {
    Storage::fake('local');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.avatar.store'), ['avatar' => $file])
        ->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar_path)->toBeNull();
})->with([
    'not an image' => fn () => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
    'too large' => fn () => UploadedFile::fake()->image('huge.jpg', 200, 200)->size(5000),
    'too small' => fn () => UploadedFile::fake()->image('tiny.jpg', 32, 32),
]);

test('a user can remove their profile photo', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('settings.avatar.store'), ['avatar' => UploadedFile::fake()->image('me.jpg', 200, 200)]);
    $path = $user->fresh()->avatar_path;

    $this->actingAs($user)
        ->delete(route('settings.avatar.destroy'))
        ->assertSessionHas('success', 'Profile photo removed.');

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

test('profile photos are served only to signed-in users', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('settings.avatar.store'), ['avatar' => UploadedFile::fake()->image('me.jpg', 200, 200)]);
    $viewer = User::factory()->create();

    $this->actingAs($viewer)->get(route('users.avatar', $user))->assertOk();
    $this->actingAs($viewer)->get(route('users.avatar', $viewer))->assertNotFound();

    auth()->logout();
    $this->get(route('users.avatar', $user))->assertRedirect(route('login'));
});
