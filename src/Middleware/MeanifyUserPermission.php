<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;

class MeanifyUserPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user_id = 1; //TODO: update this code to get user_id from request

        $target_class  = $request->route()->getControllerClass();
        $target_method = $request->route()->getActionMethod();

        $permission = meanify_permissions()->getClassMethodPermissionCode($target_class, $target_method);

        if($permission) //If exists means that the target is handled by Laravel Permissions
        {
            if(!meanify_permissions()->forUser($user_id)->has($permission))
            {
                abort(403);
            }
        }

        return $next($request);
    }
}
