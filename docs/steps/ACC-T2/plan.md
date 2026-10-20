# [ACC-T2 | PLAN v1 | Gemini]

---

### 1. Understanding

The objective of **ACC-T2** (Tokens track, owned by Achintha, branch `feat/tokens-issuer-and-endpoints`) is to implement the Passport token issuance, refresh rotation, and revocation engine along with the public authentication token endpoints for the GetNow backend module:

1. **Domain Token Engine (`CP-A`)**:
   - Implement `PassportTokenIssuer` in [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php), fulfilling [`App\Domain\Accounts\Contracts\TokenIssuer`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Contracts/TokenIssuer.php) without reading user credentials, roles, or querying the `users` table (`OWN4`, `D-a`).
   - Implement `issue(User $user): TokenPairData`: Mints a signed Passport OAuth2 JWT access token (valid for configured lifetime, default 900 seconds) and an encrypted OAuth2 refresh token (valid for configured lifetime, default 14 days), persisting both records in `oauth_access_tokens` and `oauth_refresh_tokens`.
   - Implement `refresh(string $refreshToken): TokenPairData`: Decrypts and validates the refresh token payload against PostgreSQL, revokes the consumed refresh token and its parent access token in a database transaction (`TOK4`), and issues a fresh rotated token pair. Reused, expired, revoked, or structurally invalid refresh tokens throw `Illuminate\Auth\AuthenticationException`.
   - Implement `revoke(User $user): void`: Revokes the active access token associated with the current request (`$user->token()`) and all refresh tokens in `oauth_refresh_tokens` linked to that `access_token_id`. If the token is null or transient, exits without error.
   - Implement `revokeAll(User $user): void`: Revokes every non-revoked access token and associated refresh token belonging to `$user` across all devices.
   - Implement `TokenServiceProvider` in [`app/Providers/TokenServiceProvider.php`](file:///c:/dev/getnow-auth/app/Providers/TokenServiceProvider.php) and register it in [`bootstrap/providers.php`](file:///c:/dev/getnow-auth/bootstrap/providers.php): Binds [`TokenIssuer`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Contracts/TokenIssuer.php) to [`PassportTokenIssuer`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php), configures Passport token lifetimes via `Passport::tokensExpireIn()` and `Passport::refreshTokensExpireIn()`, and configures named rate limiters `token-refresh` and `token-logout` (`TOK3`, `TOK5`).
   - Record Architecture Decision Record `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md` resolving `OQ1`, `OQ2`, `OQ3`, and `OQ4`.
   - Add unit/domain feature tests in [`tests/Feature/Tokens/PassportTokenIssuerTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/PassportTokenIssuerTest.php) verifying all methods against real Passport in PostgreSQL.

2. **HTTP Endpoints, Routes & Verification (`CP-B`)**:
   - Implement `RefreshTokenRequest` in [`app/Http/Requests/Auth/RefreshTokenRequest.php`](file:///c:/dev/getnow-auth/app/Http/Requests/Auth/RefreshTokenRequest.php) validating `refresh_token` (`required`, `string`).
   - Implement `TokenController` in [`app/Http/Controllers/Api/V1/Auth/TokenController.php`](file:///c:/dev/getnow-auth/app/Http/Controllers/Api/V1/Auth/TokenController.php):
     - `refresh(RefreshTokenRequest $request)`: Calls `TokenIssuer::refresh()`, returning `HTTP 200` with JSON `{ "data": { "tokens": { "access_token": "...", "refresh_token": "...", "token_type": "Bearer", "expires_in": 900 } } }` on success, or `HTTP 401 Unauthorized` on invalid, expired, revoked, or reused tokens (`D-c`).
     - `logout()`: Resolves the authenticated user from the `api` guard via `Auth::guard('api')->user()`, calls `TokenIssuer::revoke($user)`, and returns `HTTP 204 No Content`.
   - Populate [`routes/api/tokens.php`](file:///c:/dev/getnow-auth/routes/api/tokens.php) with `POST /api/v1/auth/refresh` (throttled by `throttle:token-refresh`) and `POST /api/v1/auth/logout` (protected by `auth:api` and throttled by `throttle:token-logout`).
   - Update [`ARCHITECTURE.md`](file:///c:/dev/getnow-auth/ARCHITECTURE.md) Section 11 table rows for `OQ1`–`OQ4` referencing ADR 0001.
   - Add feature tests in [`tests/Feature/Tokens/TokenEndpointsTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/TokenEndpointsTest.php) covering valid issuance, rotation enforcement, token reuse rejection, expiration rejection, logout invalidation, isolation from other sessions, and throttle enforcement (`TOK5`).

---

### 2. Sources Read

The following files were inspected in the workspace prior to writing this plan:
1. [`AGENTS.md`](file:///c:/dev/getnow-auth/AGENTS.md) (lines 1–160): Track identification, Tokens track constraints, coding standards, database rules, testing rules.
2. [`ARCHITECTURE.md`](file:///c:/dev/getnow-auth/ARCHITECTURE.md) (lines 1–275): Module architecture, layer boundaries, file ownership table, the `TokenIssuer` seam, endpoint specifications, open decisions table (`OQ1`–`OQ7`).
3. [`app/Domain/Accounts/Contracts/TokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Contracts/TokenIssuer.php) (lines 1–29): Seam interface defining `issue`, `refresh`, `revoke`, and `revokeAll`.
4. [`app/Domain/Accounts/Data/TokenPairData.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Data/TokenPairData.php) (lines 1–21): DTO with `SnakeCaseMapper`, `accessToken`, `refreshToken`, `tokenType`, `expiresIn`.
5. [`app/Models/User.php`](file:///c:/dev/getnow-auth/app/Models/User.php) (lines 1–67): Verified `HasApiTokens` trait usage, `OAuthenticatable` interface implementation, and `hashed` password cast.
6. [`config/auth.php`](file:///c:/dev/getnow-auth/config/auth.php) (lines 1–124): Guards configuration verifying `api` guard driver is `passport` and provider is `users`.
7. [`config/passport.php`](file:///c:/dev/getnow-auth/config/passport.php) (lines 1–70): Token lifetimes configuration (`access_token_ttl_seconds` = 900, `refresh_token_ttl_days` = 14).
8. [`database/migrations/2026_10_19_102055_create_oauth_access_tokens_table.php`](file:///c:/dev/getnow-auth/database/migrations/2026_10_19_102055_create_oauth_access_tokens_table.php) (lines 1–44): Schema for `oauth_access_tokens` (`char(80)` id, `user_id`, `client_id` uuid, `revoked`, `expires_at`).
9. [`database/migrations/2026_10_19_102056_create_oauth_refresh_tokens_table.php`](file:///c:/dev/getnow-auth/database/migrations/2026_10_19_102056_create_oauth_refresh_tokens_table.php) (lines 1–40): Schema for `oauth_refresh_tokens` (`char(80)` id, `access_token_id` char(80), `revoked`, `expires_at`).
10. [`database/migrations/2026_10_19_102057_create_oauth_clients_table.php`](file:///c:/dev/getnow-auth/database/migrations/2026_10_19_102057_create_oauth_clients_table.php) (lines 1–45): Schema for `oauth_clients` (`uuid` id, `redirect_uris`, `grant_types`, `revoked`).
11. [`bootstrap/providers.php`](file:///c:/dev/getnow-auth/bootstrap/providers.php) (lines 1–12): Application service providers list.
12. [`bootstrap/app.php`](file:///c:/dev/getnow-auth/bootstrap/app.php) (lines 1–26): Verified `apiPrefix: 'api/v1'` configuration.
13. [`routes/api.php`](file:///c:/dev/getnow-auth/routes/api.php) (lines 1–7): Verified root API route inclusion requiring `routes/api/tokens.php`.
14. [`routes/api/tokens.php`](file:///c:/dev/getnow-auth/routes/api/tokens.php) (lines 1–4): Verified present and empty.
15. [`tests/Arch/AccountsArchTest.php`](file:///c:/dev/getnow-auth/tests/Arch/AccountsArchTest.php) (lines 1–41): Verified strict types check, domain layer isolation from HTTP (`App\Domain` not using `Illuminate\Http` or `App\Http`), controller repository bypass checks, and users track passport isolation.
16. [`tests/Pest.php`](file:///c:/dev/getnow-auth/tests/Pest.php) (lines 1–53): Pest configuration with `RefreshDatabase`.
17. [`phpunit.xml`](file:///c:/dev/getnow-auth/phpunit.xml) (lines 1–35): Testing environment setup configured with `DB_CONNECTION=pgsql`.
18. [`tests/Feature/Tokens/PassportSetupTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/PassportSetupTest.php) (lines 1–41): Baseline tests for Passport configuration and tables.
19. `vendor/laravel/passport/src/Passport.php` (lines 1–450, 605–625): Methods `tokensExpireIn`, `refreshTokensExpireIn`, `tokenEncryptionKey`, `keyPath`, `tokenModel`, `refreshTokenModel`, `clientModel`.
20. `vendor/laravel/passport/src/PassportServiceProvider.php` (lines 1–300): Bindings for `AuthorizationServer`, `ResourceServer`, `TokenGuard`, and `makeCryptKey`.
21. `vendor/laravel/passport/src/TokenRepository.php` (lines 1–47): Verified `TokenRepository` methods are deprecated query wrappers, not issuing factories.
22. `vendor/laravel/passport/src/Token.php` (lines 1–125): Eloquent model for `oauth_access_tokens` with `revoke()`.
23. `vendor/laravel/passport/src/RefreshToken.php` (lines 1–81): Eloquent model for `oauth_refresh_tokens` with `revoke()`.
24. `vendor/laravel/passport/src/ClientRepository.php` (lines 1–261): Client repository methods in v13.10.
25. `vendor/laravel/passport/src/Client.php` (lines 1–243): Client model with `grant_types` array cast and UUID support.
26. `vendor/laravel/passport/src/HasApiTokens.php` (lines 1–136): User trait methods: `token()`, `currentAccessToken()`, `withAccessToken()`.
27. `vendor/laravel/passport/src/AccessToken.php` (lines 1–179): Request access token wrapper implementing `ScopeAuthorizable` with `revoke()`.
28. `vendor/laravel/passport/src/Guards/TokenGuard.php` (lines 1–319): Guard execution flow via `ResourceServer::validateAuthenticatedRequest` and client lookup.
29. `vendor/laravel/passport/src/Bridge/AccessToken.php` (lines 1–34): League bridge access token entity.
30. `vendor/laravel/passport/src/Bridge/AccessTokenRepository.php` (lines 1–69): Implementation of `AccessTokenRepositoryInterface` (`persistNewAccessToken`, `revokeAccessToken`, `isAccessTokenRevoked`).
31. `vendor/laravel/passport/src/Bridge/RefreshTokenRepository.php` (lines 1–60): Implementation of `RefreshTokenRepositoryInterface` (`persistNewRefreshToken`, `revokeRefreshToken`, `isRefreshTokenRevoked`).
32. `vendor/league/oauth2-server/src/CryptKey.php` (lines 1–146): Verified `CryptKey` parses in-memory string keys directly when not prefixed with `file://`.
33. `vendor/league/oauth2-server/src/CryptTrait.php` (lines 1–94): Defuse encryption/decryption routines using `encryptionKey`.
34. `vendor/league/oauth2-server/src/ResponseTypes/BearerTokenResponse.php` (lines 1–86): Structure of standard OAuth2 Bearer token response and encrypted refresh token payload.
35. `vendor/league/oauth2-server/src/Grant/RefreshTokenGrant.php` (lines 1–143): Grant rotation logic, payload verification, and revocation behavior.
36. `vendor/league/oauth2-server/src/Entities/Traits/AccessTokenTrait.php` (lines 1–113): JWT building, claims generation, and signing via `Lcobucci\JWT`.

---

### 3. Verified Current State

From direct inspection of the codebase:
- **Git Branch**: Currently on `feat/tokens-passport-install`. Step `ACC-T2` will be executed on a dedicated branch `feat/tokens-issuer-and-endpoints` branched from `main`.
- **Passport Package**: `laravel/passport` is installed at version `v13.10.0` with `league/oauth2-server` at `v9.4.0`.
- **Database Schema**: The 5 Passport migrations (`oauth_auth_codes`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_clients`, `oauth_device_codes`) are present and executed in PostgreSQL. The `oauth_clients` table uses a `uuid` primary key with JSON `grant_types` and `redirect_uris` columns.
- **Contract Seam**: [`app/Domain/Accounts/Contracts/TokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Contracts/TokenIssuer.php) and [`app/Domain/Accounts/Data/TokenPairData.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Data/TokenPairData.php) are defined. No production implementation of `TokenIssuer` exists yet.
- **User Model**: [`app/Models/User.php`](file:///c:/dev/getnow-auth/app/Models/User.php) uses `Laravel\Passport\HasApiTokens`, implements `Laravel\Passport\Contracts\OAuthenticatable`, and uses `'password' => 'hashed'`.
- **HTTP Routing Setup**: [`bootstrap/app.php`](file:///c:/dev/getnow-auth/bootstrap/app.php) sets `apiPrefix: 'api/v1'`. [`routes/api.php`](file:///c:/dev/getnow-auth/routes/api.php) requires [`routes/api/tokens.php`](file:///c:/dev/getnow-auth/routes/api/tokens.php). [`routes/api/tokens.php`](file:///c:/dev/getnow-auth/routes/api/tokens.php) exists but contains only `<?php declare(strict_types=1);`.
- **Architecture Validation**: Architecture rules in [`tests/Arch/AccountsArchTest.php`](file:///c:/dev/getnow-auth/tests/Arch/AccountsArchTest.php) forbid `App\Domain` from importing `Illuminate\Http` or `App\Http`.
- **Baseline Quality Checks**: `composer check` passes with 41 tests, 107 assertions, 0 lint errors, and 0 static analysis errors.

---

### 4. File Manifest

| File Path | Track | Action | Description |
|---|---|---|---|
| [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php) | `[A]` | Create | Final class implementing `TokenIssuer` contract: `issue`, `refresh` with rotation, `revoke`, `revokeAll`. Zero `Illuminate\Http` imports. |
| [`app/Providers/TokenServiceProvider.php`](file:///c:/dev/getnow-auth/app/Providers/TokenServiceProvider.php) | `[A]` | Create | Service provider binding `TokenIssuer::class` to `PassportTokenIssuer::class`, setting token lifetimes from config, and registering rate limiters. |
| [`bootstrap/providers.php`](file:///c:/dev/getnow-auth/bootstrap/providers.php) | Shared `[A]` | Modify | Register `App\Providers\TokenServiceProvider::class`. |
| [`docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`](file:///c:/dev/getnow-auth/docs/adr/0001-passport-token-issuance-and-refresh-rotation.md) | `[A]` | Create | Architecture Decision Record answering `OQ1`–`OQ4`. |
| [`tests/Feature/Tokens/PassportTokenIssuerTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/PassportTokenIssuerTest.php) | `[A]` | Create | Feature tests testing `PassportTokenIssuer` methods directly against PostgreSQL. |
| [`app/Http/Requests/Auth/RefreshTokenRequest.php`](file:///c:/dev/getnow-auth/app/Http/Requests/Auth/RefreshTokenRequest.php) | `[A]` | Create | FormRequest validating `refresh_token` parameter (`required`, `string`). |
| [`app/Http/Controllers/Api/V1/Auth/TokenController.php`](file:///c:/dev/getnow-auth/app/Http/Controllers/Api/V1/Auth/TokenController.php) | `[A]` | Create | API controller exposing `refresh` (`POST /api/v1/auth/refresh`) and `logout` (`POST /api/v1/auth/logout`). |
| [`routes/api/tokens.php`](file:///c:/dev/getnow-auth/routes/api/tokens.php) | `[A]` | Modify | Declare endpoints for `refresh` and `logout` with throttle and auth middleware. |
| [`tests/Feature/Tokens/TokenEndpointsTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/TokenEndpointsTest.php) | `[A]` | Create | Feature tests for `POST /api/v1/auth/refresh` and `POST /api/v1/auth/logout`. |
| [`ARCHITECTURE.md`](file:///c:/dev/getnow-auth/ARCHITECTURE.md) | `[A]` | Modify | Update Section 11 open decisions table for `OQ1`–`OQ4` referencing ADR 0001. |

---

### 5. Interface Specs

#### 5.1 `PassportTokenIssuer`
- **Class**: `final class PassportTokenIssuer implements TokenIssuer`
- **Namespace**: `App\Domain\Accounts\Tokens`
- **Constructor Dependencies**:
  - `League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface $accessTokenRepository` (injected as `Laravel\Passport\Bridge\AccessTokenRepository`)
  - `League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface $refreshTokenRepository` (injected as `Laravel\Passport\Bridge\RefreshTokenRepository`)
  - `Illuminate\Contracts\Encryption\Encrypter $encrypter`
- **Contract Methods**:
  ```php
  public function issue(User $user): TokenPairData
  ```
  - Resolves or creates the first-party OAuth client record in `oauth_clients`.
  - Mints an 80-character access token identifier via `bin2hex(random_bytes(40))`.
  - Creates and signs an OAuth2 JWT access token via `Laravel\Passport\Bridge\AccessToken` using the RSA private key from `CryptKey`.
  - Persists access token row in `oauth_access_tokens` via `AccessTokenRepositoryInterface::persistNewAccessToken()`.
  - Mints an 80-character refresh token identifier via `bin2hex(random_bytes(40))`.
  - Creates a `Laravel\Passport\Bridge\RefreshToken` entity linked to the access token.
  - Persists refresh token row in `oauth_refresh_tokens` via `RefreshTokenRepositoryInterface::persistNewRefreshToken()`.
  - Encrypts the refresh token payload JSON string (`client_id`, `refresh_token_id`, `access_token_id`, `scopes: []`, `user_id`, `expire_time`) using `Defuse\Crypto\Crypto::encryptWithPassword($payloadJson, $encryptionKey)` matching League OAuth2 standard format.
  - Returns `new TokenPairData(accessToken: $jwt, refreshToken: $encryptedRefreshToken, tokenType: 'Bearer', expiresIn: $ttlSeconds)`.

  ```php
  public function refresh(string $refreshToken): TokenPairData
  ```
  - Decrypts `$refreshToken` using `Defuse\Crypto\Crypto::decryptWithPassword($refreshToken, $encryptionKey)`. Throws `Illuminate\Auth\AuthenticationException('Unauthenticated.')` upon decryption error.
  - Decodes JSON payload. Validates structure contains non-empty strings for `refresh_token_id`, `access_token_id`, `client_id`, and integer `expire_time`. Throws `AuthenticationException` if invalid.
  - Checks expiration: `if ($payload['expire_time'] < time()) { throw new AuthenticationException('Unauthenticated.'); }`.
  - Database check inside a transaction (`DB::transaction`):
    - Retrieves refresh token row from `Passport::refreshToken()` by `refresh_token_id`. If absent or `revoked === true`, throws `AuthenticationException('Unauthenticated.')`.
    - Retrieves access token row from `Passport::token()` by `access_token_id`. If absent or `revoked === true`, throws `AuthenticationException('Unauthenticated.')`.
    - Retrieves client row from `Passport::client()` by `client_id`. If absent or `revoked === true`, throws `AuthenticationException('Unauthenticated.')`.
    - Atomically revokes consumed refresh token: `$consumedRefreshToken->revoke()`.
    - Atomically revokes consumed access token: `$priorAccessToken->revoke()`.
    - Resolves the user model: `$user = User::query()->findOrFail((int) $payload['user_id'])`.
    - Mints and returns a fresh `TokenPairData` for `$user` and `$client`.

  ```php
  public function revoke(User $user): void
  ```
  - Retrieves active access token: `$token = $user->token()`.
  - If `$token === null` or `$token->transient() === true`, returns cleanly (no-op).
  - Obtains access token ID: `$tokenId = $token->oauth_access_token_id ?? $token->getKey()`.
  - Revokes access token: `$token->revoke()`.
  - Revokes associated refresh token in `oauth_refresh_tokens`:
    `Passport::refreshToken()->newQuery()->where('access_token_id', $tokenId)->update(['revoked' => true]);`.

  ```php
  public function revokeAll(User $user): void
  ```
  - Obtains all active access token IDs for the user:
    `$tokenIds = Passport::token()->newQuery()->where('user_id', $user->getAuthIdentifier())->where('revoked', false)->pluck('id')->all();`.
  - Marks all user access tokens revoked:
    `Passport::token()->newQuery()->where('user_id', $user->getAuthIdentifier())->where('revoked', false)->update(['revoked' => true]);`.
  - Marks all associated refresh tokens revoked:
    `Passport::refreshToken()->newQuery()->whereIn('access_token_id', $tokenIds)->where('revoked', false)->update(['revoked' => true]);`.

- **Internal Helper Methods**:
  - `private function resolveClient(): Client`: Queries `Passport::client()->newQuery()->where('revoked', false)->where('name', 'GetNow First-Party Auth Client')->first()`. If none exists, creates and persists a client with UUID, name `'GetNow First-Party Auth Client'`, `redirect_uris: []`, `grant_types: ['refresh_token']`, `revoked: false`.
  - `private function getPrivateKey(): CryptKey`: Returns `new CryptKey(config('passport.private_key') ?: 'file://'.Passport::keyPath('oauth-private.key'), null, false)`.
  - `private function getEncryptionKey(): string`: Returns `Passport::tokenEncryptionKey($this->encrypter)`.

#### 5.2 `TokenServiceProvider`
- **Class**: `final class TokenServiceProvider extends ServiceProvider`
- **Namespace**: `App\Providers`
- **Methods**:
  - `register(): void`:
    `$this->app->bind(TokenIssuer::class, PassportTokenIssuer::class);`
  - `boot(): void`:
    - Configure Passport lifetimes from config:
      `$accessTtl = (int) config('passport.token_lifetimes.access_token_ttl_seconds', 900);`
      `$refreshTtl = (int) config('passport.token_lifetimes.refresh_token_ttl_days', 14);`
      `Passport::tokensExpireIn(Date::now()->addSeconds($accessTtl));`
      `Passport::refreshTokensExpireIn(Date::now()->addDays($refreshTtl));`
    - Configure Rate Limiters:
      ```php
      RateLimiter::for('token-refresh', function (Request $request): Limit {
          return Limit::perMinute(10)->by($request->ip());
      });

      RateLimiter::for('token-logout', function (Request $request): Limit {
          return Limit::perMinute(30)->by(
              $request->user()?->getAuthIdentifier() ?? $request->ip()
          );
      });
      ```

#### 5.3 `RefreshTokenRequest`
- **Class**: `final class RefreshTokenRequest extends FormRequest`
- **Namespace**: `App\Http\Requests\Auth`
- **Methods**:
  - `authorize(): bool`: Returns `true`.
  - `rules(): array`:
    ```php
    return [
        'refresh_token' => ['required', 'string'],
    ];
    ```
  - `messages(): array`:
    ```php
    return [
        'refresh_token.required' => 'The refresh token field is required.',
        'refresh_token.string' => 'The refresh token field must be a string.',
    ];
    ```

#### 5.4 `TokenController`
- **Class**: `final class TokenController extends Controller`
- **Namespace**: `App\Http\Controllers\Api\V1\Auth`
- **Constructor**:
  `public function __construct(private readonly TokenIssuer $tokenIssuer)`
- **Methods**:
  - `public function refresh(RefreshTokenRequest $request): JsonResponse`:
    Executes `$tokenPair = $this->tokenIssuer->refresh((string) $request->validated('refresh_token'));`.
    Catches `Illuminate\Auth\AuthenticationException` and returns `response()->json(['message' => 'Unauthenticated.'], 401)`.
    On success returns `response()->json(['data' => ['tokens' => $tokenPair->toArray()]], 200)`.
  - `public function logout(): Response`:
    Resolves `$user = Auth::guard('api')->user();`.
    Calls `$this->tokenIssuer->revoke($user);`.
    Returns `response()->noContent();` (`HTTP 204 No Content`).

#### 5.5 Route Declarations in `routes/api/tokens.php`
```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('refresh', [TokenController::class, 'refresh'])
        ->middleware('throttle:token-refresh');

    Route::post('logout', [TokenController::class, 'logout'])
        ->middleware(['auth:api', 'throttle:token-logout']);
});
```

---

### 6. Data and Migrations

- **Migrations Added/Modified**: None. All 5 Passport migrations (`2026_10_19_102054` through `2026_10_19_102058`) exist from `ACC-T1`.
- **Database Schema Interactions**:
  1. `oauth_clients`:
     - Schema: `id` (UUID), `name` (string), `secret` (string nullable), `provider` (string nullable), `redirect_uris` (text array), `grant_types` (text array), `revoked` (boolean), `timestamps`.
     - Read/Write: Queried for active client name `'GetNow First-Party Auth Client'`. Created if absent.
  2. `oauth_access_tokens`:
     - Schema: `id` (`char(80)`), `user_id` (`bigint`), `client_id` (`uuid`), `name` (`string` nullable), `scopes` (`text` nullable), `revoked` (`boolean`), `created_at`, `updated_at`, `expires_at` (`datetime`).
     - Write: Inserted on `issue` and `refresh`. Updated `revoked = true` on `refresh` (prior token), `revoke` (single session), and `revokeAll` (all user sessions).
  3. `oauth_refresh_tokens`:
     - Schema: `id` (`char(80)`), `access_token_id` (`char(80)` index), `revoked` (`boolean`), `expires_at` (`datetime`).
     - Write: Inserted on `issue` and `refresh`. Updated `revoked = true` on `refresh` (consumed token), `revoke` (single session), and `revokeAll` (all user sessions).
- **Data Transfer Objects**:
  - [`TokenPairData`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Data/TokenPairData.php): Output DTO mapped via `SnakeCaseMapper`. Produces JSON keys: `access_token`, `refresh_token`, `token_type`, `expires_in`.

---

### 7. Behavior and Flows

#### Flow 1: Token Issuance (`issue(User $user)`)
```
AuthService (User track) -> TokenIssuer::issue($user)
  -> PassportTokenIssuer resolves client 'GetNow First-Party Auth Client'
  -> Generates random 80-char accessTokenId
  -> Mints signed JWT AccessToken (expires in config: 900s)
  -> Persists row in oauth_access_tokens (revoked = false)
  -> Generates random 80-char refreshTokenId
  -> Persists row in oauth_refresh_tokens (revoked = false, expires in config: 14d)
  -> Encrypts JSON payload with Defuse Crypto key
  -> Returns TokenPairData(accessToken, refreshToken, 'Bearer', 900)
```

#### Flow 2: Refresh Token Rotation (`POST /api/v1/auth/refresh`)
```
Client -> POST /api/v1/auth/refresh { "refresh_token": "..." }
  -> RefreshTokenRequest validates presence and string type
  -> TokenController::refresh() -> TokenIssuer::refresh($token)
  -> Decrypts refresh token payload
     [Failure to decrypt -> throws AuthenticationException -> 401]
  -> Validates payload expiration (expire_time >= time())
     [Expired -> throws AuthenticationException -> 401]
  -> DB::transaction:
     -> Checks oauth_refresh_tokens: row exists and revoked == false
        [Revoked or missing -> throws AuthenticationException -> 401]
     -> Checks oauth_access_tokens: row exists and revoked == false
        [Revoked or missing -> throws AuthenticationException -> 401]
     -> Checks oauth_clients: row exists and revoked == false
        [Revoked or missing -> throws AuthenticationException -> 401]
     -> Sets oauth_refresh_tokens.revoked = true for consumed token
     -> Sets oauth_access_tokens.revoked = true for prior access token
     -> Issues new access token (900s) and persists in oauth_access_tokens
     -> Issues new refresh token (14d) and persists in oauth_refresh_tokens
     -> Encrypts new refresh token payload
  -> Returns HTTP 200 with JSON { "data": { "tokens": { ... } } }
```

#### Flow 3: Logout (`POST /api/v1/auth/logout`)
```
Client -> POST /api/v1/auth/logout (Header: "Authorization: Bearer <jwt>")
  -> auth:api middleware validates Bearer token via TokenGuard & ResourceServer
     [Invalid/Revoked token -> 401 Unauthorized]
  -> TokenController::logout() resolves User from Auth::guard('api')->user()
  -> TokenIssuer::revoke($user)
  -> Reads $user->token() (instance of Laravel\Passport\AccessToken)
  -> Sets oauth_access_tokens.revoked = true for $token->oauth_access_token_id
  -> Sets oauth_refresh_tokens.revoked = true WHERE access_token_id = $tokenId
  -> Other active tokens belonging to the same user remain untouched
  -> Returns HTTP 204 No Content
```

#### Flow 4: Session Invalidation on Security Events (`revokeAll(User $user)`)
```
UserService (password change, role change, user deletion) -> TokenIssuer::revokeAll($user)
  -> Queries all active access tokens for $user->id
  -> Sets oauth_access_tokens.revoked = true for all $user->id rows
  -> Sets oauth_refresh_tokens.revoked = true for all matching access_token_ids
  -> All sessions on all devices for this user are now invalid (401 on next request)
```

---

### 8. Rule Compliance Matrix

| Rule ID | Requirement | Compliance in this Plan |
|---|---|---|
| `OWN1` | Never edit a file owned by the other track (`[R]`) | No file marked `[R]` in `ARCHITECTURE.md` is edited. All code written lives in `[A]` files. |
| `OWN2` | Shared files follow the table in `ARCHITECTURE.md` Section 5 | `bootstrap/providers.php` modified by appending `TokenServiceProvider::class`. `routes/api.php` is untouched. `composer.json` is untouched. |
| `OWN3` | Contract files (`TokenIssuer`, `TokenPairData`) only changed in approved contract PR | [`TokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Contracts/TokenIssuer.php) and [`TokenPairData.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Data/TokenPairData.php) are untouched and read-only. |
| `OWN4` | Tokens track agents never read credentials, roles, or users table beyond `TokenIssuer` input | `PassportTokenIssuer` receives `User $user` and only reads `$user->getAuthIdentifier()`. It never inspects `password`, `role`, or queries credentials. |
| `L1` | Layers match `ARCHITECTURE.md` Section 4 | Flow follows FormRequest (`RefreshTokenRequest`) -> Controller (`TokenController`) -> Domain Service (`PassportTokenIssuer`). |
| `L2` | No `Illuminate\Http` inside `app/Domain/` | `PassportTokenIssuer` does not import `Illuminate\Http\*` or `App\Http\*`. Errors are signaled via `Illuminate\Auth\AuthenticationException`. Validated by `AccountsArchTest.php`. |
| `C1` | `declare(strict_types=1);` in every PHP file | Present in every created and modified file. |
| `C2` | Type every parameter, return value, and property; prefer `final` classes | `PassportTokenIssuer`, `TokenServiceProvider`, `RefreshTokenRequest`, and `TokenController` are declared `final` with complete type signatures. |
| `C3` | Use `spatie/laravel-data` for structured data | Returns contract DTO [`TokenPairData`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Data/TokenPairData.php). |
| `C4` | Pint and Larastan compliance with zero baseline additions | Code conforms to Pint and Larastan; `composer check` will pass with zero errors. |
| `TOK1` | Tokens track implements `TokenIssuer` in `PassportTokenIssuer.php` and binds in `TokenServiceProvider` | `PassportTokenIssuer` implements `TokenIssuer`; `TokenServiceProvider` registers the container binding. |
| `TOK2` | Check installed Passport version documentation before choosing grant or API; record as ADR 0001 (`OQ1`) | Passport v13.10 and League OAuth2 Server inspected; documented in ADR 0001. |
| `TOK3` | Access tokens short-lived, refresh tokens longer; lifetimes live in config | Lifetimes read from `config('passport.token_lifetimes')` (900s access, 14d refresh). |
| `TOK4` | Refresh tokens rotate on use; used or revoked tokens must fail | Single-use rotation revokes consumed refresh token and its parent access token in DB transaction; second attempt fails with 401. |
| `TOK5` | `refresh` and `logout` are throttled and covered by tests | Rate limiters `token-refresh` (10/min) and `token-logout` (30/min) registered and enforced. |
| `DB1` | PostgreSQL only; tests run against PostgreSQL, never SQLite | Verified `phpunit.xml` configures `DB_CONNECTION=pgsql`. All feature tests execute against PostgreSQL. |
| `DB2` | No destructive schema changes | No tables or columns dropped. Existing migrations unmodified. |
| `SEC1` | Never commit secrets, `.env`, or real keys | No `.key` files committed; tests generate ephemeral RSA keys in memory via config overrides. |
| `SEC2` | Never log passwords, tokens, or refresh tokens | Raw tokens and refresh tokens are excluded from logs and exception messages. |
| `T1` | Pest tests against real Passport in PostgreSQL; cover failure and authorization cases | Tests in `tests/Feature/Tokens/` use real Passport models and database tables, testing happy path, expired tokens, revoked tokens, reused tokens, and throttles. |
| `E1` | Windows environment; PowerShell commands run one at a time, no bash syntax | All commands run sequentially without bash chaining or operators. |

---

### 9. Acceptance Criteria Traceability

| Acceptance Criteria | Implementation Component | Test Verification |
|---|---|---|
| **AC-1**: `TokenServiceProvider` binds `TokenIssuer`, configures lifetimes, registers rate limiters | [`app/Providers/TokenServiceProvider.php`](file:///c:/dev/getnow-auth/app/Providers/TokenServiceProvider.php), [`bootstrap/providers.php`](file:///c:/dev/getnow-auth/bootstrap/providers.php) | [`PassportTokenIssuerTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/PassportTokenIssuerTest.php): verifies container binding and Passport lifetime settings |
| **AC-2**: `PassportTokenIssuer::issue()` returns valid `TokenPairData`; access token authenticates `auth:api` | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php) (`issue`) | [`PassportTokenIssuerTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/PassportTokenIssuerTest.php), [`TokenEndpointsTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/TokenEndpointsTest.php) |
| **AC-3**: `refresh()` and `POST /api/v1/auth/refresh` rotate token and revoke old pair | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php) (`refresh`), [`TokenController.php`](file:///c:/dev/getnow-auth/app/Http/Controllers/Api/V1/Auth/TokenController.php) | [`PassportTokenIssuerTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/PassportTokenIssuerTest.php), [`TokenEndpointsTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/TokenEndpointsTest.php) |
| **AC-4**: Reusing consumed refresh token or passing expired/revoked/malformed token returns HTTP 401 | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php) (`refresh`), [`TokenController.php`](file:///c:/dev/getnow-auth/app/Http/Controllers/Api/V1/Auth/TokenController.php) | [`PassportTokenIssuerTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/PassportTokenIssuerTest.php), [`TokenEndpointsTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/TokenEndpointsTest.php) |
| **AC-5**: `POST /api/v1/auth/logout` revokes current session without revoking user's other sessions | [`app/Http/Controllers/Api/V1/Auth/TokenController.php`](file:///c:/dev/getnow-auth/app/Http/Controllers/Api/V1/Auth/TokenController.php) (`logout`), [`PassportTokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php) (`revoke`) | [`TokenEndpointsTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/TokenEndpointsTest.php) |
| **AC-6**: `PassportTokenIssuer::revokeAll()` revokes every session for user across all devices | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php) (`revokeAll`) | [`PassportTokenIssuerTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/PassportTokenIssuerTest.php), [`TokenEndpointsTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/TokenEndpointsTest.php) |
| **AC-7**: Endpoints are throttled and return HTTP 429 when limits exceeded | [`routes/api/tokens.php`](file:///c:/dev/getnow-auth/routes/api/tokens.php), [`TokenServiceProvider.php`](file:///c:/dev/getnow-auth/app/Providers/TokenServiceProvider.php) | [`TokenEndpointsTest.php`](file:///c:/dev/getnow-auth/tests/Feature/Tokens/TokenEndpointsTest.php) |
| **AC-8**: Zero `[R]`, `User.php`, or `[C]` edits; zero `Illuminate\Http` imports in `app/Domain` | Scope boundary check, Git diff | `tests/Arch/AccountsArchTest.php`, `git diff --name-only` check |
| **AC-9**: ADR 0001 created, `ARCHITECTURE.md` updated, `composer check` passes | [`docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`](file:///c:/dev/getnow-auth/docs/adr/0001-passport-token-issuance-and-refresh-rotation.md), [`ARCHITECTURE.md`](file:///c:/dev/getnow-auth/ARCHITECTURE.md) | Verification of documentation and clean `composer check` execution |

---

### 10. Test Plan

#### 10.1 RSA Key Handling in Test Setup (`SEC1`)
To satisfy `SEC1` without committing `.key` files to Git, tests use an in-memory key generator function executed during test setup:
```php
function ensurePassportKeys(): void
{
    if (config('passport.private_key') && config('passport.public_key')) {
        return;
    }

    $res = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($res, $privateKey);
    $details = openssl_pkey_get_details($res);
    $publicKey = $details['key'];

    config([
        'passport.private_key' => $privateKey,
        'passport.public_key' => $publicKey,
    ]);
}
```
Called in `beforeEach()` of `PassportTokenIssuerTest.php` and `TokenEndpointsTest.php`.

#### 10.2 Domain Feature Tests: `tests/Feature/Tokens/PassportTokenIssuerTest.php`
- `test('token service provider binds token issuer interface to passport token issuer')`:
  Asserts `app(TokenIssuer::class)` returns an instance of `PassportTokenIssuer`.
- `test('issue mints a valid token pair and persists records in postgresql')`:
  Creates user via `User::factory()->create()`. Calls `TokenIssuer::issue($user)`.
  Asserts `accessToken`, `refreshToken`, `tokenType === 'Bearer'`, `expiresIn === 900`.
  Asserts database rows exist in `oauth_access_tokens` and `oauth_refresh_tokens` with `revoked === false`.
- `test('refresh rotates the refresh token and revokes consumed tokens in postgresql')`:
  Issues pair for user. Calls `TokenIssuer::refresh($pair->refreshToken)`.
  Asserts new pair returned with new access token and new refresh token.
  Asserts prior refresh token row in `oauth_refresh_tokens` has `revoked === true`.
  Asserts prior access token row in `oauth_access_tokens` has `revoked === true`.
  Asserts new tokens have `revoked === false`.
- `test('refresh rejects reused refresh token with authentication exception')`:
  Issues pair, refreshes once successfully. Calls `TokenIssuer::refresh($originalRefreshToken)` again.
  Expects `Illuminate\Auth\AuthenticationException` to be thrown.
- `test('refresh rejects expired refresh token with authentication exception')`:
  Manually alters `expires_at` of refresh token row in database to past date. Calls `refresh()`.
  Expects `AuthenticationException`.
- `test('refresh rejects malformed and tampered refresh tokens with authentication exception')`:
  Calls `refresh('not-a-valid-token')`. Expects `AuthenticationException`.
- `test('revoke revokes current access token and its paired refresh token')`:
  Issues token pair. Emulates authenticated request by setting token on user. Calls `TokenIssuer::revoke($user)`.
  Asserts current access token and linked refresh token rows have `revoked === true`.
- `test('revoke handles null or transient token safely')`:
  Calls `TokenIssuer::revoke($user)` on user with no access token. Asserts no exception is thrown.
- `test('revokeAll revokes every access token and refresh token of user')`:
  Issues three separate token pairs for the same user. Calls `TokenIssuer::revokeAll($user)`.
  Asserts all three access tokens and all three refresh tokens have `revoked === true`.

#### 10.3 Endpoint Feature Tests: `tests/Feature/Tokens/TokenEndpointsTest.php`
- `test('post api v1 auth refresh rotates token pair successfully')`:
  Issues token pair for user. Calls `postJson('/api/v1/auth/refresh', ['refresh_token' => $pair->refreshToken])`.
  Asserts `HTTP 200` with JSON structure `['data' => ['tokens' => ['access_token', 'refresh_token', 'token_type', 'expires_in']]]`.
  Asserts new access token authenticates `getJson('/api/v1/auth/logout')` or protected endpoint.
- `test('post api v1 auth refresh fails with 401 when refresh token is reused')`:
  Calls endpoint with same refresh token twice. First call returns `200`, second returns `401 Unauthorized`.
- `test('post api v1 auth refresh fails with 401 on expired, revoked, or invalid token')`:
  Tests garbage token, expired token, and revoked token. All assert `HTTP 401`.
- `test('post api v1 auth refresh validates refresh token parameter')`:
  Calls endpoint with empty body. Asserts `HTTP 422 Unprocessable Entity` with validation error on `refresh_token`.
- `test('post api v1 auth logout revokes current session')`:
  Issues token pair. Calls `postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$pair->accessToken])`.
  Asserts `HTTP 204 No Content`.
  Subsequent request with same Bearer token returns `HTTP 401 Unauthorized`.
- `test('post api v1 auth logout leaves user other active sessions valid')`:
  Issues session A and session B for same user. Calls logout with session A.
  Session A becomes invalid (`401`), while session B remains valid (`200`).
- `test('post api v1 auth logout requires authentication')`:
  Calls `postJson('/api/v1/auth/logout')` without authorization header. Asserts `HTTP 401 Unauthorized`.
- `test('refresh and logout endpoints enforce rate limits')`:
  Calls refresh endpoint 11 times within 1 minute. The 11th request asserts `HTTP 429 Too Many Requests`.

---

### 11. Docs and Contract Updates

#### 11.1 ADR 0001 (`docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`)
Record decisions addressing open questions:
- **`OQ1` (Passport Grant & Issuance Architecture)**:
  - Context: Built-in Passport `PasswordGrant` requires plaintext credentials, violating `OWN4` zero-credential coupling. `PersonalAccessGrant` does not issue refresh tokens.
  - Decision: `PassportTokenIssuer` acts as an authenticated token factory for the first-party application. It mints signed OAuth2 JWT access tokens and encrypted refresh tokens matching League OAuth2 specifications, storing rows in `oauth_access_tokens` and `oauth_refresh_tokens`. In `refresh()`, it rotates tokens by atomically revoking the consumed pair in PostgreSQL.
- **`OQ2` (Refresh Token Delivery)**:
  - Decision: JSON response body delivery via `{ "data": { "tokens": { "access_token": "...", "refresh_token": "...", "token_type": "Bearer", "expires_in": 900 } } }`. Supports cross-platform clients (mobile app and single-page application).
- **`OQ3` (Token Lifetimes)**:
  - Decision: Configured via `config('passport.token_lifetimes')`: 900 seconds (15 minutes) for access tokens, 14 days for refresh tokens.
- **`OQ4` (Authorization Model)**:
  - Decision: Tokens carry empty/default scopes. Authorization is enforced strictly on the server by `UserPolicy` inspecting `User::$role`.

#### 11.2 `ARCHITECTURE.md` Section 11 Update
Update rows `OQ1`–`OQ4` in the Open Decisions table of [`ARCHITECTURE.md`](file:///c:/dev/getnow-auth/ARCHITECTURE.md) to indicate they are decided, pointing to `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`.

#### 11.3 Contract Seam Confirmation
The contract interface [`app/Domain/Accounts/Contracts/TokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Contracts/TokenIssuer.php) and data class [`app/Domain/Accounts/Data/TokenPairData.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Data/TokenPairData.php) remain completely unmodified.

---

### 12. Implementation Order

#### Checkpoint CP-A: Domain Engine, Service Provider, ADR, and Issuer Tests
1. Create and switch to branch `feat/tokens-issuer-and-endpoints` from `main`.
2. Create directory `docs/adr/` and write `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`.
3. Implement `app/Domain/Accounts/Tokens/PassportTokenIssuer.php`.
4. Implement `app/Providers/TokenServiceProvider.php`.
5. Register `App\Providers\TokenServiceProvider::class` in `bootstrap/providers.php`.
6. Implement `tests/Feature/Tokens/PassportTokenIssuerTest.php`.
7. Execute validation commands:
   - `composer lint`
   - `composer stan`
   - `composer test -- --filter=PassportTokenIssuerTest`
   - `composer check`
8. Git commit Checkpoint CP-A: `feat(tokens): implement passport token issuer and rotation engine`.

#### Checkpoint CP-B: HTTP Endpoints, Routing, Endpoint Tests, and Architecture Docs
9. Implement `app/Http/Requests/Auth/RefreshTokenRequest.php`.
10. Implement `app/Http/Controllers/Api/V1/Auth/TokenController.php`.
11. Populate `routes/api/tokens.php` with `/refresh` and `/logout` routes.
12. Update `ARCHITECTURE.md` Section 11 open decisions table.
13. Implement `tests/Feature/Tokens/TokenEndpointsTest.php`.
14. Execute step-specific checks:
    - `php artisan route:list --path=api/v1/auth`
    - Verify routes `api/v1/auth/refresh` and `api/v1/auth/logout` are properly registered.
    - Check for forbidden domain imports:
      `Get-ChildItem app/Domain -Recurse -Filter *.php | Select-String -SimpleMatch 'App\Http','Illuminate\Http'`
    - Verify modified files list:
      `git diff main...HEAD --name-only`
    - Full test suite execution:
      `composer check`
15. Git commit Checkpoint CP-B: `feat(tokens): add refresh and logout endpoints with rotation tests`.

---

### 13. Risks, Assumptions, Conflicts

#### Risks
1. **Defuse Crypto Key Mismatch**:
   - Risk: If the encrypter key changes between token issuance and refresh, decryption fails.
   - Mitigation: `Passport::tokenEncryptionKey(app('encrypter'))` resolves directly from `config('app.key')`. Decryption failure is trapped and cleanly returned as `AuthenticationException` (HTTP 401).
2. **Missing OAuth Client in Clean Environments**:
   - Risk: In a freshly migrated database where client seeders have not run, `oauth_clients` may be empty.
   - Mitigation: `PassportTokenIssuer::resolveClient()` queries for the first-party client and creates it automatically if missing.

#### Assumptions
1. Token lifetimes default to 900 seconds (access token) and 14 days (refresh token) as defined in `config/passport.php`.
2. Logout returns `HTTP 204 No Content` as there is no payload returned on session termination.
3. Authorization checks for protected endpoints rely on server policies, so access tokens require no custom scopes.

#### Conflicts
None identified. Scope boundaries between Users track (`[R]`) and Tokens track (`[A]`) are strictly preserved.

---

### 14. Dependencies

1. `laravel/passport` (`v13.10.0`) and `league/oauth2-server` (`v9.4.0`) in `vendor/`.
2. PostgreSQL test database configured in `phpunit.xml` (`DB_CONNECTION=pgsql`).
3. Existing migrations in `database/migrations/` creating `oauth_access_tokens`, `oauth_refresh_tokens`, and `oauth_clients`.
4. Contract interface [`app/Domain/Accounts/Contracts/TokenIssuer.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Contracts/TokenIssuer.php) and [`app/Domain/Accounts/Data/TokenPairData.php`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Data/TokenPairData.php).
5. User model [`app/Models/User.php`](file:///c:/dev/getnow-auth/app/Models/User.php) implementing `OAuthenticatable` and using `HasApiTokens`.
