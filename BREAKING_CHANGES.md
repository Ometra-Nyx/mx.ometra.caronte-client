# Breaking Changes

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
