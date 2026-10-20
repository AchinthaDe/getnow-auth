# [ACC-T1 | PLAN v1 | Gemini]

### 1. Understanding

The objective of step `ACC-T1` is to install and configure `laravel/passport` on branch `feat/tokens-passport-install`. This step is owned by Achintha under the Tokens track (`[A]`) and serves as the foundation PR required before parallel track development starts.

Scope includes:
- Installing `laravel/passport` via Composer (`composer require laravel/passport --no-interaction`).
- Publishing Passport configuration (`config/passport.php`) and database migrations into `database/migrations/`.
- Configuring the `api` authentication guard in `config/auth.php` to use the `passport` driver with the `users` provider.
- Adding configurable access and refresh token lifetime keys to `config/passport.php` and appending corresponding environment variables to `.env.example` and `.env` under `# Tokens (Achintha)`:
  - `PASSPORT_ACCESS_TOKEN_TTL_SECONDS=900` (15 minutes)
  - `PASSPORT_REFRESH_TOKEN_TTL_DAYS=14` (14 days)
  - `PASSPORT_PRIVATE_KEY=`
  - `PASSPORT_PUBLIC_KEY=`
- Verifying `.gitignore` excludes `/storage/*.key` so RSA keys (`storage/oauth-private.key` and `storage/oauth-public.key`) are never committed (`SEC1`).
- Adding `Laravel\Passport\HasApiTokens` to `app/Models/User.php` as the single dedicated edit permitted by `ARCHITECTURE.md` Section 5 (`OWN3`).
- Authoring a smoke test suite in `tests/Feature/Tokens/PassportSetupTest.php` verifying guard configuration, trait inclusion, token lifetime settings, and the presence of Passport's OAuth tables in the PostgreSQL test database.

Explicitly out of scope:
- Implementing `PassportTokenIssuer`, `TokenServiceProvider`, `TokenController`, `RefreshTokenRequest`, or `routes/api/tokens.php` (reserved for step `ACC-T2`).
- Modifying any file owned by Rathu (`[R]`): `AuthService`, `UserService`, `UserController`, `AuthController`, `UserRepository`, `UserPolicy`, `UserData`, `UserFactory`, `SuperadminSeeder`, or `tests/Feature/Accounts/*`.
- Modifying contract seam files (`[C]`): `app/Domain/Accounts/Contracts/TokenIssuer.php` and `app/Domain/Accounts/Data/TokenPairData.php`.
- Generating production OAuth keys or committing client credentials.

---

### 2. Sources read

The following files and line ranges were read in full prior to drafting:
- `AGENTS.md` (lines 1–160): Track identification, ownership protocol, PowerShell environment, database rules, token rules, coding standards.
- `ARCHITECTURE.md` (lines 1–275): System architecture, track separation, shared files rules, TokenIssuer seam, endpoints, token lifetime decisions, open decisions.
- `composer.json` (lines 1–111): Package dependencies, scripts (`lint`, `fix`, `stan`, `test`, `check`, `ci:check`), autoloading.
- `app/Models/User.php` (lines 1–65): User authenticatable model, casts, traits, initials method.
- `config/auth.php` (lines 1–120): Guard definitions, user providers, password reset settings.
- `.gitignore` (lines 1–40): Git ignore patterns including `/storage/*.key`.
- `.env.example` (lines 1–66): Default environment variable declarations.
- `.env` (lines 1–66): Local environment configuration with PostgreSQL credentials.
- `phpunit.xml` (lines 1–35): Test suite configuration and PostgreSQL test database environment variables.
- `tests/Arch/AccountsArchTest.php` (lines 1–40): Pest architecture assertions enforcing strict types, domain isolation, and Passport boundary.
- `app/Domain/Accounts/Contracts/TokenIssuer.php` (lines 1–29): Contract seam interface with four token management methods.
- `app/Domain/Accounts/Data/TokenPairData.php` (lines 1–21): Contract seam DTO extending Spatie Data with SnakeCaseMapper.
- `docs/steps/ACC-C0/report.md` (lines 1–533): Implementation report of the completed ACC-C0 contract foundation step.
- `docs/steps/ACC-C0/plan.md` (lines 1–400): Approved contract foundation plan structure and conventions.

---

### 3. Verified current state

File and environment inspection verified:
- Git status and branch:
  - Current working branch is `feat/contract-foundation-and-seam`.
  - Working tree is clean (0 modified files, 0 untracked files).
  - Git status confirms zero `.key` files are tracked.
