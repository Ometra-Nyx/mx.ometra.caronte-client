# Caronte Client

`ometra/caronte-client` is a Laravel package that connects a host application to the Caronte authentication server using the current server contract.

It provides:

- User authentication against Caronte
- Token validation and token exchange
- Host endpoint protection with `X-Application-Token` and `X-Tenant-Id`
- Config-driven role synchronization
- Optional user-management routes and UI
- Blade and Inertia surfaces for auth and management flows

## Current contract

This package targets the current Caronte API surface:

- `X-Application-Token`
- `X-User-Token`
- `X-Tenant-Id`
- `/api/auth`
- `/api/applications`
- `/api/users`

Legacy `Authorization: Bearer`, `Authorization: Token`, `/api/user/*`, and `/api/app/*` assumptions are not part of this package anymore.

## Requirements

- PHP `^8.2`
- Laravel `^12.0`
- `equidna/bee-hive ^1.0`
- `equidna/laravel-toolkit >=1.0.0`
- `lcobucci/jwt ^5.3`
- `inertiajs/inertia-laravel ^2.0`

## Installation

```bash
composer require ometra/caronte-client
```

Publish package resources as needed:

```bash
php artisan vendor:publish --tag=caronte
php artisan vendor:publish --tag=caronte:config
php artisan vendor:publish --tag=caronte:views
php artisan vendor:publish --tag=caronte:inertia
php artisan vendor:publish --tag=caronte-assets
php artisan vendor:publish --tag=caronte:migrations
```

If you enable local user synchronization, publish migrations and run them:

```bash
php artisan migrate
```

## Required configuration

Set these values in the host application:

```env
CARONTE_URL=https://caronte.example.com
CARONTE_APP_ID=app.example.com
CARONTE_APP_SECRET=replace-with-the-secret-issued-by-caronte
```

`CARONTE_APP_ID` is the Caronte application CN. The package derives the hashed `app_id` internally.

Important optional settings:

```env
CARONTE_ENFORCE_ISSUER=false
CARONTE_ISSUER_ID=
CARONTE_2FA=false
CARONTE_NOTIFICATION_DELIVERY=server
CARONTE_USE_INERTIA=false
CARONTE_MANAGEMENT_ENABLED=true
CARONTE_MANAGEMENT_ACCESS_ROLES=root
```

## Roles

Roles are defined only in `config/caronte.php`:

```php
'roles' => [
    'root' => 'Default super administrator role',
    'admin' => 'Administrative access',
],
```

Rules:

- `root` is always present
- role names are normalized to lowercase
- management access roles must exist in `caronte.roles`
- roles are synchronized with Caronte through `caronte:roles:sync`

## Commands

Available Artisan commands:

- `php artisan caronte:admin`
- `php artisan caronte:roles:sync`
- `php artisan caronte:users:list --tenant=tenant-id`
- `php artisan caronte:users:create --tenant=tenant-id`
- `php artisan caronte:users:update {uri_user} --tenant=tenant-id`
- `php artisan caronte:users:delete {uri_user} --tenant=tenant-id`
- `php artisan caronte:users:roles:sync {uri_user} --tenant=tenant-id`

Notes:

- `caronte:roles:sync` reads `config('caronte.roles')`
- `caronte:roles:sync --dry-run` shows the normalized role set without pushing changes
- user-management commands fail fast when `caronte.management.enabled=false`
- user-management commands are tenant-scoped and require `--tenant` unless tenant context is already resolved

## Middleware

Middleware aliases registered by the package:

- `caronte.session`
- `caronte.roles`
- `caronte.application`
- `caronte.tenant`

Example usage:

```php
Route::middleware(['caronte.session'])->group(function () {
    Route::get('/dashboard', fn () => 'ok');
});

Route::middleware(['caronte.session', 'caronte.roles:root,admin'])->group(function () {
    Route::get('/admin', fn () => 'ok');
});

Route::middleware(['caronte.application', 'caronte.tenant'])->group(function () {
    Route::get('/internal/users', fn () => 'ok');
});
```

`caronte.tenant` runs after `caronte.application`, requires `X-Tenant-Id`, normalizes tenant context for the request lifecycle, and binds that tenant to Caronte tenant-scoped `/api/users` calls.

## Auth and management UI

The package ships with publishable Blade and Inertia views for:

- login
- 2FA request and token login
- password recovery request and reset
- user-management dashboard
- user detail and role sync

Auth routes are package-owned. By default:

- login form: `/login`
- logout: `POST /logout`
- password recovery form: `/password/recover`

Management routes are only registered when `caronte.management.enabled=true`.

Default management route prefix:

```php
'management' => [
    'route_prefix' => 'caronte/management',
]
```

Management access defaults to `root` only:

```php
'management' => [
    'access_roles' => ['root'],
]
```

## Host-managed password recovery and 2FA

Set `CARONTE_NOTIFICATION_DELIVERY=host` if the host application should send password-recovery and 2FA emails.

In that mode the package expects Caronte to expose:

- `POST /api/auth/2fa/issue`
- `POST /api/auth/password/recover/issue`

The package then dispatches the delivery through these contracts:

- `Ometra\Caronte\Contracts\SendsTwoFactorChallenge`
- `Ometra\Caronte\Contracts\SendsPasswordRecovery`

Default implementations send Laravel mailables, but the host application can bind its own implementations.

## Local user synchronization

If `CARONTE_UPDATE_LOCAL_USER=true`, the package updates the local `CaronteUser` record from token claims without making local persistence a hard dependency of the authentication flow.

## Testing

Run the package test suite with:

```bash
composer test
```

## License

MIT
