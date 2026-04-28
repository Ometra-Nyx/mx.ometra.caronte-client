# Artisan Commands

All commands are prefixed with `caronte:` and registered by `CaronteServiceProvider`.

---

## caronte:admin

**Class:** `ManagementCaronte`

Interactive TUI menu that dispatches the sub-commands below. Useful for one-off administrative tasks.

```bash
php artisan caronte:admin
```

No arguments. Presents a selection menu; choose an action and follow the prompts.

---

## caronte:roles:sync

**Class:** `Roles\SyncRoles`

Reads roles defined in `config/caronte.php` under the `roles` key, displays their current remote status, and pushes them to the Caronte server.

```bash
# Preview (no remote changes)
php artisan caronte:roles:sync --dry-run

# Apply
php artisan caronte:roles:sync
```

**Options:**

| Option | Description |
|---|---|
| `--dry-run` | Show table of configured vs. remote roles without pushing |

**Table columns:** Role name · Description · `uri_applicationRole` (SHA1) · Remote status (`ok` / `outdated` / `missing`)

---

## caronte:users:list

**Class:** `Users\ListUsers`

Lists all users registered in the Caronte server for a given tenant.

```bash
php artisan caronte:users:list
php artisan caronte:users:list --tenant=acme
```

**Options:**

| Option | Description |
|---|---|
| `--tenant=` | Tenant identifier (prompted if omitted) |

---

## caronte:users:create

**Class:** `Users\CreateUser`

Creates a new user on the Caronte server.

```bash
php artisan caronte:users:create
php artisan caronte:users:create --name="Jane Doe" --email=jane@example.com --tenant=acme --role=admin --role=editor
```

**Options:**

| Option | Description |
|---|---|
| `--tenant=` | Tenant identifier |
| `--name=` | Full name |
| `--email=` | Email address |
| `--password=` | Initial password |
| `--role=*` | Role(s) to assign (repeatable) |

All options are prompted interactively if omitted.

---

## caronte:users:update

**Class:** `Users\UpdateUser`

Updates an existing user's data on the Caronte server.

```bash
php artisan caronte:users:update
```

Options follow the same pattern as `caronte:users:create` (plus `--uri=` to identify the user).

---

## caronte:users:delete

**Class:** `Users\DeleteUser`

Deletes a user from the Caronte server.

```bash
php artisan caronte:users:delete --uri=<user-uri> --tenant=acme
```

**Options:**

| Option | Description |
|---|---|
| `--uri=` | User URI identifier |
| `--tenant=` | Tenant identifier |

Prompts for confirmation before deleting.

---

## caronte:users:roles:sync

**Class:** `Users\SyncUserRoles`

Overwrites the roles assigned to a user on the Caronte server.

```bash
# Assign roles
php artisan caronte:users:roles:sync --uri=<user-uri> --role=admin --role=editor --tenant=acme

# Clear all roles
php artisan caronte:users:roles:sync --uri=<user-uri> --clear --tenant=acme
```

**Options:**

| Option | Description |
|---|---|
| `--uri=` | User URI identifier |
| `--tenant=` | Tenant identifier |
| `--role=*` | Role(s) to assign (repeatable) |
| `--clear` | Remove all roles from the user |

---

## Interactive Prompts

All commands use `laravel/prompts` for interactive input. Skipping an option launches the interactive version; providing all options runs non-interactively (suitable for scripts/CI).
