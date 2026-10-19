# [ACC-C0 | PLAN v1 | Gemini]

### 1. Understanding

The objective of step `ACC-C0` is to establish the contract foundation for the standalone Accounts and Authentication module on branch `feat/contract-foundation-and-seam`. This step is co-owned by both developers (Achintha and Rathu) under the `[C]` contract designation.

Scope includes:
- Configuring the module quality toolchain in `composer.json` (`composer lint`, `composer fix`, `composer stan`, `composer test`, `composer check`).
- Installing `spatie/laravel-data` and ensuring `pestphp/pest-plugin-arch` is declared in `require-dev`.
- Configuring `phpunit.xml` to execute tests exclusively against PostgreSQL.
- Constructing the `[C]` contract seam: the `TokenIssuer` interface and the `TokenPairData` DTO.
- Scaffolding the conflict-free route entrypoint in `bootstrap/app.php` and `routes/api.php`, including route stubs `routes/api/users.php` (`[R]`) and `routes/api/tokens.php` (`[A]`).
- Adding strict types (`declare(strict_types=1);`) across all existing repository PHP files.
- Implementing architectural assertions in `tests/Arch/AccountsArchTest.php` and unit tests in `tests/Unit/Domain/Accounts/TokenPairDataTest.php`.

Explicitly out of scope:
- Installing `laravel/passport` or adding `HasApiTokens` to `app/Models/User.php` (reserved for `ACC-T1`).
- Implementing `PassportTokenIssuer`, `TokenServiceProvider`, `TokenController`, or `RefreshTokenRequest` (reserved for `ACC-T2`).
- Implementing Users track domain or HTTP classes (`UserData`, `UserService`, `AuthService`, `UserController`, `AuthController`, migrations).

---

### 2. Sources read

The following files and line ranges were read in full prior to drafting:
- `AGENTS.md` (lines 1–160): Project rules, command definitions, ownership protocol, environment rules, database rules, coding standards.
- `ARCHITECTURE.md` (lines 1–275): Module architecture, layer boundaries, contract seam specification, endpoints, DTO rules.
- `composer.json` (lines 1–115): Package dependencies, scripts, autoload settings.
- `composer.lock` (lines 1–40, 11480–11492): Locked versions, platform constraints.
- `bootstrap/app.php` (lines 1–22): Application configuration and routing definition.
- `bootstrap/providers.php` (lines 1–10): Application service providers list.
- `app/Models/User.php` (lines 1–63): User Eloquent model.
- `config/auth.php` (lines 1–100): Authentication guards and providers configuration.
- `config/database.php` (lines 1–100): Database connections configuration.
- `phpunit.xml` (lines 1–37): Test suites and PHP environment variable definitions.
- `.env.example` (lines 1–66): Default environment variable declarations.
- `routes/web.php` (lines 1–12): Web route declarations.
- `tests/Pest.php` (lines 1–51): Pest configuration, test bindings, expectations.
- `tests/TestCase.php` (lines 1–17): Base test case class.
- `pint.json` (lines 1–4): Pint preset configuration.
- `phpstan.neon` (lines 1–14): PHPStan includes, analysis paths, level setting.
- `.github/workflows/tests.yml` (lines 1–62): CI workflow and PostgreSQL service definition.

---

### 3. Verified current state

File and environment inspection verified:
- Git status: Branch is `main`, clean working tree. Latest commit is `6945c53673e684cdd0119fd4755dfd90b08b8191`.
- PHP runtime: Native CLI version is `PHP 8.5.9`. `composer.json` requires `"php": "^8.4"`.
- Dependencies:
  - `spatie/laravel-data` is not present in `composer.json` (verified via dry-run resolution to version `4.23.0`).
  - `pestphp/pest-plugin-arch` is present in `vendor/` at `v5.0.0` but not explicitly listed in `composer.json` `require-dev`.
- Composer scripts in `composer.json`:
  - Current scripts are `setup`, `dev`, `lint`, `lint:check`, `ci:check`, `types:check`, `test`.
  - Required scripts from `AGENTS.md` missing or mismatched: `lint` is configured to run `pint --parallel` instead of checking (`pint --test`), `fix` is missing, `stan` is missing, and `check` is missing.
