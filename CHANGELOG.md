# Changelog

All notable changes to the Caronte Client package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- No changes yet.

## [2.1.0] - 2026-04-27 "Sentinel"

### Added

- `CARONTE_TLS_VERIFY` configuration option: TLS certificate verification can now be toggled independently from `CARONTE_ALLOW_HTTP_REQUESTS`.
- `caronte.application` middleware alias (`ResolveApplicationContext`): validates `X-Application-Token` header for server-to-server routes, binds `CaronteApplicationContext`, and supports `tenant_required` mode.
- `CaronteHttpClient` — dedicated HTTP client wrapping auth and application requests with proper headers.
- `ApplicationToken` support class for generating and matching application tokens (`base64(sha1(APP_ID) + ":" + APP_SECRET)`).
- `CaronteResponse` support class standardising the API response envelope shape.
- `ConfiguredRoles` support class encapsulating `config('caronte.roles')` logic; always injects `root`.
- `RequestContext` support class for per-request data propagation.
- `TenantContextResolver` support class with three-step fallback chain: route/request parameter → BeeHive context → JWT `id_tenant` claim.
- `CaronteApplicationContext` HTTP context class bound into the container on successful application-token validation.
- `CaronteTenantResolver` tenancy resolver integrating with `equidna/bee-hive`.
- `CaronteApiException` exception for non-2xx responses from the Caronte server.
- `GuardsManagement` console concern enforcing `caronte.management.enabled` guard across all management commands.
- `SendsPasswordRecovery` and `SendsTwoFactorChallenge` contracts abstracting notification delivery.
- `PasswordRecoveryMail` and `TwoFactorChallengeMail` mailables for host-side email delivery (`CARONTE_NOTIFICATION_DELIVERY=host`).
- `LaravelPasswordRecoverySender` and `LaravelTwoFactorChallengeSender` notification sender implementations.
- New Artisan commands (replacing the legacy monolithic command set):
  - `caronte:admin` — interactive TUI menu.
  - `caronte:roles:sync [--dry-run]` — syncs configured roles to the Caronte server.
  - `caronte:users:list [--tenant=] [--search=] [--all]` — lists users.
  - `caronte:users:create [--tenant=] [--name=] [--email=] [--password=] [--role=]*` — creates a user.
  - `caronte:users:update [--tenant=] [--uri-user=] [--name=]` — updates a user.
  - `caronte:users:delete [--tenant=] [--uri-user=]` — deletes a user.
  - `caronte:users:roles:sync [--tenant=] [--uri-user=] [--role=]*` — syncs roles for a user.
- Management UI user-detail view (`management/user-detail`) for both Blade and Inertia rendering.
- `users_table` migration: added `id_tenant` column for BeeHive tenant association.
- Comprehensive documentation suite in `doc/`:
  - `deployment-instructions.md`
  - `api-documentation.md`
  - `routes-documentation.md`
  - `artisan-commands.md`
  - `tests-documentation.md`
  - `architecture-diagrams.md` (C4, container, component, and sequence diagrams)
  - `monitoring.md`
  - `business-logic-and-core-processes.md`
  - `open-questions-and-assumptions.md`
- 9 focused Feature tests replacing the previous smoke-test suite:
  - `AuthContractTest`, `CommandBehaviorTest`, `CommandBehaviorWhenManagementDisabledTest`, `ConfigurationValidationTest`, `ManagementUiTest`, `ManagementUiWhenDisabledTest`, `MiddlewareBehaviorTest`, `RouteRegistrationTest`, `RouteRegistrationWhenDisabledTest`.
- `.env.example` updated with all supported environment variables.

### Changed

- **Default issuer validation is now `true`**: `CARONTE_ENFORCE_ISSUER` defaults to `true`; JWT issuer claim is validated on every request by default. See migration notes in `BREAKING_CHANGES.md`.
- `routes/web.php` restructured: explicit HTTP-verb groupings, management routes use a dedicated closure group.
- `CaronteToken` refactored: token exchange and signature checks isolated; static `$exchanging` guard prevents recursive exchange.
- `CaronteRequest` refactored: all methods delegate to `CaronteHttpClient`.
- `CaronteRoleManager` refactored: uses `ConfiguredRoles` and `RoleApi`; `previewSync()` and `syncConfiguredRoles()` are the canonical entry points.
- `CaronteServiceProvider` refactored: middleware registration extracted; commands registered conditionally on console.
- `PermissionHelper` refactored: `hasApplication()` and `hasRoles()` both use `ApplicationToken::matches()` and `ConfiguredRoles` internally.
- `ValidateSession` updated: forwards renewed JWT in `X-User-Token` response header for SPA/API clients.
- All four controllers (`AuthController`, `ManagementController`, `UserController`, `RoleController`) refactored for domain separation and consistency.
- `ClientApi` and `RoleApi` refactored to use `CaronteHttpClient`.
- CSS, Blade views, and Inertia JSX pages overhauled for visual and structural consistency.
- `ManagementController@dashboard()` adds pagination.
- `README.md` overhauled: installation, configuration, middleware reference, command reference, and architecture overview.
- `logoutAll` behaviour clarified: revokes sessions only for the current Caronte application, not globally across all apps.

### Removed

- `CaronteCommand` base class (replaced by `GuardsManagement` concern).
- Monolithic `ManagementRoles` and `ManagementUsers` command classes.
- Individual legacy commands: `CreateRole`, `DeleteRole`, `ShowRoles`, `UpdateRole`, `AttachRoles`, `DeleteRolesUser`, `ShowRolesByUser`.
- Legacy smoke tests: `RoutesSmokeTest`, `PublishCommandsTest`.

