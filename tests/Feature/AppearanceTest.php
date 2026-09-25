<?php

use App\Models\User;

test('pages default to system appearance and the purple theme', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('data-theme="purple"', false);
    $response->assertDontSee('class="dark"', false);
});

test('a saved dark appearance renders the page in dark mode', function () {
    $response = $this->withUnencryptedCookie('appearance', 'dark')->get(route('login'));

    $response->assertSee('class="dark"', false);
});

test('a saved accent theme is applied to the page', function () {
    $response = $this->withUnencryptedCookie('theme', 'teal')->get(route('login'));

    $response->assertSee('data-theme="teal"', false);
});

test('unknown appearance and theme values fall back to the defaults', function () {
    $response = $this
        ->withUnencryptedCookie('appearance', 'neon')
        ->withUnencryptedCookie('theme', '"><script>alert(1)</script>')
        ->get(route('login'));

    $response->assertSee('data-theme="purple"', false);
    $response->assertDontSee('class="dark"', false);
    $response->assertDontSee('<script>alert(1)</script>', false);
});

test('the collapsed sidebar preference set by the browser is remembered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withUnencryptedCookie('sidebar_open', 'false')
        ->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page->where('sidebarOpen', false));
});
