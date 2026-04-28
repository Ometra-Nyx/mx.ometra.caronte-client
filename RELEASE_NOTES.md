# Release v3.0.0 "Archon"

> **Release date:** 2026-04-28  
> **Type:** Major — contains breaking changes. See [BREAKING_CHANGES.md](BREAKING_CHANGES.md) for migration guidance.

---

## Summary

v3.0.0 "Archon" is a focused consolidation release that sharpens every public surface of the Caronte Client package: class names are now consistently prefixed, the middleware stack is simplified from four concerns down to three, legacy wrapper classes are removed in favour of direct API clients, and the UI layer ships with a full default stylesheet and branding system so host applications need less boilerplate to look polished.

The codename _Archon_ (ἄρχων — chief magistrate of an ancient Greek city-state) reflects the package's core responsibility: governing identity, enforcing access rules, and brokering authentication across distributed Laravel applications with clarity and authority.

---

## Highlights

- **Unified naming convention** — all core classes now carry the `Caronte` prefix; middleware aliases follow `ValidateUser*` / `Resolve*` patterns.
- **Middleware consolidation** — `ResolveApplicationToken` + `ResolveTenantContext` collapsed into a single `ResolveApplicationContext` middleware.
- **`CARONTE_APP_ID` → `CARONTE_APP_CN`** — environment variable renamed to match the Caronte server's "Common Name" terminology.
- **`AuthApi` extracted** — authentication calls now live in a dedicated `AuthApi` class rather than being scattered across controllers.
- **Default UI styles shipped** — `base.blade.php` now includes a comprehensive CSS foundation; auth and management views work out-of-the-box without host-app styling.
- **Branding via env** — six `CARONTE_UI_*` variables let you customise app name, headline, logo URL, accent colour, and support email without publishing views.
- **Configurable notification senders** — `two_factor_sender` and `password_recovery_sender` are now resolved from `config('caronte.notifications')`, making it trivial to swap transports.
- **Dependency constraint relaxed** — `equidna/bee-hive` now accepts `>=2.0` instead of `^2.0`, reducing lockfile friction for projects on future minor releases.

---

## Added

- `AuthApi` class — all authentication-related Caronte server calls in one place.
- `CaronteApiClient` — base API client replacing the fragmented `BaseApiClient`/`BaseHttpClient` hierarchy.
- `CaronteServiceClient` — renamed service client extending `CaronteHttpClient` (`Support` namespace).
- `BindsTenantContext` console concern — Artisan commands that need a tenant context use this instead of the removed `GuardsManagement`.
- `ResolveApplicationContext` middleware — single middleware replacing `ResolveApplicationToken` + `ResolveTenantContext`.
- `RouteMode` support class — enum-like value object for route registration modes.
- `resources/views/layouts/base.blade.php` — default layout with a full CSS design system (colour palette, typography, forms, buttons, responsive grid).
- Default `$branding` variable injected into all package views (auth + management).
- `CARONTE_UI_APP_NAME`, `CARONTE_UI_HEADLINE`, `CARONTE_UI_SUBHEADLINE`, `CARONTE_UI_SUPPORT_EMAIL`, `CARONTE_UI_LOGO_URL`, `CARONTE_UI_ACCENT` env variables.
- Configurable notification senders: `config('caronte.notifications.two_factor_sender')` and `config('caronte.notifications.password_recovery_sender')`.
- Flash/messages partial rewritten with error deduplication and unified flash+validation rendering.
- `_gen_docs.py` documentation generator for the `doc/` suite.
- Feature tests: view rendering without explicit branding, mailables with string expiration values, flash partial deduplication.

## Changed

- `equidna/bee-hive` constraint: `^2.0` → `>=2.0`.
- Version field removed from `composer.json` (managed by Git tags).
- `AuthController` refactored to delegate to `AuthApi`.
- `CaronteServiceProvider` updated: registers `CaronteApiClient` singleton, new middleware aliases, `BindsTenantContext`.
- All Artisan command classes updated to use `BindsTenantContext`.
- Documentation suite (`doc/`) fully regenerated.
- `README.md` restructured for clarity.

## Removed

- `CaronteRequest`, `CaronteRoleManager`, `BaseApiClient`.
- `ResolveApplicationToken`, `ResolveTenantContext` middleware.
- `GuardsManagement` console concern.
- `RequestContext`, `TenantContextResolver` support classes.
- `ManagementCaronte` command class.
- Version field from `composer.json`.

## Breaking Changes

See [BREAKING_CHANGES.md](BREAKING_CHANGES.md) for complete migration steps.

| Area           | Before                                             | After                             |
| -------------- | -------------------------------------------------- | --------------------------------- |
| Env variable   | `CARONTE_APP_ID`                                   | `CARONTE_APP_CN`                  |
| Config key     | `config('caronte.app_id')`                         | `config('caronte.app_cn')`        |
| Token class    | `CaronteToken`                                     | `CaronteUserToken`                |
| Service client | `ServiceClient`                                    | `CaronteServiceClient`            |
| HTTP client NS | `Api\CaronteHttpClient`                            | `Support\CaronteHttpClient`       |
| App token NS   | `Support\ApplicationToken`                         | `Support\CaronteApplicationToken` |
| Middleware     | `ValidateSession`                                  | `ValidateUserToken`               |
| Middleware     | `ValidateRoles`                                    | `ValidateUserRoles`               |
| Middleware     | `ResolveApplicationToken` + `ResolveTenantContext` | `ResolveApplicationContext`       |

---

## Full Changelog

See [CHANGELOG.md](CHANGELOG.md) for the complete project history.

## Migration Guide

See [BREAKING_CHANGES.md](BREAKING_CHANGES.md) for step-by-step migration instructions including before/after code samples.
