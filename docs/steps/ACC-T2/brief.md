# ACC-T2 | BRIEF | Claude

Track: Tokens (Achintha — `ACC-T2`, branch `feat/tokens-issuer-and-endpoints`)

Goal: Implement `PassportTokenIssuer` (`issue`, `refresh` with strict one-time rotation,
  `revoke`, and `revokeAll`) against Laravel Passport v13.10, bind `TokenIssuer` and
  configure Passport lifetimes and rate limiters in `TokenServiceProvider`, build
  `RefreshTokenRequest` and `TokenController` for `POST /api/v1/auth/refresh` and
  `POST /api/v1/auth/logout` in `routes/api/tokens.php`, record ADR 0001 answering
  `OQ1`–`OQ4`, and verify all token flows with Pest feature tests against real Passport in
  PostgreSQL.

Depends on (verify exact signatures from code in Section 3):
  - `app/Domain/Accounts/Contracts/TokenIssuer.php` (`issue(User $user): TokenPairData`,
    `refresh(string $refreshToken): TokenPairData`, `revoke(User $user): void`,
    `revokeAll(User $user): void`)
  - `app/Domain/Accounts/Data/TokenPairData.php` (`#[MapName(SnakeCaseMapper::class)]`,
    `accessToken`, `refreshToken`, `tokenType`, `expiresIn`)
  - `app/Models/User.php` (implements `OAuthenticatable`, uses `HasApiTokens` from `ACC-T1`)
  - `config/auth.php` and `config/passport.php` (from `ACC-T1`)
  - Passport v13.10 migrations in `database/migrations/` (`oauth_access_tokens` with
    `char(80)` id and `uuid` `client_id`; `oauth_refresh_tokens` with `char(80)` id and
    `access_token_id`; `oauth_clients` with `uuid` id, `redirect_uris`, `grant_types`)
  - Installed `laravel/passport` (`v13.10.0`) and `league/oauth2-server` (`^9.4`) classes in
    `vendor/laravel/passport/src/` and `vendor/league/oauth2-server/src/` (inspect exact
    classes in Section 3 before designing `PassportTokenIssuer`)
  - `bootstrap/providers.php` and `routes/api.php`
  - `tests/Arch/AccountsArchTest.php`

