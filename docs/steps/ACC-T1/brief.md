# ACC-T1 | BRIEF | Claude

**Track**: Tokens (Achintha — `ACC-T1`, branch `feat/tokens-passport-install`)

**Goal**: Install and configure `laravel/passport`, publish its configuration and database
migrations, configure the `api` authentication guard in `config/auth.php` to use
`passport`, add `Laravel\Passport\HasApiTokens` to `app/Models/User.php` (the single
dedicated edit permitted by `ARCHITECTURE.md` Section 5), and append Passport environment
variables (including configurable token lifetimes) to `.env.example` so the shared files
(`composer.json`, `composer.lock`, `app/Models/User.php`, `config/auth.php`) merge cleanly
before parallel track development begins.

## Depends on
- `composer.json` (verify whether `composer lint`, `composer fix`, `composer stan`,
  `composer test`, `composer check`, `spatie/laravel-data`, and `laravel/passport` exist)
- `app/Domain/Accounts/Contracts/TokenIssuer.php` and
  `app/Domain/Accounts/Data/TokenPairData.php` (verify whether the `ACC-C0` contract PR
  has already been merged; if missing, report in Section 13 so we know whether to
  include or wait for `ACC-C0`)
- `app/Models/User.php`
- `config/auth.php`
- `database/migrations/`
- `.gitignore` and `.env.example`
- `phpunit.xml` (verify tests are configured for PostgreSQL, never SQLite)
- `tests/Arch/` (if present)

## In scope
1. Require `laravel/passport` via Composer (`composer require laravel/passport --no-interaction`)
   if not yet installed in `composer.json`.
2. Publish Passport config (`config/passport.php`) and Passport database migrations into
   `database/migrations/` (without editing any existing Users track migrations).
3. Add configurable access and refresh token lifetime keys to `config/passport.php` and
   `.env.example` (under `# Tokens (Achintha)`):
   - `PASSPORT_ACCESS_TOKEN_TTL_SECONDS=900` (15 minutes)
   - `PASSPORT_REFRESH_TOKEN_TTL_DAYS=14` (14 days)
   - `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` placeholders in `.env.example`.
4. Verify `.gitignore` ignores `/storage/*.key` so `oauth-private.key` and
   `oauth-public.key` can never be committed (`SEC1`).
5. Update `config/auth.php` so the `api` guard uses `'driver' => 'passport'` and
   `'provider' => 'users'`.
6. Add `use Laravel\Passport\HasApiTokens;` to `app/Models/User.php` without modifying
   any other logic, casts, or fields owned by Rathu (`OWN3`).
7. Add a smoke test in `tests/Feature/Tokens/PassportSetupTest.php` verifying that:
   - `config('auth.guards.api.driver')` is `'passport'`,
   - `User` uses `Laravel\Passport\HasApiTokens`, and
   - Passport's OAuth tables exist in the PostgreSQL test database after migrations.

## Out of scope
- Implementing `PassportTokenIssuer`, `TokenServiceProvider`, `TokenController`,
  `RefreshTokenRequest`, or `routes/api/tokens.php` (those belong to `ACC-T2` after this
  foundational PR merges).
- Editing any `[R]` file owned by Rathu (`AuthService`, `UserService`, `UserController`,
  `AuthController`, `UserRepository`, `UserPolicy`, `UserData`, `UserFactory`,
  `SuperadminSeeder`, `tests/Feature/Accounts/*`).
- Modifying `TokenIssuer` or `TokenPairData` (`[C]` files) without an explicit Contract PR.

## Read first
- `AGENTS.md` (all sections, especially 'First thing every session: identify the track',
  'Environment', 'Token rules', 'Database rules', 'Security', 'Git').
- `ARCHITECTURE.md` sections 1, 2, 5, 6, 9, 10, 11.
- Inspect `composer.json`, `app/Models/User.php`, `config/auth.php`, `.gitignore`,
  `.env.example`, `phpunit.xml`, and `database/migrations/`.

## Decisions made (do not re-decide)
- **D-a Dedicated Bootstrap PR (`ARCHITECTURE.md` Section 5 & 10)**:
  This step isolates `composer.json` / `composer.lock` (Passport install),
  `config/auth.php`, and the one-time `HasApiTokens` trait addition on
  `app/Models/User.php` into a minimal PR so Rathu and Achintha never conflict on
  `User.php` or `composer.lock` during `ACC-T2` and `ACC-U*`.
- **D-b Token Lifetimes in Config (`TOK3`, `OQ3`)**:
  Access token lifetime defaults to `900` seconds (15 minutes, matching the
  `expires_in: 900` contract in `ARCHITECTURE.md` Section 7) and refresh token lifetime
  defaults to `14` days, read from `config/passport.php` via environment variables.
- **D-c Key Security (`SEC1`)**:
  RSA keys (`storage/oauth-private.key`, `storage/oauth-public.key`) are generated
  locally via `php artisan passport:keys` (or loaded from env in testing) and must
  never be tracked by Git.

## Open decisions
None.

## Acceptance criteria
- **AC-1**: `laravel/passport` is installed in `composer.json` and `composer.lock`, and its
  migrations run cleanly on PostgreSQL (`php artisan migrate`).
- **AC-2**: `app/Models/User.php` uses `Laravel\Passport\HasApiTokens` with `declare(strict_types=1);`
  and zero other modifications to Rathu's model code (`OWN3`).
- **AC-3**: `config/auth.php` defines the `api` guard with `'driver' => 'passport'` and
  `'provider' => 'users'`.
- **AC-4**: `config/passport.php` and `.env.example` (under `# Tokens (Achintha)`) define
  configurable access and refresh token lifetimes (`900` seconds and `14` days by
  default).
- **AC-5**: `.gitignore` excludes `storage/*.key` (`oauth-private.key` and `oauth-public.key`),
  and `git status` shows zero `.key` files tracked (`SEC1`).
- **AC-6**: `tests/Feature/Tokens/PassportSetupTest.php` passes against the PostgreSQL test
  database (`DB1`), and `composer check` passes with zero errors (`C4`).

## Rule IDs to evaluate in Section 8 (Rule compliance matrix)
`OWN1`, `OWN2`, `OWN3`, `OWN4`, `L1`, `L2`, `C1`, `C2`, `C3`, `C4`, `TOK1`, `TOK2`, `TOK3`, `TOK4`, `TOK5`, `DB1`, `DB2`, `SEC1`, `SEC2`, `T1`, `E1`.

## Known traps
1. `php artisan passport:install` vs migrations: Do not commit generated client secrets
   or `storage/oauth-*.key` files. Use `vendor:publish` for `passport-config` and
   `passport-migrations` as needed, and ensure `storage/*.key` is ignored by Git.
2. Editing `app/Models/User.php`: Only add `HasApiTokens` (and `declare(strict_types=1);`
   if not already present). Do not add role methods, casts, or relations that belong to
   Rathu's Users track (`[R]`).
3. SQLite in `phpunit.xml`: Verify `phpunit.xml` uses `pgsql` (`DB1`), never `sqlite`
   `:memory:`.

## Size guard
Expected 6 to 10 files (including published Passport config and migrations).

## Step-specific checks (Layer A extras)
- `php artisan migrate:status`
- `git status --short`
- `git diff main...HEAD -- app/Models/User.php`
