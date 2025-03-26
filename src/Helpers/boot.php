<?php

use Meanify\LaravelPermissions\Support\PermissionManager;

if (!function_exists('meanifyPermissions'))
{
    function meanifyPermissions(?string $source = null, bool $throws = true): PermissionManager
    {
        return new PermissionManager($source, $throws);
    }
}
