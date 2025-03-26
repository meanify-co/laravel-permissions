<?php

namespace Meanify\LaravelPermissions\Support\Handlers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserHandler
{
    protected $user_id;
    protected string $source;
    protected string $cache_store;
    protected int $ttl;
    protected const PREFIX = 'meanify_laravel_permissions';

    /**
     * @param int|string $user_id
     * @param string $source
     * @param string $cache_store
     * @param int $ttl
     */
    public function __construct(int|string $user_id, string $source, string $cache_store, int $ttl)
    {
        $this->user_id     = $user_id;
        $this->source      = $source;
        $this->cache_store = $cache_store;
        $this->ttl         = $ttl;
    }

    /**
     * @param string $permission_code
     * @return bool
     */
    public function has(string $permission_code): bool
    {
        return in_array($permission_code, $this->permissions(), true);
    }

    /**
     * @return array
     */
    public function permissions(): array
    {
        return Cache::store($this->cache_store)->remember(
            self::PREFIX . ".user_permissions.{$this->user_id}",
            $this->ttl * 60,
            function () {
                $role_ids = DB::connection($this->getConnection())->table('users_roles')
                    ->where('user_id', $this->user_id)
                    ->pluck('role_id');

                return DB::connection($this->getConnection())->table('roles_permissions as rp')
                    ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                    ->whereIn('rp.role_id', $role_ids)
                    ->when($this->hasDeletedAt('permissions'), fn ($q) => $q->whereNull('p.deleted_at'))
                    ->pluck('p.code')
                    ->unique()
                    ->values()
                    ->toArray();
            }
        );
    }

    /**
     * @return void
     */
    public function refreshCache(): void
    {
        Cache::store($this->cache_store)->forget(self::PREFIX . ".user_permissions.{$this->user_id}");
    }

    /**
     * @return string
     */
    protected function getConnection(): string
    {
        return $this->source === 'cache' ? config('database.default') : $this->source;
    }

    /**
     * @param string $table
     * @return bool
     */
    protected function hasDeletedAt(string $table): bool
    {
        return Schema::connection($this->getConnection())->hasColumn($table, 'deleted_at');
    }
}