In scope:
  Part A — Domain Token Issuer, Service Provider & ADR (CP-A):
  1. Inspect the installed `laravel/passport` v13.10 classes in `vendor/laravel/passport/src/`
     during Section 3 (`TOK2`) and implement
     `app/Domain/Accounts/Tokens/PassportTokenIssuer.php`
     (`final class PassportTokenIssuer implements TokenIssuer`):
     - `issue(User $user): TokenPairData`:
       Issues a real signed Passport OAuth2 JWT access token (`Bearer`, expiring in
       `config('passport.token_lifetimes.access_token_ttl_seconds', 900)`) AND a real
       Passport OAuth2 refresh token (expiring in
       `config('passport.token_lifetimes.refresh_token_ttl_days', 14)` days) persisted in
       Passport's `oauth_access_tokens` and `oauth_refresh_tokens` tables for `$user`,
       without requiring or reading the user's password or role (`OWN4`). Returns
       `TokenPairData(accessToken: ..., refreshToken: ..., tokenType: 'Bearer', expiresIn: ...)`.
     - `refresh(string $refreshToken): TokenPairData`:
       Validates the incoming refresh token (must be structurally valid, unrevoked, and
       unexpired, and its associated access token/user must be valid), **rotates** the
       refresh token on use (revoking both the consumed refresh token and its prior access
       token in PostgreSQL so any second attempt with the same refresh token fails per
       `TOK4`), and issues + returns a fresh `TokenPairData`. Throws an authentication
       exception (`Illuminate\Auth\AuthenticationException`) that renders cleanly as HTTP
       `401 Unauthorized` when the refresh token is malformed, expired, revoked, or reused.
     - `revoke(User $user): void`:
       Revokes the access token behind the current authenticated request (`$user->token()`)
       AND revokes any refresh tokens in `oauth_refresh_tokens` linked to that access token
       (`access_token_id`). If `$user->token()` is null or transient, handles cleanly
       (specify exact behavior in Sections 5 & 7).
     - `revokeAll(User $user): void`:
       Revokes every non-revoked Passport access token belonging to `$user` in
       `oauth_access_tokens` AND every associated refresh token in `oauth_refresh_tokens`
       (used when `UserService` deletes a user, changes a password, or changes a role per
       `ARCHITECTURE.md` Section 3 Rule 6 & Section 6).
  2. Implement `app/Providers/TokenServiceProvider.php` and register it in
     `bootstrap/providers.php`:
     - Binds `TokenIssuer::class` to `PassportTokenIssuer::class`.
     - Configures `Passport::tokensExpireIn(...)` and `Passport::refreshTokensExpireIn(...)`
       from `config/passport.php` (`TOK3`).
     - Configures named rate limiters for `token-refresh` and `token-logout` (`TOK5`).
  3. Create `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md` recording the
     decisions for `OQ1` (how `PassportTokenIssuer` issues and rotates OAuth2 access +
     refresh tokens with Passport v13.10 after `AuthService` verifies credentials), `OQ2`
     (JSON body delivery), `OQ3` (config-driven lifetimes: 900s access, 14d refresh), and
     `OQ4` (authorization enforced via `UserPolicy`, not token scopes).
  4. Add `tests/Feature/Tokens/PassportTokenIssuerTest.php` testing `issue()`, `refresh()`
     (including rotation and rejection of reused, expired, and revoked refresh tokens),
     `revoke()`, and `revokeAll()` directly against real Passport in PostgreSQL.

  Part B — HTTP Endpoints (`refresh`, `logout`), Routes & Endpoint Feature Tests (CP-B):
  5. Implement `app/Http/Requests/Auth/RefreshTokenRequest.php`:
     - Validates `refresh_token` (`required`, `string`).
  6. Implement `app/Http/Controllers/Api/V1/Auth/TokenController.php`:
     - `refresh(RefreshTokenRequest $request)`: calls
       `$this->tokenIssuer->refresh($request->validated('refresh_token'))` and returns the
       token pair JSON response (`HTTP 200`, or `HTTP 401` on invalid/expired/revoked/reused
       refresh token without leaking internal stack traces or raw tokens in logs).
     - `logout()`: resolves the authenticated `User` from the `auth:api` guard (`Auth::guard('api')->user()`),
       calls `$this->tokenIssuer->revoke($user)`, and returns `204 No Content` (or JSON
       success response; specify exact choice in Section 5).
  7. Populate `routes/api/tokens.php` (`[A]` — do not edit `routes/api.php` since `ACC-C0`
     already requires `routes/api/tokens.php` under `/api/v1`):
     - `POST /api/v1/auth/refresh` (throttled via `throttle:token-refresh`).
     - `POST /api/v1/auth/logout` (protected by `auth:api` and throttled via
       `throttle:token-logout`).
  8. Add `tests/Feature/Tokens/TokenEndpointsTest.php` covering:
     - `POST /api/v1/auth/refresh` happy path: returns a new access token and a rotated
       refresh token, and the new access token authenticates `auth:api` requests.
     - Refresh token rotation enforcement (`TOK4`): using the same refresh token a second
       time returns `401 Unauthorized`.
     - Expired refresh token, revoked refresh token (after `revoke` or `revokeAll`), and
       tampered/garbage refresh token all return `401 Unauthorized`.
     - Validation failure on missing/empty `refresh_token` returns `422 Unprocessable Entity`.
     - `POST /api/v1/auth/logout` happy path: revokes the current access token and its
       refresh token; subsequent requests with that access token or refresh token return
       `401 Unauthorized`, while a second active session token for the same user remains
       valid (whereas `revokeAll` invalidates all sessions).
     - Unauthenticated `POST /api/v1/auth/logout` returns `401 Unauthorized`.
     - Rate limiting (`TOK5`): exceeding the throttle limit on `POST /api/v1/auth/refresh`
       and `POST /api/v1/auth/logout` returns `429 Too Many Requests`.
  9. Update `ARCHITECTURE.md` Section 11 open decisions table (`OQ1`–`OQ4`) to reference
     `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`.

