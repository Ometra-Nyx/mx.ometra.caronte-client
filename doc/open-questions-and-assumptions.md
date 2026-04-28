# Open Questions and Assumptions

This document tracks design decisions whose rationale was not explicitly documented in the codebase, along with questions that should be answered before the package is considered fully production-ready.

---

## Deployment

### 1. Laravel version support discrepancy

The `copilot-instructions.md` internal documentation states `Laravel ^10.0 || ^11.0 || ^12.0`, but `composer.json` declares `laravel/framework: ^12.0`. This means the package **does not** support Laravel 10 or 11 today, despite references to broader support in developer notes.

**Action required:** Confirm and align the supported range in `composer.json`, `README.md`, and all internal documentation.

---

### 2. No queue workers or scheduler required

The package makes no use of Laravel queues or the task scheduler. This is an explicit design assumption. All Caronte server communication is **synchronous** within the request lifecycle.

**Implication:** If network latency to the Caronte server increases significantly, per-request token validation may cause measurable slowdowns. No retry/circuit-breaker pattern exists beyond `CARONTE_HTTP_RETRIES`.

---

### 3. RDBMS compatibility

Migrations use InnoDB engine hints but no RDBMS-specific column types that would block SQLite or PostgreSQL. Oracle and SQL Server have not been tested.

**Assumption:** The package is expected to work with MySQL, MariaDB, PostgreSQL, and SQLite (for testing). Other engines are out of scope.

---

## API / Integration

### 4. Caronte server API contract is external

The package makes outbound HTTP calls to a Caronte server whose API spec is not included in this repository. The endpoint paths, response shapes, and authentication format (`X-Application-Token`) are hardcoded in `src/Api/CaronteHttpClient.php` and `src/Api/ClientApi.php`.

**Assumption:** The Caronte server API is stable and under the same team's control. Any breaking API changes on the server side will require a coordinated package update.

---

### 5. Management UI metadata operations parity with Inertia

The Management UI supports metadata CRUD (`UserController@storeMetadata`, `UserController@deleteMetadata`). It is unclear whether corresponding Inertia page components exist for these operations or whether they are Blade-only.

**Action required:** Verify Inertia pages under `resources/js/Pages/` cover metadata operations, or document the gap.

---

### 6. `equidna/bee-hive` stability

The multi-tenancy integration relies on `equidna/bee-hive ^1.0`. This package is an internal dependency and its stability guarantee (semver compliance, long-term support) is not documented here.

**Assumption:** `bee-hive` is maintained alongside this package and breaking changes will be coordinated.

---

## Business Logic

### 7. `uri_user` immutability

`uri_user` is the primary key of `CaronteUser` and is treated as immutable. The `UpdateUser` command only supports updating `name`, not `uri_user`. If the Caronte server ever changes a user's `uri_user`, the local mirror will become inconsistent.

**Assumption:** The Caronte server guarantees `uri_user` is immutable once assigned.

---

### 8. `_self` application token is undocumented

`PermissionHelper::hasApplication()` accepts a special `_self` token value that bypasses application-token validation (`src/Helpers/PermissionHelper.php`). This mechanism is not explained in any documentation.

**Action required:** Document when and why `_self` should be used; add a guard to ensure it is never used in production contexts unless explicitly enabled.

---

### 9. `ConfiguredRoles::normalizeEntry()` accepted formats

`ConfiguredRoles` processes `config('caronte.roles')` which can be formatted as either:

```php
// Option A: associative (name => description)
'roles' => ['admin' => 'Administrative access']

// Option B: indexed list
'roles' => ['admin', 'editor']
```

The exact normalization logic is not documented. Mixing both formats in the same array has undefined behavior.

**Action required:** Document the canonical format and add a validation step in the service provider boot.

---

## Monitoring

### 10. No structured logging planned

The package uses `Log::info/warning/error()` with unstructured string messages. There is no structured (JSON) logging, no request correlation IDs, and no log context is injected automatically.

**Implication:** Log aggregation and filtering in production requires pattern-matching on message strings.

---

## Tests

### 11. No unit tests for core classes

`CaronteToken`, `CaronteRoleManager`, `PermissionHelper`, `ApplicationToken`, and `ConfiguredRoles` are tested only indirectly through Feature tests. Edge cases in JWT parsing and permission logic may be uncovered.

**Action required:** Add dedicated unit tests for `CaronteToken::validateToken()` edge cases (clock skew, wrong issuer, tampered payload) and `PermissionHelper::hasRoles()`.

---

### 12. Mail and notification senders are untested

`PasswordRecoverySender` and `TwoFactorChallengeSender` (under `src/Notifications/`) are the default host-delivery implementations. Custom senders can be configured through `caronte.notifications.password_recovery_sender` and `caronte.notifications.two_factor_sender`.

**Action required:** Add feature tests using `Mail::fake()` or `Notification::fake()` to verify email dispatch when the host delivery mode is active.
