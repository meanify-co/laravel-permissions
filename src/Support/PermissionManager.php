<?php

namespace Meanify\LaravelPermissions\Support;

use Illuminate\Support\Facades\Config;
use Meanify\LaravelPermissions\Support\Handlers\UserHandler;
use Meanify\LaravelPermissions\Support\Handlers\RoleHandler;
use Meanify\LaravelPermissions\Support\Handlers\PermissionHandler;

class PermissionManager
{
    protected string $source;
    protected string $cache_store;
    protected int $ttl_minutes;
    protected PermissionHandler $permissionHandler;

    /**
     * @param string|null $source
     * @param bool $throws_exception_for_not_configured_db
     */
    public function __construct(?string $source = null, bool $throws_exception_for_not_configured_db = true)
    {
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

        $this->permissionHandler = new PermissionHandler($this->source, $this->cache_store, $this->ttl_minutes);
    }

    /**
     * @param int|string $user_id
     * @return UserHandler
     */
    public function forUser(int|string $user_id): UserHandler
    {
        return new UserHandler($user_id, $this->source, $this->cache_store, $this->ttl_minutes);
    }

    /**
     * @param int|string $role_id
     * @return RoleHandler
     */
    public function forRole(int|string $role_id): RoleHandler
    {
        return new RoleHandler($role_id, $this->source, $this->cache_store, $this->ttl_minutes);
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
     * @return array
     */
    public function getPermissionsByClassMethod(): array
    {
        return $this->permissionHandler->getPermissionsByClassMethod();
    }

    /**
     * @return void
     */
    public function refreshCaches(): void
    {
        $this->permissionHandler->refreshClassMethodMap();
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->permissionHandler->clearBaseCache();
    }
}
