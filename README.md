<p align="center">
  <a href="https://www.meanify.co?from=github&lib=laravel-permissions">
    <img src="https://meanify.co/assets/core/img/logo/png/meanify_color_dark_horizontal_02.png" width="200" alt="Meanify Logo" />
  </a>
</p>

# Laravel Permissions

A PHP library to handle permissions in Laravel, designed by [Meanify](https://meanify.co) to be cache-friendly, scalable and decoupled from Eloquent models.

---

## Installation

Install the package via Composer:

```bash
composer require meanify-co/laravel-permissions:dev-master
```

---

## Publishing Configuration & Migrations

```bash
php artisan vendor:publish --tag=meanify-config
php artisan vendor:publish --tag=meanify-migrations
php artisan vendor:publish --tag=meanify-models
php artisan vendor:publish --tag=meanify-middleware
```

Run the migrations:

```bash
php artisan migrate
```

---

## Command Usage

```bash
php artisan meanify:permissions
```

The command supports two actions:

- `import` – Import permissions from a YAML file into the database
- `generate` – Scan classes and generate a YAML file, with optional database sync

You will be prompted for required values if not passed as options.

### Options

| Option            | Description                                                                 |
|-------------------|-----------------------------------------------------------------------------|
| `--action`        | `"import"` or `"generate"`                                                  |
| `--file`          | Path to the YAML file to use                                                |
| `--path`          | Base path to scan for permission generation (for `generate`)                |
| `--prefix`        | Prefix for permission codes (for `generate`)                                |
| `--sync`          | Whether to sync the generated permissions to the database                   |
| `--dry-run`       | Only simulate the sync (no DB changes)                                      |
| `--connection`    | Database connection to use                                                  |
| `--non-interactive` | Skip all confirmation prompts                                             |

---

## Attributes

You can add permissions using PHP 8+ attributes:

```php
use Meanify\LaravelPermissions\Attributes\Permission;

#[Permission(code: 'admin.users.view', group: 'Users', label: 'View Users')]
public function index() {
    //
}
```

If no attribute is provided, it falls back to:

```text
{prefix}.{ClassName}.{methodName}
```

---

## Global Helper

You can use the global helper `meanifyPermissions()` for checking permissions.

```php
// Check if user has permission
meanifyPermissions()->forUser(1)->has('admin.users.create');

// Check if role has permission
meanifyPermissions()->forRole(3)->has('admin.reports.view');

// Get permissions for a user
meanifyPermissions()->forUser(1)->getUserPermissions();

// Get permissions for a class method
meanifyPermissions()->getClassMethodPermissionCode(ClassName::class, 'methodName');

// Refresh user or role permission cache
meanifyPermissions()->forUser(1)->refreshCache();
meanifyPermissions()->forRole(3)->refreshCache();

// Clear base caches (roles, permissions, class maps)
meanifyPermissions()->clearCache();
```

---

## Middleware

You can use the included middleware to auto-check permissions based on class/method mapping.

```php
// Example
Route::middleware(['meanify.user.permission'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

It will check if the current user has permission based on the controller and method.

---

## Caching Strategy

Cached:

- All permissions
- All roles
- Permissions for each user and role
- Mapped permission codes by `Class::method`

---

## License

MIT © [Meanify Tecnologia LTDA](https://www.meanify.co)