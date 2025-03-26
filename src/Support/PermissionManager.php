<?php

namespace Meanify\LaravelPermissions\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Collection;

class PermissionManager
{
    protected string $source;
    protected string $cache_store;
    protected int $ttl_minutes;
    protected int|string|null $user_id = null;
    protected int|string|null $role_id = null;

    protected const CACHE_PREFIX = 'meanify_laravel_permissions';

    /**
     * @param string|null $source
     * @param bool $throws_exception_for_not_configured_db
     */
    public function __construct(?string $source = null, bool $throws_exception_for_not_configured_db = true)
    {
        $this->source = $source ?? Config::get('meanify-laravel-permissions.source', 'cache');

        if ($this->source !== 'cache' && !array_key_exists($this->source, config('database.connections', [])) && $throws_exception_for_not_configured_db) {
            throw new \InvalidArgumentException("Invalid '{$this->source}' connection. Use 'cache' or valid db connection");
        }

        $this->cache_store = Config::get('meanify-laravel-permissions.cache.store', Config::get('cache.default'));
        $this->ttl_minutes = Config::get('meanify-laravel-permissions.cache.ttl', 60 * 12);
    }

    /**
     * @param int|string $user_id
     * @return $this
     */
    public function forUser(int|string $user_id): static
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @param int|string $role_id
     * @return $this
     */
    public function forRole(int|string $role_id): static
    {
        $this->role_id = $role_id;
        return $this;
    }

    /**
     * @param string $permission_code
     * @return bool
     */
    public function has(string $permission_code): bool
    {
        if ($this->user_id) {
            return $this->userHasPermission($this->user_id, $permission_code);
        }

        if ($this->role_id) {
            return $this->roleHasPermission($this->role_id, $permission_code);
        }

        return false;
    }

    /**
     * @param int|string $user_id
     * @param string $permission_code
     * @return bool
     */
    public function userHasPermission(int|string $user_id, string $permission_code): bool
    {
        if ($this->source === 'cache') {
            $roles = $this->getCachedUserRoles($user_id);
            $permissions = $this->getAllPermissions();

            return $permissions->where('code', $permission_code)
                ->whereIn('id', $this->getPermissionIdsForRoles($roles))
                ->isNotEmpty();
        }

        return DB::connection($this->source)->table('users_roles as ur')
            ->join('roles_permissions as pr', 'ur.role_id', '=', 'pr.role_id')
            ->join('permissions as p', 'p.id', '=', 'pr.permission_id')
            ->where('ur.user_id', $user_id)
            ->where('p.code', $permission_code)
            ->exists();
    }

    /**
     * @param int|string $role_id
     * @param string $permission_code
     * @return bool
     */
    public function roleHasPermission(int|string $role_id, string $permission_code): bool
    {
        if ($this->source === 'cache') {
            $permissions = $this->getAllPermissions();
            $pivot = $this->getCachedPivotPermissionsRoles();

            $permission_ids = $pivot
                ->where('role_id', $role_id)
                ->pluck('permission_id')
                ->all();

            return $permissions->where('code', $permission_code)
                ->whereIn('id', $permission_ids)
                ->isNotEmpty();
        }

        return DB::connection($this->source)->table('roles_permissions as pr')
            ->join('permissions as p', 'p.id', '=', 'pr.permission_id')
            ->where('pr.role_id', $role_id)
            ->where('p.code', $permission_code)
            ->exists();
    }

    /**
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        if ($this->source === 'cache') {
            return Cache::store($this->cache_store)->remember(
                self::CACHE_PREFIX . '.permissions.all',
                $this->ttl_minutes * 60,
                fn () => DB::table('permissions')->get()
            );
        }

        return DB::connection($this->source)->table('permissions')->get();
    }

    /**
     * @return Collection
     */
    public function getAllRoles(): Collection
    {
        if ($this->source === 'cache') {
            return Cache::store($this->cache_store)->remember(
                self::CACHE_PREFIX . '.roles.all',
                $this->ttl_minutes * 60,
                fn () => DB::table('roles')->get()
            );
        }

        return DB::connection($this->source)->table('roles')->get();
    }

    /**
     * @param int|string $user_id
     * @return Collection
     */
    protected function getCachedUserRoles(int|string $user_id): Collection
    {
        return Cache::store($this->cache_store)->remember(
            self::CACHE_PREFIX . ".user_roles.{$user_id}",
            $this->ttl_minutes * 60,
            fn () => DB::table('users_roles')
                ->where('user_id', $user_id)
                ->pluck('role_id')
        );
    }

    /**
     * @return Collection
     */
    protected function getCachedPivotPermissionsRoles(): Collection
    {
        return Cache::store($this->cache_store)->remember(
            self::CACHE_PREFIX . '.roles_permissions.pivot',
            $this->ttl_minutes * 60,
            fn () => DB::table('roles_permissions')->get()
        );
    }

    /**
     * @param Collection $role_ids
     * @return array
     */
    protected function getPermissionIdsForRoles(Collection $role_ids): array
    {
        return $this->getCachedPivotPermissionsRoles()
            ->whereIn('role_id', $role_ids)
            ->pluck('permission_id')
            ->all();
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        Cache::store($this->cache_store)->forget(self::CACHE_PREFIX . '.permissions.all');
        Cache::store($this->cache_store)->forget(self::CACHE_PREFIX . '.roles.all');
        Cache::store($this->cache_store)->forget(self::CACHE_PREFIX . '.roles_permissions.pivot');
    }
}
