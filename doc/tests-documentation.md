# Tests Documentation

## Overview

The package uses PHPUnit 11 through Orchestra Testbench 10.

```bash
composer test
php vendor/bin/phpunit --filter MiddlewareBehaviorTest
npm run typecheck
```

## Feature Coverage

| Area                                                                                  | Test file                           |
| ------------------------------------------------------------------------------------- | ----------------------------------- |
| Auth pages, login, logout, password recovery, 2FA                                     | `AuthContractTest`                  |
| Commands for roles, permissions, and users                                            | `CommandBehaviorTest`               |
| Config validation for roles and management access                                     | `ConfigurationValidationTest`       |
| Management UI routes                                                                  | `ManagementUiTest`                  |
| Resolved tenancy, Inertia, and helper contracts                                       | `ResolvedOpenQuestionsTest`         |
| Disabled management routes                                                            | `ManagementUiWhenDisabledTest`      |
| Middleware: user session, roles, app token, group app token, application access token | `MiddlewareBehaviorTest`            |
| Route registration                                                                    | `RouteRegistrationTest`             |
| Disabled route registration                                                           | `RouteRegistrationWhenDisabledTest` |

## Important Contracts Covered

- Group app credentials are accepted by `caronte.application`.
- Group user JWTs validate against group id and group secret.
- Expired user JWTs trigger exchange.
- Phase-2 user JWT claims are preferred and tokens without the legacy `user` claim are accepted.
- SDK logout sends application and user tokens to the server logout endpoint.
- `ProvisioningApi` wraps the server tenant provisioning endpoint.
- `CaronteTenantResolver` reads tenant id from the authenticated user JWT.
- Management dashboard supports Inertia responses when enabled.
- `CaronteUserHelper` reads local cache name, email, and metadata values.
- Role middleware rejects missing roles.
- `caronte.app-token` accepts valid `ApplicationTokens`.
- `caronte.app-permissions` rejects missing permissions.
- `caronte:permissions:sync` sends the configured API permission catalog to Caronte.

## Test Helpers

`tests/TestCase.php` provides:

- `makeToken()` for user JWTs.
- `makeApplicationAccessToken()` for `ApplicationToken` JWTs.

Both helpers sign tokens with the configured test secrets.
