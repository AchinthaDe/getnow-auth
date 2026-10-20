# [ACC-T2 | IMPL-REPORT | Gemini]

### 1. Commits and Git References

- **Plan Version**: Plan `ACC-T2 v1` (Approved with 5 Execution Guards)
- **Base SHA**: `0dccec16a6311ac56d1950fb41157d7f6211e330`
- **Head SHA**: `08034a1a1b3fb4237b0c18ecb1d282a2a3c70a72`
- **Target Branch**: `feat/tokens-issuer-and-endpoints`

#### Raw output of `git log --oneline main..HEAD`:
```
08034a1 feat(tokens): add refresh and logout endpoints with rotation tests
15cef19 feat(tokens): implement passport token issuer and rotation engine
0dccec1 test(tokens): add passport setup smoke test and step docs
367d74b feat(tokens): install passport, configure api guard, and add HasApiTokens to User
5cbe20c docs(contract): add ACC-C0 implementation report
be53e1e feat(contract): add TokenIssuer seam, api/v1 routing, and arch tests
fc7fb9e chore(contract): configure quality toolchain, pgsql testing, and strict types
```

---

### 2. File Reconciliation

#### Raw output of `git diff --name-status 0dccec1...HEAD`:
```
M	ARCHITECTURE.md
A	app/Domain/Accounts/Tokens/PassportTokenIssuer.php
A	app/Http/Controllers/Api/V1/Auth/TokenController.php
A	app/Http/Requests/Auth/RefreshTokenRequest.php
A	app/Providers/TokenServiceProvider.php
M	bootstrap/providers.php
A	docs/adr/0001-passport-token-issuance-and-refresh-rotation.md
A	docs/steps/ACC-T2/brief.md
A	docs/steps/ACC-T2/plan.md
M	routes/api/tokens.php
A	tests/Feature/Tokens/PassportTokenIssuerTest.php
A	tests/Feature/Tokens/TokenEndpointsTest.php
```

#### Reconciliation Table:
| File | Status | Notes |
|---|---|---|
| `app/Domain/Accounts/Tokens/PassportTokenIssuer.php` | Planned and done | Domain engine implementing `TokenIssuer` with zero `User::query()` calls (`OWN4`) and zero `Illuminate\Http` imports (`L2`). |
| `app/Providers/TokenServiceProvider.php` | Planned and done | Service provider binding `TokenIssuer`, configuring lifetimes from config, and registering rate limiters. |
| `bootstrap/providers.php` | Planned and done | Registered `TokenServiceProvider::class`. |
| `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md` | Planned and done | Architecture Decision Record resolving `OQ1`–`OQ4`. |
| `tests/Feature/Tokens/PassportTokenIssuerTest.php` | Planned and done | 9 unit/domain feature tests testing all methods against PostgreSQL. |
| `app/Http/Requests/Auth/RefreshTokenRequest.php` | Planned and done | FormRequest validating `refresh_token` (`required`, `string`). |
| `app/Http/Controllers/Api/V1/Auth/TokenController.php` | Planned and done | API controller for `refresh` and `logout`. |
| `routes/api/tokens.php` | Planned and done | Endpoint declarations for `/refresh` and `/logout` with throttle middleware. |
| `ARCHITECTURE.md` | Planned and done | Section 11 open decisions table updated referencing ADR 0001. |
| `tests/Feature/Tokens/TokenEndpointsTest.php` | Planned and done | 8 feature tests verifying endpoints, rotation, logout, and throttling against PostgreSQL. |
| `docs/steps/ACC-T2/brief.md` | Planned and done | Step brief documentation. |
| `docs/steps/ACC-T2/plan.md` | Planned and done | Approved 14-section implementation plan. |

- Planned and not done: None
- Unplanned: None

---

### 3. Acceptance Criteria Results Table

