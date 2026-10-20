# Architecture: Accounts and Authentication

Standalone design for the GetNow authentication module. It is built in its own project for now and merged into the GetNow main repository later (section 12). Two developers work on it in parallel, each with AI agents, so this document defines the layers, the file ownership and the one contract that joins the two tracks.

If this file and `AGENTS.md` disagree, stop and ask.

## 1. Scope

In scope:
- User CRUD (index, store, show, update, destroy), restricted to the superadmin.
- Customer self-registration.
- Login for superadmin, retailer and customer.
- Access and refresh token system on Laravel Passport (issue, refresh, revoke).

Out of scope (do not build unless asked): Filament UI, retailer panel, email verification, password reset, 2FA, permission packages (Spatie permission/Shield), business accounts, social login.

## 2. Tracks and owners

| Track | Owner | Owns |
|---|---|---|
| Users (`U`) | Rathu | User CRUD, register, login credential check, roles, user DTO, controller, service, repository, repository interface, `UserPolicy` |
| Tokens (`T`) | Achintha | Passport install and config, token issue, refresh, revoke, token lifetimes, auth guard, `PassportTokenIssuer` |
| Contract (`C`) | Both | `TokenIssuer` interface and `TokenPairData` (section 6). Any change needs both approvals. |

The tracks meet only at the `TokenIssuer` contract. Neither track edits the other's files (full table in section 5).

## 3. Roles and rules

| Role | Created by | Can log in | Can call `/users` CRUD |
|---|---|---|---|
| `superadmin` | Seeder or artisan command only, never the API | Yes | Yes |
| `retailer` | Superadmin, via `POST /users` | Yes | No |
| `customer` | Self-register, or superadmin via `POST /users` | Yes | No |

Rules:
1. Public registration creates a **customer only**. The role is set inside `AuthService`. The register request never carries a role and any `role` input is ignored.
2. Retailers cannot register. Only the superadmin creates them.
3. Only the superadmin can use the `/users` endpoints.
4. The API never creates or promotes a `superadmin`: `store` and `update` reject `role = superadmin`. *(Proposed, confirm with the senior.)*
5. The superadmin logs in through the same login endpoint, otherwise it cannot call `/users`. *(Assumption, confirm.)*
6. Deleting a user, changing a user's password or changing a user's role revokes all of that user's tokens (the service calls `TokenIssuer::revokeAll`).

## 4. Layering

```
Request ─► FormRequest ─► Controller ─► Service ─► UserRepositoryInterface ◄─ UserRepository ─► DB
           (validate)     (HTTP only)   (rules)     (contract)                (Eloquent)
```

| Layer | Does | Must not |
|---|---|---|
| FormRequest | Validates input, builds the DTO | Contain business rules |
| Controller | Authorizes (Policy), calls the Service, returns a response | Touch Eloquent, repositories or business rules |
| Service | Business rules, role rules, orchestration, calls `TokenIssuer` when needed | Use `Request`/`Response`, query Eloquent directly |
| Repository interface | Contract the Service depends on | |
| Repository | All Eloquent and query code | Contain business rules, throw HTTP exceptions |
| DTO | Carries typed data between layers | Contain logic or validation |

