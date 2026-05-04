# Business Logic & Core Processes

## Authentication Flow

1. User submits credentials through the package auth routes.
2. `AuthApi::login()` sends credentials to Caronte with `X-Application-Token`.
3. Caronte returns a user JWT.
4. The package stores the JWT in session for web requests or accepts it as bearer token for JSON/API requests.
5. `caronte.session` validates the token on protected routes.

User tokens can be app-scoped or group-scoped. Group-scoped tokens are validated with `CARONTE_APPLICATION_GROUP_SECRET` and must include the configured `CARONTE_APPLICATION_GROUP_ID`.

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

## Management UI

The package's management UI remains app-local. The global Caronte administration console lives in `mx.ometra.caronte-admin` and manages tenants, applications, groups, application tokens, and global admin workflows.