Out of scope:
  - Editing any `[R]` file owned by Rathu (`app/Models/User.php`, `AuthController`,
    `UserController`, `AuthService`, `UserService`, `UserRepository`, `UserPolicy`,
    `UserData`, `LoginRequest`, `RegisterRequest`, `routes/api/users.php`,
    `FakeTokenIssuer`, `tests/Feature/Accounts/*`).
  - Modifying `TokenIssuer`, `TokenPairData`, or `routes/api.php` (`[C]` contract files).
  - Building `/auth/login`, `/auth/register`, or `/users` CRUD endpoints (Users track).

Read first:
  - `AGENTS.md` (all sections).
  - `ARCHITECTURE.md` (all sections, especially 4, 5, 6, 7, 10, 11).
  - Inspect existing code in the repository:
    * `app/Domain/Accounts/Contracts/TokenIssuer.php`
    * `app/Domain/Accounts/Data/TokenPairData.php`
    * `app/Models/User.php`
    * `config/auth.php` and `config/passport.php`
    * `database/migrations/2026_10_19_102055_create_oauth_access_tokens_table.php`
    * `database/migrations/2026_10_19_102056_create_oauth_refresh_tokens_table.php`
    * `database/migrations/2026_10_19_102057_create_oauth_clients_table.php`
    * `bootstrap/providers.php` and `routes/api.php`
    * `tests/Arch/AccountsArchTest.php`
    * `vendor/laravel/passport/src/` (inspect how Passport v13.10's `ClientRepository`,
      `TokenRepository`, `RefreshTokenRepository`, `Passport`, `Bridge\AccessTokenRepository`,
      `Bridge\RefreshTokenRepository`, and `TokenGuard` work).

Decisions made (do not re-decide):
  D-a Zero Credential/Role Coupling in `PassportTokenIssuer` (`OWN4`):
      `PassportTokenIssuer` receives `User $user` after `AuthService` has already verified
      credentials. It must never read `password` or `role`, and it must never query `users`
      by email/password.
  D-b Real OAuth2 Access + Refresh Tokens with Rotation (`TOK1`, `TOK2`, `TOK4`):
      Both access tokens and refresh tokens must be backed by Passport's
      `oauth_access_tokens` and `oauth_refresh_tokens` tables so `auth:api` (`passport`
      guard) authenticates the JWT access token and `refresh()` rotates the refresh token
      and revokes the old pair.
  D-c JSON Response Format (`ARCHITECTURE.md` Section 7, `OQ2`):
      `POST /api/v1/auth/refresh` returns JSON with the `tokens` key (`TokenPairData::toArray()`
      outputs `access_token`, `refresh_token`, `token_type`, `expires_in` via `SnakeCaseMapper`):
      `{ "data": { "tokens": { "access_token": "...", "refresh_token": "...", "token_type": "Bearer", "expires_in": 900 } } }`.
  D-d `revoke(User $user)` vs `revokeAll(User $user)`:
      `revoke($user)` (logout) revokes the current session's access token (`$user->token()`)
      and its associated refresh token (`oauth_refresh_tokens.access_token_id`).
      `revokeAll($user)` revokes all access tokens and all refresh tokens belonging to
      `$user` across all devices/sessions.
  D-e No `Illuminate\Http` Inside `app/Domain/` (`L2`):
      `PassportTokenIssuer` lives in `app/Domain/Accounts/Tokens/` and must not import
      `Illuminate\Http\*` or `App\Http\*`.

Open decisions:
  None.