Method parity (the senior's rule: the same five names in every layer):

| Controller | Service | Repository (and interface) |
|---|---|---|
| `index(IndexUserRequest)` | `index(UserFilterData)` | `index(UserFilterData)` |
| `store(StoreUserRequest)` | `store(UserData)` | `store(UserData)` |
| `show(User)` | `show(User)` | `show(string $publicId)` |
| `update(UpdateUserRequest, User)` | `update(User, UserData)` | `update(User, UserData)` |
| `destroy(User)` | `destroy(User)` | `destroy(User)` |

The interface is bound to the Eloquent implementation in `AccountsServiceProvider`. Services type-hint the interface only.

## 5. Structure and ownership

Paths mirror the GetNow main repo (`app/Domain/<Context>`, `app/Http/Controllers/Api/V1/...`) so merging is a file move, not a rename. `[R]` Rathu, `[A]` Achintha, `[C]` contract (both approve).

```
app/
├── Models/User.php                              [R] (Achintha adds HasApiTokens once, see rule below)
├── Domain/Accounts/
│   ├── Contracts/
│   │   ├── UserRepositoryInterface.php          [R]
│   │   └── TokenIssuer.php                      [C]
│   ├── Data/
│   │   ├── UserData.php                         [R] single DTO for store and update
│   │   ├── UserFilterData.php                   [R]
│   │   ├── UserResponseData.php                 [R] API output, never the model
│   │   ├── LoginData.php                        [R]
│   │   └── TokenPairData.php                    [C]
│   ├── Enums/UserRole.php                       [R]
│   ├── Repositories/UserRepository.php          [R]
│   ├── Services/
│   │   ├── UserService.php                      [R]
│   │   └── AuthService.php                      [R] register, login
│   ├── Policies/UserPolicy.php                  [R]
│   └── Tokens/PassportTokenIssuer.php           [A] implements TokenIssuer
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── Users/UserController.php             [R]
│   │   └── Auth/
│   │       ├── AuthController.php               [R] register, login
│   │       └── TokenController.php              [A] refresh, logout
│   └── Requests/
│       ├── Users/{Store,Update,Index}UserRequest.php   [R]
│       └── Auth/
│           ├── {Register,Login}Request.php             [R]
│           └── RefreshTokenRequest.php                 [A]
└── Providers/
    ├── AccountsServiceProvider.php              [R] binds UserRepositoryInterface
    └── TokenServiceProvider.php                 [A] binds TokenIssuer, Passport config
routes/
├── api.php                                      [shared, one include line per track]
└── api/{users.php [R], tokens.php [A]}
database/
├── migrations/                                  each track adds its own files, never edits the other's
├── factories/UserFactory.php                    [R]
└── seeders/SuperadminSeeder.php                 [R]
tests/
├── Feature/Accounts/                            [R]
├── Feature/Tokens/                              [A]
├── Support/FakeTokenIssuer.php                  [R] used by Rathu's tests until the real one lands
└── Arch/                                        [C]
docs/{adr,contracts}/
```

Shared files and how they are handled:

| File | Rule |
|---|---|
| `composer.json` / `composer.lock` | Achintha's Passport install goes in the first merged PR. Later dependency changes are announced before the PR. Rebase the lock file, never hand-merge it. |
| `app/Models/User.php` | Rathu owns it. Achintha's only edit is adding `HasApiTokens`, in a tiny dedicated PR merged before token work starts. |
| `config/auth.php` | Achintha owns it (guard and provider for `api`). |
| `routes/api.php` | One `require` line per track. Each track owns its own file under `routes/api/`. |
| `bootstrap/providers.php` | One line per provider. Trivial merge conflict, resolve by keeping both. |
| `.env.example` | Append only, new variables grouped under a comment with the track name. |

## 6. The seam: `TokenIssuer`

This is the only thing the two tracks share. Rathu's `AuthService` and `UserService` depend on it. Achintha implements it with Passport.

```php
interface TokenIssuer
{
    /** Called after AuthService has verified credentials. */
    public function issue(User $user): TokenPairData;

    public function refresh(string $refreshToken): TokenPairData;

    /** Logout: revoke the token pair behind the current request. */
    public function revoke(User $user): void;

    /** Delete, password change, role change: revoke every token of the user. */
    public function revokeAll(User $user): void;
}

final class TokenPairData extends Data
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,   // "Bearer"
        public int $expiresIn,      // seconds
    ) {}
}
```

Rules:
- Rathu codes against the interface and tests with `FakeTokenIssuer`. No production binding exists until Achintha's `TokenServiceProvider` lands, so a missing binding fails loudly instead of silently issuing nothing.
- Achintha never touches credentials, roles or the users table. `AuthService` has already decided the user may log in before `issue()` is called.
- Changing the interface or `TokenPairData` is a contract change (section 10).

## 7. Endpoints

Base path `/api/v1`. Output is always `UserResponseData`, never the model. Users are addressed by `public_id` (ULID).

| Method and path | Auth | Role | Track |
|---|---|---|---|
| `POST /auth/register` | none, throttled | customer only | U |
| `POST /auth/login` | none, throttled | superadmin, retailer, customer | U |
| `POST /auth/refresh` | refresh token, throttled | any | T |
| `POST /auth/logout` | access token | any | T |
| `GET /users` | access token | superadmin | U |
| `POST /users` | access token | superadmin | U |
| `GET /users/{user}` | access token | superadmin | U |
| `PUT/PATCH /users/{user}` | access token | superadmin | U |
| `DELETE /users/{user}` | access token | superadmin | U |

Login and register success response:

```json
{ "data": { "user": { "id": "01J...", "name": "", "email": "", "role": "customer" },
            "tokens": { "access_token": "", "refresh_token": "", "token_type": "Bearer", "expires_in": 900 } } }
```

`GET /users` supports `role`, `search` (name or email), `page` and `per_page` (max 100).

## 8. One DTO for insert and update

**What "combining" means.** The idea is one `UserData` class used by both `store` and `update`, instead of `CreateUserData` and `UpdateUserData` with the same fields copied into both. The two classes drift apart over time (a field added to one and forgotten in the other), and every layer needs two code paths.

**The catch.** Create needs every field. Update usually sends only some. A DTO whose fields are all `null` cannot tell "field not sent" from "set it to null", so a partial update can wipe data by accident.

**Chosen approach.** One `UserData` (spatie/laravel-data, already in the GetNow stack) whose properties are `Optional` when absent:

```php
final class UserData extends Data
{
    public function __construct(
        public string|Optional $name,
        public string|Optional $email,
        public string|Optional $password,
        public UserRole|Optional $role,
    ) {}
}
```

- **Rules live in the FormRequests, not the DTO.** `StoreUserRequest` marks name, email, password and role as required. `UpdateUserRequest` marks them `sometimes`. The DTO only carries what was validated: `UserData::from($request->validated())`.
- **Update only writes what was sent.** `Optional` fields are left out of `toArray()`, so the repository calls `$user->update($data->toArray())` and untouched fields stay untouched.
- **Passwords are hashed once**, by the `hashed` cast on the `User` model. No layer calls `Hash::make`.
- **Register** builds `UserData` inside `AuthService` with `role = UserRole::Customer`. The request has no role field.
- Confirm with the senior that "combine" meant this. If he meant something else, record the answer as an ADR.

Rejected: two separate DTOs (duplication and drift); one DTO with plain nullable fields (cannot distinguish absent from null).

## 9. Data model

`users` additions (PostgreSQL, same conventions as the main repo):
- `public_id` ULID, unique. `bigint` primary key stays internal and is never exposed.
- `role` varchar(16), `CHECK (role IN ('superadmin','retailer','customer'))`, indexed.
- `email` unique, `password` hashed, `deleted_at` (soft delete, proposed so later orders and content can keep their foreign keys).

Passport's own tables come from its published migrations and belong to Achintha.

## 10. Working rules between the two tracks

- A contract change (section 6) is its own PR, labelled `contract`, approved by both developers, merged before either side relies on it.
- Do not edit a file owned by the other track. Write a handoff note in the PR or ask the owner.
- Merge order: contract PR (C0), Passport install plus `HasApiTokens` PR, then tracks in parallel.
- Rebase on `main` at least daily. Keep PRs small, one concern each.
- Each track records significant decisions as an ADR in `docs/adr/`.

## 11. Security, testing and decisions

Security:
- Throttle `register`, `login` and `refresh`.
- Login returns the same error for an unknown email and a wrong password.
- Never accept `role` from the public register request. Never mass-assign `role` from any request outside `store` and `update` with the rules in section 3.
- Never return, log or cache passwords or raw tokens.
- Tokens are revoked on delete, password change and role change.
- Authorization is server-side through `UserPolicy`.

Testing (Pest, PostgreSQL, factories, no SQLite):
- Feature tests: every endpoint, including authorization failures (customer and retailer calling `/users`), register ignoring a sent `role`, login for all three roles, partial update leaving other fields untouched, destroy revoking tokens (via `FakeTokenIssuer`).
- Architecture tests in `tests/Arch`: controllers do not use repositories or models directly, services do not use `Illuminate\Http`, services depend on `UserRepositoryInterface` not `UserRepository`, `Domain` does not import `Http`, every file has `strict_types`.

Open decisions (record as ADRs):

| ID | Question | Owner | Status / Decision |
|---|---|---|---|
| OQ1 | Which Passport grant issues the access and refresh pair after `AuthService` has verified credentials? Check what the installed Passport version supports before choosing. | Achintha | Decided: direct entity minting and rotation via `PassportTokenIssuer`. See `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`. |
| OQ2 | Refresh token delivery: JSON body or httpOnly cookie. | Achintha | Decided: JSON body delivery (`data.tokens`). See `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`. |
| OQ3 | Token lifetimes (access short, refresh longer). | Achintha | Decided: 900s access, 14d refresh via `config/passport.php`. See `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`. |
| OQ4 | Do tokens carry scopes per role, or is the role checked only in policies? | Both | Decided: empty/default scopes, authorization enforced strictly via `UserPolicy`. See `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`. |
| OQ5 | How does a retailer receive its first password: set by the superadmin, or an emailed set-password link? | Rathu with the senior | Open |
| OQ6 | Hard delete or soft delete for `destroy`. | Rathu with the senior | Open |
| OQ7 | Self-service profile endpoints (`/me`) for retailers and customers. | Senior | Open |

## 12. Merge plan into the GetNow main repository

The main repo currently treats Accounts as a later phase and has rules this module changes. Handle these in the merge PR:
1. Fold this document into the main `ARCHITECTURE.md` as an Accounts section, move Accounts from "Later" into the phase table, and update the "do not build accounts" line in the main `AGENTS.md`.
2. The main repo puts business logic in Actions and keeps controllers thin. This module uses Controller, Service, Repository per the senior's instruction. Record an ADR that the Accounts context uses Services and Repositories, and adjust the arch tests so the rule applies per context.
3. The main repo's `users` table and `is_platform_admin` flag already exist and gate Filament `canAccessPanel`. Reconcile with `role = superadmin` in an additive migration (no drops in the same release). Until an ADR says otherwise, keep `canAccessPanel` on `is_platform_admin`.
4. Keep `App\Models\User` where it is. Main-repo tables reference `users` through foreign keys.
5. Add the Passport and spatie/laravel-data dependencies to the main stack table with justification.
6. Paths already match the main repo layout, so no renames are needed.
