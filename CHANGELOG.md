# Changelog

## Unreleased

- Changed default issuer validation to enabled with `CARONTE_ISSUER_ID=caronte`.
- Added `CARONTE_TLS_VERIFY` so TLS certificate verification is independent from allowing plain HTTP URLs.
- Documented that `logoutAll` revokes sessions only for the current Caronte application.

All notable changes to the Caronte Client package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- No changes yet.

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
