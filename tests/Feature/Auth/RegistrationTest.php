<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'username' => 'johndoe',
        'email' => 'test@example.com',
        'address' => '42 Main Street, Colombo 03',
        'mobile_phone' => '+94771234567',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

/**
 * @param  array<string, string|array<int, string>>  $overrides
 * @return array<string, string|array<int, string>>
 */
function registrationInput(array $overrides = []): array
{
    return array_replace([
        'name' => 'Jane Doe',
        'username' => 'jane_doe',
        'email' => 'jane@example.com',
        'address' => '42 Main Street, Colombo 03',
        'mobile_phone' => '+94771234567',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ], $overrides);
}

test('registration uses the requested url and retains the old link', function () {
    expect(route('register', absolute: false))->toBe('/registration');
    expect(route('register.store', absolute: false))->toBe('/registration');

    $this->get('/registration')->assertOk()->assertSee('Create account');
    $this->get('/register')->assertRedirect('/registration');
    $this->get('/login')->assertSee(route('register'));
});

test('registration normalizes identifiers and securely stores all account details', function () {
    Notification::fake();

    $this->post('/registration', registrationInput([
        'name' => '  Jane Doe  ',
        'username' => '  Jane_Doe  ',
        'email' => '  JANE@Example.com  ',
        'address' => '  42 Main Street, Colombo 03  ',
        'mobile_phone' => '+94 (77) 123-4567',
    ]))->assertSessionHasNoErrors()->assertRedirect('/dashboard');

    $user = User::sole();
    expect($user->name)->toBe('Jane Doe')
        ->and($user->username)->toBe('jane_doe')
        ->and($user->email)->toBe('jane@example.com')
        ->and($user->address)->toBe('42 Main Street, Colombo 03')
        ->and($user->mobile_phone)->toBe('+94771234567')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->password)->not->toBe('SecurePassword123!');
    expect(Hash::check('SecurePassword123!', $user->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->get('/dashboard')->assertRedirect(route('verification.notice'));
});

test('registration rejects invalid details without creating a user', function (array $changes, string $field) {
    $this->from('/registration')->post('/registration', registrationInput($changes))
        ->assertRedirect('/registration')->assertSessionHasErrors($field);

    $this->assertDatabaseCount('users', 0);
    $this->assertGuest();
})->with([
    'missing name' => [['name' => ''], 'name'],
    'long name' => [['name' => str_repeat('a', 256)], 'name'],
    'missing username' => [['username' => ''], 'username'],
    'short username' => [['username' => 'ab'], 'username'],
    'long username' => [['username' => str_repeat('a', 31)], 'username'],
    'username with spaces' => [['username' => 'jane doe'], 'username'],
    'username with symbols' => [['username' => 'jane@doe'], 'username'],
    'missing email' => [['email' => ''], 'email'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'missing password' => [['password' => ''], 'password'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'mismatched passwords' => [['password_confirmation' => 'different-password'], 'password_confirmation'],
    'missing confirmation' => [['password_confirmation' => ''], 'password_confirmation'],
    'malformed username' => [['username' => ['invalid']], 'username'],
    'malformed email' => [['email' => ['invalid']], 'email'],
    'malformed password' => [['password' => ['invalid']], 'password'],
    'missing address' => [['address' => ''], 'address'],
    'short address' => [['address' => 'home'], 'address'],
    'long address' => [['address' => str_repeat('a', 501)], 'address'],
    'malformed address' => [['address' => ['invalid']], 'address'],
    'missing mobile phone' => [['mobile_phone' => ''], 'mobile_phone'],
    'phone with letters' => [['mobile_phone' => '+9477CALLNOW'], 'mobile_phone'],
    'short phone' => [['mobile_phone' => '123456'], 'mobile_phone'],
    'long phone' => [['mobile_phone' => '+1234567890123456'], 'mobile_phone'],
    'misplaced country prefix' => [['mobile_phone' => '94+771234567'], 'mobile_phone'],
    'malformed phone' => [['mobile_phone' => ['invalid']], 'mobile_phone'],
]);

test('registration rejects duplicate email and username after normalization', function () {
    User::factory()->create(['email' => 'jane@example.com', 'username' => 'jane_doe']);

    $this->post('/registration', registrationInput([
        'email' => 'JANE@EXAMPLE.COM',
        'username' => 'Jane_Doe',
    ]))->assertSessionHasErrors(['email', 'username']);

    $this->assertDatabaseCount('users', 1);
    $this->assertGuest();
});

test('authenticated users cannot access or submit registration', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/registration')->assertRedirect(route('dashboard'));
    $this->post('/registration', registrationInput())->assertRedirect(route('dashboard'));

    $this->assertDatabaseCount('users', 1);
    $this->assertAuthenticatedAs($user);
});

test('registration throttles repeated submissions', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/registration', [])->assertSessionHasErrors('name');
    }

    $this->post('/registration', [])->assertTooManyRequests();
    $this->assertDatabaseCount('users', 0);
});

test('registration preserves safe values and renders accessible field errors', function () {
    $this->from('/registration')->post('/registration', registrationInput([
        'email' => 'invalid',
        'password_confirmation' => 'different-password',
    ]))->assertRedirect('/registration');

    $this->get('/registration')
        ->assertOk()
        ->assertSee('value="Jane Doe"', false)
        ->assertSee('value="jane_doe"', false)
        ->assertSee('42 Main Street, Colombo 03')
        ->assertSee('value="+94771234567"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('id="email-error"', false)
        ->assertSee('id="password_confirmation-error"', false)
        ->assertDontSee('value="SecurePassword123!"', false)
        ->assertDontSee('value="different-password"', false);
});

test('registered users can log out and authenticate by either identifier with contact details preserved', function () {
    Notification::fake();
    $this->post('/registration', registrationInput())->assertSessionHasNoErrors();
    $user = User::sole();

    foreach ([$user->email, $user->username] as $identifier) {
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => $identifier,
            'password' => 'SecurePassword123!',
        ])->assertSessionHasNoErrors()->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    expect($user->fresh()->address)->toBe('42 Main Street, Colombo 03')
        ->and($user->fresh()->mobile_phone)->toBe('+94771234567');

    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);
    $this->get($verificationUrl)->assertRedirect('/dashboard?verified=1');
    $this->get(route('dashboard'))->assertOk();
});

test('existing users without contact details can still authenticate', function () {
    $user = User::factory()->create(['address' => null, 'mobile_phone' => null]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('registration accepts local mobile phone formatting', function () {
    $this->post('/registration', registrationInput(['mobile_phone' => '077 123 4567']))
        ->assertSessionHasNoErrors();

    expect(User::sole()->mobile_phone)->toBe('0771234567');
});

test('contact details are not exposed in public user serialization', function () {
    $user = User::factory()->create([
        'address' => '42 Main Street, Colombo 03',
        'mobile_phone' => '+94771234567',
    ]);

    expect($user->toArray())->not->toHaveKeys(['address', 'mobile_phone']);
    $this->getJson('/api/v1/users/'.$user->id)
        ->assertOk()
        ->assertJsonMissingPath('data.address')
        ->assertJsonMissingPath('data.mobile_phone');
});
