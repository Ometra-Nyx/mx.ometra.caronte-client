# Monitoring & Troubleshooting

## Logging

The package does **not** define a dedicated log channel. All internal log calls use `Log::channel(config('caronte.log_channel', 'daily'))` which defaults to the host application's default log channel.

### Recommended: Dedicated log channel

Add a `caronte` channel in the host app's `config/logging.php`:

```php
'channels' => [
    // …
    'caronte' => [
        'driver' => 'daily',
        'path'   => storage_path('logs/caronte.log'),
        'level'  => env('CARONTE_LOG_LEVEL', 'info'),
        'days'   => 14,
    ],
],
```

Then configure it:

```dotenv
CARONTE_LOG_CHANNEL=caronte
CARONTE_LOG_LEVEL=debug
```

---

## Exception Types

| Exception                                          | Cause                                             |
| -------------------------------------------------- | ------------------------------------------------- |
| `Ometra\Caronte\Exceptions\CaronteApiException`    | Non-2xx response from the Caronte server          |
| `Ometra\Caronte\Exceptions\TenantMissingException` | `caronte.application:tenant_required`: no tenant resolvable |
| `Equidna\Toolkit\Exceptions\UnauthorizedException` | Invalid or missing user JWT                       |
| `Equidna\Toolkit\Exceptions\BadRequestException`   | Malformed API request payload                     |

---

## Recommended Observability Stack

| Tool                | Purpose                                       | Notes                                         |
| ------------------- | --------------------------------------------- | --------------------------------------------- |
| Laravel Telescope   | Request/response inspection, exception viewer | Install separately in development             |
| Laravel Horizon     | Queue monitoring                              | No queues in the package; useful for host app |
| Sentry / Flare      | Error tracking and alerting                   | Add `sentry/sentry-laravel` to the host app   |
| Datadog / New Relic | APM, latency tracking                         | Use the host app's APM agent                  |

---

## Key Metrics to Track

| Metric                       | Signal                                       | Recommended Alert                      |
| ---------------------------- | -------------------------------------------- | -------------------------------------- |
| `401 Unauthorized` rate      | Expired/invalid tokens or wrong `APP_SECRET` | Alert if > 5% of requests in 5 minutes |
| `CaronteApiException` rate   | Caronte server unreachable or returning 5xx  | Alert on any exception spike           |
| Token exchange frequency     | High rate may indicate very short token TTL  | Informational; baseline in first week  |
| Caronte server response time | Latency > threshold blocks login/validation  | Alert if P95 > 2 s                     |
| Management UI error rate     | Persistent failures may indicate sync issues | Alert on > 10% error rate              |

---

## Troubleshooting

### All requests return 401 / redirect to login

1. Verify `CARONTE_URL` points to a reachable Caronte server.
2. Check `CARONTE_APP_CN` and `CARONTE_APP_SECRET` are correct and at least 32 characters long.
3. Confirm the JWT `issuer` claim matches `CARONTE_ISSUER_ID` (default: `caronte`).
4. Check for clock drift between the host server and the Caronte server (JWT `nbf`/`exp` claims are time-sensitive).
5. Run `php artisan config:clear` to ensure stale configuration is not cached.

---

### Token exchange loop (repeated `POST api/auth/exchange`)

- `CaronteToken` has a static `$exchanging` guard (`src/CaronteToken.php`) that prevents recursive exchange. If you see repeated exchange calls, it indicates the Caronte server is issuing tokens that immediately expire.
- Check the server's token TTL configuration and the host server's system clock.

---

### Management UI returns 403

1. Confirm the authenticated user has the `root` role or one of the roles in `CARONTE_MANAGEMENT_ACCESS_ROLES`.
2. Check that the application token (`app_cn` + `app_secret`) is correctly registered on the Caronte server.
3. Run `php artisan caronte:roles:sync` to ensure roles are synced.

---

### `APP_SECRET` validation error on boot

The package enforces a minimum secret length in `CaronteToken::getConfig()` (`src/CaronteToken.php`). If the secret is too short, a `RuntimeException` is thrown during service provider boot. Ensure `CARONTE_APP_SECRET` is at least 32 characters.

---

### Roles not appearing after `caronte:roles:sync`

1. Check `config/caronte.php` `roles` array for correct names and descriptions.
2. Run with verbose output: `php artisan caronte:roles:sync -v`.
3. Inspect the Caronte server's API response by temporarily setting `CARONTE_LOG_LEVEL=debug`.

---

### `TenantMissingException` in production

`caronte.application:tenant_required` requires one of:

- `X-Tenant-Id` header in the request.
- A tenant bound by `equidna/bee-hive`'s `TenantContext`.
- An `id_tenant` claim in the authenticated user's JWT.

If none are available, the exception is thrown. Ensure the calling service sends the appropriate header.
