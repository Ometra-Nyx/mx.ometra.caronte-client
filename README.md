# README (Root of the Project)

> This documentation follows the project's Coding Standards Guide and PHPDoc Style Guide, as established in the repository's instruction files.

---

## Project Overview

**`ometra/caronte-client`** is a **Laravel package** that integrates any Laravel application with the central **Caronte** authentication server. It provides distributed JWT-based authentication, a built-in user-management UI, and a suite of Artisan commands — all without duplicating auth logic in every consuming application.

**Primary audience:** Internal development teams building Laravel applications that share a common Caronte identity provider.

**Main use cases:**

- Drop-in JWT authentication middleware for web and API routes.
- Ready-to-use login, logout, 2FA, and password-recovery flows.
- Centralized user and role management surface (Blade or Inertia).
- Server-to-server API calls from the host app to the Caronte server using application tokens.

---

## Project Type & Tech Summary

| Attribute        | Value                                            |
| ---------------- | ------------------------------------------------ |
| Type             | **Laravel Package** (library)                    |
| Package name     | `ometra/caronte-client` v2.0.0                   |
| PHP              | `^8.2`                                           |
| Laravel          | `^12.0`                                          |
| JWT library      | `lcobucci/jwt ^5.3` + `lcobucci/clock ^3.2`      |
| UI adapter       | `inertiajs/inertia-laravel ^2.0` (optional)      |
| Multi-tenancy    | `equidna/bee-hive ^1.0`                          |
| Helpers          | `equidna/laravel-toolkit >=1.0.0`                |
| Database         | Any Laravel-supported RDBMS (InnoDB recommended) |
| Cache/Queue      | Not directly used by this package                |
| External service | The **Caronte server** (HTTP API)                |

---

## Quick Start

1. **Require the package**

   ```bash
   composer require ometra/caronte-client
   ```

2. **Publish the config**

   ```bash
   php artisan vendor:publish --tag=caronte:config
   ```

3. **Set the three required secrets in `.env`**

   ```dotenv
   CARONTE_URL=https://your-caronte-server.example.com/
   CARONTE_APP_CN=your-app-cn
   CARONTE_APP_SECRET=your-app-secret-minimum-32-chars
   ```

4. **Run migrations** (creates local `Users` and `UsersMetadata` tables)

   ```bash
   php artisan migrate
   ```

5. **Sync roles** defined in `config/caronte.php` to the Caronte server

   ```bash
   php artisan caronte:roles:sync
   ```

6. **Protect routes** using the provided middleware aliases

   ```php
   Route::middleware(['caronte.session'])->group(function () {
       // authenticated routes
   });
   ```

7. Visit `/login` (or the path configured by `CARONTE_LOGIN_URL`) to authenticate.

For full deployment details, see [Deployment Instructions](doc/deployment-instructions.md).

---

## Documentation Index

- [Deployment Instructions](doc/deployment-instructions.md)
- [API Documentation](doc/api-documentation.md)
- [Routes Documentation](doc/routes-documentation.md)
- [Artisan Commands](doc/artisan-commands.md)
- [Tests Documentation](doc/tests-documentation.md)
- [Architecture Diagrams](doc/architecture-diagrams.md)
- [Monitoring](doc/monitoring.md)
- [Business Logic & Core Processes](doc/business-logic-and-core-processes.md)
- [Open Questions & Assumptions](doc/open-questions-and-assumptions.md)

---

## Standards Note

All code examples and class references in this documentation follow the project's **Coding Standards Guide** and **PHPDoc Style Guide** (available as instruction files in the repository). Namespace references use the `Ometra\Caronte\` root namespace; file paths are relative to the package root.

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

`POST /logout` with the `all` flag calls Caronte `logoutAll`, which revokes sessions for the current Caronte application only.

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

The sender implementations are configurable in `config/caronte.php`:

```php
'notifications' => [
    'two_factor_sender' => App\Auth\TwoFactorChallengeSender::class,
    'password_recovery_sender' => App\Auth\PasswordRecoverySender::class,
],
```

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
