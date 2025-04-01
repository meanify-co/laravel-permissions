<?php

use Meanify\LaravelPermissions\Support\PermissionManager;

if (!function_exists('meanify_permissions'))
{
    function meanify_permissions(?string $application = null, ?string $source = null, bool $throws = true): PermissionManager
    {
        return new PermissionManager($application, $source, $throws);
    }
}
