# ACC-C0 | BRIEF | Claude

**Track**: Contract (Both developers: Achintha & Rathu — `ACC-C0`, branch `feat/contract-foundation-and-seam`)

**Goal**: Establish the foundational Contract PR (`ACC-C0`) for the standalone Accounts and Authentication module by configuring the GetNow quality toolchain (`composer lint`, `composer fix`, `composer stan`, `composer test`, `composer check` with Pint, Larastan, and Pest on PostgreSQL), installing `spatie/laravel-data`, creating the `[C]` contract seam (`TokenIssuer` interface and `TokenPairData` DTO), wiring the shared `/api/v1` route entrypoint (`routes/api.php` requiring `routes/api/users.php` and `routes/api/tokens.php`), and implementing the `[C]` architecture test suite in `tests/Arch/AccountsArchTest.php`.

## Depends on
- `AGENTS.md` and `ARCHITECTURE.md`
- `composer.json` and `composer.lock`
- `bootstrap/app.php` and `bootstrap/providers.php`
- `app/Models/User.php`
- `phpunit.xml` and `.env.example`
- `pint.json` and `phpstan.neon` (verify whether they exist or need to be created)
- `routes/` and `tests/` directories

## In scope
1. **Toolchain & Dependencies (`AGENTS.md` Environment & Commands)**:
   - Ensure `spatie/laravel-data` is installed in `require` in `composer.json`.
   - Ensure `laravel/pint`, `larastan/larastan`, `pestphp/pest`, `pestphp/pest-plugin-laravel`, and `pestphp/pest-plugin-arch` are installed in `require-dev` in `composer.json`.
   - Configure exact scripts in `composer.json`:
     - `"lint": "pint --test"`
     - `"fix": "pint"`
     - `"stan": "phpstan analyse --memory-limit=512M"`
     - `"test": "pest"`
     - `"check": ["@lint", "@stan", "@test"]`
   - Create or verify `pint.json` and `phpstan.neon` (covering `app`, `config`, `database`, `routes`, `tests`) so `composer lint` and `composer stan` run cleanly.
   - Ensure `declare(strict_types=1);` is present on all existing PHP files under `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, and `tests/`.
2. **PostgreSQL Test Configuration (`AGENTS.md` Database rules)**:
   - Configure `phpunit.xml` and `.env.example` to use PostgreSQL (`DB_CONNECTION=pgsql`), removing or replacing any SQLite (`:memory:`) test configuration.
3. **The Contract Seam (`[C]` files in `ARCHITECTURE.md` Sections 5, 6, 7)**:
   - Create `app/Domain/Accounts/Contracts/TokenIssuer.php`:
     - `public function issue(User $user): TokenPairData;`
     - `public function refresh(string $refreshToken): TokenPairData;`
     - `public function revoke(User $user): void;`
     - `public function revokeAll(User $user): void;`
   - Create `app/Domain/Accounts/Data/TokenPairData.php`:
     - `final class TokenPairData extends Data` decorated with `#[MapName(SnakeCaseMapper::class)]` so constructor properties `public string $accessToken`, `public string $refreshToken`, `public string $tokenType`, and `public int $expiresIn` match `ARCHITECTURE.md` Section 6 while serializing to `access_token`, `refresh_token`, `token_type`, and `expires_in` per `ARCHITECTURE.md` Section 7.
4. **Shared `/api/v1` Route Scaffolding (`ARCHITECTURE.md` Sections 5 & 7)**:
   - Configure `bootstrap/app.php` to load `routes/api.php` (if not already wired).
   - Create `routes/api.php` mounting a `Route::prefix('v1')->group(...)` (or configure the `apiPrefix: 'api/v1'` in `bootstrap/app.php` — specify exact choice in Section 5) that requires `routes/api/users.php` and `routes/api/tokens.php`.
   - Create empty strict-typed stub files `routes/api/users.php` and `routes/api/tokens.php` so neither track needs to edit `routes/api.php` later.
5. **Contract & Architecture Tests (`[C]` in `ARCHITECTURE.md` Sections 5 & 11)**:
   - Create `tests/Arch/AccountsArchTest.php` verifying:
     - Every PHP file in `app/` uses strict types (`declare(strict_types=1);`).
     - `App\Domain` does not import `Illuminate\Http` or `App\Http`.
     - `App\Http\Controllers` does not use `App\Domain\Accounts\Repositories` directly.
     - `App\Domain\Accounts\Services` does not depend on concrete `App\Domain\Accounts\Repositories\UserRepository` or `Illuminate\Http`.
     - Users track classes (`App\Domain\Accounts\Services`, `App\Domain\Accounts\Repositories`, `App\Domain\Accounts\Policies`, `App\Http\Controllers\Api\V1\Users`, `App\Http\Controllers\Api\V1\Auth\AuthController`) never import `Laravel\Passport`.
   - Create `tests/Unit/Domain/Accounts/TokenPairDataTest.php` verifying `TokenPairData` construction and `toArray()` snake_case mapping (`access_token`, `refresh_token`, `token_type`, `expires_in`).

