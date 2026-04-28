import os, sys

base = r'e:\Desarrollo\Ometra-Core\Caronte\mx.ometra.caronte-client'
doc  = os.path.join(base, 'doc')

def w(path, content):
    with open(path, 'w', encoding='utf-8', newline='\n') as f:
        f.write(content.lstrip('\n'))
    print(f'Written: {path}')

# ─── deployment-instructions.md ───────────────────────────────────────────────
w(os.path.join(doc, 'deployment-instructions.md'), r"""
# Deployment Instructions

## Prerequisites

- PHP `^8.2`
- Laravel `^12.0`
- A running Caronte authentication server (external)
- Database connection configured in the host application
- Composer

---

## 1. Installation

```bash
composer require ometra/caronte-client
```

The package auto-registers via Laravel's package discovery (`extra.laravel.providers`). No manual service-provider registration is required.

---

## 2. Configuration

### 2.1 Publish the config file

```bash
php artisan vendor:publish --tag=caronte:config
```

This creates `config/caronte.php` in the host application.

### 2.2 Required environment variables

Only three secrets belong in `.env`:

```env
CARONTE_URL=https://your-caronte-server.example.com
CARONTE_APP_CN=your-app-canonical-name
CARONTE_APP_SECRET=a-strong-secret-at-least-32-chars
```

| Variable | Required | Description |
|---|---|---|
| `CARONTE_URL` | Yes | Base URL of the Caronte authentication server |
| `CARONTE_APP_CN` | Yes | Canonical name that identifies this application in Caronte |
| `CARONTE_APP_SECRET` | Yes | Shared secret for application token generation |
| `CARONTE_ISSUER_ID` | No | Overrides JWT issuer claim validation |

> All other configuration lives in `config/caronte.php` with hardcoded defaults. Do **not** add feature flags to `.env`.

### 2.3 Key configuration options (excerpt)

```php
return [
    'use_2fa'               => false,        // Enable two-factor authentication
    'allow_http_requests'   => false,        // Allow non-TLS (dev only)
    'tls_verify'            => true,         // Verify TLS certificates
    'update_local_user'     => true,         // Sync user to local DB on login

    // 'server': Caronte sends; 'host': this app sends via Mailable classes
    'notification_delivery' => 'server',

    'routes_prefix' => 'caronte',
    'success_url'   => '/',
    'login_url'     => '/caronte/login',

    'http' => [
        'timeout'     => 10,   // seconds
        'retries'     => 2,
        'retry_sleep' => 500,  // ms between retries
    ],

    'roles' => [
        'admin'  => 'Administrator',
        'editor' => 'Content editor',
    ],

    'management' => [
        'enabled'      => true,
        'route_prefix' => 'caronte/management',
        'access_roles' => ['admin'],
        'use_inertia'  => false,
        'features'     => [
            'metadata'         => false,
            'profile_pictures' => false,
        ],
    ],

    'table_prefix' => '',   // Prefix for package DB tables
];
```

---

## 3. Database Migrations

| Migration file | Table created |
|---|---|
| `users_table.php` | `{prefix}Users` |
| `user_metadata_table.php` | `{prefix}UserMetadata` |

```bash
php artisan migrate
```

To publish migration files for customisation:

```bash
php artisan vendor:publish --tag=caronte:migrations
```

> Set `table_prefix` before running the first migration.

---

## 4. Publishing Assets

| Tag | Published to |
|---|---|
| `caronte:config` | `config/caronte.php` |
| `caronte:views` | `resources/views/vendor/caronte` |
| `caronte:migrations` | `database/migrations` |
| `caronte:inertia` | `resources/js/vendor/caronte` |
| `caronte-assets` | `public/vendor/caronte` |

```bash
# Publish everything at once
php artisan vendor:publish --tag=caronte
```

---

## 5. Middleware

Three aliases are registered automatically by `CaronteServiceProvider`:

| Alias | Class | Purpose |
|---|---|---|
| `caronte.session` | `ValidateUserToken` | Validates and auto-renews the user JWT |
| `caronte.roles` | `ValidateUserRoles` | Checks the user has the specified roles |
| `caronte.application` | `ResolveApplicationContext` | Validates incoming application tokens |

```php
// Auth guard
Route::middleware('caronte.session')->group(function () { ... });

// Auth + role check
Route::middleware(['caronte.session', 'caronte.roles:admin,editor'])->group(...);

// Service-to-service
Route::middleware('caronte.application')->group(function () { ... });

// Service-to-service with mandatory tenant header
Route::middleware('caronte.application:tenant_required')->group(function () { ... });
```

---

## 6. Notification Setup (Host Delivery Mode)

When `notification_delivery = 'host'`, the package dispatches emails itself. The sender classes are swappable:

```php
// config/caronte.php
'two_factor_sender'        => \Ometra\Caronte\Notifications\LaravelTwoFactorChallengeSender::class,
'password_recovery_sender' => \Ometra\Caronte\Notifications\LaravelPasswordRecoverySender::class,
```

Bind a custom implementation in your `AppServiceProvider`:

```php
use Ometra\Caronte\Contracts\SendsTwoFactorChallenge;

$this->app->bind(SendsTwoFactorChallenge::class, YourCustomSender::class);
```

---

## 7. Initial Role Sync

```bash
# Preview
php artisan caronte:roles:sync --dry-run

# Apply
php artisan caronte:roles:sync
```

---

## 8. Post-Deployment Checklist

- [ ] `CARONTE_URL`, `CARONTE_APP_CN`, `CARONTE_APP_SECRET` present in `.env`
- [ ] `php artisan migrate` completed
- [ ] `php artisan caronte:roles:sync` completed
- [ ] Management UI accessible at `/{management.route_prefix}`
- [ ] At least one user linked to `admin` (or configured access role) via `caronte:users:roles:sync`
- [ ] `tls_verify = true` for production
- [ ] `allow_http_requests = false` for production
""")

