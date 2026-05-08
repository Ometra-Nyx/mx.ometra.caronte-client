# Release v3.3.0 "Chronos"

> **Release date:** 2026-05-07
> **Type:** Minor — new features added backwards-compatibly. No breaking changes.

---

## Summary

v3.3.0 "Chronos" delivers three focused improvements to the Caronte SDK: **configurable token clock skew**, a **GitHub Actions CI pipeline**, and a **frontend TypeScript migration** with new legacy-compatible management routes. Clock-skew tolerance closes an operational gap for multi-host deployments where clocks are not perfectly synchronised. The CI pipeline brings automated PHP and TypeScript quality checks to every push and PR. Migrating the React UI to TypeScript improves long-term maintainability and enables compile-time safety for SDK data shapes.

The codename *Chronos* — the Greek personification of time — reflects the clock-skew theme and the automated, time-orchestrated CI jobs that now guard each commit.

---

## Highlights

- **Configurable clock skew** — `CARONTE_TOKEN_CLOCK_SKEW_SECONDS` (default `60`) lets deployments tolerate small clock differences without token validation failures.
- **GitHub Actions CI** — PHP (PHPUnit) and TypeScript (`tsc --noEmit`) jobs run automatically on push and pull requests.
- **TypeScript frontend** — all React management pages are now `.tsx` with a shared `types.ts` file; no host-application changes required.
- **Legacy management routes** — `users.list` and `users.roles.list` JSON endpoints added for backwards-compatible integrations.
- **Improved Blade views** — create-user modal includes a temporary password field; roles partial is now resilient to missing variables.

---

## Added

### Configurable Token Clock Skew

Set `CARONTE_TOKEN_CLOCK_SKEW_SECONDS` in your environment (or override `token_clock_skew_seconds` in `config/caronte.php`) to allow a leeway window during `iat`/`nbf` claim validation:

```dotenv
# .env
CARONTE_TOKEN_CLOCK_SKEW_SECONDS=120   # allow up to 2 minutes of clock drift
```

The default is `60` seconds — the same leeway applied silently in previous releases via a hard-coded constant. No host-application changes are required if the default is acceptable.

### GitHub Actions CI Workflow

`.github/workflows/ci.yml` runs two parallel jobs on every push to `main`/`dev` and on pull requests:

| Job        | Tool        | What it checks                       |
| ---------- | ----------- | ------------------------------------ |
| PHP        | PHPUnit     | Full test suite via `composer test`  |
| TypeScript | `tsc --noEmit` | Type correctness of frontend assets |

### Frontend TypeScript Migration

All management React pages have been migrated from `.jsx` to `.tsx`. A new `resources/js/types.ts` file provides strongly-typed interfaces for SDK data structures:

```ts
// resources/js/types.ts (excerpt)
export interface CaronteUser {
    uuid: string;
    name: string;
    email: string;
    roles: CaronteRole[];
    // ...
}
```

`tsconfig.json` and `package.json` (TypeScript dev-dependencies) are included so the type-check step works with `npm ci && npm run typecheck` out of the box.

### Legacy Management Routes

New named routes for backwards-compatible JSON access:

| Route name          | Method | URI                            |
| ------------------- | ------ | ------------------------------ |
| `users.list`        | GET    | `/caronte/management/users`    |
| `users.roles.list`  | GET    | `/caronte/management/users/{user}/roles` |

`UserController` exposes `list()`, `listRoles()`, and legacy `update()`/`delete()` wrapper methods. `RoleController` redirects unsupported legacy mutations with a clear error response.

---

## Changed

- `ManagementController` now passes configured roles to index/dashboard views.
- Blade `create.blade.php` modal includes a temporary password field.
- `roles-checkboxes.blade.php` partial uses an `availableRoles` fallback to prevent undefined-variable errors.
- `doc/routes-documentation.md` updated to document current vs legacy routes.
- `doc/deployment-instructions.md` notes the new TSX asset compilation step.

---

## Full History