- Dependencies and toolchain in `composer.json`:
  - `spatie/laravel-data` is installed at `^4.23`.
  - `larastan/larastan` is installed at `^3.9`.
  - `laravel/pint` is installed at `^1.27`.
  - `pestphp/pest` is installed at `^5.3`.
  - `pestphp/pest-plugin-arch` is installed at `^5.0`.
  - Scripts `lint`, `fix`, `stan`, `test`, `check`, and `ci:check` exist and are functioning.
  - Command `composer check` passes with 0 Pint errors, 0 PHPStan errors, and 36 passing tests.
  - Package `laravel/passport` is not yet installed in `composer.json`.
  - Package dry-run resolution confirms `laravel/passport` resolves to version `^13.10` (`v13.10.0`).
- Runtime environment:
  - Native PHP runtime is `PHP 8.5.9`.
  - Framework version is Laravel Framework `13.37.0`.
  - Native PHP configuration in `C:\tools\php85\php.ini` has `;extension=sodium` commented out at line 947. Package `lcobucci/jwt` `^5.6` (required by `league/oauth2-server` `^9.4` and `laravel/passport` `^13.10`) requires extension `ext-sodium`. Testing confirms dynamic loading via `php -d extension=sodium` loads module `sodium` and resolves all 12 package dependencies.
- Contract seam state:
  - Interface `TokenIssuer` is present and declares methods `issue`, `refresh`, `revoke`, and `revokeAll`.
  - Class `TokenPairData` is present and declares constructor properties `accessToken`, `refreshToken`, `tokenType`, `expiresIn` mapped to snake_case.
  - Contract step `ACC-C0` is completed.
- User model state:
  - `app/Models/User.php` contains `declare(strict_types=1);`.
  - `User` imports and uses traits `HasFactory`, `Notifiable`, `PasskeyAuthenticatable`, `TwoFactorAuthenticatable`.
  - `User` does not import or use `Laravel\Passport\HasApiTokens`.
- Authentication configuration:
  - `config/auth.php` defines only guard `'web'` (`session` driver). Guard `'api'` is not defined.
- Key security:
  - `.gitignore` line 7 specifies `/storage/*.key`.
  - Directory `storage/` contains no `.key` files.
- Environment files:
  - `.env.example` ends at line 66 (`VITE_APP_NAME`). Contains zero Passport variables.
  - `.env` contains local PostgreSQL database credentials (`DB_DATABASE=getnow_auth`, `DB_USERNAME=postgres`, `DB_PASSWORD=postgres`).
- Testing configuration:
  - `phpunit.xml` line 26 specifies `<env name="DB_CONNECTION" value="pgsql"/>`. SQLite is absent.

---

### 4. File manifest

| File | Operation | Track | Description |
|---|---|---|---|
| `composer.json` | Modify | `[A]` (Shared bootstrap) | Add `"laravel/passport": "^13.10"` to `require`. |
| `composer.lock` | Modify | `[A]` (Shared bootstrap) | Synchronized by Composer during package installation. |
| `config/auth.php` | Modify | `[A]` (Shared bootstrap) | Add `api` guard definition with driver `passport` and provider `users`. |
| `config/passport.php` | Create | `[A]` | Published Passport configuration, extended with token lifetime configuration keys. |
| `database/migrations/0001_01_01_000003_create_oauth_auth_codes_table.php` | Create | `[A]` | Published Passport migration creating table `oauth_auth_codes`. |
| `database/migrations/0001_01_01_000004_create_oauth_access_tokens_table.php` | Create | `[A]` | Published Passport migration creating table `oauth_access_tokens`. |
| `database/migrations/0001_01_01_000005_create_oauth_refresh_tokens_table.php` | Create | `[A]` | Published Passport migration creating table `oauth_refresh_tokens`. |
| `database/migrations/0001_01_01_000006_create_oauth_clients_table.php` | Create | `[A]` | Published Passport migration creating table `oauth_clients`. |
| `database/migrations/0001_01_01_000007_create_oauth_personal_access_clients_table.php` | Create | `[A]` | Published Passport migration creating table `oauth_personal_access_clients`. |
| `app/Models/User.php` | Modify | `[A]` (Shared bootstrap) | Add `use Laravel\Passport\HasApiTokens;` import and trait usage without altering any other logic. |
| `.env.example` | Modify | `[A]` (Shared bootstrap) | Append `# Tokens (Achintha)` block declaring token lifetimes and key placeholders. |
| `.env` | Modify | `[A]` (Local dev environment) | Append `# Tokens (Achintha)` block declaring token lifetimes and key placeholders. |
| `tests/Feature/Tokens/PassportSetupTest.php` | Create | `[A]` | Feature smoke test verifying guard settings, trait integration, token lifetime config, and database tables. |

