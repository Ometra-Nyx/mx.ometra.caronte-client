# API Documentation

## Overview

`ometra/caronte-client` is a **Laravel package** and does **not** expose its own `routes/api.php`. All HTTP routes registered by the package are **web routes** (session-based).

Internal server-to-server communication is performed by the package **outbound** to the Caronte server using `Ometra\Caronte\Api\CaronteHttpClient` (`src/Api/CaronteHttpClient.php`). These are not public endpoints exposed by the host app.

For all HTTP routes exposed to browsers, see [Routes Documentation](routes-documentation.md).

---

## Outbound Caronte Server API (Internal)

The package calls the following Caronte server endpoints. These are documented for developer reference when troubleshooting integration issues.

All requests include an `X-Application-Token` header computed as:

```
base64( sha1(APP_ID) + ":" + APP_SECRET )
```

### Authentication Endpoints

Handled by `Ometra\Caronte\Api\CaronteHttpClient::authRequest()` (`src/Api/CaronteHttpClient.php`).

| Method | Path                                | Purpose                                     |
| ------ | ----------------------------------- | ------------------------------------------- |
| `POST` | `api/auth/login`                    | Authenticate with email + password, get JWT |
| `POST` | `api/auth/2fa/issue`                | Request a 2FA token via email               |
| `GET`  | `api/auth/2fa/{token}`              | Validate a 2FA token and receive a JWT      |
| `POST` | `api/auth/exchange`                 | Exchange an expired JWT for a fresh one     |
| `POST` | `api/auth/password/recover/request` | Initiate password recovery                  |
| `GET`  | `api/auth/password/recover/{token}` | Validate a recovery token                   |
| `POST` | `api/auth/password/recover/{token}` | Submit a new password                       |

### Application (Management) Endpoints

Handled by `Ometra\Caronte\Api\CaronteHttpClient::applicationRequest()`. Also include `X-Tenant-Id` header when tenant context is available.

**Users**

| Method   | Path                         | Purpose                        |
| -------- | ---------------------------- | ------------------------------ |
| `GET`    | `api/users`                  | List users for the application |
| `POST`   | `api/users`                  | Create a user                  |
| `GET`    | `api/users/{uri_user}`       | Show a single user             |
| `PUT`    | `api/users/{uri_user}`       | Update a user's name           |
| `DELETE` | `api/users/{uri_user}`       | Delete a user                  |
| `GET`    | `api/users/{uri_user}/roles` | List roles assigned to a user  |
| `PUT`    | `api/users/{uri_user}/roles` | Sync roles assigned to a user  |

**Roles**

| Method | Path                     | Purpose                                   |
| ------ | ------------------------ | ----------------------------------------- |
| `GET`  | `api/applications/roles` | List roles registered for the application |
| `PUT`  | `api/applications/roles` | Sync configured roles to the server       |

### Standard Response Shape

All methods in `Ometra\Caronte\Api\ClientApi` (`src/Api/ClientApi.php`) and `Ometra\Caronte\Api\RoleApi` (`src/Api/RoleApi.php`) return:

```php
[
    'status'  => int,                       // HTTP status code
    'message' => string,                    // Human-readable message
    'data'    => mixed,                     // Payload (array or null)
    'errors'  => array<int|string, mixed>,  // Validation / error details
]
```

---

## Inbound Application Token Verification

The package also exposes middleware for routes in the **host application** that need to accept calls from the Caronte server or other trusted services:

- `caronte.application` → `Ometra\Caronte\Http\Middleware\ResolveApplicationToken` (`src/Http/Middleware/ResolveApplicationToken.php`)
  - Expects `X-Application-Token` header.
  - Validates the token against the configured `APP_ID` + `APP_SECRET`.
  - On success, binds a `CaronteApplicationContext` instance into the service container.
- `caronte.tenant` → `Ometra\Caronte\Http\Middleware\ResolveTenantContext` (`src/Http/Middleware/ResolveTenantContext.php`)
  - Must follow `caronte.application`.
  - Expects `X-Tenant-Id` header.
  - Stores the tenant in `equidna/bee-hive`'s `TenantContext` and on the request attributes.

### Example: Protecting an inbound server-to-server route

```php
Route::middleware(['caronte.application', 'caronte.tenant'])
    ->get('/internal/data', function (Request $request) {
        $tenantId = $request->attributes->get('tenant_id');
        // ...
    });
```