See [CHANGELOG.md](CHANGELOG.md) for the complete project history.
No breaking changes in this release. See [BREAKING_CHANGES.md](BREAKING_CHANGES.md) for migration guidance on previous versions.

---

# Release v3.2.1

> **Release date:** 2026-05-07
> **Type:** Patch — backwards-compatible security improvement.

---

## Summary

v3.2.1 delivers a targeted security hardening to the Caronte SDK's HTTP layer. Every user-authenticated API call made through `CaronteHttpClient::userRequest()` now includes both the `X-Application-Token` and the `X-User-Token` headers. Previously only `X-User-Token` was sent, which meant the Caronte server could not simultaneously verify the calling application's identity during user-context operations. This patch closes that gap without requiring any host-application changes.

---

## Highlights

- **Dual-token user requests** — `CaronteHttpClient::userRequest()` now sends `X-Application-Token` alongside `X-User-Token`, strengthening the trust chain for all user-context API calls.
- **Test coverage updated** — `AuthContractTest` updated to assert both tokens are present in user requests.

---

## Changed

### `X-Application-Token` Added to User Requests

`CaronteHttpClient::userRequest()` previously sent only the `X-User-Token` header. Starting with v3.2.1 it also sends the `X-Application-Token`, derived from the configured application credentials.

**Before (≤ v3.2.0):**

```php
// Only user token was forwarded
'X-User-Token' => Caronte::getToken()->toString()
```

**After (v3.2.1):**

```php
// Both application and user tokens are forwarded
'X-Application-Token' => $this->makeApplicationToken(),
'X-User-Token'        => Caronte::getToken()->toString(),
```

No configuration changes or host-application code changes are required. The `CARONTE_APP_CN` and `CARONTE_APP_SECRET` credentials already in use for app-level requests are reused here.

---

## Full History

See [CHANGELOG.md](CHANGELOG.md) for the complete project history.

---

# Release v3.2.0 "Hermes"

> **Release date:** 2026-05-06
> **Type:** Minor — new features added backwards-compatibly.
> One internal class removal requires attention if referenced directly. See [BREAKING_CHANGES.md](BREAKING_CHANGES.md).

---

## Summary

v3.2.0 "Hermes" delivers three focused improvements to the Caronte SDK: **multi-tenant login support**, **phase-2 (flat) JWT claim parsing**, and a **new provisioning API client**. Host applications with multi-tenant user bases can now present a tenant picker at login time without any custom controller logic. The phase-2 JWT changes introduce a flatter claim structure that coexists transparently with the legacy `user` nested claim — no migration required for existing tokens. Server-side tenant provisioning is now first-class through the new `ProvisioningApi`.

The codename _Hermes_ — the messenger god who moved freely between realms and delivered messages between mortals and gods — captures the theme of this release: bridging multiple tenant realms at login, carrying phase-2 claims between client and server, and provisioning new tenants through a dedicated message channel.

---

## Highlights

- **Multi-tenant login** — automatic tenant picker when a user belongs to multiple tenants; 409 `tenant_selection_required` handled gracefully out of the box.
- **Phase-2 JWT claims** — `CaronteUserToken::userPayload()` reads flat top-level claims (`sub`, `tenant_id`, `roles`, `metadata`) while falling back to legacy nested `user` claim.
- **`ProvisioningApi`** — `provisionTenant()` triggers server-side tenant provisioning; `AuthApi` updated for new two-factor and logout endpoints.
- **`.editorconfig` / `.gitattributes`** — repository-wide tooling rules for consistent formatting and line endings.
- **`RouteMode` removed** — `Ometra\Caronte\Support\RouteMode` replaced by inline `Request` detection. See [BREAKING_CHANGES.md](BREAKING_CHANGES.md).

---

## Added

### Multi-Tenant Login Flow

When the Caronte server returns HTTP 409 `tenant_selection_required`, the SDK now handles it automatically:

