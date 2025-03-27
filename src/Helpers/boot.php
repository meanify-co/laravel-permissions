<?php

use Meanify\LaravelPermissions\Support\PermissionManager;

if (!function_exists('meanifyPermissions'))
{
    function meanifyPermissions(?string $application = null, ?string $source = null, bool $throws = true): PermissionManager
    {
        return new PermissionManager($application, $source, $throws);
    }
}
