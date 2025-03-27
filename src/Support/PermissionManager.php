<?php

namespace Meanify\LaravelPermissions\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Meanify\LaravelPermissions\Support\Handlers\UserHandler;
use Meanify\LaravelPermissions\Support\Handlers\RoleHandler;
use Meanify\LaravelPermissions\Support\Handlers\PermissionHandler;

class PermissionManager
{
    protected string $application;
    protected string $source;
    protected string $cache_store;
    protected int $ttl_minutes;
    protected PermissionHandler $permissionHandler;

    /**
     * @param string|null $source
     * @param bool $throws_exception_for_not_configured_db
     */
    public function __construct(?string $application = null, ?string $source = null, bool $throws_exception_for_not_configured_db = true)
    {
        $this->application  = $application ?? Config::get('meanify-laravel-permissions.default_application');
        $this->source       = $source ?? Config::get('meanify-laravel-permissions.source', 'cache');
        $this->cache_store  = Config::get('meanify-laravel-permissions.cache.store', Config::get('cache.default'));
        $this->ttl_minutes  = Config::get('meanify-laravel-permissions.cache.ttl', 60 * 12);

        if (
            $this->source !== 'cache'
            && !array_key_exists($this->source, config('database.connections', []))
            && $throws_exception_for_not_configured_db
        ) {
            throw new \InvalidArgumentException("Invalid '{$this->source}' connection. Use 'cache' or valid db connection.");
        }

        $this->permissionHandler = new PermissionHandler($this->application, $this->source, $this->cache_store, $this->ttl_minutes);
    }

    /**
     * @param int|string $user_id
     * @return UserHandler
     */
    public function forUser(int|string $user_id): UserHandler
    {
        return new UserHandler($user_id, $this->application, $this->source, $this->cache_store, $this->ttl_minutes);
    }

    /**
     * @param int|string $role_id
     * @return RoleHandler
     */
    public function forRole(int|string $role_id): RoleHandler
    {
        return new RoleHandler($role_id, $this->application, $this->source, $this->cache_store, $this->ttl_minutes);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        return $this->permissionHandler->getAllPermissions();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAllRoles(): \Illuminate\Support\Collection
    {
        return $this->permissionHandler->getAllRoles();
    }

    /**
     * @return mixed
     */
    public function getClassMethodPermissionCode(string $class, string $method): mixed
    {
        return $this->permissionHandler->getClassMethodPermissionCode($class, $method);
    }

    /**
     * @return void
     */
    public function refreshAll(): void
    {
        $handlers = $this->instanceAll();

        foreach ($handlers as $handler)
        {
            $handler->refreshCache();
        }
        
        $this->permissionHandler->refreshBaseCache();
    }

    /**
     * @return void
     */
    public function clearAll(): void
    {
        $handlers = $this->instanceAll();

        foreach ($handlers as $handler)
        {
            $handler->clearCache();
        }

        $this->permissionHandler->clearBaseCache();
    }

    /**
     * @return array
     */
    protected function instanceAll(): array
    {
        $handlers = [];

        $models = \config('meanify-laravel-permissions.models');

        foreach ($models as $model_name => $model_path)
        {
            $model_instance = new $model_path;

            $items = DB::table($model_instance->getTable())->get();

            if(strtolower($model_name) == 'user')
            {
                foreach ($items as $item)
                {
                    $handler = new UserHandler($item->id, $this->application, $this->source, $this->cache_store, $this->ttl_minutes);

                    $handlers[] = $handler;
                }
            }

            if(strtolower($model_name) == 'role')
            {
                foreach ($items as $item)
                {
                    $handler = new RoleHandler($item->id, $this->application, $this->source, $this->cache_store, $this->ttl_minutes);

                    $handlers[] = $handler;
                }
            }
        }

        return $handlers;
    }
}
