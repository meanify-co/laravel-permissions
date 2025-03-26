<p align="center">
  <a href="https://www.meanify.co?from=github&lib=laravel-permissions">
    <img src="https://meanify.co/assets/core/img/logo/png/meanify_color_dark_horizontal_02.png" width="200" alt="Meanify Logo" />
  </a>
</p>

# Laravel Permissions

A PHP library to handle permissions in Laravel, designed by [Meanify](https://meanify.co) to be **decoupled from Eloquent**, **cache-friendly** and **scalable**.

---

## 📦 Installation

Install via Composer:

```bash
composer require meanify-co/laravel-permissions
```

---

## 🔧 Publishing Files

Publish the configuration, migrations, models and middleware stub:

```bash
php artisan vendor:publish --tag=meanify-permissions-config
php artisan vendor:publish --tag=meanify-laravel-permissions-migrations
php artisan vendor:publish --tag=meanify-laravel-permissions-models
php artisan vendor:publish --tag=meanify-laravel-permissions-middleware
```

Then run:

```bash
php artisan migrate
```

---

## ⚙️ Permission Command

```bash
php artisan meanify:permissions
```

### Options:

| Option             | Description                                                              |
|--------------------|--------------------------------------------------------------------------|
| `--sync`           | Synchronize YAML with the database                                       |
| `--path=`          | Path to scan for class methods (default: `app/Http/Controllers`)         |
| `--prefix=`        | Optional prefix to prepend to permission codes                           |
| `--file=`          | Path to the YAML file (default: `permissions.yaml`)                      |
| `--dry-run`        | Simulates synchronization without altering the database                  |
| `--connection=`    | Database connection to use when syncing                                  |
| `--non-interactive`| Skips prompts and uses provided or default values                        |

---

## ✅ Attributes Support (PHP 8+)

Define permissions directly in your classes:

```php
use Meanify\LaravelPermissions\Attributes\Permission;

class EquipmentLocator
{
    #[Permission(code: 'admin.equipment.view', group: 'Equipment', label: 'View Equipment')]
    public function view() {
        //
    }

    #[Permission(apply: false)]
    public function internalUtility() {
        //
    }
}
```

If no attributes are found, permission codes are generated using:
```
{prefix}.{ClassName}.{methodName}
```

---

## 🧠 Global Helper: `meanifyPermissions()`

Use `meanifyPermissions()` for fluent access to all permission features.

### Examples

```php
// Check if a user has a permission
meanifyPermissions()->forUser(10)->has('admin.users.view');

// Check if a role has a permission
meanifyPermissions()->forRole(2)->has('admin.reports.generate');

// Get all permissions and roles (only when not scoped)
meanifyPermissions()->getAllPermissions();
meanifyPermissions()->getAllRoles();

// Get permissions grouped by class and method
meanifyPermissions()->getPermissionsByClassMethod();

// Get permission code for a given class and method
meanifyPermissions()->getClassMethodPermissionCode(MyController::class, 'store');

// Clear or refresh caches
meanifyPermissions()->clearCache();
meanifyPermissions()->refreshCaches();
```

---

## 🧱 Internal Structure

The package separates logic using **Handlers**:

- `UserHandler`: `forUser(...)->has()`, `getUserPermissions()`, `refreshCache()`
- `RoleHandler`: `forRole(...)->has()`, `getRolePermissions()`, `refreshCache()`
- `PermissionHandler`: `getAllPermissions()`, `getAllRoles()`, `getPermissionsByClassMethod()`, `getClassMethodPermissionCode()`

This makes the system scalable and flexible for distributed environments.

---

## 🔐 Authorization Middleware

The package provides a ready-to-use middleware:

```php
use Closure;

class MeanifyUserPermission
{
    public function handle($request, Closure $next)
    {
        $user_id = 1; //TODO: set user_id from request

        $target_class  = $request->route()->getControllerClass();
        $target_method = $request->route()->getActionMethod();

        $permission = meanifyPermissions()->getClassMethodPermissionCode($target_class, $target_method);

        if (!$permission) {
            abort(500);
        }

        if (!meanifyPermissions()->forUser($user_id)->has($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
```

Use it in your `Kernel.php` like:

```php
protected $routeMiddleware = [
    'meanify.permission' => \App\Http\Middleware\MeanifyUserPermission::class,
];
```

---

## 🗂 Published Models

When running `vendor:publish --tag=meanify-laravel-permissions-models`, the following models will be available in `App\Models`:

- `Permission`
- `Role`
- `UserRole`
- `RolePermission`

These models are optional and follow Eloquent standards, but the package itself works even without them.

---

## 🧠 Cache Structure

The system uses the following cache keys:

| Cache Key                                       | Description                              |
|------------------------------------------------|------------------------------------------|
| `meanify_laravel_permissions.permissions.all`   | All permissions                          |
| `meanify_laravel_permissions.roles.all`         | All roles                                |
| `meanify_laravel_permissions.user_permissions.{user_id}` | Permissions for a given user     |
| `meanify_laravel_permissions.role_permissions.{role_id}` | Permissions for a given role     |
| `meanify_laravel_permissions.class_method_map`  | Class::method => [permission_codes]      |
| `meanify_laravel_permissions.class_method_code.{class}::{method}` | Specific code for a method |

Use `clearCache()` and `refreshCaches()` as needed.

---

## 🧪 Testing

You can write tests using `meanifyPermissions()` or by importing the individual handlers:

```php
$userHandler = new \Meanify\LaravelPermissions\Support\Handlers\UserHandler(1, 'cache', 'file', 720);
$this->assertTrue($userHandler->has('admin.users.view'));
```

---

## 🧾 License

MIT © [Meanify Tecnologia LTDA](https://www.meanify.co)