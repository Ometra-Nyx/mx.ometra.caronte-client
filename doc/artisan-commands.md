# Artisan Commands

All custom commands are registered by `Ometra\Caronte\Providers\CaronteServiceProvider` (`src/Providers/CaronteServiceProvider.php`) when the application is running in console mode. They follow the `caronte:*` signature prefix.

---

## `caronte:admin`

**Class:** `Ometra\Caronte\Console\Commands\ManagementCaronte` (`src/Console/Commands/ManagementCaronte.php`)

**Purpose:** Interactive TUI entry point that delegates to all other Caronte management commands. Intended for developers who want a guided experience instead of remembering individual command names.

**Signature:**

```
php artisan caronte:admin
```

**Behavior:** Presents a looping `choice` menu with options:

- Sync configured roles
- List users
- Create user
- Update user
- Delete user
- Sync user roles
- Exit

Requires `CARONTE_MANAGEMENT_ENABLED=true`. Exits with `FAILURE` if management is disabled.

---

## `caronte:roles:sync`

**Class:** `Ometra\Caronte\Console\Commands\Roles\SyncRoles` (`src/Console/Commands/Roles/SyncRoles.php`)

**Purpose:** Synchronizes the roles defined in `config/caronte.php` (`caronte.roles`) with the Caronte server. Creates missing roles and updates outdated descriptions.

**Signature:**

```
php artisan caronte:roles:sync [--dry-run]
```

**Options:**

| Option      | Description                                                           |
| ----------- | --------------------------------------------------------------------- |
| `--dry-run` | Preview the configured/remote role comparison without pushing changes |

**Example:**

```bash
# Preview what would change
php artisan caronte:roles:sync --dry-run

# Apply changes
php artisan caronte:roles:sync
```

**When to use:** After adding or modifying roles in `config/caronte.php`, or during initial setup.

---

## `caronte:users:list`

**Class:** `Ometra\Caronte\Console\Commands\Users\ListUsers` (`src/Console/Commands/Users/ListUsers.php`)

**Purpose:** Lists users visible to the configured application in the Caronte server.

**Signature:**

```
php artisan caronte:users:list [--tenant=] [--search=] [--all]
```

**Options:**

| Option      | Description                                                               |
| ----------- | ------------------------------------------------------------------------- |
| `--tenant=` | Tenant identifier (prompted interactively if omitted)                     |
| `--search=` | Optional name or email filter                                             |
| `--all`     | Include users not currently linked to the application (default: app-only) |

**Example:**

```bash
php artisan caronte:users:list --tenant=tenant-123 --search=john
```

---

## `caronte:users:create`

**Class:** `Ometra\Caronte\Console\Commands\Users\CreateUser` (`src/Console/Commands/Users/CreateUser.php`)

**Purpose:** Creates a new user in the Caronte server and optionally assigns configured roles.

**Signature:**

```
php artisan caronte:users:create [--tenant=] [--name=] [--email=] [--password=] [--role=]*
```

**Options:**

| Option        | Description                                                  |
| ------------- | ------------------------------------------------------------ |
| `--tenant=`   | Tenant identifier (prompted if omitted)                      |
| `--name=`     | User display name (prompted if omitted)                      |
| `--email=`    | User email address (prompted if omitted)                     |
| `--password=` | Initial password (prompted as a secret if omitted)           |
| `--role=`     | Role name to assign; option is repeatable for multiple roles |

**Example:**

```bash
php artisan caronte:users:create \
  --tenant=tenant-123 \
  --name="Jane Doe" \
  --email=jane@example.com \
  --role=admin
```

---

## `caronte:users:update`

**Class:** `Ometra\Caronte\Console\Commands\Users\UpdateUser` (`src/Console/Commands/Users/UpdateUser.php`)

**Purpose:** Updates an existing user's attributes on the Caronte server.

**Signature:**

```
php artisan caronte:users:update [--tenant=] [--uri-user=] [--name=]
```

**Example:**

```bash
php artisan caronte:users:update --tenant=tenant-123 --uri-user=user-abc --name="Jane Smith"
```

---

## `caronte:users:delete`

**Class:** `Ometra\Caronte\Console\Commands\Users\DeleteUser` (`src/Console/Commands/Users/DeleteUser.php`)

**Purpose:** Deletes a user from the Caronte server.

**Signature:**

```
php artisan caronte:users:delete [--tenant=] [--uri-user=]
```

**Example:**

```bash
php artisan caronte:users:delete --tenant=tenant-123 --uri-user=user-abc
```

---

## `caronte:users:roles:sync`

**Class:** `Ometra\Caronte\Console\Commands\Users\SyncUserRoles` (`src/Console/Commands/Users/SyncUserRoles.php`)

**Purpose:** Replaces the full set of roles assigned to a user with the specified configured roles.

**Signature:**

```
php artisan caronte:users:roles:sync [--tenant=] [--uri-user=] [--role=]*
```

**Example:**

```bash
php artisan caronte:users:roles:sync \
  --tenant=tenant-123 \
  --uri-user=user-abc \
  --role=admin \
  --role=editor
```

---

## Notes

- All user management commands check `CARONTE_MANAGEMENT_ENABLED` via the `GuardsManagement` concern (`src/Console/Concerns/GuardsManagement.php`) and abort with a `FAILURE` exit code if management is disabled.
- Commands are **not** scheduled. They are intended for manual execution or CI/CD pipelines.
- Interactive prompts use `laravel/prompts` for a richer terminal experience.
- Role names passed to `--role` must match entries in `config('caronte.roles')`.
