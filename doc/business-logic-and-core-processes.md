# Business Logic & Core Processes

## Authentication Flow

1. User submits credentials through the package auth routes.
2. `AuthApi::login()` sends credentials to Caronte with `X-Application-Token`.
3. Caronte returns a user JWT.
4. The package stores the JWT in session for web requests or accepts it as bearer token for JSON/API requests.
5. `caronte.session` validates the token on protected routes.

User tokens can be app-scoped or group-scoped. Group-scoped tokens are validated with `CARONTE_APPLICATION_GROUP_SECRET` and must include the configured `CARONTE_APPLICATION_GROUP_ID`.

The SDK reads phase-2 top-level JWT claims first: `sub`, `aud`, `jti`, `tenant_id`, `roles`, `metadata`, `app_id`, and `token_audience`. The legacy nested `user` claim remains supported as a fallback.

Logout is server-backed. The SDK web route accepts `GET` and `POST`, clears the local session, and calls the Caronte server with `POST /api/auth/logout` or `POST /api/auth/logoutAll`.

## Roles

Roles are user-facing authorization values.

1. Define roles in `config/caronte.php`.
2. Run `php artisan caronte:roles:sync`.
3. Protect routes with `caronte.roles:<role>`.

`root` always satisfies role checks.

## Application API Permissions

Permissions are not user roles. They describe operations an external application may perform against this application's API.

1. Define permissions in `config/caronte.php`.
2. Run `php artisan caronte:permissions:sync`.
3. A tenant admin uses Caronte Admin to generate an `ApplicationToken` with a subset of those permissions.
4. This application protects API routes with `caronte.app-token` and `caronte.app-permissions:<permission>`.

Example:

```php
'permissions' => [
    'invoices.read' => 'Read invoices',
    'invoices.write' => 'Write invoices',
],
```

```php
Route::middleware([
    'caronte.app-token',
    'caronte.app-permissions:invoices.read',
])->get('/api/invoices', InvoiceController::class);
```

## Application Tokens

`ApplicationTokens` are JWT credentials created in Caronte Admin for a tenant and target app. They are intended for external applications consuming this app's API.

Validation rules:

- `token_audience` must be `application_token`.
- `app_id` must match this app.
- Signature is verified with `CARONTE_APP_SECRET`.
- `tenant_id` must be present.
- `permissions` must be an array.
- `exp`, `nbf`, and `iat` must be valid.

After `caronte.app-token` passes, `CaronteApplicationAccessContext` is available from the container.

## App-To-App Credentials

`caronte.application` validates `X-Application-Token` for service-to-service calls.

- Individual app token: `base64(app_id:app_secret)`
- Group token: `base64(group_id:application_group_secret)`

Group credentials only identify group membership for app-to-app calls. They do not create permissions by themselves.

## Local User Synchronization

When `caronte.update_local_user=true`, the package updates the local `CaronteUser` cache from validated user JWTs. The host app should still treat Caronte as the source of truth for user identity and role assignments.

## Tenant Resolution

`CaronteTenantResolver` implements Bee Hive's `TenantResolverInterface`. It resolves the tenant from the currently authenticated Caronte user by calling `Caronte::getTenantId()`.

The resolver depends on a valid user JWT in the current request/session. If no user token is available, or if the token has no tenant claim, tenant resolution fails with the same auth/tenant exception path used by `Caronte::getTenantId()`.

In `single` tenancy mode (`caronte.tenancy.mode=single`), the configured `caronte.tenancy.tenant_id` is mandatory and enforced in:

- `AuthController` login path
- `ValidateUserToken` middleware
- `ResolveApplicationContext` middleware

Any mismatch returns `403` and clears invalid user context when applicable.

Local `CaronteUser` rows use `id_tenant` as the Bee Hive tenant key. During local user sync, the SDK temporarily binds `TenantContext` to the token tenant so `BelongsToTenant` writes and reads the correct local tenant cache.

## Provisioning

`ProvisioningApi::provisionTenant()` wraps `POST /api/provisioning/tenants`. Use it only from trusted server-side code configured with an application that has the Caronte platform permission `tenants.provision`.

## Management UI

The package's management UI remains app-local. The global Caronte administration console lives in `mx.ometra.caronte-admin` and manages tenants, applications, groups, application tokens, and global admin workflows.

The app-local management UI supports Blade by default and Inertia when `caronte.management.use_inertia=true`. The Inertia components are published with `php artisan vendor:publish --tag=caronte:inertia`; host apps are responsible for compiling those assets in their own frontend pipeline.

## Local Helper APIs

`CaronteUserHelper` is a read-only helper for the local user cache:

- `getUserName($uriUser)` returns the cached user's name or `User not found`.
- `getUserEmail($uriUser)` returns the cached user's email or `User not found`.
- `getUserMetadata($uriUser, $key)` returns the cached metadata value or `null`.

The helper reads the local `CaronteUser` and `CaronteUserMetadata` models, so Bee Hive tenant context applies. Use it only after the request has an authenticated tenant context or after explicitly binding `TenantContext`.