- Static analysis and linting:
  - `pint.json` contains `{"preset": "laravel"}` and does not declare `declare_strict_types`.
  - `phpstan.neon` paths include `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/` at level 7. It excludes `tests/` and `bootstrap/providers.php`. Running `phpstan` without a memory limit argument encounters parallel worker crashes at 128M.
- Strict types:
  - Exactly 50 existing PHP files reside in `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `tests/`.
  - 0 of 50 existing PHP files declare `declare(strict_types=1);`.
- Test configuration:
  - `phpunit.xml` lines 26–27 specify `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`, violating rule DB1.
- Routes:
  - `bootstrap/app.php` does not declare `api:` or `apiPrefix:`.
  - Neither `routes/api.php` nor the directory `routes/api/` exists.
- Contract seam:
  - Directory `app/Domain/Accounts/Contracts/` does not exist.
  - Directory `app/Domain/Accounts/Data/` does not exist.
- Test suites:
  - Directory `tests/Arch/` does not exist.
  - Directory `tests/Unit/Domain/Accounts/` does not exist.

---

### 4. File manifest

| File | Operation | Track | Description |
|---|---|---|---|
| `composer.json` | Modify | `[C]` | Add `spatie/laravel-data` to `require`, `pestphp/pest-plugin-arch` to `require-dev`, configure scripts `lint`, `fix`, `stan`, `test`, `check`. |
| `composer.lock` | Modify | `[C]` | Updated automatically by Composer during package addition and script adjustments. |
| `phpunit.xml` | Modify | `[C]` | Configure `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=getnow_auth`, `DB_USERNAME=root`, `DB_PASSWORD=""`; remove SQLite settings. |
| `pint.json` | Modify | `[C]` | Add rule `"declare_strict_types": true` to enforce strict types formatting. |
| `phpstan.neon` | Modify | `[C]` | Expand paths to include `bootstrap/providers.php` and `tests/`. |
| `bootstrap/app.php` | Modify | `[C]` | Add `declare(strict_types=1);`, wire `api: __DIR__.'/../routes/api.php'` with `apiPrefix: 'api/v1'`. |
| 49 existing PHP files in `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `tests/` | Modify | `[C]` | Prepend `declare(strict_types=1);` after open tag. |
| `app/Domain/Accounts/Contracts/TokenIssuer.php` | Create | `[C]` | Contract seam interface declaring `issue`, `refresh`, `revoke`, and `revokeAll`. |
| `app/Domain/Accounts/Data/TokenPairData.php` | Create | `[C]` | Contract seam DTO extending `Spatie\LaravelData\Data` with `SnakeCaseMapper`. |
| `routes/api.php` | Create | `[C]` | Shared route root requiring `routes/api/users.php` and `routes/api/tokens.php`. |
| `routes/api/users.php` | Create | `[R]` | Empty route stub with `declare(strict_types=1);` reserved for Users track. |
| `routes/api/tokens.php` | Create | `[A]` | Empty route stub with `declare(strict_types=1);` reserved for Tokens track. |
| `tests/Arch/AccountsArchTest.php` | Create | `[C]` | Pest Arch suite enforcing strict types, layer isolation, and Passport boundaries. |
| `tests/Unit/Domain/Accounts/TokenPairDataTest.php` | Create | `[C]` | Unit test verifying `TokenPairData` construction and snake_case serialization. |

---

### 5. Interface specs

#### 5.1 `TokenIssuer` Interface
File: `app/Domain/Accounts/Contracts/TokenIssuer.php`
```php
<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Contracts;

use App\Domain\Accounts\Data\TokenPairData;
use App\Models\User;

interface TokenIssuer
{
    /**
     * Called after AuthService has verified credentials.
     */
    public function issue(User $user): TokenPairData;

    public function refresh(string $refreshToken): TokenPairData;

    /**
     * Logout: revoke the token pair behind the current request.
     */
    public function revoke(User $user): void;

    /**
     * Delete, password change, role change: revoke every token of the user.
     */
    public function revokeAll(User $user): void;
}
```

#### 5.2 `TokenPairData` Class
File: `app/Domain/Accounts/Data/TokenPairData.php`
```php
<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class TokenPairData extends Data
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,
        public int $expiresIn,
    ) {}
}
```

