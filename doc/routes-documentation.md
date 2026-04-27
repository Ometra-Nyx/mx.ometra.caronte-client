# Routes Documentation

## Registration

Routes are registered by `Ometra\Caronte\Providers\CaronteServiceProvider` (`src/Providers/CaronteServiceProvider.php`) inside a `web` middleware group:

```php
Route::middleware(['web'])->group(function (): void {
    $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
});
```

Source: `routes/web.php`.

---

## Configuration

| Config key                        | `.env` variable                   | Default              |
| --------------------------------- | --------------------------------- | -------------------- |
| `caronte.ROUTES_PREFIX`           | `CARONTE_ROUTES_PREFIX`           | `` (empty)           |
| `caronte.LOGIN_URL`               | `CARONTE_LOGIN_URL`               | `/login`             |
| `caronte.management.enabled`      | `CARONTE_MANAGEMENT_ENABLED`      | `true`               |
| `caronte.management.route_prefix` | `CARONTE_MANAGEMENT_ROUTE_PREFIX` | `caronte/management` |
| `caronte.management.access_roles` | `CARONTE_MANAGEMENT_ACCESS_ROLES` | `root`               |

---

## Authentication Routes

All routes are prefixed with `CARONTE_ROUTES_PREFIX` (default: none) and named under the `caronte.` namespace.

| Method | URI                         | Route Name                                | Controller@Method                               | Middleware |
| ------ | --------------------------- | ----------------------------------------- | ----------------------------------------------- | ---------- |
| `GET`  | `/login`                    | `caronte.login.form`                      | `AuthController@loginForm`                      | `web`      |
| `POST` | `/login`                    | `caronte.login`                           | `AuthController@login`                          | `web`      |
| `POST` | `/logout`                   | `caronte.logout`                          | `AuthController@logout`                         | `web`      |
| `POST` | `/2fa`                      | `caronte.2fa.request`                     | `AuthController@twoFactorTokenRequest`          | `web`      |
| `GET`  | `/2fa/{token}`              | `caronte.2fa.login`                       | `AuthController@twoFactorTokenLogin`            | `web`      |
| `GET`  | `/password/recover`         | `caronte.password.recover.form`           | `AuthController@passwordRecoverRequestForm`     | `web`      |
| `POST` | `/password/recover`         | `caronte.password.recover.request`        | `AuthController@passwordRecoverRequest`         | `web`      |
| `GET`  | `/password/recover/{token}` | `caronte.password.recover.validate-token` | `AuthController@passwordRecoverTokenValidation` | `web`      |
| `POST` | `/password/recover/{token}` | `caronte.password.recover.submit`         | `AuthController@passwordRecover`                | `web`      |

> When `CARONTE_2FA=true`, the `GET /login` route renders the 2FA email-request form instead of the standard credential form.

---

## Management Routes

Registered only when `caronte.management.enabled` is `true`. Named under the `caronte.management.` namespace. Protected by `caronte.session` + `caronte.roles:{access_roles}`.

Base prefix: `caronte/management` (configurable via `CARONTE_MANAGEMENT_ROUTE_PREFIX`).

| Method   | URI                                            | Route Name                                 | Controller@Method                |
| -------- | ---------------------------------------------- | ------------------------------------------ | -------------------------------- |
| `GET`    | `caronte/management`                           | `caronte.management.dashboard`             | `ManagementController@dashboard` |
| `POST`   | `caronte/management/roles/sync`                | `caronte.management.roles.sync`            | `RoleController@sync`            |
| `POST`   | `caronte/management/users`                     | `caronte.management.users.store`           | `UserController@store`           |
| `GET`    | `caronte/management/users/{uri_user}`          | `caronte.management.users.show`            | `UserController@show`            |
| `PUT`    | `caronte/management/users/{uri_user}`          | `caronte.management.users.update`          | `UserController@update`          |
| `DELETE` | `caronte/management/users/{uri_user}`          | `caronte.management.users.delete`          | `UserController@delete`          |
| `PUT`    | `caronte/management/users/{uri_user}/roles`    | `caronte.management.users.roles.sync`      | `UserController@syncRoles`       |
| `POST`   | `caronte/management/users/{uri_user}/metadata` | `caronte.management.users.metadata.store`  | `UserController@storeMetadata`   |
| `DELETE` | `caronte/management/users/{uri_user}/metadata` | `caronte.management.users.metadata.delete` | `UserController@deleteMetadata`  |

---

## Middleware Aliases

Registered by the service provider on the Laravel `Router`:

| Alias                 | Class                                                    | Purpose                                         |
| --------------------- | -------------------------------------------------------- | ----------------------------------------------- |
| `caronte.session`     | `Ometra\Caronte\Http\Middleware\ValidateSession`         | Validates JWT; auto-renews expired tokens       |
| `caronte.roles`       | `Ometra\Caronte\Http\Middleware\ValidateRoles`           | Checks user has at least one of the given roles |
| `caronte.application` | `Ometra\Caronte\Http\Middleware\ResolveApplicationToken` | Validates inbound `X-Application-Token` header  |
| `caronte.tenant`      | `Ometra\Caronte\Http\Middleware\ResolveTenantContext`    | Extracts `X-Tenant-Id` into tenant context      |

### Usage examples

```php
// Require authenticated session
Route::middleware(['caronte.session'])->group(function () { /* … */ });

// Require specific roles (root is always implicitly included)
Route::middleware(['caronte.session', 'caronte.roles:admin,editor'])->group(function () { /* … */ });

// Accept inbound server-to-server calls
Route::middleware(['caronte.application', 'caronte.tenant'])->group(function () { /* … */ });
```

> **Order matters:** `caronte.session` must run before `caronte.roles`. `caronte.application` must run before `caronte.tenant`.
