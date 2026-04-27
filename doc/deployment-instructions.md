# Deployment Instructions

## System Requirements

| Requirement    | Minimum                                                      |
| -------------- | ------------------------------------------------------------ |
| PHP            | `^8.2`                                                       |
| PHP extensions | `mbstring`, `openssl`, `PDO`, `json`, `tokenizer`, `xml`     |
| Laravel        | `^12.0`                                                      |
| Database       | Any PDO-compatible RDBMS; InnoDB engine recommended          |
| Caronte server | A running Caronte authentication server reachable over HTTPS |
| Composer       | `^2.0`                                                       |

---

## Environment Configuration

Only the following variables **must** be present in the host application's `.env`. All other feature flags live in `config/caronte.php` with defaults.

```dotenv
# ── Required ────────────────────────────────────────────────────────────────
CARONTE_URL=https://your-caronte-server.example.com/
CARONTE_APP_ID=your-app-id
CARONTE_APP_SECRET=your-app-secret-minimum-32-characters-long

# ── Optional overrides ───────────────────────────────────────────────────────
# JWT issuer identifier (default: "caronte")
CARONTE_ISSUER_ID=caronte

# Whether to enforce the JWT issuer claim (default: true)
CARONTE_ENFORCE_ISSUER=true

# Enable two-factor authentication flow (default: false)
CARONTE_2FA=false

# Allow HTTP (non-TLS) connections to the Caronte server (default: false)
CARONTE_ALLOW_HTTP_REQUESTS=false

# Verify TLS certificates on outbound requests (default: true)
CARONTE_TLS_VERIFY=true

# Whether to write Caronte user data into the local database on each request (default: false)
CARONTE_UPDATE_LOCAL_USER=false

# Notification delivery: "server" (Caronte sends emails) | "host" (package sends emails)
CARONTE_NOTIFICATION_DELIVERY=server

# Session key used to store the user JWT (default: "caronte.user_token")
CARONTE_SESSION_KEY=caronte.user_token

# Route prefix for auth routes (empty = no prefix)
CARONTE_ROUTES_PREFIX=

# URL to redirect to after successful login (default: "/")
CARONTE_SUCCESS_URL=/

# Login page path (default: "/login")
CARONTE_LOGIN_URL=/login

# HTTP client settings
CARONTE_HTTP_TIMEOUT=10
CARONTE_HTTP_RETRIES=1
CARONTE_HTTP_RETRY_SLEEP=150

# Management UI
CARONTE_MANAGEMENT_ENABLED=true
CARONTE_MANAGEMENT_ROUTE_PREFIX=caronte/management
CARONTE_MANAGEMENT_ACCESS_ROLES=root

# Inertia adapter (set to true if the host app uses Inertia.js)
CARONTE_USE_INERTIA=false

# Database table prefix (e.g. "caronte_" produces "caronte_Users")
CARONTE_TABLE_PREFIX=

# UI branding
CARONTE_UI_APP_NAME="My Application"
CARONTE_UI_HEADLINE="Secure access"
CARONTE_UI_SUBHEADLINE="Authenticate users with Caronte."
CARONTE_UI_SUPPORT_EMAIL=support@example.com
CARONTE_UI_LOGO_URL=
CARONTE_UI_ACCENT=#0f766e
```

> **Security note:** `CARONTE_APP_SECRET` must be **at least 32 characters** long. The package enforces this at runtime in `CaronteToken::getConfig()` (`src/CaronteToken.php`). Never commit real secrets to version control.

---

## Initial Setup Steps

### 1. Install the package

```bash
composer require ometra/caronte-client
```

### 2. Publish the configuration

```bash
php artisan vendor:publish --tag=caronte:config
```

This places `config/caronte.php` in the host application.

### 3. Set environment variables

Add the required `.env` keys listed above.

### 4. Run migrations

```bash
php artisan migrate
```

The package ships two migrations (auto-loaded via `CaronteServiceProvider`):

- `{prefix}Users` — stores local copies of authenticated users.
- `{prefix}UsersMetadata` — stores scoped key/value metadata for users.

To publish migrations instead of auto-loading them:

```bash
php artisan vendor:publish --tag=caronte:migrations
```

### 5. Sync roles

Define roles in `config/caronte.php`:

```php
'roles' => [
    'root'  => 'Default super administrator role',
    'admin' => 'Administrative access',
],
```

Then push them to the Caronte server:

```bash
php artisan caronte:roles:sync
```

### 6. (Optional) Publish views

```bash
php artisan vendor:publish --tag=caronte:views
```

Views land in `resources/views/vendor/caronte/`.

### 7. (Optional) Publish Inertia pages

If the host app uses Inertia.js, set `CARONTE_USE_INERTIA=true` and publish the Vue/React pages:

```bash
php artisan vendor:publish --tag=caronte:inertia
```

Pages land in `resources/js/vendor/caronte/`.

### 8. (Optional) Publish assets

```bash
php artisan vendor:publish --tag=caronte-assets
```

CSS and JS assets land in `public/vendor/caronte/`.

---

## Deployment Workflow

The package is a dependency of a host Laravel application. A typical deployment sequence is:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

No queue workers or scheduler configuration is required by the package itself.

---

## Notes on Multi-Tenancy

If `equidna/bee-hive` is configured in the host app, the `ResolveTenantContext` middleware (`caronte.tenant`) will set the active tenant ID from the `X-Tenant-Id` request header. The `TenantContextResolver` (`src/Support/TenantContextResolver.php`) falls back to the authenticated user's `id_tenant` JWT claim if no header is present.
