# Monitoring

This package does not ship built-in metrics, health checks, or dashboards. Monitoring relies on the host application's infrastructure.

---

## 1. Log Points

The package uses Laravel's `Log` facade (no custom channel). Look for these patterns in your application logs:

| Event | Log level | Context |
|---|---|---|
| Caronte server unreachable (after retries) | `error` | Exception from `CaronteHttpClient::request()` |
| JWT validation failure | `warning` (host app decides) | `CaronteUserToken::validateToken()` returns `false` |
| Config validation failure at boot | `critical` | `CaronteServiceProvider::validateCaronteConfig()` |
| Application token mismatch | `warning` (host app decides) | `ResolveApplicationContext` middleware returns 401 |

> The package itself does not write log entries. Wrap API calls in your own try/catch and log as needed.

---

## 2. HTTP Retry Configuration

The HTTP client retries failed requests automatically:

```php
// config/caronte.php
'http' => [
    'timeout'     => 10,    // seconds per attempt
    'retries'     => 2,     // total retry attempts after first failure
    'retry_sleep' => 500,   // ms between retries
],
```

Tune these values based on your network conditions and the Caronte server SLA.

---

## 3. Recommended Monitoring Setup

### 3.1 Caronte Server Health

Add an external HTTP monitor to `{CARONTE_URL}/health` (or equivalent Caronte endpoint) to alert when the auth server is unavailable.

### 3.2 Login Failure Rate

Track failed login attempts via your host app's logging pipeline:

- Abnormal spikes indicate brute-force attempts or Caronte server issues.
- The `AuthController` sets validation errors in the session on failure — these can be captured with Laravel Telescope or a custom event listener.

### 3.3 Token Exchange Failures

Monitor for 401/403 responses on protected routes. These indicate:

- Expired tokens that could not be exchanged (Caronte server unreachable)
- Role permission violations (`caronte.roles` middleware returning 403)

### 3.4 Database Table Growth

The `CaronteUser` and `CaronteUserMetadata` tables grow as users log in (when `update_local_user = true`). Monitor table size and add a retention policy if needed.

---

## 4. Laravel Telescope

If Telescope is installed in the host app, all HTTP requests to the Caronte server will appear in the **HTTP Client** tab, including:

- Request URL, method, headers (token values will be visible — ensure Telescope is not enabled in production)
- Response status and body
- Retry attempts

---

## 5. Alerting Recommendations

| Condition | Suggested alert |
|---|---|
| Caronte server returns 5xx for > 1 min | PagerDuty / OpsGenie critical |
| Login error rate > 10% over 5 min window | Slack warning |
| Config boot exception | Deploy pipeline failure alert |
| Management route 403 rate spike | Security review trigger |
