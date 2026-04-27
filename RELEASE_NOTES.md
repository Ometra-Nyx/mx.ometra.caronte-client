# Release v2.1.0 "Sentinel"

**Release date:** 2026-04-27  
**Package:** `ometra/caronte-client`  
**Branch:** `dev` → `main`

---

## Summary

Sentinel is a significant feature release that hardens security defaults, introduces a server-to-server authentication layer, delivers a fully rebuilt CLI command suite, and ships the first comprehensive documentation set for the package.

The most visible security change is that **JWT issuer validation is now on by default**. Combined with the new independent `CARONTE_TLS_VERIFY` flag and two new middleware guards (`caronte.application`, `caronte.tenant`), this release lives up to its codename: the package now actively watches every request at the perimeter.

---

## Highlights

- **Security by default** — JWT issuer validation is enabled (`CARONTE_ENFORCE_ISSUER=true`) out of the box, closing a token-forgery risk present in prior versions.
- **Independent TLS control** — `CARONTE_TLS_VERIFY` decouples certificate verification from the plain-HTTP allowance flag.
- **Application & tenant middleware** — `caronte.application` and `caronte.tenant` provide a clean, documented interface for server-to-server API routes.
- **Rebuilt CLI** — seven focused Artisan commands replace the previous monolithic management commands; all support interactive prompts and tenant scoping.
- **Full documentation suite** — nine reference documents added under `doc/`, covering deployment, API, routes, commands, tests, architecture diagrams, monitoring, business logic, and open questions.
- **Comprehensive test coverage** — nine Feature tests validate routes, middleware, auth flows, management UI, and command behaviour.
- **BeeHive tenancy integration** — models and migrations now carry `id_tenant`; `TenantContextResolver` resolves tenant from three sources with a documented fallback chain.

---

## Added

- `CARONTE_TLS_VERIFY` configuration key.
- `caronte.application` middleware alias (`ResolveApplicationToken`).
- `caronte.tenant` middleware alias (`ResolveTenantContext`).
- Support classes: `CaronteHttpClient`, `ApplicationToken`, `CaronteResponse`, `ConfiguredRoles`, `RequestContext`, `TenantContextResolver`.
- `CaronteApplicationContext` HTTP context class.
- `CaronteTenantResolver` for BeeHive.
- `CaronteApiException` exception class.
- `SendsPasswordRecovery` / `SendsTwoFactorChallenge` contracts.
- `PasswordRecoveryMail` / `TwoFactorChallengeMail` mailables.
- `LaravelPasswordRecoverySender` / `LaravelTwoFactorChallengeSender` notification senders.
- New Artisan command suite: `caronte:admin`, `caronte:roles:sync`, `caronte:users:list`, `caronte:users:create`, `caronte:users:update`, `caronte:users:delete`, `caronte:users:roles:sync`.
- Management UI user-detail view (Blade + Inertia).
- `id_tenant` column in `users_table` migration.
- `doc/` documentation suite (9 files).
- 9 Feature tests.
- `.env.example`.

## Changed

- **`CARONTE_ENFORCE_ISSUER` now defaults to `true`** (see `BREAKING_CHANGES.md`).
- Routes, controllers, API clients, middleware, and service provider all refactored for consistency.
- `ValidateSession` forwards renewed tokens via `X-User-Token` response header.
- `ManagementController@dashboard()` adds pagination.
- CSS, Blade views, and Inertia JSX pages overhauled.
- `README.md` fully overhauled.

## Removed

- `CaronteCommand` base class.
- Monolithic `ManagementRoles` and `ManagementUsers` command classes.
- Individual legacy commands: `CreateRole`, `DeleteRole`, `ShowRoles`, `UpdateRole`, `AttachRoles`, `DeleteRolesUser`, `ShowRolesByUser`.
- Legacy smoke tests: `RoutesSmokeTest`, `PublishCommandsTest`.

## Security

- JWT issuer claim validation is now enforced by default, eliminating a class of token-forgery risk present in earlier versions.

---

## Migration

Upgrading from v2.0.0? Review [`BREAKING_CHANGES.md`](BREAKING_CHANGES.md) for:

1. **Issuer validation default change** — set `CARONTE_ISSUER_ID` or opt out explicitly.
2. **Command signatures changed** — update any scripts referencing removed commands.

---

## Full History

See [`CHANGELOG.md`](CHANGELOG.md) for the complete project history.