---

### 5. Interface specs

#### 5.1 Authentication Guard Specification
File: `config/auth.php`
Target block within `'guards'`:
```php
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],
```

#### 5.2 User Model Trait Specification
File: `app/Models/User.php`
Import addition:
```php
use Laravel\Passport\HasApiTokens;
```
Trait usage addition within `User`:
```php
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;
```

#### 5.3 Passport Configuration and Lifetime Specification
File: `config/passport.php`
Header enforcement:
```php
<?php

declare(strict_types=1);
```
Lifetime configuration block appended to returned array:
```php
    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes (Achintha)
    |--------------------------------------------------------------------------
    |
    | Access tokens are short-lived, refresh tokens longer. Lifetimes live in
    | configuration, not in code.
    |
    */

    'token_lifetimes' => [
        'access_token_ttl_seconds' => (int) env('PASSPORT_ACCESS_TOKEN_TTL_SECONDS', 900),
        'refresh_token_ttl_days' => (int) env('PASSPORT_REFRESH_TOKEN_TTL_DAYS', 14),
    ],

    'access_token_ttl' => (int) env('PASSPORT_ACCESS_TOKEN_TTL_SECONDS', 900),

    'refresh_token_ttl' => (int) env('PASSPORT_REFRESH_TOKEN_TTL_DAYS', 14),
```

#### 5.4 Environment Variables Specification
File: `.env.example` and `.env`
Appended block:
```env
# Tokens (Achintha)
PASSPORT_ACCESS_TOKEN_TTL_SECONDS=900
PASSPORT_REFRESH_TOKEN_TTL_DAYS=14
PASSPORT_PRIVATE_KEY=
PASSPORT_PUBLIC_KEY=
```

---

### 6. Data and migrations

- Published Passport Migrations:
  Published into `database/migrations/` using `php artisan vendor:publish --tag=passport-migrations`.
  Tables created:
  1. `oauth_auth_codes`:
     - Columns: `id` (varchar 100, PK), `user_id` (bigint, indexed), `client_id` (uuid or bigint), `scopes` (text, nullable), `revoked` (boolean), `expires_at` (timestamp, nullable).
  2. `oauth_access_tokens`:
     - Columns: `id` (varchar 100, PK), `user_id` (bigint, indexed, nullable), `client_id` (uuid or bigint), `name` (varchar, nullable), `scopes` (text, nullable), `revoked` (boolean), `created_at` (timestamp), `updated_at` (timestamp), `expires_at` (timestamp, nullable).
  3. `oauth_refresh_tokens`:
     - Columns: `id` (varchar 100, PK), `access_token_id` (varchar 100, indexed), `revoked` (boolean), `expires_at` (timestamp, nullable).
  4. `oauth_clients`:
     - Columns: `id` (uuid or bigint, PK), `user_id` (bigint, indexed, nullable), `name` (varchar), `secret` (varchar 100, nullable), `provider` (varchar, nullable), `redirect` (text), `personal_access_client` (boolean), `password_client` (boolean), `revoked` (boolean), `created_at` (timestamp), `updated_at` (timestamp).
  5. `oauth_personal_access_clients`:
     - Columns: `id` (bigint, PK), `client_id` (uuid or bigint), `created_at` (timestamp), `updated_at` (timestamp).
- Existing Migrations Integrity:
  The 5 existing migrations in `database/migrations/` (`0001_01_01_000000_create_users_table.php`, `0001_01_01_000001_create_cache_table.php`, `0001_01_01_000002_create_jobs_table.php`, `2024_01_01_000000_create_passkeys_table.php`, `2025_08_14_170933_add_two_factor_columns_to_users_table.php`) remain unaltered.

---

### 7. Behavior and flows

- Package Bootstrap:
  During `composer require laravel/passport`, Composer registers `Laravel\Passport\PassportServiceProvider` in package discovery.
