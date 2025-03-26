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

        $permission = meanifyPermissions()->getClassMethodPermissionCode($target_class, $target_method);

        if(!$permission)
        {
            abort(500);
        }
        elseif(!meanifyPermissions()->forUser($user_id)->has($permission->code))
        {
            abort(403);
        }

        return $next($request);
    }
}
