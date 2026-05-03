# Tests Documentation

## Overview

The package uses PHPUnit 11 through Orchestra Testbench 10.

Current suite: `32 tests, 94 assertions`.

```bash
php vendor/bin/phpunit --do-not-cache-result
php vendor/bin/phpunit --filter MiddlewareBehaviorTest
```

## Feature Coverage

| Area | Test file |
|---|---|
| Auth pages, login, logout, password recovery, 2FA | `AuthContractTest` |
| Commands for roles, permissions, and users | `CommandBehaviorTest` |
| Config validation for roles and management access | `ConfigurationValidationTest` |
| Management UI routes | `ManagementUiTest` |
| Disabled management routes | `ManagementUiWhenDisabledTest` |
| Middleware: user session, roles, app token, group app token, application access token | `MiddlewareBehaviorTest` |
| Route registration | `RouteRegistrationTest` |
| Disabled route registration | `RouteRegistrationWhenDisabledTest` |

## Important Contracts Covered

- Group app credentials are accepted by `caronte.application`.
- Group user JWTs validate against group id and group secret.
- Expired user JWTs trigger exchange.
- Role middleware rejects missing roles.
- `caronte.app-token` accepts valid `ApplicationTokens`.
- `caronte.app-permissions` rejects missing permissions.
- `caronte:permissions:sync` sends the configured API permission catalog to Caronte.

## Test Helpers

`tests/TestCase.php` provides:

- `makeToken()` for user JWTs.
- `makeApplicationAccessToken()` for `ApplicationToken` JWTs.

Both helpers sign tokens with the configured test secrets.
