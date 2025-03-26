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

Publish the config file and database migrations:

```bash
php artisan vendor:publish --tag=meanify-laravel-permissions-config
php artisan vendor:publish --tag=meanify-laravel-permissions-migrations
```

Run the migrations:

```bash
php artisan migrate
```

---

## Permission Management

### Interactive Command

The following command will help you generate and/or sync permissions:

```bash
php artisan meanify:permissions
```

It will ask:

- Which path to scan (default: `app/Http/Controllers`)
- A prefix for permission codes (default: `meanify`)
- Where to save the output YAML file (default: `permissions.yaml`)
- Which database connection to use (default: from `config/database.php`)
- Whether to sync to database or only generate the file

To skip the prompts, pass options directly (see below).

---

### Available Options

```bash
php artisan meanify:permissions [--sync] [--path=] [--prefix=] [--file=] [--dry-run] [--connection=] [--non-interactive]
```

#### Flags

| Option              | Description                                                                 |
|---------------------|-----------------------------------------------------------------------------|
| `--sync`            | Synchronizes the YAML file into the database                                |
| `--path=`           | Base path to scan classes (default: `app/Http/Controllers`)                 |
| `--prefix=`         | Prefix for permission codes (default: `meanify`)                            |
| `--file=`           | Output YAML file path (default: `permissions.yaml`)                         |
| `--dry-run`         | Simulates generation without syncing to the database                        |
| `--connection=`     | Database connection name (default: from `config/database.php`)              |
| `--non-interactive` | Runs the command without asking confirmation (suitable for CI or scripting) |

---

## Attribute-based Permission Annotation

You can customize permissions using PHP 8+ attributes:

```php
use Meanify\LaravelPermissions\Attributes\Permission;

class PartnerLocator {

    #[Permission(code: 'admin.partners.rename', group: 'Partners')]
    public function rename() {
        // logic here
    }
}
```

If not defined, the permission will fallback to:

```text
{prefix}.{ClassName}.{methodName}
```

---

## Access Helper: `meanifyPermissions()`

Use the global helper `meanifyPermissions()` to work with permission and role checks:

```php
// Check if user has permission (cached or database)
meanifyPermissions()->forUser(1)->has('admin.users.view');

// Check if a role has permission
meanifyPermissions('mysql')->forRole(3)->has('admin.reports.generate');

// Clear cached permissions and roles
meanifyPermissions()->clearCache();
```

---

## Configuration

You may customize how permissions are read:

`config/meanify-permissions.php`

```php
return [
    'source' => env('MEANIFY_PERMISSIONS_SOURCE', 'cache'), // or use a connection like 'mysql'

    'cache' => [
        'store' => env('MEANIFY_PERMISSIONS_CACHE_DRIVER', env('CACHE_DRIVER', 'file')),
        'ttl'   => env('MEANIFY_PERMISSIONS_CACHE_TTL', 720), // in minutes
    ],
];
```

---

## Caching Strategy

By default, the following structures are cached:

- `permissions`
- `roles`
- `roles_permissions` (pivot)
- `users_roles` (by user)

Use `meanifyPermissions()->clearCache()` to flush everything.

---

## License

MIT © [Meanify Tecnologia LTDA](https://www.meanify.co)