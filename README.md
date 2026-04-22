# Caronte Client (Laravel Package)

Caronte Client is a Laravel package that provides distributed JWT authentication with middleware, role-based access control, and comprehensive user/role management commands for Laravel applications. It connects your application to a centralized Caronte authentication server for secure, scalable multi-tenant authentication.

[![Latest Release](https://img.shields.io/github/v/release/Ometra-Core/mx.ometra.caronte-client)](https://github.com/Ometra-Core/mx.ometra.caronte-client/releases)
[![Tests Passing](https://img.shields.io/badge/tests-11%2F11%20passing-brightgreen)](https://github.com/Ometra-Core/mx.ometra.caronte-client)
[![PHP Version](https://img.shields.io/badge/php-%5E8.0-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0%20%7C%20%5E11.0%20%7C%20%5E12.0-red)](https://laravel.com/)

---

## 🎯 Main Features

- **JWT-based authentication** with automatic token renewal
- **Tenant access from JWT claims** for multi-tenant applications
- **Role-based access control** (RBAC) with fine-grained permissions
- **Dual authentication model**: User tokens (JWT) + App tokens (API)
- **Laravel middleware** for session and role validation
- **Artisan commands** for autonomous user/role management
- **Inertia.js support** for modern SPA rendering
- **Configurable table prefix** for multi-tenant deployments
- **Zero local caching** - all data fetched fresh from server
- **Comprehensive test suite** (11 tests, 62 assertions)

---

## 📋 Requirements

- PHP `^8.2`
- Laravel `^12.0`
- `lcobucci/jwt ^5.3` (JWT token handling)
- `equidna/laravel-toolkit >=1.0.0` (Exceptions, helpers)
- `equidna/bee-hive ^1.0` (Tenant resolver/context and global tenant scope)

### Optional

- `inertiajs/inertia-laravel ^2.0` (for Inertia.js rendering)

---

## 🚀 Installation

Install Caronte Client via Composer:

```bash
composer require ometra/caronte-client
```

### Publish Resources

Publish configuration, views, and migrations as needed:

```bash
# All resources with single command
php artisan vendor:publish --tag=caronte

# Or individual tags:
php artisan vendor:publish --tag=caronte:config      # Configuration
php artisan vendor:publish --tag=caronte:views       # Blade views
php artisan vendor:publish --tag=caronte:migrations  # Database tables
php artisan vendor:publish --tag=caronte:inertia     # Inertia components
php artisan vendor:publish --tag=caronte-assets      # CSS/JS files
```

Publishing instructions are described in this section and in the release notes/changelog history.

---

## ⚙️ Configuration

The Caronte Client package is designed to minimize `.env` pollution. **Only authentication secrets** need to be defined in the host application's `.env`. All other settings have sensible defaults in the package's config file.

### Required Environment Variables (Secrets)

Add **only these** to your application's `.env`:

| Variable             | Example Value                 | Description                   |
| -------------------- | ----------------------------- | ----------------------------- |
| `CARONTE_URL`        | `https://caronte.example.com` | FQDN of Caronte server        |
| `CARONTE_APP_ID`     | `app.example.com`             | Registered application ID     |
| `CARONTE_APP_SECRET` | `OgNy19ZMRLXBsuAwTQSbpbzU...` | Registered application secret |

### Optional Environment Variables

These can be overridden if needed, but have defaults in `config/caronte.php`:

| Variable                      | Default Value | Description                            |
| ----------------------------- | ------------- | -------------------------------------- |
| `CARONTE_ISSUER_ID`           | `''`          | JWT issuer ID (if ENFORCE_ISSUER=true) |
| `CARONTE_ENFORCE_ISSUER`      | `true`        | Enforce strict issuer validation       |
| `CARONTE_2FA`                 | `false`       | Enable two-factor authentication       |
| `CARONTE_ALLOW_HTTP_REQUESTS` | `false`       | Disable SSL verification (dev only)    |

### Configuration File

All other settings are configured in `config/caronte.php` with sensible defaults:

- `ROUTES_PREFIX` - Prefix for Caronte routes (default: `''`)
- `SUCCESS_URL` - Post-login redirect (default: `'/'`)
- `LOGIN_URL` - Login route path (default: `'/login'`)
- `UPDATE_LOCAL_USER` - Sync users to local database (default: `false`)
- `USE_INERTIA` - Enable Inertia.js rendering (default: `false`)
- `table_prefix` - Database table prefix for multi-tenancy (default: `''`)

To customize, publish the config:

```bash
php artisan vendor:publish --tag=caronte:config
```

### Migrations (Optional)

If you enable local user synchronization (`UPDATE_LOCAL_USER=true`), publish and run migrations:

```bash
php artisan vendor:publish --tag=caronte:migrations
php artisan migrate
```

---

## 🛠️ Available Commands

This package includes Artisan commands (prefix `caronte-client:`) for autonomous administration of users and roles.

### Entry Point

```bash
php artisan caronte-client:management
```

Interactive wizard to manage **Users** and **Roles**. All operations guide you through required steps with clear prompts.

### Role Management

Manage role definitions within your application scope.

| Command                                       | Description                 |
| --------------------------------------------- | --------------------------- |
| `php artisan caronte-client:create-role`      | Create a new role           |
| `php artisan caronte-client:update-role`      | Update role description     |
| `php artisan caronte-client:delete-role`      | Delete a role               |
| `php artisan caronte-client:show-roles`       | List all roles              |
| `php artisan caronte-client:management-roles` | Interactive role management |

### User Management

> **⚠️ Important Workflow**
>
> To manage a user's roles, the user **MUST** first be linked to the application:
>
> 1. User exists in system
> 2. Run `caronte-client:attach-roles` to link roles
> 3. Then use update/delete operations

| Command                                        | Description                          |
| ---------------------------------------------- | ------------------------------------ |
| `php artisan caronte-client:create-user`       | Create a new user                    |
| `php artisan caronte-client:update-user`       | Update user details                  |
| `php artisan caronte-client:delete-user-roles` | Remove roles from user               |
| `php artisan caronte-client:show-user-roles`   | Show user's assigned roles           |
| `php artisan caronte-client:attach-roles`      | Link roles to user (required first!) |
| `php artisan caronte-client:management-users`  | Interactive user management          |

---

## 💻 Usage Examples

### Authenticating Users

```php
use Caronte;

// Retrieve the current JWT token
$token = Caronte::getToken();

// Get the authenticated user object from the token
$user = Caronte::getUser();

// Get the authenticated tenant id (string) from the token user claim
$tenant = Caronte::getTenantId();

// Check if token is valid
if (Caronte::checkToken()) {
    // User is authenticated
}

// If id_tenant is missing, Caronte throws TenantMissingException.
```

### Middleware Integration

Add Caronte middleware to your routes for session and role validation:

```php
// In your routes/web.php
Route::middleware(['Caronte.ValidateSession'])->group(function () {
    Route::get('/dashboard', function () {
        // Only accessible to authenticated users
    });
});

Route::middleware(['Caronte.ValidateRoles:administrator,manager'])->group(function () {
    Route::get('/admin', function () {
        // Only accessible to users with administrator or manager roles (includes root)
    });
});
```

### Permission Checks in Code

```php
use Ometra\Caronte\Helpers\PermissionHelper;

// Check if the user has access to the application
if (PermissionHelper::hasApplication()) {
    // User has access
}

// Check if the user has a specific role
if (PermissionHelper::hasRoles(['administrator', 'editor'])) {
    // User has one of the required roles (root always included)
}
```

---

## 🧪 Testing

The package includes comprehensive test coverage to ensure reliability and validate publish command infrastructure.

### Running Tests

```bash
composer test
```

### Test Coverage

- **11 tests** with **62 assertions**
- **RoutesSmokeTest** (3 tests): Validates route registration and controller bindings
- **PublishCommandsTest** (8 tests): Validates all publish sources and configurations

### Example Test Output

```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.16
Configuration: phpunit.xml.dist

............... 11 / 11 (100%)

Time: 00:00.150, Memory: 30.00 MB

OK (11 tests, 62 assertions)
```

---

## 📚 Documentation

- **[PUBLISHING.md](./PUBLISHING.md)** - Complete guide for publishing package resources
- **[CHANGELOG.md](./CHANGELOG.md)** - Version history and breaking changes
- **[config/caronte.php](./config/caronte.php)** - Configuration reference

---

## 🔄 Migration from v1.3.x to v1.4.0

### Breaking Changes

#### Controllers

All controllers have been **refactored and moved** to `src/Http/Controllers/`:

- `AuthController` - Authentication flows (login, 2FA, password recovery)
- `ManagementController` - Dashboard and synchronization
- `UserController` - User CRUD operations
- `RoleController` - Role management

**Action Required**: Update any external references to use the new modular controllers.

#### Console Commands

Command signature updated:

- `caronte-client:attached-roles` → `caronte-client:attach-roles`

#### Removed Components

The following legacy code paths have been **removed** in v1.4.0:

- `src/Http/Controllers/CaronteController.php` → Use modular controllers above
- `src/AppBoundRequest.php` → Use `ClientApi` and `RoleApiClient`
- Legacy route files and configurations
- Deprecated views under `resources/views/auth/Management/`

**Migration Path**: See [CHANGELOG.md](./CHANGELOG.md) for complete migration guide.

---

## 📦 API Client Architecture

The package uses a clean API client pattern for server-to-server communication:

```php
use Ometra\Caronte\Api\RoleApiClient;
use Ometra\Caronte\CaronteRoleManager;

// High-level interface for role management
$manager = resolve(CaronteRoleManager::class);
$roles = $manager->getAllRoles();

// Or use the API client directly for custom calls
$client = resolve(RoleApiClient::class);
$response = $client->getRole($roleId);
```

---

## 📄 License

This package is licensed under the MIT License. See [LICENSE](./LICENSE) for details.

---

## 🤝 Contributing

Contributions are welcome! Please follow the existing code style and ensure all tests pass before submitting a pull request.

---

## 📞 Support

For issues, questions, or suggestions, please open an issue on [GitHub](https://github.com/Ometra-Core/mx.ometra.caronte-client/issues).