# ─── api-documentation.md ─────────────────────────────────────────────────────
w(os.path.join(doc, 'api-documentation.md'), r"""
# API Documentation

This package is a **client** — it does not expose a REST API itself. This document describes:

1. The Caronte server endpoints this package calls (via `AuthApi`, `ClientApi`, `RoleApi`)
2. The `CaronteServiceClient` public API for inter-service communication
3. The `CaronteApplicationContext` middleware for receiving application tokens

---

## 1. Caronte Server Endpoints (Outgoing Calls)

All calls are made through three static proxy classes that delegate to `CaronteApiClient`.

### 1.1 Auth Endpoints — `AuthApi`

> Base URL: `config('caronte.url')`  
> Auth: Application Token header (`X-Application-Token`) for `applicationRequest`; user JWT for `authRequest`.

| Method | Verb | Path | PHP call | Description |
|---|---|---|---|---|
| `login` | POST | `/api/auth/login` | `AuthApi::login(email, password, appCn?, tenant?)` | Password-based login |
| `requestTwoFactor` | POST | `/api/auth/2fa/request` | `AuthApi::requestTwoFactor(email, tenant?)` | Request 2FA code |
| `issueTwoFactor` | POST | `/api/auth/2fa/issue` | `AuthApi::issueTwoFactor(email, tenant?)` | Issue/resend 2FA challenge |
| `consumeTwoFactor` | POST | `/api/auth/2fa/consume` | `AuthApi::consumeTwoFactor(email, code, tenant?)` | Submit 2FA code |
| `exchange` | POST | `/api/auth/exchange` | `AuthApi::exchange(token)` | Exchange/renew a user JWT |
| `requestPasswordRecovery` | POST | `/api/auth/password/request` | `AuthApi::requestPasswordRecovery(email, tenant?)` | Start password recovery |
| `consumePasswordRecovery` | POST | `/api/auth/password/consume` | `AuthApi::consumePasswordRecovery(token, password)` | Complete password recovery |

All methods return:

```php
array{
    status:  int,           // HTTP status code
    message: string,        // Human-readable message
    data:    mixed,         // Payload (user object, token, etc.)
    errors:  array|null,    // Validation errors
}
```

### 1.2 User/Client Endpoints — `ClientApi`

> Auth: Application Token (`X-Application-Token`) + optional `X-Tenant-Id`

| Method | Verb | Path | Description |
|---|---|---|---|
| `showUsers(tenant?)` | GET | `/api/clients` | List all users for a tenant |
| `createUser(data, tenant?)` | POST | `/api/clients` | Create a new user |
| `showUser(uri, tenant?)` | GET | `/api/clients/{uri}` | Get a single user |
| `updateUser(uri, data, tenant?)` | PUT | `/api/clients/{uri}` | Update user data |
| `deleteUser(uri, tenant?)` | DELETE | `/api/clients/{uri}` | Delete a user |
| `showUserRoles(uri, tenant?)` | GET | `/api/clients/{uri}/roles` | List roles assigned to a user |
| `syncUserRoles(uri, roles, tenant?)` | PUT | `/api/clients/{uri}/roles` | Overwrite user role assignments |
| `storeUserMetadata(uri, key, value, tenant?)` | POST | `/api/clients/{uri}/metadata` | Set a metadata key |
| `deleteUserMetadata(uri, key, tenant?)` | DELETE | `/api/clients/{uri}/metadata/{key}` | Remove a metadata key |

### 1.3 Role Endpoints — `RoleApi`

> Auth: Application Token

| Method | Verb | Path | Description |
|---|---|---|---|
| `showRoles()` | GET | `/api/applications/roles` | List all roles for this application |
| `syncRoles(roles)` | PUT | `/api/applications/roles` | Overwrite application roles |

`syncRoles` payload shape:

```php
[
    ['name' => 'admin',  'description' => 'Administrator'],
    ['name' => 'editor', 'description' => 'Content editor'],
]
```

---

## 2. Application Token Format

All server-to-server calls use an `X-Application-Token` header:

```
X-Application-Token: base64( sha1(lower(app_cn)) : app_secret )
```

Generated by `CaronteApplicationToken::make()`.

---

## 3. CaronteServiceClient (Inter-Service Communication)

Use `CaronteServiceClient` when this host application needs to call **another** Caronte-protected service.

```php
use Ometra\Caronte\CaronteServiceClient;

// Same Caronte credentials (shares this app's token)
$client = new CaronteServiceClient('https://service-b.example.com');

// Different Caronte credentials
$client = new CaronteServiceClient(
    baseUrl:   'https://service-b.example.com',
    appCn:     'service-b-cn',
    appSecret: 'service-b-secret',
);

// Application-token request (no user context)
$response = $client->applicationRequest('GET', 'api/resources', [], tenantId: 'tenant-1');

// User-token request (forwards user JWT)
$token    = Caronte::getToken();
$response = $client->userRequest('POST', 'api/orders', $data, $token, tenantId: 'tenant-1');
```

Response shape is the same normalised array as all other API methods.

---

## 4. Receiving Application Tokens (Incoming Calls)

The `caronte.application` middleware validates the `X-Application-Token` header on routes that accept inter-service calls:

```
X-Application-Token: <base64 token>
X-Tenant-Id: <optional tenant identifier>
```

On success it binds a `CaronteApplicationContext` instance into the IoC container:

```php
$ctx = app(CaronteApplicationContext::class);
$ctx->appCn;             // Canonical name extracted from token
$ctx->appId;             // sha1(lower(appCn))
$ctx->applicationToken;  // Raw token string
```

When the middleware is used with the `tenant_required` argument, requests without an `X-Tenant-Id` header are rejected with `422`.
""")