## Out of scope
- Installing `laravel/passport` or adding `HasApiTokens` to `app/Models/User.php` (belongs to `ACC-T1` after `ACC-C0` merges, per `AGENTS.md` Merge order).
- Implementing `PassportTokenIssuer`, `TokenServiceProvider`, `TokenController`, or `RefreshTokenRequest` (belongs to `ACC-T2`).
- Implementing any `[R]` Users track files (`UserRole`, `UserData`, `UserFilterData`, `UserResponseData`, `LoginData`, `UserRepositoryInterface`, `UserRepository`, `UserService`, `AuthService`, `UserPolicy`, `UserController`, `AuthController`, `AccountsServiceProvider`, `FakeTokenIssuer`, or `users` table migrations).

## Read first
- `AGENTS.md` (all sections).
- `ARCHITECTURE.md` (all sections, especially 1, 2, 4, 5, 6, 7, 10, 11).
- Inspect existing repository files:
  - `composer.json`
  - `bootstrap/app.php` and `bootstrap/providers.php`
  - `app/Models/User.php`
  - `config/auth.php` and `config/database.php`
  - `phpunit.xml` and `.env.example`
  - `routes/web.php` and `routes/api.php` (if present)
  - `tests/Pest.php` and `tests/TestCase.php`

## Decisions made
- **D-a Merge Order Compliance (`AGENTS.md` Git & `ARCHITECTURE.md` Section 10)**:
  `ACC-C0` contains only the toolchain, shared `/api/v1` route loader, `[C]` contract classes (`TokenIssuer`, `TokenPairData`), and `tests/Arch/AccountsArchTest.php`. Passport installation and `HasApiTokens` on `User` remain in the next PR (`ACC-T1`).
- **D-b `TokenPairData` SnakeCase Serialization (`ARCHITECTURE.md` Section 6 & 7)**:
  `TokenPairData` keeps the exact camelCase constructor parameters from Section 6 (`$accessToken`, `$refreshToken`, `$tokenType`, `$expiresIn`) and uses Spatie Data's `#[MapName(SnakeCaseMapper::class)]` attribute so `toArray()` / JSON responses output `access_token`, `refresh_token`, `token_type`, and `expires_in` per Section 7.
- **D-c Conflict-Free Track Route Files (`ARCHITECTURE.md` Section 5 & 7)**:
  `routes/api.php` requires `routes/api/users.php` and `routes/api/tokens.php` under the `/api/v1` prefix so all endpoints in Section 7 resolve under `/api/v1/...` and each track only edits its own route file in subsequent PRs.

## Open decisions
None.

## Acceptance criteria
- **AC-1**: `composer lint`, `composer fix`, `composer stan`, `composer test`, and `composer check` are defined in `composer.json` and pass with zero errors.
- **AC-2**: `phpunit.xml` is configured to use PostgreSQL (`DB_CONNECTION=pgsql`), never SQLite.
- **AC-3**: `app/Domain/Accounts/Contracts/TokenIssuer.php` defines `issue(User $user): TokenPairData`, `refresh(string $refreshToken): TokenPairData`, `revoke(User $user): void`, and `revokeAll(User $user): void` with `declare(strict_types=1);`.
- **AC-4**: `app/Domain/Accounts/Data/TokenPairData.php` is a `final` Spatie `Data` class with `accessToken`, `refreshToken`, `tokenType`, and `expiresIn` properties that serialize to `access_token`, `refresh_token`, `token_type`, and `expires_in` (verified by `TokenPairDataTest`).
- **AC-5**: `routes/api.php` loads `routes/api/users.php` and `routes/api/tokens.php` under the `/api/v1` prefix.
- **AC-6**: `tests/Arch/AccountsArchTest.php` passes and enforces `strict_types`, `Domain` isolation from `Http`, Service/Controller layering rules, and the rule that Users track namespaces do not import `Laravel\Passport`.

## Rule IDs to evaluate in Section 8 (Rule compliance matrix)
`OWN1` (Track file ownership), `OWN2` (Contract files only changed in a contract PR), `OWN3` (User.php ownership rule), `OWN4` (Tokens track never reads/writes credentials or roles), `L1` (Controller thinness & layering), `L2` (Domain never imports Illuminate\Http or App\Http), `C1` (declare(strict_types=1) in every PHP file), `C2` (Full parameter, return, and property types; prefer final classes), `C3` (spatie/laravel-data DTOs between layers), `C4` (Pint and Larastan pass with zero errors and no unapproved baseline), `DB1` (PostgreSQL only; tests never use SQLite), `SEC1` (No secrets, .env, or keys committed; .env.example updated), `T1` (Pest unit, feature, and arch tests), `E1` (Windows PowerShell, one command at a time, no && or bash syntax).

## Known traps
1. Missing directories on fresh repo: In `tests/Arch/AccountsArchTest.php`, arch assertions targeting namespaces whose folders do not exist yet (such as `App\Domain\Accounts\Services` or `App\Domain\Accounts\Repositories`) must be written so Pest Arch does not fail when a namespace has zero classes yet (verify with `composer test` before finishing).
2. Existing Laravel skeleton files missing `declare(strict_types=1);`: Adding a strict-types arch test will fail if default Laravel files in `app/Models/User.php` or `app/Providers/AppServiceProvider.php` lack `declare(strict_types=1);`. Inspect all existing files in Section 3 and include them in the manifest if they need `declare(strict_types=1);`.
3. Windows PowerShell commands: Never chain commands with `&&` (`E1`). Run each command separately with `--no-interaction` where supported.

## Size guard
Expected 10 to 15 files (including toolchain configs, contract seam files, route stubs, and arch/unit tests).

## Step-specific checks (Layer A extras)
- `composer check`
- `git status --short`