### Security

- JWT issuer claim validation is now **on by default**, eliminating a class of token-forgery risk in installations that did not explicitly configure issuer verification.

## [2.0.0] - 2026-04-13

### Breaking Changes

- Renamed API method `Caronte::getTenant()` to `Caronte::getTenantId()`.
- Changed tenant access contract from tenant object payload to `id_tenant` string return value.
- Removed fallback tenant payload (`id_tenant: 0`, `name: "No tenant"`) when tenant information is missing.

### Changed

- `Caronte::getTenantId()` now resolves tenant information from the `user` claim and enforces `id_tenant` presence.
- Simplified `getTenantId()` implementation by removing redundant catch/rethrow blocks.
- Updated facade annotations and README usage examples to use `getTenantId()`.

### Fixed

- Improved tenant retrieval error handling by introducing `TenantMissingException` when `id_tenant` is missing.
- Replaced deprecated `str_contains` usage in token validation message detection with `stripos(... ) !== false` for compatibility.

### Removed

- Removed `PUBLISHING.md` from repository documentation set.

## [1.6.0] - 2026-03-23

### Added

- Added `Caronte::getTenant()` to expose the `tenant` JWT claim through the main client and facade.
- Added a default fallback tenant payload when the claim is missing, returning `id_tenant: 0` and `name: "No tenant"`.

## [1.5.0] - 2026-03-08

### Fixed

- Fixed provider boot validation to avoid failing unrelated console tooling commands when `CARONTE_*` variables are not initialized yet.
- Fixed publishing documentation env key typo: `CARONTE_ENFORCER_ISSUER` -> `CARONTE_ENFORCE_ISSUER`.

### Breaking Changes

- Removed legacy controller `src/Http/Controllers/CaronteController.php`.
- Removed legacy API wrapper `src/AppBoundRequest.php`.
- Removed legacy route file `src/routes/web.php`.
- Removed legacy package config duplicate `src/config/caronte.php`.
- Removed obsolete sync job `src/Jobs/SynchronizeRoles.php`.
- Removed deprecated legacy views under `resources/views/auth/Management/`.

**Migration Path**:

- Use `routes/web.php` as the single source for package routes.
- Use `AuthController`, `ManagementController`, `UserController`, and `RoleController` under `src/Http/Controllers/`.
- Use `ClientApi` and `RoleApi` under `src/Api/`.

### Added

- Added package smoke tests with Testbench:
  - `tests/Feature/RoutesSmokeTest.php`
  - `tests/Feature/PublishCommandsTest.php` (8 tests validating publish command infrastructure)
  - `tests/TestCase.php`
  - `phpunit.xml.dist`
- Added `UserController::store()` as REST alias forwarding to `create()` for route compatibility.
- **Test Coverage**: 11 tests with 62 assertions ensure routes are properly registered and publish commands are configured correctly.

## [1.4.0] - 2026-02-08

### Breaking Changes

#### Controller Methods Renamed (REST Conventions)

- `RoleController::listAll()` → `index()`
- `RoleController::assign()` → `attach()`
- `UserController::list()` → `index()`
- `UserController::create()` → `store()`
- `ManagementController::dashboardApp()` → `dashboard()`
- `ManagementController::synchronizeData()` → `synchronize()`

**Migration Path**: Update route references and controller calls to use new method names.

#### Console Command Signatures Renamed

- `caronte-client:attached-roles` → `caronte-client:attach-roles`
- `caronte-client:edit-role` → `caronte-client:update-role`
- `caronte-client:users-roles` → `caronte-client:show-user-roles`
- `caronte-client:delete-roles-user` → `caronte-client:delete-user-roles`

**Migration Path**: Update any scripts or documentation referencing old command names.

#### Removed Classes

- Removed `AppBound.php` (deprecated backward-compatibility alias)
- Removed `SuperCommand.php` (use `CaronteCommand` instead)

### Changed

#### Directory Structure

- Renamed `src/Console/Commands/CrudRoles/` → `Roles/`
- Renamed `src/Console/Commands/CrudUsers/` → `Users/`
- Updated all namespaces accordingly

#### Console Commands

- Renamed `AttachedRoles` class → `AttachRoles`
- Updated all command signatures for consistency
- Updated ServiceProvider imports

### Fixed

- Fixed `SynchronizeRoles.php`: Corrected undefined `RoleManager` → `CaronteRoleManager`
- Fixed `CaronteRequest::passwordRecoverTokenValidation()`: Added `InertiaResponse` to return type
- Fixed HTTP SSL verification: `RoleApiClient::makeRequest()` now honors `ALLOW_HTTP_REQUESTS` config
- Fixed `ManagementController::dashboard()`: Corrected return type to `View|InertiaResponse`

### Removed

- Removed unused imports across 6 files:
  - `AttachRoles.php`: `Illuminate\Support\Str`
  - `ManagementUsers.php`: `CaronteRoleManager`, `Illuminate\Support\Str`
  - `ManagementRoles.php`: `Illuminate\Support\Str`
  - `DeleteRolesUser.php`: `Illuminate\Support\Str`
  - `PermissionHelper.php`: `Equidna\Toolkit\Exceptions\UnauthorizedException`
- Removed unused config keys from `config/caronte.php`:
  - `queue_connection`
  - `queue_name`

### Documentation

- Updated README.md:
  - Corrected all console command names and signatures
  - Improved feature descriptions
  - Added installation instructions
  - Clarified user workflow requirements
  - Updated command tables with accurate names

## [1.3.4] - Previous Release

See git history for changes prior to 1.4.0.
