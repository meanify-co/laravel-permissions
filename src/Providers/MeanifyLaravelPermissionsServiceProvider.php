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

        //Config
        $this->publishes([
            __DIR__ . '/../Config/meanify-laravel-permissions.php' => config_path('meanify-laravel-permissions.php'),
        ], 'meanify-laravel-permissions-config');

        //Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        $this->publishes([
            __DIR__ . '/../Database/migrations' => database_path('migrations'),
        ], 'meanify-laravel-permissions-migrations');
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
