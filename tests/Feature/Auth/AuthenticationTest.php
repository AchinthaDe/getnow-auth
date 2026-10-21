<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users can authenticate with their username', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->username,
        'password' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

test('a username cannot authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->username,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('authenticated users are redirected away from login', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('login rejects malformed credentials without a server error', function (string $field) {
    $credentials = ['email' => 'unknown@example.com', 'password' => 'password'];
    $credentials[$field] = ['invalid'];

    $this->post(route('login.store'), $credentials)->assertSessionHasErrors($field);
    $this->assertGuest();
})->with(['email', 'password']);

test('login throttles repeated authentication attempts', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), [
            'email' => 'unknown@example.com', 'password' => 'incorrect',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), [
        'email' => 'unknown@example.com', 'password' => 'incorrect',
    ])->assertTooManyRequests();
    $this->assertGuest();
});
