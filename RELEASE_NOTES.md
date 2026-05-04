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