- Authentication Guard Resolution:
  1. An incoming API request requiring authentication hits middleware `auth:api`.
  2. Laravel consults `config/auth.php`, resolving `'api'` guard configuration (`'driver' => 'passport'`, `'provider' => 'users'`).
  3. Passport's `TokenGuard` validates the incoming Bearer token using OAuth public key verification against `oauth_access_tokens`.
  4. Upon successful validation, the authenticated `User` entity is bound to the request.
- Model Token Capabilities:
  Trait `Laravel\Passport\HasApiTokens` provides the `User` instance with methods `tokens()`, `token()`, and `withAccessToken()`, enabling token binding and inspection without modifying Rathu's user fields or business methods.
- Lifetime Configuration Flow:
  Token issuance in subsequent steps reads token durations from `config('passport.token_lifetimes.access_token_ttl_seconds')` and `config('passport.token_lifetimes.refresh_token_ttl_days')`. In the absence of environment overrides, defaults evaluate to 900 seconds (15 minutes) and 14 days.

---

### 8. Rule compliance matrix

| Rule ID | Rule Summary | Compliance Plan |
|---|---|---|
| `OWN1` | Track file ownership | Only files designated under Achintha (`[A]`) or shared bootstrap files permitted by `ARCHITECTURE.md` Section 5 (`composer.json`, `composer.lock`, `config/auth.php`, `app/Models/User.php`, `.env.example`) are modified. Zero `[R]` files are touched. |
| `OWN2` | Contract files only changed in contract PR | `TokenIssuer.php` and `TokenPairData.php` remain completely untouched. |
| `OWN3` | `User.php` ownership rule | Achintha performs the single dedicated edit to `app/Models/User.php`: adding `use Laravel\Passport\HasApiTokens;` import and trait usage. Zero methods, attributes, casts, or relationships owned by Rathu are touched. |
| `OWN4` | Tokens track isolation | No credential validation, user role checks, or user table queries are implemented. |
| `L1` | Controller thinness & layering | No controllers or HTTP request handlers are created in this step. |
| `L2` | Domain never imports Http | No domain classes are created in this step. |
| `C1` | `declare(strict_types=1);` in every file | All created or modified PHP files (`config/auth.php`, `config/passport.php`, `app/Models/User.php`, `tests/Feature/Tokens/PassportSetupTest.php`) declare strict types. |
| `C2` | Full typing, prefer `final` | All test closures, functions, and configuration returns follow strict type declarations. |
| `C3` | `spatie/laravel-data` DTOs | Existing DTOs continue using `spatie/laravel-data`. No non-data transfers are introduced. |
| `C4` | Pint and Larastan zero errors | `composer check` (`pint --test`, `phpstan analyse`, `pest`) executes with zero errors. No baseline entries are created. |
| `TOK1` | Implement `TokenIssuer` in `PassportTokenIssuer` | Foundation laid via Passport installation and guard configuration. Implementation deferred to `ACC-T2`. |
| `TOK2` | Check installed Passport version documentation before choosing grant | Passport version locked to `v13.10.0`. Grant architecture decision (OQ1) documented in Section 11 for `ACC-T2`. |
| `TOK3` | Token lifetimes live in config | Lifetimes defined in `config/passport.php` and bound to `PASSPORT_ACCESS_TOKEN_TTL_SECONDS` (900) and `PASSPORT_REFRESH_TOKEN_TTL_DAYS` (14). |
| `TOK4` | Refresh token rotation | Database migration creates `oauth_refresh_tokens` table supporting token rotation in `ACC-T2`. |
| `TOK5` | Throttled refresh and logout covered by tests | Foundation established. Endpoint throttling and feature tests implemented in `ACC-T2`. |
| `DB1` | PostgreSQL only, never SQLite | All migrations and smoke tests run against native PostgreSQL database `getnow_auth` via `phpunit.xml`. SQLite is not used. |
| `DB2` | Migrations additive, never edit merged migrations | Published Passport migrations are additive. Existing migrations are untouched. |
| `SEC1` | No secrets committed | `.gitignore` specifies `/storage/*.key`. Environment placeholders in `.env.example` contain empty values. Zero `.key` files are committed. |
| `SEC2` | Server-side authorization | Authentication guard configured at server level in `config/auth.php`. |
| `T1` | Pest tests | Author feature smoke test `tests/Feature/Tokens/PassportSetupTest.php` with deterministic assertions. |
| `E1` | Windows PowerShell environment | All CLI steps run individually in PowerShell without bash syntax or `&&` chaining operators. |

