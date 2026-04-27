# Tests Documentation

## Framework

| Attribute        | Value                           |
| ---------------- | ------------------------------- |
| Framework        | **PHPUnit** `^10.0 \| ^11.0`    |
| Test environment | **Orchestra Testbench** `^10.0` |
| Configuration    | `phpunit.xml.dist`              |

---

## Running Tests

```bash
# Using the Composer script
composer test

# Directly via the PHPUnit binary
./vendor/bin/phpunit

# With a filter
./vendor/bin/phpunit --filter MiddlewareBehaviorTest
```

---

## Test Structure

All tests live under `tests/`. There are currently only **Feature** tests; no dedicated Unit test folder exists.

```
tests/
├── TestCase.php                                         # Base test case (Orchestra Testbench)
├── DisabledManagementTestCase.php                       # Variant with management disabled
└── Feature/
    ├── AuthContractTest.php                             # Login, 2FA, password recovery flows
    ├── CommandBehaviorTest.php                          # Artisan command behavior
    ├── CommandBehaviorWhenManagementDisabledTest.php    # Commands fail gracefully when disabled
    ├── ConfigurationValidationTest.php                  # Service provider config validation
    ├── ManagementUiTest.php                             # Management dashboard and CRUD endpoints
    ├── ManagementUiWhenDisabledTest.php                 # Routes return 404 when management disabled
    ├── MiddlewareBehaviorTest.php                       # Session, roles, application, tenant middleware
    ├── RouteRegistrationTest.php                        # Verifies all expected routes are registered
    └── RouteRegistrationWhenDisabledTest.php            # Verifies management routes absent when disabled
```

### Base Test Cases

**`Tests\TestCase`** (`tests/TestCase.php`)

- Extends `Orchestra\Testbench\TestCase`.
- Registers `Ometra\Caronte\Providers\CaronteServiceProvider`.
- `defineEnvironment()` sets a complete working Caronte config using a test Caronte URL `https://caronte.test/`.
- Provides `makeToken(?array $user, ?DateTimeImmutable $issuedAt, ?DateTimeImmutable $expiresAt): string` — creates a signed HS256 JWT for use in requests.

**`Tests\DisabledManagementTestCase`** (`tests/DisabledManagementTestCase.php`)

- Extends `Tests\TestCase`.
- Overrides config to set `caronte.management.enabled = false`.
- Used as the base for `CommandBehaviorWhenManagementDisabledTest`, `ManagementUiWhenDisabledTest`, and `RouteRegistrationWhenDisabledTest`.

---

## Coverage Overview

| Area                                     | Coverage                                                           |
| ---------------------------------------- | ------------------------------------------------------------------ |
| Route registration (enabled/disabled)    | Good                                                               |
| Auth flows (login, 2FA, recovery)        | Good                                                               |
| Middleware (session, roles, app, tenant) | Good                                                               |
| Management UI (dashboard, user CRUD)     | Good                                                               |
| Artisan commands                         | Good                                                               |
| Config validation                        | Good                                                               |
| `Caronte` / `CaronteToken` unit logic    | Partial (exercised through Feature tests; no dedicated unit tests) |
| `CaronteRoleManager` unit logic          | Partial                                                            |
| `PermissionHelper` edge cases            | Partial                                                            |
| Mail / Notification senders              | Not covered                                                        |

---

## Adding New Tests

**Naming convention:** `{Domain}{Behavior}Test.php`, e.g. `UserMetadataTest.php`.

**Placement:**

- Feature tests (HTTP, commands, integration): `tests/Feature/`
- Extend `Tests\TestCase` for standard config.
- Extend `Tests\DisabledManagementTestCase` when testing behavior when management is disabled.

**Pattern for HTTP tests:**

```php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    public function test_something_works(): void
    {
        Http::fake([
            'caronte.test/*' => Http::response(
                ['data' => [], 'message' => 'ok', 'status' => 200, 'errors' => []],
                200
            ),
        ]);

        $token = $this->makeToken();

        $this->withSession(['caronte.user_token' => $token])
            ->getJson('/caronte/management')
            ->assertOk();
    }
}
```

**Key helpers:**

- Use `Http::fake()` to mock all outbound calls to the Caronte server.
- Use `$this->makeToken()` to produce a valid signed JWT; pass a custom `$user` array to test role or tenant scenarios.
- Use `->withSession(['caronte.user_token' => $token])` to simulate an authenticated browser session.
