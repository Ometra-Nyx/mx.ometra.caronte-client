# Breaking Changes

## v2.1.0

### What Changed

1. **JWT issuer validation now defaults to `true`** (`CARONTE_ENFORCE_ISSUER`).
2. **Legacy Artisan commands removed** — the monolithic and individual role/user command classes were replaced by a new, focused command suite.

### Why

- Issuer validation was silently disabled by default, creating a window for token forgery from any HS256-compatible source sharing the same secret. Enforcing it by default closes this gap.
- The legacy command set (`ManagementRoles`, `ManagementUsers`, individual `CreateRole`/`DeleteRole`/etc.) was split into composable, focused commands aligned with the domain model.

---

### Migration: Issuer Validation Default

**Before (≤ v2.0.0)**

Issuer validation was **disabled** by default. The JWT `iss` claim was not checked unless you explicitly set:

```dotenv
CARONTE_ENFORCE_ISSUER=true
CARONTE_ISSUER_ID=caronte
```

**After (v2.1.0+)**

Issuer validation is **enabled** by default with `CARONTE_ISSUER_ID=caronte`.

If your Caronte server issues tokens with a different issuer identifier, set:

```dotenv
CARONTE_ISSUER_ID=your-custom-issuer
```

If you need to disable issuer validation entirely (not recommended):

```dotenv
CARONTE_ENFORCE_ISSUER=false
```

**Affected surface:** `src/CaronteToken.php`, `config/caronte.php`.

---

### Migration: Artisan Commands

The following commands **no longer exist** as of v2.1.0:

| Removed command                    | Replacement                                           |
| ---------------------------------- | ----------------------------------------------------- |
| `caronte-client:management` (TUI)  | `caronte:admin`                                       |
| Monolithic role management         | `caronte:roles:sync [--dry-run]`                      |
| Monolithic user management         | `caronte:users:list`, `caronte:users:create`, etc.    |
| `AttachRoles` / `DeleteRolesUser`  | `caronte:users:roles:sync`                            |
| `CreateRole` / `UpdateRole` / etc. | `caronte:roles:sync` (single sync command)            |
| `ShowRolesByUser`                  | `caronte:users:list` with `--search` + inspect output |

**Update any scripts, CI pipelines, or documentation** that reference the old command signatures.

**Affected surface:** `src/Console/Commands/`.

---

## v2.0.0

### What Changed

- `Caronte::getTenant()` was renamed to `Caronte::getTenantId()`.
- Tenant access now returns a string (`id_tenant`) instead of a tenant object payload.
- Default fallback payload for missing tenant data was removed.
- `equidna/bee-hive` is now a required dependency.
- Package baseline is now PHP `^8.2` and Laravel `^12.0`.

### Why

The new API makes tenant usage explicit and stable for multi-tenant checks:

- Consumers usually need only `id_tenant`.
- Returning a strict `string` reduces ambiguity.
- Missing tenant identifiers now fail fast with `TenantMissingException`.

### Migration Steps

1. Replace method calls:

```php
// Before (v1.6.x)
$tenant = Caronte::getTenant();
$tenantId = (string) $tenant->id_tenant;
```

```php
// After (v2.0.0)
$tenantId = Caronte::getTenantId();
```

2. Remove fallback assumptions:

```php
// Before
$tenant = Caronte::getTenant();
if ($tenant->id_tenant === 0) {
    // no tenant
}
```

```php
// After
try {
    $tenantId = Caronte::getTenantId();
} catch (\Ometra\Caronte\Exceptions\TenantMissingException $e) {
    // handle missing tenant
}
```

3. Update custom wrappers and type hints from `stdClass` to `string` where applicable.

4. Ensure BeeHive configuration is present in the host application and select the desired resolver.

```bash
php artisan vendor:publish --tag=bee-hive:config
```

### Affected Surface

- `src/Caronte.php`
- `src/Facades/Caronte.php`
- `README.md`

### Related Docs

- `CHANGELOG.md` for complete project history and release grouping.
- `RELEASE_NOTES.md` for high-level release communication.
