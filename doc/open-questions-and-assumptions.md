# Open Questions & Assumptions

---

## Open Questions

### OQ-1: Token invalidation on logout

**Question:** Should the client notify the Caronte server on logout to invalidate the JWT server-side?

**Current state:** `AuthController::logout()` only clears the local session. No call is made to the Caronte server. JWTs expire naturally.

**Impact:** A logged-out user can still use a captured token until it expires. If short expiry is enforced on the server this is acceptable; if not, a server-side revocation endpoint should be added.

---

### OQ-2: Multi-tenancy behaviour under the default resolver

**Question:** The `TenantContextResolver` exists in `src/Tenancy/Resolvers/`. What is the full contract between this resolver and `equidna/bee-hive`?

**Current state:** The resolver is registered but its interaction with `BelongsToTenant` on `CaronteUser` under different tenant modes is not fully documented.

**Impact:** Deploying in a multi-tenant environment without understanding the resolver behaviour may cause cross-tenant data leaks.

---

### OQ-3: Management UI Inertia vs. Blade parity

**Question:** Are all management features available in both Inertia (Vue) and Blade rendering modes?

**Current state:** Both modes exist but test coverage focuses on the Blade path. The Inertia page components in `resources/js/Pages/` are not covered by the PHPUnit suite.

**Impact:** A host app that enables `management.use_inertia = true` may encounter untested code paths.

---

### OQ-4: Caronte server contract versioning

**Question:** Is there a version contract between this package and the Caronte server API?

**Current state:** All API paths (e.g. `/api/auth/login`) are hardcoded in `AuthApi`, `ClientApi`, `RoleApi`. No version prefix is used.

**Impact:** A breaking change in the Caronte server API requires a package update.

---

### OQ-5: `CaronteUserHelper` role

**Question:** `src/Helpers/CaronteUserHelper.php` exists but is not documented in the inline comments. What does it provide beyond `PermissionHelper`?

**Current state:** Unknown without reading the file in depth.

---

## Assumptions

### A-1: Caronte server availability

The package assumes the Caronte server is always reachable. There is no circuit-breaker or graceful degradation beyond HTTP retries. If the server is down, all protected routes will fail to exchange tokens.

### A-2: JWT key strength

The minimum key length of 32 characters for `app_secret` is enforced by `CaronteUserToken::MINIMUM_KEY_LENGTH`. It is assumed the host application enforces this via the deployment checklist.

### A-3: Single Caronte server per host application

The package reads a single `CARONTE_URL`. Multi-server or HA setups must be handled at the infrastructure level (e.g. load balancer in front of multiple Caronte instances).

### A-4: `root` role is always trusted

The `root` role bypasses all role checks. It is assumed the Caronte server only grants `root` to fully trusted administrators.

### A-5: Table prefix set before first migration

If `caronte.table_prefix` is changed after the initial migration, the tables must be renamed manually. The package does not provide a migration for prefix changes.

### A-6: Notification delivery mode is consistent

It is assumed `notification_delivery` does not change after the initial deployment. Switching from `server` to `host` mid-deployment without configuring the Mailable senders will break 2FA and password recovery.