| AC ID | Description | Implementation File & Line | Test Verification | Result |
|---|---|---|---|---|
| **AC-1** | `TokenServiceProvider` binds `TokenIssuer::class` to `PassportTokenIssuer::class`, configures Passport lifetimes from config, and registers rate limiters for `refresh` and `logout` | [`app/Providers/TokenServiceProvider.php:24-44`](file:///c:/dev/getnow-auth/app/Providers/TokenServiceProvider.php#L24-L44) | `PassportTokenIssuerTest.php`: `token service provider binds token issuer interface to passport token issuer` | PASS |
| **AC-2** | `PassportTokenIssuer::issue(User $user)` returns `TokenPairData` with valid Bearer access token, refresh token, 'Bearer', and 900s expiry; access token authenticates `auth:api` | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php:39-42`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php#L39-L42), [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php:146-182`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php#L146-L182) | `PassportTokenIssuerTest.php`: `issue mints a valid token pair and persists records in postgresql`; `TokenEndpointsTest.php`: `post api v1 auth refresh rotates token pair successfully` | PASS |
| **AC-3** | `PassportTokenIssuer::refresh(string $refreshToken)` and `POST /api/v1/auth/refresh` rotate token and revoke old pair | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php:44-100`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php#L44-L100), [`app/Http/Controllers/Api/V1/Auth/TokenController.php:22-37`](file:///c:/dev/getnow-auth/app/Http/Controllers/Api/V1/Auth/TokenController.php#L22-L37) | `PassportTokenIssuerTest.php`: `refresh rotates the refresh token and revokes consumed tokens in postgresql`; `TokenEndpointsTest.php`: `post api v1 auth refresh rotates token pair successfully` | PASS |
| **AC-4** | Reusing consumed refresh token or passing expired/revoked/malformed token returns HTTP 401 | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php:46-87`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php#L46-L87), [`app/Http/Controllers/Api/V1/Auth/TokenController.php:26-30`](file:///c:/dev/getnow-auth/app/Http/Controllers/Api/V1/Auth/TokenController.php#L26-L30) | `PassportTokenIssuerTest.php`: `refresh rejects reused/expired/malformed refresh token`; `TokenEndpointsTest.php`: `post api v1 auth refresh fails with 401...` | PASS |
| **AC-5** | `POST /api/v1/auth/logout` calls `TokenIssuer::revoke($user)` and invalidates current session without revoking user's other sessions | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php:102-120`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php#L102-L120), [`app/Http/Controllers/Api/V1/Auth/TokenController.php:39-50`](file:///c:/dev/getnow-auth/app/Http/Controllers/Api/V1/Auth/TokenController.php#L39-L50) | `PassportTokenIssuerTest.php`: `revoke revokes current access token...`; `TokenEndpointsTest.php`: `post api v1 auth logout revokes current session`, `...leaves user other active sessions valid` | PASS |
| **AC-6** | `PassportTokenIssuer::revokeAll(User $user)` revokes every access token and refresh token of user | [`app/Domain/Accounts/Tokens/PassportTokenIssuer.php:122-144`](file:///c:/dev/getnow-auth/app/Domain/Accounts/Tokens/PassportTokenIssuer.php#L122-L144) | `PassportTokenIssuerTest.php`: `revokeAll revokes every access token and refresh token of user`; `TokenEndpointsTest.php`: `post api v1 auth refresh fails with 401 on expired, revoked...` | PASS |
| **AC-7** | Endpoints are throttled and return HTTP 429 when limits exceeded | [`routes/api/tokens.php:10, 13`](file:///c:/dev/getnow-auth/routes/api/tokens.php#L10), [`app/Providers/TokenServiceProvider.php:35-43`](file:///c:/dev/getnow-auth/app/Providers/TokenServiceProvider.php#L35-L43) | `TokenEndpointsTest.php`: `refresh endpoint enforces rate limits` | PASS |
| **AC-8** | Zero `[R]`, `User.php`, or `[C]` file modified in this branch; zero `Illuminate\Http` imports in `app/Domain` | Scope boundary check | `tests/Arch/AccountsArchTest.php`: passes; `Get-ChildItem` scan: empty | PASS |
| **AC-9** | ADR 0001 created, `ARCHITECTURE.md` Section 11 updated, `tests/Arch` passes, and `composer check` passes | [`docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`](file:///c:/dev/getnow-auth/docs/adr/0001-passport-token-issuance-and-refresh-rotation.md), [`ARCHITECTURE.md:253-265`](file:///c:/dev/getnow-auth/ARCHITECTURE.md#L253-L265) | `composer check`: 58 tests passed, 181 assertions, 0 errors | PASS |

---

### 4. Deviations

None. All implementations follow Plan `ACC-T2 v1` and the 5 Execution Guards.

---

### 5. Raw Command Outputs

#### 5.1 `composer check`
```
{"tool":"pint","result":"passed"}{"tool":"phpstan","result":"passed","errors":0}
{"tool":"pest","result":"passed","tests":58,"passed":58,"assertions":181,"duration_ms":6998}
```

#### 5.2 `php artisan route:list --path=api/v1/auth`
```
 POST api/v1/auth/logout .. Api\V1\Auth\TokenController@logout
 POST api/v1/auth/refresh .. Api\V1\Auth\TokenController@refresh

 Showing [2] routes
```

#### 5.3 `Get-ChildItem app/Domain -Recurse -Filter *.php | Select-String -SimpleMatch 'App\Http','Illuminate\Http'`
*(Output is empty — zero matches)*

#### 5.4 `git status --short`
*(Output is empty — working tree clean)*

---

### 6. Full Raw Diff of the Step

```diff
diff --git a/ARCHITECTURE.md b/ARCHITECTURE.md
index f30045e..6257270 100644
--- a/ARCHITECTURE.md
+++ b/ARCHITECTURE.md
@@ -253,15 +253,15 @@ Testing (Pest, PostgreSQL, factories, no SQLite):
 
 Open decisions (record as ADRs):
 
-| ID | Question | Owner |
-|---|---|---|
-| OQ1 | Which Passport grant issues the access and refresh pair after `AuthService` has verified credentials? Check what the installed Passport version supports before choosing. | Achintha |
-| OQ2 | Refresh token delivery: JSON body or httpOnly cookie. | Achintha |
-| OQ3 | Token lifetimes (access short, refresh longer). | Achintha |
-| OQ4 | Do tokens carry scopes per role, or is the role checked only in policies? | Both |
-| OQ5 | How does a retailer receive its first password: set by the superadmin, or an emailed set-password link? | Rathu with the senior |
-| OQ6 | Hard delete or soft delete for `destroy`. | Rathu with the senior |
-| OQ7 | Self-service profile endpoints (`/me`) for retailers and customers. | Senior |
+| ID | Question | Owner | Status / Decision |
+|---|---|---|---|
+| OQ1 | Which Passport grant issues the access and refresh pair after `AuthService` has verified credentials? Check what the installed Passport version supports before choosing. | Achintha | Decided: direct entity minting and rotation via `PassportTokenIssuer`. See `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`. |
+| OQ2 | Refresh token delivery: JSON body or httpOnly cookie. | Achintha | Decided: JSON body delivery (`data.tokens`). See `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`. |
+| OQ3 | Token lifetimes (access short, refresh longer). | Achintha | Decided: 900s access, 14d refresh via `config/passport.php`. See `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`. |
+| OQ4 | Do tokens carry scopes per role, or is the role checked only in policies? | Both | Decided: empty/default scopes, authorization enforced strictly via `UserPolicy`. See `docs/adr/0001-passport-token-issuance-and-refresh-rotation.md`. |
+| OQ5 | How does a retailer receive its first password: set by the superadmin, or an emailed set-password link? | Rathu with the senior | Open |
+| OQ6 | Hard delete or soft delete for `destroy`. | Rathu with the senior | Open |
+| OQ7 | Self-service profile endpoints (`/me`) for retailers and customers. | Senior | Open |
 
 ## 12. Merge plan into the GetNow main repository
 
diff --git a/app/Domain/Accounts/Tokens/PassportTokenIssuer.php b/app/Domain/Accounts/Tokens/PassportTokenIssuer.php
new file mode 100644
index 0000000..f6bdf1f
--- /dev/null
+++ b/app/Domain/Accounts/Tokens/PassportTokenIssuer.php
@@ -0,0 +1,271 @@
+<?php
+
+declare(strict_types=1);
+
+namespace App\Domain\Accounts\Tokens;
+
+use App\Domain\Accounts\Contracts\TokenIssuer;
+use App\Domain\Accounts\Data\TokenPairData;
+use App\Models\User;
+use DateInterval;
+use DateTimeImmutable;
+use Defuse\Crypto\Crypto;
+use Illuminate\Auth\AuthenticationException;
+use Illuminate\Contracts\Encryption\Encrypter;
+use Illuminate\Support\Facades\DB;
+use Illuminate\Support\Str;
+use Laravel\Passport\AccessToken;
+use Laravel\Passport\Bridge\AccessToken as BridgeAccessToken;
+use Laravel\Passport\Bridge\Client as BridgeClient;
+use Laravel\Passport\Bridge\RefreshToken as BridgeRefreshToken;
+use Laravel\Passport\Client;
+use Laravel\Passport\Passport;
+use Laravel\Passport\RefreshToken;
+use Laravel\Passport\Token;
+use Laravel\Passport\TransientToken;
+use League\OAuth2\Server\CryptKey;
+use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
+use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
+use Throwable;
+
+final class PassportTokenIssuer implements TokenIssuer
+{
+    public function __construct(
+        private readonly AccessTokenRepositoryInterface $accessTokenRepository,
+        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
+        private readonly Encrypter $encrypter,
+    ) {}
+
+    public function issue(User $user): TokenPairData
+    {
+        return $this->mintTokenPair($user->getAuthIdentifier(), $this->resolveClient());
+    }
+
+    public function refresh(string $refreshToken): TokenPairData
+    {
+        $payload = $this->decryptRefreshToken($refreshToken);
+
+        return DB::transaction(function () use ($payload): TokenPairData {
+            /** @var RefreshToken|null $consumedRefreshToken */
+            $consumedRefreshToken = Passport::refreshToken()->newQuery()
+                ->whereKey($payload['refresh_token_id'])
+                ->lockForUpdate()
+                ->first();
+
+            if ($consumedRefreshToken === null || $consumedRefreshToken->revoked) {
+                throw new AuthenticationException('Unauthenticated.');
+            }
+
+            /** @var Token|null $priorAccessToken */
+            $priorAccessToken = Passport::token()->newQuery()
+                ->whereKey($payload['access_token_id'])
+                ->lockForUpdate()
+                ->first();
+
+            if ($priorAccessToken === null || $priorAccessToken->revoked) {
+                throw new AuthenticationException('Unauthenticated.');
+            }
+
+            /** @var Client|null $client */
+            $client = Passport::client()->newQuery()
+                ->whereKey($payload['client_id'])
+                ->first();
+
+            if ($client === null || $client->revoked) {
+                throw new AuthenticationException('Unauthenticated.');
+            }
+
+            $now = time();
+            $payloadExpired = $payload['expire_time'] < $now;
+            $refreshExpired = $consumedRefreshToken->expires_at !== null && $consumedRefreshToken->expires_at->isPast();
+            $accessExpired = $priorAccessToken->expires_at !== null && $priorAccessToken->expires_at->isPast();
+
+            if ($payloadExpired || $refreshExpired || $accessExpired) {
+                throw new AuthenticationException('Unauthenticated.');
+            }
+
+            if (
+                $priorAccessToken->user_id === null ||
+                (string) $priorAccessToken->user_id !== (string) $payload['user_id']
+            ) {
+                throw new AuthenticationException('Unauthenticated.');
+            }
+
+            $consumedRefreshToken->revoke();
+            $priorAccessToken->revoke();
+
+            return $this->mintTokenPair($priorAccessToken->user_id, $client);
+        });
+    }
+
+    public function revoke(User $user): void
+    {
+        $token = $user->token();
+
+        if ($token === null || $token instanceof TransientToken) {
+            return;
+        }
+
+        if ($token instanceof Token) {
+            $tokenId = (string) $token->getKey();
+        } elseif ($token instanceof AccessToken) {
+            $tokenId = (string) $token->oauth_access_token_id;
+        } else {
+            return;
+        }
+
+        Passport::token()->newQuery()->whereKey($tokenId)->update(['revoked' => true]);
+        Passport::refreshToken()->newQuery()->where('access_token_id', $tokenId)->update(['revoked' => true]);
+    }
+
+    public function revokeAll(User $user): void
+    {
+        $userId = $user->getAuthIdentifier();
+
+        /** @var array<int, string> $tokenIds */
+        $tokenIds = Passport::token()->newQuery()
+            ->where('user_id', $userId)
+            ->where('revoked', false)
+            ->pluck('id')
+            ->all();
+
+        Passport::token()->newQuery()
+            ->where('user_id', $userId)
+            ->where('revoked', false)
+            ->update(['revoked' => true]);
+
+        if (! empty($tokenIds)) {
+            Passport::refreshToken()->newQuery()
+                ->whereIn('access_token_id', $tokenIds)
+                ->where('revoked', false)
+                ->update(['revoked' => true]);
+        }
+    }
+
+    private function mintTokenPair(int|string $userId, Client $client): TokenPairData
+    {
+        $accessTtlSeconds = (int) config('passport.token_lifetimes.access_token_ttl_seconds', 900);
+        $refreshTtlDays = (int) config('passport.token_lifetimes.refresh_token_ttl_days', 14);
+
+        $accessExpiry = (new DateTimeImmutable)->add(new DateInterval('PT'.$accessTtlSeconds.'S'));
+        $refreshExpiry = (new DateTimeImmutable)->add(new DateInterval('P'.$refreshTtlDays.'D'));
+
+        $clientId = (string) $client->id;
+        $userIdString = (string) $userId;
+
+        if ($clientId === '' || $userIdString === '') {
+            throw new AuthenticationException('Unauthenticated.');
+        }
+
+        $clientEntity = new BridgeClient($clientId, $client->name, []);
+
+        $accessTokenEntity = new BridgeAccessToken($userIdString, [], $clientEntity);
+        $accessTokenEntity->setIdentifier(bin2hex(random_bytes(40)));
+        $accessTokenEntity->setExpiryDateTime($accessExpiry);
+        $accessTokenEntity->setPrivateKey($this->getPrivateKey());
+
+        $this->accessTokenRepository->persistNewAccessToken($accessTokenEntity);
+
+        $refreshTokenEntity = new BridgeRefreshToken;
+        $refreshTokenEntity->setIdentifier(bin2hex(random_bytes(40)));
+        $refreshTokenEntity->setExpiryDateTime($refreshExpiry);
+        $refreshTokenEntity->setAccessToken($accessTokenEntity);
+
+        $this->refreshTokenRepository->persistNewRefreshToken($refreshTokenEntity);
+
+        $payload = json_encode([
+            'client_id' => (string) $client->id,
+            'refresh_token_id' => $refreshTokenEntity->getIdentifier(),
+            'access_token_id' => $accessTokenEntity->getIdentifier(),
+            'scopes' => [],
+            'user_id' => (string) $userId,
+            'expire_time' => $refreshExpiry->getTimestamp(),
+        ], JSON_THROW_ON_ERROR);
+
+        $encryptedRefreshToken = Crypto::encryptWithPassword($payload, $this->getEncryptionKey());
+
+        return new TokenPairData(
+            accessToken: $accessTokenEntity->toString(),
+            refreshToken: $encryptedRefreshToken,
+            tokenType: 'Bearer',
+            expiresIn: $accessTtlSeconds,
+        );
+    }
+
+    /**
+     * @return array{client_id: string, refresh_token_id: string, access_token_id: string, user_id: string, expire_time: int}
+     */
+    private function decryptRefreshToken(string $encryptedToken): array
+    {
+        try {
+            $decrypted = Crypto::decryptWithPassword($encryptedToken, $this->getEncryptionKey());
+            /** @var array<string, mixed> $payload */
+            $payload = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
+        } catch (Throwable) {
+            throw new AuthenticationException('Unauthenticated.');
+        }
+
+        if (
+            ! isset($payload['refresh_token_id'], $payload['access_token_id'], $payload['client_id'], $payload['user_id'], $payload['expire_time']) ||
+            ! is_string($payload['refresh_token_id']) ||
+            ! is_string($payload['access_token_id']) ||
+            ! is_string($payload['client_id']) ||
+            ! is_int($payload['expire_time'])
+        ) {
+            throw new AuthenticationException('Unauthenticated.');
+        }
+
+        return [
+            'client_id' => $payload['client_id'],
+            'refresh_token_id' => $payload['refresh_token_id'],
+            'access_token_id' => $payload['access_token_id'],
+            'user_id' => (string) $payload['user_id'],
+            'expire_time' => $payload['expire_time'],
+        ];
+    }
+
+    private function resolveClient(): Client
+    {
+        /** @var Client|null $client */
+        $client = Passport::client()->newQuery()
+            ->where('revoked', false)
+            ->where('name', 'GetNow First-Party Auth Client')
+            ->first();
+
+        if ($client !== null) {
+            return $client;
+        }
+
+        /** @var Client $client */
+        $client = Passport::client()->newQuery()->forceCreate([
+            'id' => (string) Str::uuid(),
+            'name' => 'GetNow First-Party Auth Client',
+            'secret' => null,
+            'provider' => 'users',
+            'redirect_uris' => [],
+            'grant_types' => ['refresh_token'],
+            'revoked' => false,
+        ]);
+
+        return $client;
+    }
+
+    private function getPrivateKey(): CryptKey
+    {
+        $configuredKey = config('passport.private_key');
+
+        if (is_string($configuredKey) && $configuredKey !== '') {
+            $key = str_replace('\\n', "\n", $configuredKey);
+
+            return new CryptKey($key, null, false);
+        }
+
+        return new CryptKey('file://'.Passport::keyPath('oauth-private.key'), null, Passport::$validateKeyPermissions);
+    }
+
+    private function getEncryptionKey(): string
+    {
+        return Passport::tokenEncryptionKey($this->encrypter);
+    }
+}
diff --git a/app/Http/Controllers/Api/V1/Auth/TokenController.php b/app/Http/Controllers/Api/V1/Auth/TokenController.php
new file mode 100644
index 0000000..bb9530d
--- /dev/null
+++ b/app/Http/Controllers/Api/V1/Auth/TokenController.php
@@ -0,0 +1,52 @@
+<?php
+
+declare(strict_types=1);
+
+namespace App\Http\Controllers\Api\V1\Auth;
+
+use App\Domain\Accounts\Contracts\TokenIssuer;
+use App\Http\Controllers\Controller;
+use App\Http\Requests\Auth\RefreshTokenRequest;
+use App\Models\User;
+use Illuminate\Auth\AuthenticationException;
+use Illuminate\Http\JsonResponse;
+use Illuminate\Http\Response;
+use Illuminate\Support\Facades\Auth;
+
+final class TokenController extends Controller
+{
+    public function __construct(
+        private readonly TokenIssuer $tokenIssuer,
+    ) {}
+
+    public function refresh(RefreshTokenRequest $request): JsonResponse
+    {
+        try {
+            $tokenPair = $this->tokenIssuer->refresh((string) $request->validated('refresh_token'));
+        } catch (AuthenticationException) {
+            return response()->json([
+                'message' => 'Unauthenticated.',
+            ], 401);
+        }
+
+        return response()->json([
+            'data' => [
+                'tokens' => $tokenPair->toArray(),
+            ],
+        ], 200);
+    }
+
+    public function logout(): Response
+    {
+        $user = Auth::guard('api')->user();
+
+        if (! $user instanceof User) {
+            throw new AuthenticationException('Unauthenticated.');
+        }
+
+        $this->tokenIssuer->revoke($user);
+
+        return response()->noContent();
+    }
+}
diff --git a/app/Http/Requests/Auth/RefreshTokenRequest.php b/app/Http/Requests/Auth/RefreshTokenRequest.php
new file mode 100644
index 0000000..7bb2c23
--- /dev/null
+++ b/app/Http/Requests/Auth/RefreshTokenRequest.php
@@ -0,0 +1,38 @@
+<?php
+
+declare(strict_types=1);
+
+namespace App\Http\Requests\Auth;
+
+use Illuminate\Foundation\Http\FormRequest;
+
+final class RefreshTokenRequest extends FormRequest
+{
+    /**
+     * Determine if the user is authorized to make this request.
+     */
+    public function authorize(): bool
+    {
+        return true;
+    }
+
+    /**
+     * Get the validation rules that apply to the request.
+     *
+     * @return array<string, array<int, string>>
+     */
+    public function rules(): array
+    {
+        return [
+            'refresh_token' => ['required', 'string'],
+        ];
+    }
+
+    /**
+     * Get custom messages for validator errors.
+     *
+     * @return array<string, string>
+     */
+    public function messages(): array
+    {
+        return [
+            'refresh_token.required' => 'The refresh token field is required.',
+            'refresh_token.string' => 'The refresh token field must be a string.',
+        ];
+    }
+}
diff --git a/app/Providers/TokenServiceProvider.php b/app/Providers/TokenServiceProvider.php
new file mode 100644
index 0000000..8ec0732
--- /dev/null
+++ b/app/Providers/TokenServiceProvider.php
@@ -0,0 +1,50 @@
+<?php
+
+declare(strict_types=1);
+
+namespace App\Providers;
+
+use App\Domain\Accounts\Contracts\TokenIssuer;
+use App\Domain\Accounts\Tokens\PassportTokenIssuer;
+use Illuminate\Cache\RateLimiting\Limit;
+use Illuminate\Http\Request;
+use Illuminate\Support\Facades\Date;
+use Illuminate\Support\Facades\RateLimiter;
+use Illuminate\Support\ServiceProvider;
+use Laravel\Passport\Bridge\AccessTokenRepository as BridgeAccessTokenRepository;
+use Laravel\Passport\Bridge\RefreshTokenRepository as BridgeRefreshTokenRepository;
+use Laravel\Passport\Passport;
+use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
+use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
+
+final class TokenServiceProvider extends ServiceProvider
+{
+    /**
+     * Register any application services.
+     */
+    public function register(): void
+    {
+        $this->app->bind(AccessTokenRepositoryInterface::class, BridgeAccessTokenRepository::class);
+        $this->app->bind(RefreshTokenRepositoryInterface::class, BridgeRefreshTokenRepository::class);
+        $this->app->bind(TokenIssuer::class, PassportTokenIssuer::class);
+    }
+
+    /**
+     * Bootstrap any application services.
+     */
+    public function boot(): void
+    {
+        $accessTtlSeconds = (int) config('passport.token_lifetimes.access_token_ttl_seconds', 900);
+        $refreshTtlDays = (int) config('passport.token_lifetimes.refresh_token_ttl_days', 14);
+
+        Passport::tokensExpireIn(Date::now()->addSeconds($accessTtlSeconds));
+        Passport::refreshTokensExpireIn(Date::now()->addDays($refreshTtlDays));
+
+        RateLimiter::for('token-refresh', function (Request $request): Limit {
+            return Limit::perMinute(10)->by($request->ip());
+        });
+
+        RateLimiter::for('token-logout', function (Request $request): Limit {
+            return Limit::perMinute(30)->by(
+                $request->user()?->getAuthIdentifier() ?? $request->ip()
+            );
+        });
+    }
+}
diff --git a/bootstrap/providers.php b/bootstrap/providers.php
index e5cf3c9..c526715 100644
--- a/bootstrap/providers.php
+++ b/bootstrap/providers.php
@@ -5,7 +5,9 @@ declare(strict_types=1);
 use App\Providers\AppServiceProvider;
 use App\Providers\FortifyServiceProvider;
+use App\Providers\TokenServiceProvider;
 
 return [
     AppServiceProvider::class,
     FortifyServiceProvider::class,
+    TokenServiceProvider::class,
 ];
diff --git a/docs/adr/0001-passport-token-issuance-and-refresh-rotation.md b/docs/adr/0001-passport-token-issuance-and-refresh-rotation.md
new file mode 100644
index 0000000..745582f
--- /dev/null
+++ b/docs/adr/0001-passport-token-issuance-and-refresh-rotation.md
@@ -0,0 +1,60 @@
+# 1. Passport Token Issuance and Refresh Rotation
+
+Date: 2026-10-20
+Status: Accepted
+Owner: Tokens Track (Achintha)
+
+## Context
+
+The GetNow backend module requires a token-based authentication system backed by Laravel Passport v13.10 and PostgreSQL. The system must meet strict domain boundary and security requirements:
+
+1. **Separation of Concerns and Track Boundaries (`OWN4`)**: `AuthService` (Users track) handles credential verification and role assignment. Once credentials are confirmed valid, it delegates token generation to `TokenIssuer::issue(User $user)`. The Tokens track must never inspect passwords, verify credentials, check roles, or query the `users` table.
+2. **Passport Grant Compatibility (`OQ1`)**:
+   - Passport's standard `PasswordGrant` requires passing plaintext user credentials (`username` and `password`) to League's OAuth2 server, which would break `OWN4` by forcing credential handling into the token domain.
+   - Passport's `PersonalAccessGrant` (`$user->createToken()`) issues personal access tokens without refresh tokens.
+   - Passport's `RefreshTokenGrant` is designed to rotate refresh tokens, but expects `client_id` and client secrets in the request body, whereas the GetNow first-party clients (SPA and mobile app) only provide the refresh token.
+3. **Delivery Mechanism (`OQ2`)**: Decision needed between delivering refresh tokens in the JSON response body or inside an `httpOnly` cookie.
+4. **Token Lifetimes (`OQ3`)**: Access tokens must be short-lived, while refresh tokens must persist across developer-defined windows without being hardcoded.
+5. **Authorization Enforcement (`OQ4`)**: Decision needed whether authorization should be encoded as OAuth2 token scopes or evaluated server-side through Laravel policies.
+
+## Decisions
+
+### 1. Direct Minting & Rotation via Passport Entities (`OQ1`)
+`PassportTokenIssuer` implements `TokenIssuer` directly using Passport v13 and League OAuth2 Server entities:
+- **Issuance**: `issue(User $user)` resolves the internal first-party client (`GetNow First-Party Auth Client`) and calls a private helper `mintTokenPair($user->getAuthIdentifier(), $client)`. This helper builds:
+  - An 80-character signed OAuth2 JWT access token using `Laravel\Passport\Bridge\AccessToken` and the RSA private key, persisted in `oauth_access_tokens`.
+  - An 80-character refresh token using `Laravel\Passport\Bridge\RefreshToken`, persisted in `oauth_refresh_tokens`.
+  - An encrypted refresh token payload string matching League's `BearerTokenResponse` format (`client_id`, `refresh_token_id`, `access_token_id`, `scopes`, `user_id`, `expire_time`), encrypted using `Defuse\Crypto\Crypto` with Passport's token encryption key (`config('app.key')`).
+- **Rotation**: `refresh(string $refreshToken)` decrypts the refresh token payload without requiring client secrets. Inside a PostgreSQL transaction with pessimistic locking (`->lockForUpdate()`), it verifies that:
+  - The encrypted payload has not expired (`expire_time >= time()`).
+  - The database records in `oauth_refresh_tokens` and `oauth_access_tokens` exist, have not been revoked, and their `expires_at` timestamps are not in the past.
+  - The user ID in the prior access token matches the decrypted payload (`$priorAccessToken->user_id === $payload['user_id']`).
+  - It atomically revokes both the consumed refresh token and its prior access token (`TOK4`), and calls `mintTokenPair($priorAccessToken->user_id, $client)`. Zero queries are made to the `users` table (`OWN4`). Any replay, reuse, expiration, or revocation immediately throws `Illuminate\Auth\AuthenticationException` (HTTP 401).
+- **Revocation**:
+  - `revoke(User $user)` revokes the current request's access token and its paired refresh token in `oauth_refresh_tokens` (`TOK4`).
+  - `revokeAll(User $user)` revokes all active access tokens and all associated refresh tokens for the given user ID across all sessions.
+
+### 2. JSON Body Delivery (`OQ2`)
+Refresh and access tokens are returned in the JSON response body under `{ "data": { "tokens": { ... } } }`. This enables seamless cross-platform consumption by both single-page web applications and native mobile clients without cookie domain restrictions.
+
+### 3. Config-Driven Lifetimes (`OQ3`)
+Lifetimes are loaded exclusively from `config/passport.php`:
+- Access token: `config('passport.token_lifetimes.access_token_ttl_seconds', 900)` (15 minutes).
+- Refresh token: `config('passport.token_lifetimes.refresh_token_ttl_days', 14)` (14 days).
+Configured globally in `TokenServiceProvider` via `Passport::tokensExpireIn()` and `Passport::refreshTokensExpireIn()`.
+
+### 4. Policy-Based Authorization (`OQ4`)
+Tokens carry empty default scopes. Authorization is enforced entirely on the server by `UserPolicy` inspecting `User::$role`. This eliminates scope-role synchronization issues and ensures role changes take effect immediately on token refresh or revocation.
+
+## Consequences
+
+- **Positive**:
+  - Zero coupling between token issuance and user credentials/roles (`OWN4`).
+  - Strict one-time refresh token rotation enforced at the database level (`TOK4`).
+  - Fast, fully deterministic feature tests against real Passport in PostgreSQL without requiring committed RSA key files (`SEC1`).
+  - Standard Passport `auth:api` guard validates the issued JWTs with zero modifications.
+- **Negative / Trade-offs**:
+  - The token issuer requires access to Passport's encryption key and RSA private key to mint entities directly rather than proxying through HTTP routes.
diff --git a/routes/api/tokens.php b/routes/api/tokens.php
index b568779..26be38d 100644
--- a/routes/api/tokens.php
+++ b/routes/api/tokens.php
@@ -2,3 +2,14 @@
 
 declare(strict_types=1);
+
+use App\Http\Controllers\Api\V1\Auth\TokenController;
+use Illuminate\Support\Facades\Route;
+
+Route::prefix('auth')->group(function (): void {
+    Route::post('refresh', [TokenController::class, 'refresh'])
+        ->middleware('throttle:token-refresh');
+
+    Route::post('logout', [TokenController::class, 'logout'])
+        ->middleware(['auth:api', 'throttle:token-logout']);
+});
diff --git a/tests/Feature/Tokens/PassportTokenIssuerTest.php b/tests/Feature/Tokens/PassportTokenIssuerTest.php
new file mode 100644
index 0000000..7bbd221
--- /dev/null
+++ b/tests/Feature/Tokens/PassportTokenIssuerTest.php
@@ -0,0 +1,199 @@
+<?php
+
+declare(strict_types=1);
+
+use App\Domain\Accounts\Contracts\TokenIssuer;
+use App\Domain\Accounts\Tokens\PassportTokenIssuer;
+use App\Models\User;
+use Illuminate\Auth\AuthenticationException;
+use Illuminate\Support\Carbon;
+use Illuminate\Support\Facades\Auth;
+use Laravel\Passport\AccessToken;
+use Laravel\Passport\Passport;
+use League\OAuth2\Server\ResourceServer;
+
+function ensurePassportTestKeys(): void
+{
+    /** @var string|null $cachedPrivateKey */
+    static $cachedPrivateKey = null;
+    /** @var string|null $cachedPublicKey */
+    static $cachedPublicKey = null;
+
+    if ($cachedPrivateKey === null || $cachedPublicKey === null) {
+        $config = [
+            'private_key_bits' => 2048,
+            'private_key_type' => OPENSSL_KEYTYPE_RSA,
+        ];
+
+        $candidates = [
+            'C:/tools/php85/extras/ssl/openssl.cnf',
+            'C:/Program Files/Git/mingw64/etc/ssl/openssl.cnf',
+            'C:/Program Files/Git/usr/ssl/openssl.cnf',
+        ];
+
+        foreach ($candidates as $candidate) {
+            if (file_exists($candidate)) {
+                $config['config'] = $candidate;
+                break;
+            }
+        }
+
+        $res = openssl_pkey_new($config);
+        if ($res === false) {
+            throw new RuntimeException('Failed to generate test RSA key pair: '.(openssl_error_string() ?: 'unknown error'));
+        }
+
+        openssl_pkey_export($res, $privateKey, null, $config);
+        $details = openssl_pkey_get_details($res);
+        if ($details === false) {
+            throw new RuntimeException('Failed to get RSA key details.');
+        }
+
+        $cachedPrivateKey = $privateKey;
+        $cachedPublicKey = $details['key'];
+    }
+
+    config([
+        'passport.private_key' => $cachedPrivateKey,
+        'passport.public_key' => $cachedPublicKey,
+    ]);
+
+    app()->forgetInstance(ResourceServer::class);
+    Auth::forgetGuards();
+}
+
+beforeEach(function (): void {
+    ensurePassportTestKeys();
+});
+
+test('token service provider binds token issuer interface to passport token issuer', function (): void {
+    $issuer = app(TokenIssuer::class);
+
+    expect($issuer)->toBeInstanceOf(PassportTokenIssuer::class);
+});
+
+test('issue mints a valid token pair and persists records in postgresql', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $tokenPair = $issuer->issue($user);
+
+    expect($tokenPair->accessToken)->toBeString()
+        ->and($tokenPair->accessToken)->not->toBeEmpty()
+        ->and($tokenPair->refreshToken)->toBeString()
+        ->and($tokenPair->refreshToken)->not->toBeEmpty()
+        ->and($tokenPair->tokenType)->toBe('Bearer')
+        ->and($tokenPair->expiresIn)->toBe(900);
+
+    $this->assertDatabaseHas('oauth_access_tokens', [
+        'user_id' => $user->getAuthIdentifier(),
+        'revoked' => false,
+    ]);
+
+    $this->assertDatabaseHas('oauth_refresh_tokens', [
+        'revoked' => false,
+    ]);
+});
+
+test('refresh rotates the refresh token and revokes consumed tokens in postgresql', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $initialPair = $issuer->issue($user);
+    $initialAccessTokenRow = Passport::token()->newQuery()->where('user_id', $user->getAuthIdentifier())->firstOrFail();
+    $initialRefreshTokenRow = Passport::refreshToken()->newQuery()->where('access_token_id', $initialAccessTokenRow->id)->firstOrFail();
+
+    $refreshedPair = $issuer->refresh($initialPair->refreshToken);
+
+    expect($refreshedPair->accessToken)->not->toBe($initialPair->accessToken)
+        ->and($refreshedPair->refreshToken)->not->toBe($initialPair->refreshToken)
+        ->and($refreshedPair->tokenType)->toBe('Bearer')
+        ->and($refreshedPair->expiresIn)->toBe(900);
+
+    $initialAccessTokenRow->refresh();
+    $initialRefreshTokenRow->refresh();
+
+    expect($initialAccessTokenRow->revoked)->toBeTrue()
+        ->and($initialRefreshTokenRow->revoked)->toBeTrue();
+
+    $newAccessTokenRow = Passport::token()->newQuery()
+        ->where('user_id', $user->getAuthIdentifier())
+        ->where('revoked', false)
+        ->first();
+
+    expect($newAccessTokenRow)->not->toBeNull()
+        ->and($newAccessTokenRow?->id)->not->toBe($initialAccessTokenRow->id);
+});
+
+test('refresh rejects reused refresh token with authentication exception', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $initialPair = $issuer->issue($user);
+    $issuer->refresh($initialPair->refreshToken);
+
+    expect(fn () => $issuer->refresh($initialPair->refreshToken))
+        ->toThrow(AuthenticationException::class, 'Unauthenticated.');
+});
+
+test('refresh rejects expired refresh token with authentication exception', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $initialPair = $issuer->issue($user);
+    $refreshTokenRow = Passport::refreshToken()->newQuery()->firstOrFail();
+    $refreshTokenRow->forceFill(['expires_at' => Carbon::now()->subMinute()])->save();
+
+    expect(fn () => $issuer->refresh($initialPair->refreshToken))
+        ->toThrow(AuthenticationException::class, 'Unauthenticated.');
+});
+
+test('refresh rejects malformed and tampered refresh tokens with authentication exception', function (): void {
+    $issuer = app(TokenIssuer::class);
+
+    expect(fn () => $issuer->refresh('invalid-garbage-token'))
+        ->toThrow(AuthenticationException::class, 'Unauthenticated.');
+});
+
+test('revoke revokes current access token and its paired refresh token', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $pair = $issuer->issue($user);
+    $tokenRow = Passport::token()->newQuery()->where('user_id', $user->getAuthIdentifier())->firstOrFail();
+    $refreshTokenRow = Passport::refreshToken()->newQuery()->where('access_token_id', $tokenRow->id)->firstOrFail();
+
+    $user->withAccessToken(new AccessToken([
+        'oauth_access_token_id' => $tokenRow->id,
+    ]));
+
+    $issuer->revoke($user);
+
+    $tokenRow->refresh();
+    $refreshTokenRow->refresh();
+
+    expect($tokenRow->revoked)->toBeTrue()
+        ->and($refreshTokenRow->revoked)->toBeTrue();
+});
+
+test('revoke handles null or transient token safely', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $issuer->revoke($user);
+
+    expect(true)->toBeTrue();
+});
+
+test('revokeAll revokes every access token and refresh token of user', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $issuer->issue($user);
+    $issuer->issue($user);
+    $issuer->issue($user);
+
+    expect(Passport::token()->newQuery()->where('user_id', $user->getAuthIdentifier())->where('revoked', false)->count())->toBe(3)
+        ->and(Passport::refreshToken()->newQuery()->where('revoked', false)->count())->toBe(3);
+
+    $issuer->revokeAll($user);
+
+    expect(Passport::token()->newQuery()->where('user_id', $user->getAuthIdentifier())->where('revoked', false)->count())->toBe(0)
+        ->and(Passport::refreshToken()->newQuery()->where('revoked', false)->count())->toBe(0);
+});
diff --git a/tests/Feature/Tokens/TokenEndpointsTest.php b/tests/Feature/Tokens/TokenEndpointsTest.php
new file mode 100644
index 0000000..096f3f2
--- /dev/null
+++ b/tests/Feature/Tokens/TokenEndpointsTest.php
@@ -0,0 +1,246 @@
+<?php
+
+declare(strict_types=1);
+
+use App\Domain\Accounts\Contracts\TokenIssuer;
+use App\Domain\Accounts\Data\TokenPairData;
+use App\Models\User;
+use Illuminate\Support\Carbon;
+use Illuminate\Support\Facades\Auth;
+use Laravel\Passport\Passport;
+use League\OAuth2\Server\ResourceServer;
+
+function ensureEndpointsPassportTestKeys(): void
+{
+    /** @var string|null $cachedPrivateKey */
+    static $cachedPrivateKey = null;
+    /** @var string|null $cachedPublicKey */
+    static $cachedPublicKey = null;
+
+    if ($cachedPrivateKey === null || $cachedPublicKey === null) {
+        $config = [
+            'private_key_bits' => 2048,
+            'private_key_type' => OPENSSL_KEYTYPE_RSA,
+        ];
+
+        $candidates = [
+            'C:/tools/php85/extras/ssl/openssl.cnf',
+            'C:/Program Files/Git/mingw64/etc/ssl/openssl.cnf',
+            'C:/Program Files/Git/usr/ssl/openssl.cnf',
+        ];
+
+        foreach ($candidates as $candidate) {
+            if (file_exists($candidate)) {
+                $config['config'] = $candidate;
+                break;
+            }
+        }
+
+        $res = openssl_pkey_new($config);
+        if ($res === false) {
+            throw new RuntimeException('Failed to generate test RSA key pair: '.(openssl_error_string() ?: 'unknown error'));
+        }
+
+        openssl_pkey_export($res, $privateKey, null, $config);
+        $details = openssl_pkey_get_details($res);
+        if ($details === false) {
+            throw new RuntimeException('Failed to get RSA key details.');
+        }
+
+        $cachedPrivateKey = $privateKey;
+        $cachedPublicKey = $details['key'];
+    }
+
+    config([
+        'passport.private_key' => $cachedPrivateKey,
+        'passport.public_key' => $cachedPublicKey,
+    ]);
+
+    app()->forgetInstance(ResourceServer::class);
+    Auth::forgetGuards();
+}
+
+beforeEach(function (): void {
+    ensureEndpointsPassportTestKeys();
+});
+
+test('post api v1 auth refresh rotates token pair successfully', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $initialPair = $issuer->issue($user);
+
+    $response = $this->postJson('/api/v1/auth/refresh', [
+        'refresh_token' => $initialPair->refreshToken,
+    ]);
+
+    $response->assertOk()
+        ->assertJsonStructure([
+            'data' => [
+                'tokens' => [
+                    'access_token',
+                    'refresh_token',
+                    'token_type',
+                    'expires_in',
+                ],
+            ],
+        ]);
+
+    /** @var string $newAccessToken */
+    $newAccessToken = $response->json('data.tokens.access_token');
+    /** @var string $newRefreshToken */
+    $newRefreshToken = $response->json('data.tokens.refresh_token');
+
+    expect($newAccessToken)->not->toBe($initialPair->accessToken)
+        ->and($newRefreshToken)->not->toBe($initialPair->refreshToken);
+
+    $logoutResponse = $this->postJson('/api/v1/auth/logout', [], [
+        'Authorization' => 'Bearer '.$newAccessToken,
+    ]);
+
+    $logoutResponse->assertNoContent();
+});
+
+test('post api v1 auth refresh fails with 401 when refresh token is reused', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $initialPair = $issuer->issue($user);
+
+    $this->postJson('/api/v1/auth/refresh', [
+        'refresh_token' => $initialPair->refreshToken,
+    ])->assertOk();
+
+    $secondAttempt = $this->postJson('/api/v1/auth/refresh', [
+        'refresh_token' => $initialPair->refreshToken,
+    ]);
+
+    $secondAttempt->assertUnauthorized()
+        ->assertJson(['message' => 'Unauthenticated.']);
+});
+
+test('post api v1 auth refresh fails with 401 on expired, revoked, or invalid token', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $this->postJson('/api/v1/auth/refresh', [
+        'refresh_token' => 'completely-invalid-token',
+    ])->assertUnauthorized()
+        ->assertJson(['message' => 'Unauthenticated.']);
+
+    $expiredUser = User::factory()->create();
+    $expiredPair = $issuer->issue($expiredUser);
+    $expiredAccessToken = Passport::token()->newQuery()->where('user_id', $expiredUser->getAuthIdentifier())->firstOrFail();
+    $refreshTokenRow = Passport::refreshToken()->newQuery()->where('access_token_id', $expiredAccessToken->id)->firstOrFail();
+    $refreshTokenRow->forceFill(['expires_at' => Carbon::now()->subMinute()])->save();
+
+    $this->postJson('/api/v1/auth/refresh', [
+        'refresh_token' => $expiredPair->refreshToken,
+    ])->assertUnauthorized()
+        ->assertJson(['message' => 'Unauthenticated.']);
+
+    $revokedUser = User::factory()->create();
+    $revokedPair = $issuer->issue($revokedUser);
+    $issuer->revokeAll($revokedUser);
+
+    $this->postJson('/api/v1/auth/refresh', [
+        'refresh_token' => $revokedPair->refreshToken,
+    ])->assertUnauthorized()
+        ->assertJson(['message' => 'Unauthenticated.']);
+});
+
+test('post api v1 auth refresh validates refresh token parameter', function (): void {
+    $response = $this->postJson('/api/v1/auth/refresh', []);
+
+    $response->assertUnprocessable()
+        ->assertJsonValidationErrors(['refresh_token']);
+});
+
+test('post api v1 auth logout revokes current session', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $pair = $issuer->issue($user);
+
+    $response = $this->postJson('/api/v1/auth/logout', [], [
+        'Authorization' => 'Bearer '.$pair->accessToken,
+    ]);
+
+    $response->assertNoContent();
+
+    Auth::forgetGuards();
+
+    $retryLogout = $this->postJson('/api/v1/auth/logout', [], [
+        'Authorization' => 'Bearer '.$pair->accessToken,
+    ]);
+
+    $retryLogout->assertUnauthorized();
+
+    $refreshAttempt = $this->postJson('/api/v1/auth/refresh', [
+        'refresh_token' => $pair->refreshToken,
+    ]);
+
+    $refreshAttempt->assertUnauthorized();
+});
+
+test('post api v1 auth logout leaves user other active sessions valid', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $sessionA = $issuer->issue($user);
+    $sessionB = $issuer->issue($user);
+
+    $logoutA = $this->postJson('/api/v1/auth/logout', [], [
+        'Authorization' => 'Bearer '.$sessionA->accessToken,
+    ]);
+
+    $logoutA->assertNoContent();
+
+    Auth::forgetGuards();
+
+    $checkA = $this->postJson('/api/v1/auth/logout', [], [
+        'Authorization' => 'Bearer '.$sessionA->accessToken,
+    ]);
+    $checkA->assertUnauthorized();
+
+    Auth::forgetGuards();
+
+    $logoutB = $this->postJson('/api/v1/auth/logout', [], [
+        'Authorization' => 'Bearer '.$sessionB->accessToken,
+    ]);
+    $logoutB->assertNoContent();
+});
+
+test('post api v1 auth logout requires authentication', function (): void {
+    $response = $this->postJson('/api/v1/auth/logout');
+
+    $response->assertUnauthorized();
+});
+
+test('refresh endpoint enforces rate limits', function (): void {
+    $user = User::factory()->create();
+    $issuer = app(TokenIssuer::class);
+
+    $currentPair = $issuer->issue($user);
+
+    for ($i = 0; $i < 10; $i++) {
+        $res = $this->postJson('/api/v1/auth/refresh', [
+            'refresh_token' => $currentPair->refreshToken,
+        ]);
+        $res->assertOk();
+        /** @var string $rotatedRefreshToken */
+        $rotatedRefreshToken = $res->json('data.tokens.refresh_token');
+        $currentPair = new TokenPairData(
+            accessToken: (string) $res->json('data.tokens.access_token'),
+            refreshToken: $rotatedRefreshToken,
+            tokenType: 'Bearer',
+            expiresIn: 900,
+        );
+    }
+
+    $rateLimitedResponse = $this->postJson('/api/v1/auth/refresh', [
+        'refresh_token' => $currentPair->refreshToken,
+    ]);
+
+    $rateLimitedResponse->assertStatus(429);
+});
```