---

### 9. AC traceability

| AC ID | Acceptance Criteria | Satisfying Implementation Details |
|---|---|---|
| `AC-1` | `laravel/passport` installed in `composer.json` and `composer.lock`, migrations run cleanly on PostgreSQL | Execute `composer require laravel/passport --no-interaction`, publish migrations, execute `php artisan migrate`, verify via `php artisan migrate:status`. |
| `AC-2` | `app/Models/User.php` uses `Laravel\Passport\HasApiTokens` with `declare(strict_types=1);` and zero other modifications | Add `use Laravel\Passport\HasApiTokens;` import and trait usage to `app/Models/User.php`. Verified via `git diff main...HEAD -- app/Models/User.php`. |
| `AC-3` | `config/auth.php` defines the `api` guard with `'driver' => 'passport'` and `'provider' => 'users'` | Update `'guards'` array in `config/auth.php` to define `'api'`. Verified in `PassportSetupTest.php`. |
| `AC-4` | `config/passport.php` and `.env.example` define configurable access and refresh token lifetimes (`900` seconds and `14` days) | Add `token_lifetimes` keys to `config/passport.php` and append `PASSPORT_ACCESS_TOKEN_TTL_SECONDS=900` and `PASSPORT_REFRESH_TOKEN_TTL_DAYS=14` to `.env.example` and `.env`. |
| `AC-5` | `.gitignore` excludes `/storage/*.key`, zero `.key` files tracked | Verify `.gitignore` line 7 contains `/storage/*.key`. Check `git status --short` confirms zero `.key` files tracked. |
| `AC-6` | `tests/Feature/Tokens/PassportSetupTest.php` passes against PostgreSQL, `composer check` passes with zero errors | Implement `tests/Feature/Tokens/PassportSetupTest.php`. Execute `composer check` and confirm zero Pint, PHPStan, or Pest errors. |

---

### 10. Test plan

#### 10.1 Feature Smoke Test Suite
File: `tests/Feature/Tokens/PassportSetupTest.php`

- Test 1: Guard configuration verification
  - Description: Assert `auth.guards.api` driver and provider are configured correctly.
  - Assertions:
    ```php
    expect(config('auth.guards.api.driver'))->toBe('passport');
    expect(config('auth.guards.api.provider'))->toBe('users');
    ```
- Test 2: User model trait verification
  - Description: Assert `App\Models\User` uses `Laravel\Passport\HasApiTokens`.
  - Assertions:
    ```php
    $traits = class_uses_recursive(\App\Models\User::class);
    expect(in_array(\Laravel\Passport\HasApiTokens::class, $traits, true))->toBeTrue();
    ```
- Test 3: Token lifetime configuration verification
  - Description: Assert default access and refresh token lifetimes match architecture contract.
  - Assertions:
    ```php
    expect(config('passport.token_lifetimes.access_token_ttl_seconds'))->toBe(900);
    expect(config('passport.token_lifetimes.refresh_token_ttl_days'))->toBe(14);
    expect(config('passport.access_token_ttl'))->toBe(900);
    expect(config('passport.refresh_token_ttl'))->toBe(14);
    ```
- Test 4: Database table presence verification
  - Description: Assert PostgreSQL test database contains all required Passport OAuth tables.
  - Assertions:
    ```php
    expect(\Illuminate\Support\Facades\Schema::hasTable('oauth_auth_codes'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('oauth_access_tokens'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('oauth_refresh_tokens'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('oauth_clients'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('oauth_personal_access_clients'))->toBeTrue();
    ```
- Test 5: Key ignore security verification
  - Description: Assert `/storage/*.key` remains in `.gitignore`.
  - Assertions:
    ```php
    $gitignore = file_get_contents(base_path('.gitignore'));
    expect($gitignore)->toContain('/storage/*.key');
    ```

#### 10.2 Architecture Suite Regression Check
File: `tests/Arch/AccountsArchTest.php`
- Execute `tests/Arch/AccountsArchTest.php` to ensure strict types enforcement and domain isolation remain green with zero regressions.

---

### 11. Docs and contract updates

- `ARCHITECTURE.md`:
  No modifications required. Section 5, Section 9, and Section 10 already document the Passport installation and the dedicated edit to `app/Models/User.php`.
