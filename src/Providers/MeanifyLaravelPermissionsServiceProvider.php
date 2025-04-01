<?php

namespace Meanify\LaravelPermissions\Providers;

use Illuminate\Support\ServiceProvider;
use Meanify\LaravelPermissions\Commands\PermissionCommand;

class MeanifyLaravelPermissionsServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        //Global helper
        if (file_exists(__DIR__ . '/../Helpers/boot.php')) {
            require_once __DIR__ . '/../Helpers/boot.php';
        }

        //Middleware
        $this->publishes([
            __DIR__ . '/../../src/Middleware/MeanifyUserPermission.php' => app_path('Http/Middleware/MeanifyUserPermission.php'),
        ], 'meanify-middlewares');

        //Config
        $this->publishes([
            __DIR__ . '/../Config/meanify-laravel-permissions.php' => config_path('meanify-laravel-permissions.php'),
        ], 'meanify-configs');

        //Migrations
        $this->publishes([
            __DIR__ . '/../Database/migrations' => database_path('migrations'),
        ], 'meanify-migrations');

        //Models
        $this->publishes([
            __DIR__ . '/../../src/Models/Role.php'           => app_path('Models/Role.php'),
            __DIR__ . '/../../src/Models/Permission.php'     => app_path('Models/Permission.php'),
            __DIR__ . '/../../src/Models/UserRole.php'       => app_path('Models/UserRole.php'),
            __DIR__ . '/../../src/Models/RolePermission.php' => app_path('Models/RolePermission.php'),
        ], 'meanify-models');
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/meanify-laravel-permissions.php',
            'meanify-laravel-permissions'
        );

        $this->commands([
            PermissionCommand::class,
        ]);

    }
}
