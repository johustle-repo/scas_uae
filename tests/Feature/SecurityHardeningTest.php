<?php

use App\Models\User;

test('web responses carry browser security headers', function () {
    $response = $this->get(route('login'));

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Content-Security-Policy', "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    $response->assertHeaderMissing('Strict-Transport-Security');
});

test('https responses enable strict transport security', function () {
    $this->get('https://localhost/login')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('failed sign-ins are recorded in the audit log', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'auth.failed',
        'user_id' => $user->id,
        'description' => "Failed sign-in for \"{$user->email}\"",
    ]);
});

test('repeated failed sign-ins lock the account temporarily and are logged', function () {
    $user = User::factory()->create();

    foreach (range(1, 6) as $attempt) {
        $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseHas('audit_logs', ['action' => 'auth.lockout']);
});
