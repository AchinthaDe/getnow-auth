<?php

declare(strict_types=1);

use App\Domain\Accounts\Contracts\TokenIssuer;
use App\Domain\Accounts\Data\TokenPairData;
use App\Domain\Accounts\Tokens\PassportTokenIssuer;
use App\Models\User;
use Mockery\MockInterface;
use PHPUnit\Framework\Assert;

function tokenHandoffIssuer(): TokenIssuer&MockInterface
{
    $issuer = Mockery::mock(TokenIssuer::class);

    if (! $issuer instanceof TokenIssuer) {
        throw new LogicException('The mock must implement the token issuer contract.');
    }

    app()->instance(TokenIssuer::class, $issuer);

    return $issuer;
}

/** @return array<string, string> */
function tokenHandoffRegistrationInput(): array
{
    return [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'username' => 'jane_doe',
        'address' => '42 Main Street, Colombo',
        'mobile_phone' => '+94771234567',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ];
}

test('api login returns the token pair supplied by the token owner', function (string $identifier) {
    $user = User::factory()->create();
    tokenHandoffIssuer()->shouldReceive('issue')->once()
        ->with(Mockery::on(fn (User $candidate): bool => $candidate->is($user)))
        ->andReturn(new TokenPairData('test-access', 'test-refresh', 'Bearer', 900));

    $this->postJson('/api/v1/auth/login', [
        'email' => strtoupper($user->{$identifier}),
        'password' => 'password',
    ])->assertOk()->assertExactJson([
        'data' => [
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'username' => $user->username],
            'tokens' => ['access_token' => 'test-access', 'refresh_token' => 'test-refresh', 'token_type' => 'Bearer', 'expires_in' => 900],
        ],
    ]);
    $this->assertGuest();
})->with(['email', 'username']);

test('invalid credentials never issue tokens and return the same error', function () {
    $user = User::factory()->create();
    tokenHandoffIssuer()->shouldNotReceive('issue');

    $wrongPassword = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'incorrect']);
    $unknownUser = $this->postJson('/api/v1/auth/login', ['email' => 'unknown@example.com', 'password' => 'incorrect']);

    $wrongPassword->assertUnprocessable()->assertJsonValidationErrors('email');
    $unknownUser->assertUnprocessable()->assertJsonValidationErrors('email');
    expect($wrongPassword->json('errors'))->toBe($unknownUser->json('errors'));
});

test('api registration passes the saved user to the token owner', function () {
    tokenHandoffIssuer()->shouldReceive('issue')->once()
        ->with(Mockery::on(fn (User $user): bool => $user->exists && $user->email === 'jane@example.com'))
        ->andReturn(new TokenPairData('registered-access', 'registered-refresh', 'Bearer', 900));

    $this->postJson('/api/v1/auth/register', array_replace(tokenHandoffRegistrationInput(), [
        'email' => ' JANE@Example.com ',
        'role' => 'superadmin',
    ]))->assertCreated()
        ->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonPath('data.tokens.access_token', 'registered-access')
        ->assertJsonPath('data.tokens.refresh_token', 'registered-refresh')
        ->assertJsonMissingPath('data.user.password')
        ->assertJsonMissingPath('data.user.address')
        ->assertJsonMissingPath('data.user.role');

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    $this->assertGuest();
});

test('failed token issuance rolls back api registration', function () {
    tokenHandoffIssuer()->shouldReceive('issue')->once()->andThrow(new RuntimeException('Token owner unavailable'));
    $this->withoutExceptionHandling();

    try {
        $this->postJson('/api/v1/auth/register', tokenHandoffRegistrationInput());
        Assert::fail('Expected the token owner failure to propagate.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Token owner unavailable');
    }

    $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
});

test('invalid registration does not create a user or issue tokens', function () {
    tokenHandoffIssuer()->shouldNotReceive('issue');

    $this->postJson('/api/v1/auth/register', array_replace(tokenHandoffRegistrationInput(), [
        'password_confirmation' => 'different',
    ]))->assertUnprocessable()->assertJsonValidationErrors('password');

    $this->assertDatabaseCount('users', 0);
});

test('malformed api credentials do not reach the token owner', function () {
    tokenHandoffIssuer()->shouldNotReceive('issue');
    $this->postJson('/api/v1/auth/login', ['email' => ['invalid'], 'password' => ['invalid']])
        ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
});

test('api login throttles repeated attempts', function () {
    tokenHandoffIssuer()->shouldNotReceive('issue');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/v1/auth/login', ['email' => 'unknown@example.com', 'password' => 'incorrect'])
            ->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'unknown@example.com', 'password' => 'incorrect'])
        ->assertTooManyRequests();
});

test('password login cannot bypass an enabled two factor challenge', function () {
    $user = User::factory()->withTwoFactor()->create();
    tokenHandoffIssuer()->shouldNotReceive('issue');
    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('the token owner provides the production issuer binding', function () {
    expect(app(TokenIssuer::class))->toBeInstanceOf(PassportTokenIssuer::class);
});
