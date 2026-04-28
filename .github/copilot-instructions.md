# Caronte Client Package - AI Agent Instructions

## Architecture Overview

This is a **Laravel package** (not a standalone project) for distributed JWT authentication. It connects Laravel apps to a central Caronte authentication server.

**Key architectural decisions:**

- **No local caching/staging**: All data fetched fresh from server via API
- **Dual authentication**: User tokens (JWT for end-users) + App tokens (for server-to-server API calls)
- **Minimal .env pollution**: Only 3 secrets required (`CARONTE_URL`, `CARONTE_APP_CN`, `CARONTE_APP_SECRET`)
- **Configurable table prefix**: Database tables use `config('caronte.table_prefix')` (default: ``) for multi-tenant support

## Core Components & Data Flow

### 1. Authentication Flow (User Tokens)

```
User → Middleware (ValidateUserToken) → Caronte::getToken() → JWT validation → CaronteToken::validateToken()
                                    ↓ (if expired)
                                 Token renewal → Save to cookie/storage
```

- `Caronte.php`: Main facade, manages user JWT tokens (stored in cookies/local storage)
- `CaronteToken.php`: JWT parsing/validation (uses `lcobucci/jwt`)
- `Http/Middleware/ValidateUserToken.php`: Authenticates requests, auto-renews expired tokens
- `Http/Middleware/ValidateUserRoles.php`: Checks user has required roles (always includes `root`)

### 2. Server Communication (App Tokens)

```
Command/Controller → CaronteRoleManager → RoleApiClient → HTTP request to Caronte server
                                        ↓
                            App token: base64(sha1(app_cn) + app_secret)
```

- `CaronteRoleManager`: Orchestrates role CRUD, generates app tokens
- `Api/RoleApiClient`: HTTP client for Caronte API (uses Laravel HTTP facade)
- `CaronteRequest`: Base HTTP request wrapper with retry logic

**CRITICAL**: User tokens (for authentication) ≠ App tokens (for API calls). Never confuse them.

### 3. Controller Structure (Recently Refactored)

Controllers are **domain-separated** (not monolithic):

- `BaseController`: Shared `toView()` method (handles Inertia/Blade rendering)
- `AuthController`: Login, logout, 2FA, password recovery (8 methods)
- `ManagementController`: Dashboard, token retrieval, metadata, sync (4 methods)
- `UserController`: User CRUD operations (6 methods)
- `RoleController`: Role management (4 methods)

**Pattern**: All controllers extend `BaseController` and use `toView($view, $data)` for rendering.

## Critical Conventions

### Configuration Rules

1. **NEVER add non-secret config to `.env.example`**: Only `CARONTE_URL`, `CARONTE_APP_CN`, `CARONTE_APP_SECRET`, optionally `CARONTE_ISSUER_ID`
2. **All feature flags live in `config/caronte.php`** with hardcoded defaults (no `env()` calls except for secrets)
3. **Table prefix pattern**: Models set table name in `__construct()`:
   ```php
   public function __construct(array $attributes = []) {
       parent::__construct($attributes);
       $this->table = config('caronte.table_prefix') . 'Users';
   }
   ```

### Naming Conventions

- Classes: `Caronte*` prefix for main classes (`CaronteRoleManager`, `CaronteToken`, `CaronteUser`)
- Middleware aliases: `Caronte.ValidateUserToken`, `Caronte.ValidateUserRoles`
- Commands: `caronte-client:*` prefix (e.g., `caronte-client:create-role`)
- Publishable tags: `caronte:*` (e.g., `caronte:views`, `caronte:config`)

### Permission Model

- **`root` role is always included** in permission checks (see `PermissionHelper::hasRoles()`)
- User workflow: User exists → Link roles via `caronte-client:attached-roles` → Then manage
- Role checks via: `PermissionHelper::hasRoles(['admin', 'editor'])` or middleware

## Development Workflows

### Testing Package Changes

```bash
# In host Laravel app (not in package):
composer update ometra/caronte-client --with-dependencies
php artisan config:clear
php artisan route:clear
```

### Publishing Workflow

```bash
php artisan vendor:publish --tag=caronte:config    # Publish config
php artisan vendor:publish --tag=caronte:views     # Publish views
php artisan vendor:publish --tag=caronte:migrations # Publish migrations
```

### Command Development

All commands extend `CaronteCommand` (base class with shared logic). Interactive prompts use `laravel/prompts`.

## Common Pitfalls

1. **Using wrong token type**: User JWT tokens (from `Caronte::getToken()`) ≠ App tokens (from `CaronteRoleManager::getToken()`)
2. **Forgetting table prefix**: Database queries must use models or manually prepend `config('caronte.table_prefix')`
3. **Adding feature flags to `.env`**: They belong in `config/caronte.php` with defaults
4. **Caching assumptions**: No local role/user caching exists - always fetches fresh from server
5. **Middleware order**: `ValidateUserToken` must run before `ValidateUserRoles`

## Key Files Reference

- `src/Caronte.php`: Main facade, user token management, cookie storage
- `src/CaronteRoleManager.php`: Role CRUD orchestrator, app token generator
- `src/Api/RoleApiClient.php`: HTTP client for Caronte API (all methods return `['success' => bool, 'data' => string|null, 'error' => string|null]`)
- `src/Providers/CaronteServiceProvider.php`: Package registration, middleware, routes, commands
- `config/caronte.php`: Single config file (consolidated, minimal `.env` usage)
- `routes/web.php`: All package routes (no API routes)
- `database/migrations/`: User tables with configurable prefix

## PHPDoc Standards

Follow PHPDoc style guide in `vscode-userdata://.../PhpDocStyle.instructions.md`:

- File-level DocBlocks required (top of every PHP file)
- No `@var` on explicitly declared properties
- Document constructor-promoted properties in constructor DocBlock only
- Use `@return static` for fluent interfaces
- Align `@param`/`@return`/`@throws` columns

## Dependencies & Compatibility

- PHP: `^8.0`
- Laravel: `^10.0 || ^11.0 || ^12.0`
- JWT: `lcobucci/jwt ^5.3` + `lcobucci/clock ^3.2`
- Toolkit: `equidna/laravel-toolkit >=1.0.0` (provides exceptions, helpers)
- Inertia: `inertiajs/inertia-laravel ^2.0` (optional, controlled by `USE_INERTIA` flag)