| Component                           | Responsibility                                                |
| ----------------------------------- | ------------------------------------------------------------- |
| `AuthController`                    | Reads `tenant_options` from session; exposes them to view/SPA |
| `AuthApi::login()`                  | Accepts optional `$tenantId`; includes it in sign-in payload  |
| `CaronteResponse::conflict()`       | Returns 409 responses (JSON or redirect) with tenant data     |
| `CaronteResponse::redirectErrors()` | Shapes session error data before redirect                     |
| React login form                    | Renders tenant dropdown when `tenant_options` present         |
| Blade login view                    | Renders tenant dropdown when `tenant_options` present         |

### Phase-2 JWT Claims

`CaronteUserToken::userPayload()` now prefers the phase-2 flat claim structure:

```json
{
    "sub": "user-uuid",
    "tenant_id": "tenant-uuid",
    "roles": ["admin"],
    "metadata": {}
}
```

Falls back to the legacy nested structure if `sub` is absent:

```json
{
    "user": {
        "id": "user-uuid",
        "tenant_id": "tenant-uuid",
        "roles": ["admin"]
    }
}
```

No code changes required in host applications for either structure.

### Provisioning API

```php
// New ProvisioningApi client
$api = new ProvisioningApi();
$api->provisionTenant($tenantId);
```

---

## Changed

- `CaronteUserHelper` metadata lookup now uses the `DB` facade directly.
- `AuthApi` updated for new two-factor endpoints and includes the user token on logout calls.
- Request-based JSON/web detection inlined; `RouteMode` static helper removed.
- Route prefix and login URL config defaults updated.
- Documentation updated: `api-documentation.md`, `business-logic-and-core-processes.md`, `deployment-instructions.md`, `routes-documentation.md`, `tests-documentation.md`.

---

## Removed

- `Ometra\Caronte\Support\RouteMode` — see [BREAKING_CHANGES.md](BREAKING_CHANGES.md) for migration.

---

## Full History

- [CHANGELOG.md](CHANGELOG.md) — complete project history.
- [BREAKING_CHANGES.md](BREAKING_CHANGES.md) — migration guides for all breaking changes.

---

# Release v3.1.0 "Aegis"

> **Release date:** 2026-05-04
> **Type:** Minor — new features added backwards-compatibly.
> One dependency change requires attention. See [BREAKING_CHANGES.md](BREAKING_CHANGES.md).

---

## Summary

v3.1.0 "Aegis" extends the Caronte SDK with two major capability pillars: **OpenID Connect (OIDC) federated authentication** and **application-group permission controls**. Host applications can now authenticate users through a standard OIDC/OAuth 2.0 authorization code flow with PKCE, validate ID tokens against a live JWKS endpoint, and enforce fine-grained server-to-server permissions via group-level application tokens — all without changing a single existing integration point.

The codename _Aegis_ (the divine shield borne by Zeus and Athena) reflects the expanded protective surface this release adds: standards-based identity federation, group-scoped credential boundaries, and declarative permission synchronisation, layered on top of the solid foundation laid by v3.0.0 "Archon".

---

## Highlights

- **OIDC / OAuth 2.0 login** — full authorization code + PKCE flow, JWKS-backed token validation, `dual` mode for gradual migration from legacy JWT.
- **Application-group tokens** — `CARONTE_APPLICATION_GROUP_ID` / `CARONTE_APPLICATION_GROUP_SECRET` credentials enable group-scoped inter-service authentication and permission assertions.
- **Permission sync command** — `caronte:permissions:sync [--dry-run]` declaratively synchronises your configured permissions to the Caronte server.
- **Two new middleware** — `caronte.app-token` and `caronte.app-permissions` for validating application-access tokens and asserting permissions on routes.
- **BeeHive 3.0 required** — `equidna/bee-hive` constraint raised to `^3.0`.

---

## Added

### OpenID Connect Support

A complete `Oidc/` module ships with this release:

| Class                | Responsibility                                               |
| -------------------- | ------------------------------------------------------------ |
| `OidcClient`         | Builds authorization URLs, exchanges codes, refreshes tokens |
| `OidcTokenValidator` | Validates OIDC ID tokens against JWKS                        |
| `JwksCache`          | Fetches and caches the JWKS document (configurable TTL)      |
| `Jwk`                | Parses JWK entries; verifies RS256 signatures                |
| `Pkce`               | Generates PKCE `code_verifier` / S256 `code_challenge` pairs |
| `Base64Url`          | URL-safe Base64 encoding helper                              |

**`OidcAuthController`** handles the full flow:

- `GET /caronte/oidc/authorize` — redirects to the OIDC provider.
- `GET /caronte/oidc/callback` — exchanges the authorization code and stores the token.
- `POST /caronte/oidc/logout` — terminates the OIDC session.

**Auth modes** (configured via `CARONTE_AUTH_MODE`):

- `legacy` (default) — existing JWT flow, no OIDC.
- `oidc` — OIDC exclusively; legacy tokens rejected.
- `dual` — accepts both; validator selected from token `kid` header.

**`Caronte::getUser()`** now falls back to OIDC standard claims (`sub`, `name`, `email`, `email_verified`) when the legacy `user` claim is absent.

### Application-Group Tokens & Permission Controls

- **`CaronteApplicationAccessToken`** — generates and validates tokens signed with the group credentials.
- **`CaronteApplicationAccessContext`** — context object bound in the DI container after successful group-token validation; carries resolved permissions.
- **`ValidateApplicationAccessToken`** (`caronte.app-token`) — validates `X-Application-Access-Token` header.
- **`ValidateApplicationAccessPermissions`** (`caronte.app-permissions`) — asserts required permissions on the bound context.

### Permission Synchronisation

- **`PermissionApi`** — API client for the Caronte `/permissions` endpoints.
- **`ConfiguredPermissions`** — encapsulates `config('caronte.permissions')` logic; always injects `root`.
- **`caronte:permissions:sync [--dry-run]`** — Artisan command; `--dry-run` previews diffs without applying.

### New Configuration Keys

```php
// config/caronte.php
'application_group_id'     => env('CARONTE_APPLICATION_GROUP_ID', ''),
'application_group_secret' => env('CARONTE_APPLICATION_GROUP_SECRET', ''),
'auth_mode'                => env('CARONTE_AUTH_MODE', 'legacy'), // legacy | oidc | dual
'oidc' => [
    'issuer'         => env('CARONTE_OIDC_ISSUER', ...),
    'client_id'      => env('CARONTE_OIDC_CLIENT_ID', ...),
    'client_secret'  => env('CARONTE_OIDC_CLIENT_SECRET', ''),
    'redirect_uri'   => env('CARONTE_OIDC_REDIRECT_URI', ''),
    'scopes'         => env('CARONTE_OIDC_SCOPES', 'openid profile email'),
    'jwks_cache_ttl' => (int) env('CARONTE_OIDC_JWKS_CACHE_TTL', 3600),
],
```

---

## Changed

- `equidna/bee-hive` constraint: `>=2.0` → `^3.0`.
- `Caronte::syncUser()` now binds a `TenantContext` during local DB operations.
- `CaronteApplicationToken` updated to match and emit group context.
- `CaronteUserToken` updated to support group-signed tokens and multi-mode validation.
- `ResolveApplicationContext` updated to populate group context when present.
- Documentation suite fully updated: `api-documentation.md`, `artisan-commands.md`, `business-logic-and-core-processes.md`, `routes-documentation.md`, `tests-documentation.md`.
- `README.md` extended with Token Types reference and OIDC quick-start.

---

## Dependency Note: BeeHive 3.0

The `equidna/bee-hive` package is now required at `^3.0`. This is the only change that may require action:

```bash
composer require equidna/bee-hive:^3.0
```

See [BREAKING_CHANGES.md](BREAKING_CHANGES.md) for full migration steps.

---

## Full Changelog

See [CHANGELOG.md](CHANGELOG.md) for the complete project history.

## Migration Guide

See [BREAKING_CHANGES.md](BREAKING_CHANGES.md) for step-by-step migration instructions.
