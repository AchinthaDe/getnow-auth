# Authentication token handoff

The users track owns credentials, user creation, DTOs, validation, controllers,
services, repositories, and login/registration screens. The tokens track owns
Passport, token generation, storage, refresh, revocation, expiry, API guards,
and the production `TokenIssuer` binding.

## Calling the token owner

`AuthService` depends on the existing
`App\Domain\Accounts\Contracts\TokenIssuer` interface. After checking a login
password or saving a registration, it calls `issue($user)` and returns the
owner's `TokenPairData` without generating or modifying tokens.

The token owner must implement the existing interface and register its binding
in their service provider. This change intentionally supplies no production
binding or fallback. Until that provider lands, the API authentication endpoints
fail with a container resolution error. Browser session authentication continues
to work independently.

The existing `TokenIssuer` and `TokenPairData` contracts are unchanged.
`refresh()`, `revoke()`, and `revokeAll()` remain the token owner's integration
points; this change does not add refresh/logout endpoints or token lifecycle code.

## Endpoints

- `POST /api/v1/auth/login`: `email` (email address or username), `password`.
- `POST /api/v1/auth/register`: `name`, `email`, `username`, `address`,
  `mobile_phone`, `password`, `password_confirmation`.

Both endpoints are throttled. Registration shares normalization and validation
with the existing browser registration action. Password failures and unknown
identifiers return the same validation error. API password login rejects accounts
with enabled two-factor authentication because this API has no two-factor flow.

Success responses use this shape (login: HTTP 200, registration: HTTP 201):

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "username": "jane_doe"
    },
    "tokens": {
      "access_token": "<provided by TokenIssuer>",
      "refresh_token": "<provided by TokenIssuer>",
      "token_type": "Bearer",
      "expires_in": 900
    }
  }
}
```

The expiry above is an example, not a configured lifetime. User output uses the
current schema's numeric ID and fields. Roles/public IDs described in
`ARCHITECTURE.md` are separate pending work; this handoff adds neither. Public
registration input cannot set a role. Passwords and contact details are omitted
from the authentication response.

User creation and token issuance run in one database transaction during API
registration so issuance failures roll back the new account. The token owner
should account for this transaction when implementing `issue()`.

The existing browser forms still use Fortify sessions. Browser token delivery,
cookies, refresh scheduling, and API guard integration belong to the token
owner's follow-up; the screens do not store raw tokens.

## Verification

`tests/Feature/Accounts/AuthTokenHandoffTest.php` binds a test-only mock of
`TokenIssuer`. It verifies successful handoff, invalid credentials and input,
registration rollback, throttling, and the absence of a production binding.
No mock is bound in application code. The current repository test configuration
uses in-memory SQLite; the PostgreSQL test target described in the architecture
is not configured by this change.