- `.env.example`:
  Append `# Tokens (Achintha)` section declaring `PASSPORT_ACCESS_TOKEN_TTL_SECONDS=900`, `PASSPORT_REFRESH_TOKEN_TTL_DAYS=14`, `PASSPORT_PRIVATE_KEY=`, and `PASSPORT_PUBLIC_KEY=`.
- `AGENTS.md`:
  No modifications required. Rules remain aligned.
- `docs/adr/`:
  ADR for OQ1 (grant selection for token issuance) will be authored in step `ACC-T2` when token issuer implementation begins.

---

### 12. Implementation order

1. **Branch checkout**:
   - Execute in PowerShell: `git checkout -b feat/tokens-passport-install`
2. **Environment extension verification**:
   - Verify native PHP configuration has `extension=sodium` enabled in `C:\tools\php85\php.ini` (uncomment line 947: `extension=sodium`).
3. **Require `laravel/passport`**:
   - Execute in PowerShell: `composer require laravel/passport --no-interaction`
4. **Publish Passport assets**:
   - Execute in PowerShell: `php artisan vendor:publish --tag=passport-config`
   - Execute in PowerShell: `php artisan vendor:publish --tag=passport-migrations`
5. **Configure token lifetimes**:
   - Update `config/passport.php` to prepend `declare(strict_types=1);` and append lifetime keys `token_lifetimes`, `access_token_ttl`, and `refresh_token_ttl`.
6. **Update environment files**:
   - Append `# Tokens (Achintha)` block to `.env.example`.
   - Append `# Tokens (Achintha)` block to `.env`.
7. **Configure API guard in `config/auth.php`**:
   - Add `'api'` guard configuration (`'driver' => 'passport'`, `'provider' => 'users'`) to `config/auth.php`.
8. **Add `HasApiTokens` to `app/Models/User.php`**:
   - Add `use Laravel\Passport\HasApiTokens;` import to `app/Models/User.php`.
   - Add `HasApiTokens` to the `use` statement in the `User` class.
9. **Execute migrations**:
   - Execute in PowerShell: `php artisan migrate`
10. **Author smoke test**:
    - Create `tests/Feature/Tokens/PassportSetupTest.php` implementing the 5 test cases from Section 10.
11. **Run quality and style checks**:
    - Execute in PowerShell: `composer fix`
    - Execute in PowerShell: `composer lint`
    - Execute in PowerShell: `composer stan`
    - Execute in PowerShell: `composer test`
    - Execute in PowerShell: `composer check`
12. **Run step-specific Layer A checks**:
    - Execute in PowerShell: `php artisan migrate:status`
    - Execute in PowerShell: `git status --short`
    - Execute in PowerShell: `git diff main...HEAD -- app/Models/User.php`

---

### 13. Risks, assumptions, conflicts

- Native PHP `ext-sodium` extension requirement:
  - Finding: Package `lcobucci/jwt` version `^5.6` (required by `league/oauth2-server` `^9.4` and `laravel/passport` `^13.10`) declares a requirement on `ext-sodium`. The native PHP runtime configuration file at `C:\tools\php85\php.ini` has `;extension=sodium` commented out at line 947.
  - Action: Uncomment `extension=sodium` in `C:\tools\php85\php.ini` prior to executing `composer require laravel/passport --no-interaction`.
- Branch base assumption:
  - Step `ACC-C0` contract commits currently reside on branch `feat/contract-foundation-and-seam`. Branch `feat/tokens-passport-install` must branch from the HEAD of `feat/contract-foundation-and-seam` (or from `main` once the `ACC-C0` PR is merged) so that contract files `TokenIssuer.php` and `TokenPairData.php` are present.
- Ambiguities and conflicts:
  - Zero conflicts exist between `AGENTS.md` and `ARCHITECTURE.md`.

---

### 14. Dependencies

- Preceding steps:
  - `ACC-C0`: Quality toolchain setup, PostgreSQL test configuration, strict typing, and contract seam creation (`TokenIssuer`, `TokenPairData`).
- Package dependencies added:
  - `laravel/passport: ^13.10`
- System dependencies:
  - PHP extension `ext-sodium` enabled in `C:\tools\php85\php.ini`.
- Downstream steps:
  - `ACC-T2`: Implementation of `PassportTokenIssuer`, `TokenServiceProvider`, `TokenController`, and `RefreshTokenRequest`.
  - `ACC-U1`: Implementation of Users track migrations (`public_id`, `role`), `UserRole` enum, and user repository.