# ─── routes-documentation.md ──────────────────────────────────────────────────
w(os.path.join(doc, 'routes-documentation.md'), r"""
# Routes Documentation

All routes are registered by `CaronteServiceProvider` inside the `web` middleware group. No API routes are provided.

---

## Route Configuration

| Config key | Default | Effect |
|---|---|---|
| `caronte.routes_prefix` | `caronte` | Prefix for all auth routes |
| `caronte.management.enabled` | `true` | Enables management routes |
| `caronte.management.route_prefix` | `caronte/management` | Prefix for management routes |

---

## Authentication Routes

These routes are always registered (cannot be disabled).

| Method | URI | Name | Middleware | Description |
|---|---|---|---|---|
| GET | `/{prefix}/login` | `caronte.login.form` | `web` | Show login form |
| POST | `/{prefix}/login` | `caronte.login` | `web` | Submit credentials |
| POST | `/{prefix}/logout` | `caronte.logout` | `web` | Log out |
| GET | `/{prefix}/2fa` | `caronte.2fa.request` | `web` | Show 2FA challenge form |
| POST | `/{prefix}/2fa` | `caronte.2fa.login` | `web` | Submit 2FA code |
| GET | `/{prefix}/password/recovery` | `caronte.password.recover.form` | `web` | Show recovery form |
| POST | `/{prefix}/password/recovery` | `caronte.password.recover.request` | `web` | Request recovery link |
| GET | `/{prefix}/password/recovery/{token}` | `caronte.password.recover.consume.form` | `web` | Show new-password form |
| POST | `/{prefix}/password/recovery/{token}` | `caronte.password.recover.consume` | `web` | Submit new password |

Default `{prefix}`: `caronte`

---

## Management Routes

Registered only when `caronte.management.enabled = true`.

All management routes require:
- `caronte.session` (ValidateUserToken)
- `caronte.roles:{access_roles}` (ValidateUserRoles)

| Method | URI | Name | Description |
|---|---|---|---|
| GET | `/{mgmt_prefix}` | `caronte.management.dashboard` | Management dashboard |
| POST | `/{mgmt_prefix}/roles/sync` | `caronte.management.roles.sync` | Sync roles to Caronte |
| POST | `/{mgmt_prefix}/users` | `caronte.management.users.store` | Create a user |
| GET | `/{mgmt_prefix}/users/{uri}` | `caronte.management.users.show` | Show user detail |
| PUT | `/{mgmt_prefix}/users/{uri}` | `caronte.management.users.update` | Update user data |
| DELETE | `/{mgmt_prefix}/users/{uri}` | `caronte.management.users.delete` | Delete user |
| PUT | `/{mgmt_prefix}/users/{uri}/roles` | `caronte.management.users.roles.sync` | Sync user roles |
| POST | `/{mgmt_prefix}/users/{uri}/metadata` | `caronte.management.users.metadata.store` | Store user metadata |
| DELETE | `/{mgmt_prefix}/users/{uri}/metadata/{key}` | `caronte.management.users.metadata.delete` | Delete metadata key |

Default `{mgmt_prefix}`: `caronte/management`

---

## Notes

- The `root` role is always treated as an access role regardless of `access_roles` configuration.
- The management UI can render either Blade views or Inertia pages depending on `management.use_inertia`.
- Views are loaded from `resources/views/vendor/caronte` if published, otherwise from the package.
""")