Acceptance criteria:
  AC-1  `TokenServiceProvider` binds `TokenIssuer::class` to `PassportTokenIssuer::class`,
        configures Passport lifetimes from config, and registers rate limiters for
        `refresh` and `logout`.
  AC-2  `PassportTokenIssuer::issue(User $user)` returns a `TokenPairData` with a valid
        Bearer `accessToken`, `refreshToken`, `tokenType: 'Bearer'`, and configured
        `expiresIn` (`900`), and the issued `accessToken` authenticates requests against
        the `auth:api` guard.
  AC-3  `PassportTokenIssuer::refresh(string $refreshToken)` and `POST /api/v1/auth/refresh`
        issue a new `TokenPairData` and atomically revoke the consumed refresh token and
        its prior access token (`TOK4`).
  AC-4  Reusing an already-consumed refresh token, or passing an expired, revoked, or
        malformed refresh token to `POST /api/v1/auth/refresh`, fails with HTTP `401
        Unauthorized`.
  AC-5  `POST /api/v1/auth/logout` (authenticated via `auth:api`) calls
        `TokenIssuer::revoke($user)` and invalidates both the current access token and its
        paired refresh token without revoking other active tokens of the same user.
  AC-6  `PassportTokenIssuer::revokeAll(User $user)` revokes every access token and every
        refresh token belonging to `$user`, causing all prior access and refresh tokens for
        that user to fail with `401`.
  AC-7  `POST /api/v1/auth/refresh` and `POST /api/v1/auth/logout` are throttled and return
        HTTP `429 Too Many Requests` when limits are exceeded (`TOK5`).
  AC-8  No `[R]` file, `app/Models/User.php`, or `[C]` contract file is modified in this
        branch (`OWN1`, `OWN2`, `OWN3`), and `app/Domain` has zero imports from
        `App\Http` or `Illuminate\Http` (`L2`).
  AC-9  `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md` and
        `ARCHITECTURE.md` Section 11 are updated, `tests/Arch` passes, and `composer check`
        passes with zero errors.

Rule IDs to evaluate in Section 8 (Rule compliance matrix):
  OWN1, OWN2, OWN3, OWN4, L1, L2, C1, C2, C3, C4, TOK1, TOK2, TOK3, TOK4, TOK5, DB1, DB2,
  SEC1, SEC2, T1, E1.

Known traps:
  1. Passport v13.10 `oauth_clients` schema: In Passport v13.10, `oauth_clients` uses a
     `uuid` primary key, `redirect_uris`, and `grant_types` (not the legacy v11/v12
     `personal_access_client` / `password_client` boolean columns). Inspect
     `vendor/laravel/passport/src/ClientRepository.php` and `Client.php` in Section 3!
  2. `User::createToken()` trap: Passport's `$user->createToken()` creates a Personal
     Access Token without a refresh token. Inspect `vendor/laravel/passport/src/` in
     Section 3 to see how Passport v13.10 constructs and signs OAuth2 access + refresh
     tokens (or how `PassportTokenIssuer` mints and rotates both tokens cleanly).
  3. RSA keys in test environment (`SEC1`): Tests in `tests/Feature/Tokens/` use real
     Passport (`T1`), which requires RSA private/public keys to sign and verify JWT access
     tokens, while `SEC1` forbids committing `storage/oauth-*.key` files. In Section 5 &
     Section 10, specify how RSA keys are provided during tests (e.g., generating ephemeral
     keys in memory / `Passport::loadKeysFrom(...)` or `config(['passport.private_key' => ..., 'passport.public_key' => ...])`
     in test setup if `storage/oauth-*.key` files are absent, so CI and fresh clones pass
     `composer test` without committed `.key` files).
  4. Transient token trap when testing `logout`: Do not use `Passport::actingAs($user)`
     to test `logout`, because `actingAs` assigns a `TransientToken` with no database row.
     Authenticate with a real Bearer token from `TokenIssuer::issue($user)` in the
     `Authorization: Bearer <accessToken>` header (`T1`).
  5. `Domain` importing `Illuminate\Http` (`L2`): Never import `Illuminate\Http\Request`
     in `PassportTokenIssuer` or `TokenController::logout` if it violates arch rules; check
     `tests/Arch/AccountsArchTest.php` in Section 3.

Size guard and checkpoints:
  Expected 8 to 11 files. Gate implementation into 2 checkpoints in Section 12:
  - CP-A (Commits 1–2): `PassportTokenIssuer`, `TokenServiceProvider`,
    `bootstrap/providers.php`, ADR 0001, and `PassportTokenIssuerTest` (`composer check`
    green).
  - CP-B (Commits 3–4): `RefreshTokenRequest`, `TokenController`, `routes/api/tokens.php`,
    `TokenEndpointsTest`, and `ARCHITECTURE.md` update (`composer check` green, full
    `IMPL-REPORT`).

Step-specific checks (Layer A extras):
  php artisan route:list --path=api/v1/auth
  git diff main...HEAD --name-only
  Get-ChildItem app/Domain -Recurse -Filter *.php | Select-String -SimpleMatch 'App\Http','Illuminate\Http'