#### 5.3 Route Entrypoint Specification
In `bootstrap/app.php`:
```php
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
```

In `routes/api.php`:
```php
<?php

declare(strict_types=1);

require __DIR__.'/api/users.php';
require __DIR__.'/api/tokens.php';
```

In `routes/api/users.php`:
```php
<?php

declare(strict_types=1);
```

In `routes/api/tokens.php`:
```php
<?php

declare(strict_types=1);
```

---

### 6. Data and migrations

- Migrations: None created in `ACC-C0`.
- Data structure: `TokenPairData` encapsulates access and refresh token metadata.
  - `$accessToken`: string (bearer token).
  - `$refreshToken`: string (rotation token).
  - `$tokenType`: string (default "Bearer").
  - `$expiresIn`: integer (seconds until expiry).
- Serialization: `#[MapName(SnakeCaseMapper::class)]` maps properties upon `toArray()` or JSON encoding to:
  - `access_token`
  - `refresh_token`
  - `token_type`
  - `expires_in`
- Database: All test interactions target PostgreSQL database `getnow_auth` configured in `phpunit.xml`.

---

### 7. Behavior and flows

- Quality Toolchain Workflow:
  1. `composer lint`: Invokes `pint --test`. Reports code styling violations without writing changes.
  2. `composer fix`: Invokes `pint`. Automatically formats all codebase files according to preset rules.
  3. `composer stan`: Invokes `phpstan analyse --memory-limit=512M`. Performs level 7 static analysis across `app/`, `bootstrap/app.php`, `bootstrap/providers.php`, `config/`, `database/`, `routes/`, and `tests/`.
  4. `composer test`: Invokes `pest`. Executes unit, feature, and architecture test suites against PostgreSQL.
  5. `composer check`: Sequentially executes `@lint`, `@stan`, and `@test`. Returns exit code 0 only when all three pass.
- Routing Resolution:
  - An HTTP request to `/api/v1/{path}` routes through Laravel's API routing pipeline configured in `bootstrap/app.php`.
  - `routes/api.php` delegates user endpoints to `routes/api/users.php` and token endpoints to `routes/api/tokens.php`.
- Contract Seam Consumption:
  - Users track `AuthService` calls `TokenIssuer::issue($user)` upon verified credentials.
  - Tokens track `PassportTokenIssuer` implements `TokenIssuer` and returns `TokenPairData`.
  - JSON serialization yields the schema defined in `ARCHITECTURE.md` Section 7.

---

### 8. Rule compliance matrix

| Rule ID | Rule Summary | Compliance Plan |
|---|---|---|
| `OWN1` | Track file ownership | `ACC-C0` operates strictly within the Contract scope. Shared files (`composer.json`, `phpunit.xml`, `bootstrap/app.php`, `routes/api.php`) are updated as contract assets. Stub files for `users.php` (`[R]`) and `tokens.php` (`[A]`) are created empty with strict types so future track PRs do not edit shared route files. |
| `OWN2` | Contract files only changed in contract PR | `TokenIssuer.php`, `TokenPairData.php`, and `AccountsArchTest.php` are authored only in this PR on branch `feat/contract-foundation-and-seam`. |
| `OWN3` | `User.php` ownership rule | `app/Models/User.php` receives only `declare(strict_types=1);` to satisfy global strict typing. No Passport traits or relationship changes are introduced. |
| `OWN4` | Tokens track isolation | No token generation logic, credential checks, or role queries are written. |
| `L1` | Controller thinness & layering | No controllers or repository queries are created. Layer boundaries are established in arch tests. |
| `L2` | Domain never imports Http | `TokenIssuer.php` and `TokenPairData.php` import zero classes from `Illuminate\Http` or `App\Http`. Verified by arch test. |
| `C1` | `declare(strict_types=1);` in every file | Prepend `declare(strict_types=1);` to all 50 existing PHP files and include it on all 5 newly created PHP files. |
| `C2` | Full typing, prefer `final` | All parameters, properties, and returns are strictly typed. `TokenPairData` is declared `final`. |
| `C3` | `spatie/laravel-data` DTOs | `TokenPairData` extends `Spatie\LaravelData\Data`. |
| `C4` | Pint and Larastan zero errors | `composer lint` and `composer stan` pass cleanly with zero errors and no baseline file additions. |
| `DB1` | PostgreSQL only, never SQLite | `phpunit.xml` replaces SQLite `:memory:` configuration with `pgsql` targeting database `getnow_auth`. |
| `SEC1` | No secrets committed | No `.env` files, credentials, or keys are committed. |
| `T1` | Pest tests | Pest executes architecture tests (`tests/Arch/AccountsArchTest.php`) and unit tests (`tests/Unit/Domain/Accounts/TokenPairDataTest.php`). |
| `E1` | Windows PowerShell rules | All commands run individually without chaining operators (`&&`) or bash syntax. |

