<?php

use Meanify\LaravelPermissions\Support\PermissionManager;

if (!function_exists('meanifyPermissions')) 
{
    function meanifyPermissions(): PermissionManager
    {
        return app(PermissionManager::class);
    }
}