# ─── artisan-commands.md ──────────────────────────────────────────────────────
w(os.path.join(doc, 'artisan-commands.md'), r"""
# Artisan Commands

All commands are prefixed with `caronte:` and registered by `CaronteServiceProvider`.

---

## caronte:admin

**Class:** `ManagementCaronte`

Interactive TUI menu that dispatches the sub-commands below. Useful for one-off administrative tasks.

```bash
php artisan caronte:admin
```

No arguments. Presents a selection menu; choose an action and follow the prompts.

---

## caronte:roles:sync

**Class:** `Roles\SyncRoles`

Reads roles defined in `config/caronte.php` under the `roles` key, displays their current remote status, and pushes them to the Caronte server.

```bash
# Preview (no remote changes)
php artisan caronte:roles:sync --dry-run

# Apply
php artisan caronte:roles:sync
```

**Options:**

| Option | Description |
|---|---|
| `--dry-run` | Show table of configured vs. remote roles without pushing |

**Table columns:** Role name · Description · `uri_applicationRole` (SHA1) · Remote status (`ok` / `outdated` / `missing`)

---

## caronte:users:list

**Class:** `Users\ListUsers`

Lists all users registered in the Caronte server for a given tenant.

```bash
php artisan caronte:users:list
php artisan caronte:users:list --tenant=acme
```

**Options:**

| Option | Description |
|---|---|
| `--tenant=` | Tenant identifier (prompted if omitted) |

---

## caronte:users:create

**Class:** `Users\CreateUser`

Creates a new user on the Caronte server.

```bash
php artisan caronte:users:create
php artisan caronte:users:create --name="Jane Doe" --email=jane@example.com --tenant=acme --role=admin --role=editor
```

**Options:**

| Option | Description |
|---|---|
| `--tenant=` | Tenant identifier |
| `--name=` | Full name |
| `--email=` | Email address |
| `--password=` | Initial password |
| `--role=*` | Role(s) to assign (repeatable) |

All options are prompted interactively if omitted.

---

## caronte:users:update

**Class:** `Users\UpdateUser`

Updates an existing user's data on the Caronte server.

```bash
php artisan caronte:users:update
```

Options follow the same pattern as `caronte:users:create` (plus `--uri=` to identify the user).

---

## caronte:users:delete

**Class:** `Users\DeleteUser`

Deletes a user from the Caronte server.

```bash
php artisan caronte:users:delete --uri=<user-uri> --tenant=acme
```

**Options:**

| Option | Description |
|---|---|
| `--uri=` | User URI identifier |
| `--tenant=` | Tenant identifier |

Prompts for confirmation before deleting.

---

## caronte:users:roles:sync

**Class:** `Users\SyncUserRoles`

Overwrites the roles assigned to a user on the Caronte server.

```bash
# Assign roles
php artisan caronte:users:roles:sync --uri=<user-uri> --role=admin --role=editor --tenant=acme

# Clear all roles
php artisan caronte:users:roles:sync --uri=<user-uri> --clear --tenant=acme
```

**Options:**

| Option | Description |
|---|---|
| `--uri=` | User URI identifier |
| `--tenant=` | Tenant identifier |
| `--role=*` | Role(s) to assign (repeatable) |
| `--clear` | Remove all roles from the user |

---

## Interactive Prompts

All commands use `laravel/prompts` for interactive input. Skipping an option launches the interactive version; providing all options runs non-interactively (suitable for scripts/CI).
""")

print('deployment, api, routes, artisan docs written')