---

### 9. AC traceability

| AC ID | Acceptance Criteria | Satisfying Implementation Details |
|---|---|---|
| `AC-1` | `composer lint`, `fix`, `stan`, `test`, `check` defined and passing | Defined in `composer.json` `scripts`. Validated by executing `composer check`. |
| `AC-2` | `phpunit.xml` configured for PostgreSQL | Configured with `DB_CONNECTION=pgsql` and PostgreSQL credentials in `phpunit.xml`. SQLite references removed. |
| `AC-3` | `TokenIssuer.php` contract defined | Created at `app/Domain/Accounts/Contracts/TokenIssuer.php` with strict types and exact four methods. |
| `AC-4` | `TokenPairData.php` defined and tested | Created at `app/Domain/Accounts/Data/TokenPairData.php` using `SnakeCaseMapper`. Verified by `tests/Unit/Domain/Accounts/TokenPairDataTest.php`. |
| `AC-5` | `routes/api.php` loads under `/api/v1` | `bootstrap/app.php` sets `apiPrefix: 'api/v1'`. `routes/api.php` requires `routes/api/users.php` and `routes/api/tokens.php`. |
| `AC-6` | `tests/Arch/AccountsArchTest.php` passing | Implemented with assertions covering strict types, domain isolation, repository separation, and Passport boundaries. |

---

### 10. Test plan

#### 10.1 Unit Tests
File: `tests/Unit/Domain/Accounts/TokenPairDataTest.php`
- Construction verification:
  - Instantiate `new TokenPairData('access-sample-token', 'refresh-sample-token', 'Bearer', 900)`.
  - Assert `$data->accessToken === 'access-sample-token'`.
  - Assert `$data->refreshToken === 'refresh-sample-token'`.
  - Assert `$data->tokenType === 'Bearer'`.
  - Assert `$data->expiresIn === 900`.
- Snake-case mapping verification:
  - Execute `$array = $data->toArray();`.
  - Assert `$array['access_token'] === 'access-sample-token'`.
  - Assert `$array['refresh_token'] === 'refresh-sample-token'`.
  - Assert `$array['token_type'] === 'Bearer'`.
  - Assert `$array['expires_in'] === 900`.
  - Assert `array_key_exists('accessToken', $array) === false`.
  - Execute `$json = $data->toJson();`.
  - Assert JSON contains `"access_token"`, `"refresh_token"`, `"token_type"`, `"expires_in"`.

#### 10.2 Architecture Tests
File: `tests/Arch/AccountsArchTest.php`
- Assert every PHP file in `App` enforces strict types:
  ```php
  arch('app uses strict types')
      ->expect('App')
      ->toUseStrictTypes();
  ```
- Assert `App\Domain` does not depend on HTTP layer:
  ```php
  arch('domain does not import http')
      ->expect('App\Domain')
      ->not->toUse(['Illuminate\Http', 'App\Http']);
  ```
- Assert Controllers do not depend directly on concrete repositories:
  ```php
  arch('controllers do not use repositories directly')
      ->expect('App\Http\Controllers')
      ->not->toUse('App\Domain\Accounts\Repositories');
  ```
