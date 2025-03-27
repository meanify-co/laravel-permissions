<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Applications
     |--------------------------------------------------------------------------
     |
     | Define applications to create roles and permissions app-by-app and define
     | superuser role name (the super role name not should be unique).
     | - Key = Application name
     | - Value = Super User role name
     |
     */
    'applications' => [
        'admin' => 'Admin',
        'app1'  => 'Full',
        'app2'  => 'Full',
        'app3'  => 'Another name',
        'app4'  => 'Administrator',
    ],

    'default_application' => env('MEANIFY_LARAVEL_PERMISSIONS_DEFAULT_APPLICATION', 'admin'),

    /*
     |--------------------------------------------------------------------------
     | Models
     |--------------------------------------------------------------------------
     |
     | Mapping model classes to caching permissions
     | - User
     | - Role
     | - Permission
     | - RolePermission
     | - UserRole
     |
     */
    'models' => [
        'User'            => \App\Models\User::class,
        'Role'            => \App\Models\Role::class,
        'Permission'      => \App\Models\Permission::class,
        'RolePermission'  => \App\Models\RolePermission::class,
        'UserRole'        => \App\Models\UserRole::class,
    ],

    /*
     |--------------------------------------------------------------------------
     | Log
     |--------------------------------------------------------------------------
     |
     | Define if log should be writted 
     |
     */
    'log' => [
        'active' => env('MEANIFY_LARAVEL_PERMISSIONS_LOGGING', false),
    ],


    /*
     |--------------------------------------------------------------------------
     | Source
     |--------------------------------------------------------------------------
     |
     | Supported: "cache" or db connection name (e.g. "mysql")
     |
     */
    'source' => env('MEANIFY_LARAVEL_PERMISSIONS_CACHE_TTL', 'cache'),


    /*
     |--------------------------------------------------------------------------
     | Cache configs
     |--------------------------------------------------------------------------
     |
     | Define driver and TTL to store data in cache
     |
     */
    'cache' => [
        'store' => env('MEANIFY_LARAVEL_PERMISSIONS_CACHE_DRIVER', env('CACHE_DRIVER', 'file')),
        'ttl'   => env('MEANIFY_LARAVEL_PERMISSIONS_CACHE_TTL', 720), //In minutes
    ],

];