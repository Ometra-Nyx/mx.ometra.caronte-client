# Tests Documentation

## Overview

The test suite uses **PHPUnit 11** via **Orchestra Testbench 10** to test the package in isolation without a real Laravel application.

- **26 tests · 79 assertions** (all passing)
- Test files live in `tests/Feature/`
- Run: `vendor/bin/phpunit`

---

## Base Test Setup

### `tests/TestCase.php`

Extends `Orchestra\Testbench\TestCase`. Responsibilities:

- Bootstraps `CaronteServiceProvider` into the test application
- Sets all required `caronte.*` config values so no `.env` is needed
- Provides the `makeToken(array $claims = [])` helper that generates a valid, signed JWT using the test key — used by any test needing a real user token

### `tests/DisabledManagementTestCase.php`

Extends `TestCase` but overrides `caronte.management.enabled = false`. Used by the disabled-management test files.

---

## Feature Tests

### `AuthContractTest`

Tests the full authentication flow end-to-end against the **mocked** Caronte server (HTTP fake).

| Test | What it verifies |
|---|---|
| `test_login_renders_login_form` | `GET /caronte/login` returns 200 |
| `test_login_with_valid_credentials` | POST login stores token in session/cookie, redirects to `success_url` |
| `test_login_with_invalid_credentials` | POST login with bad credentials redirects back with error bag |
| `test_logout_clears_session` | POST logout clears the session token and redirects to login |
| `test_2fa_form_renders` | `GET /caronte/2fa` returns 200 |
| `test_2fa_login_succeeds` | POST 2FA code returns a valid token, continues login |
| `test_password_recovery_form_renders` | Recovery form returns 200 |
| `test_password_recovery_request` | POST recovery email triggers notification |
| `test_password_recovery_consume` | POST new password with valid token redirects to login |

### `CommandBehaviorTest`

Tests Artisan commands using Laravel's `artisan()` test helper with mocked API responses.

| Test | Command | What it verifies |
|---|---|---|
| `test_roles_sync_dry_run` | `caronte:roles:sync --dry-run` | Outputs table, makes no HTTP call |
| `test_roles_sync_pushes_to_server` | `caronte:roles:sync` | Calls `RoleApi::syncRoles`, prints success |
| `test_users_list` | `caronte:users:list` | Prints user table from mocked response |
| `test_users_create` | `caronte:users:create` | Posts to Caronte, outputs success message |
| `test_users_delete` | `caronte:users:delete` | Calls delete endpoint after confirmation |
| `test_users_roles_sync` | `caronte:users:roles:sync` | Overwrites roles on Caronte server |

### `ConfigurationValidationTest`

Verifies that the service provider throws meaningful exceptions when required config is missing.

| Test | What it verifies |
|---|---|
| `test_missing_url_throws` | `caronte.url` missing → `\RuntimeException` |
| `test_missing_app_cn_throws` | `caronte.app_cn` missing → `\RuntimeException` |
| `test_missing_app_secret_throws` | `caronte.app_secret` missing → `\RuntimeException` |
| `test_valid_config_boots_cleanly` | All required keys set → no exception |

### `ManagementUiTest`

Tests management routes with an authenticated user that has the `admin` role.

| Test | What it verifies |
|---|---|
| `test_dashboard_renders` | Management dashboard returns 200 |
| `test_roles_sync_via_ui` | POST roles/sync route calls `RoleApi::syncRoles` |
| `test_users_store` | POST users route creates a user |
| `test_users_show` | GET users/{uri} returns user data |
| `test_users_update` | PUT users/{uri} updates user |
| `test_users_delete` | DELETE users/{uri} removes user |
| `test_users_roles_sync` | PUT users/{uri}/roles syncs roles |
| `test_metadata_store` | POST metadata endpoint stores value |
| `test_metadata_delete` | DELETE metadata endpoint removes key |

### `ManagementUiWhenDisabledTest` (extends `DisabledManagementTestCase`)

Verifies that management routes return **404** when `caronte.management.enabled = false`.

| Test | What it verifies |
|---|---|
| `test_dashboard_is_not_found` | GET management dashboard → 404 |
| `test_all_management_routes_are_not_found` | All management URIs → 404 |

### `MiddlewareBehaviorTest`

Unit-tests the three middleware classes in isolation.

| Test | What it verifies |
|---|---|
| `test_valid_token_passes_through` | `ValidateUserToken` with a valid JWT proceeds |
| `test_expired_token_triggers_exchange` | Expired JWT triggers `AuthApi::exchange`, sets X-User-Token header |
| `test_invalid_token_redirects` | Malformed token redirects to `login_url` |
| `test_valid_roles_pass_through` | `ValidateUserRoles` with matching roles proceeds |
| `test_missing_roles_returns_403` | Roles not satisfied → 403 |
| `test_valid_application_token_binds_context` | `ResolveApplicationContext` with valid token binds `CaronteApplicationContext` |
| `test_invalid_application_token_returns_401` | Wrong token → 401 |
| `test_tenant_required_without_header_returns_422` | `caronte.application:tenant_required` without `X-Tenant-Id` → 422 |

### `RouteRegistrationTest`

Asserts that all expected routes are registered when management is enabled.

| Test | What it verifies |
|---|---|
| `test_auth_routes_are_registered` | All 9 auth route names resolve to expected URIs |
| `test_management_routes_are_registered` | All 9 management route names resolve to expected URIs |

### `RouteRegistrationWhenDisabledTest` (extends `DisabledManagementTestCase`)

Asserts management routes are absent when the feature is disabled.

| Test | What it verifies |
|---|---|
| `test_management_routes_are_not_registered` | All management route names throw `RouteNotFoundException` |

---

## Running Tests

```bash
# All tests
vendor/bin/phpunit

# Single file
vendor/bin/phpunit tests/Feature/MiddlewareBehaviorTest.php

# Single test
vendor/bin/phpunit --filter test_expired_token_triggers_exchange

# With coverage (requires Xdebug or pcov)
vendor/bin/phpunit --coverage-html coverage/
```

---

## Adding Tests

1. Extend `TestCase` (or `DisabledManagementTestCase` for disabled-management scenarios).
2. Use `Http::fake([...])` to mock Caronte server responses.
3. Use `$this->makeToken()` for a ready-to-use valid JWT.
4. Use `$this->actingAs()` / session helpers if the test requires an authenticated request.