- Dynamic assertion guards for future track namespaces:
  ```php
  $servicesPath = app_path('Domain/Accounts/Services');
  if (is_dir($servicesPath)) {
      arch('services do not use eloquent repositories directly')
          ->expect('App\Domain\Accounts\Services')
          ->not->toUse(['App\Domain\Accounts\Repositories\UserRepository', 'Illuminate\Http']);
  }

  $usersTrackNamespaces = array_filter([
      'App\Domain\Accounts\Services',
      'App\Domain\Accounts\Repositories',
      'App\Domain\Accounts\Policies',
      'App\Http\Controllers\Api\V1\Users',
  ], fn (string $ns): bool => is_dir(base_path(str_replace(['App\\', '\\'], ['app/', '/'], $ns))));

  if (! empty($usersTrackNamespaces)) {
      arch('users track does not import passport')
          ->expect($usersTrackNamespaces)
          ->not->toUse('Laravel\Passport');
  }
  ```

---

### 11. Docs and contract updates

- `ARCHITECTURE.md`: No edits required. The interfaces and route paths align with Sections 5, 6, and 7.
- `.env.example`: Verified current configuration specifies `DB_CONNECTION=pgsql` and default port 5432; no modifications required.
- `AGENTS.md`: No edits required.

---

### 12. Implementation order

1. **Branch checkout**:
   - Execute in PowerShell: `git checkout -b feat/contract-foundation-and-seam`
2. **Dependency installation**:
   - Execute in PowerShell: `composer require spatie/laravel-data --no-interaction`
   - Execute in PowerShell: `composer require --dev pestphp/pest-plugin-arch --no-interaction`
3. **Configure Composer scripts**:
   - Update `composer.json` scripts section to include exact entries:
     - `"lint": "pint --test"`
     - `"fix": "pint"`
     - `"stan": "phpstan analyse --memory-limit=512M"`
     - `"test": "pest"`
     - `"check": ["@lint", "@stan", "@test"]`
4. **Pint configuration**:
   - Update `pint.json` with rule `"declare_strict_types": true`.
5. **PHPStan configuration**:
   - Update `phpstan.neon` to include `bootstrap/providers.php` and `tests/` in `parameters.paths`.
6. **Strict types migration on existing files**:
   - Add `declare(strict_types=1);` to all 50 existing PHP files across `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `tests/`.
   - Execute in PowerShell: `composer fix` to format all modified files.
7. **Database test configuration**:
   - Update `phpunit.xml` lines 26–27 to specify `pgsql` connection settings.
8. **Route wiring**:
   - Update `bootstrap/app.php` with `api: __DIR__.'/../routes/api.php'` and `apiPrefix: 'api/v1'`.
   - Create `routes/api.php` requiring `routes/api/users.php` and `routes/api/tokens.php`.
   - Create `routes/api/users.php` with `declare(strict_types=1);`.
   - Create `routes/api/tokens.php` with `declare(strict_types=1);`.
9. **Contract seam creation**:
   - Create `app/Domain/Accounts/Contracts/TokenIssuer.php`.
   - Create `app/Domain/Accounts/Data/TokenPairData.php`.
10. **Test authoring**:
    - Create `tests/Unit/Domain/Accounts/TokenPairDataTest.php`.
    - Create `tests/Arch/AccountsArchTest.php`.
11. **Verification**:
    - Execute in PowerShell: `composer lint`
    - Execute in PowerShell: `composer stan`
    - Execute in PowerShell: `composer test`
    - Execute in PowerShell: `composer check`
    - Read command outputs to verify zero errors across all quality checks.

---

### 13. Risks, assumptions, conflicts

- Risk: PHPStan parallel worker memory limit exhaustion.
  - Mitigation: Add explicit `--memory-limit=512M` flag in `composer stan`.
- Risk: Pest Arch throws errors if asserted namespaces have zero classes.
  - Mitigation: Wrap namespace targets that do not exist yet in directory existence guards within `tests/Arch/AccountsArchTest.php`.
- Conflict or Ambiguity: None identified. The contract seam requirements between `AGENTS.md` and `ARCHITECTURE.md` are unambiguous.

---

### 14. Dependencies

- Preceding steps: None (`ACC-C0` is the initial foundation step).
- Package dependencies:
  - `spatie/laravel-data: ^4.23`
  - `pestphp/pest-plugin-arch: ^5.0`
- Downstream steps:
  - `ACC-T1`: Passport installation, Passport migrations, and `HasApiTokens` trait on `User`.
  - `ACC-U1`: User migration additions (`public_id`, `role`), `UserRole` enum, and user repository.
