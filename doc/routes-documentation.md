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
