<?php

namespace Meanify\LaravelPermissions\Support\Handlers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionHandler
{
    protected string $source;
    protected string $cache_store;
    protected int $ttl;
    protected const PREFIX = 'meanify_laravel_permissions';

    public function __construct(string $source, string $cache_store, int $ttl)
    {
        $this->source      = $source;
        $this->cache_store = $cache_store;
        $this->ttl         = $ttl;
    }

    /**
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        return Cache::store($this->cache_store)->remember(
            self::PREFIX . '.permissions.all',
            $this->ttl * 60,
            fn () => DB::connection($this->getConnection())->table('permissions')
                ->when($this->hasDeletedAt('permissions'), fn ($q) => $q->whereNull('deleted_at'))
                ->get()
        );
    }

    /**
     * @return Collection
     */
    public function getAllRoles(): Collection
    {
        return Cache::store($this->cache_store)->remember(
            self::PREFIX . '.roles.all',
            $this->ttl * 60,
            fn () => DB::connection($this->getConnection())->table('roles')
                ->when($this->hasDeletedAt('roles'), fn ($q) => $q->whereNull('deleted_at'))
                ->get()
        );
    }

    /**
     * @return array
     */
    public function getClassMethodPermissionCode(): array
    {
        return Cache::store($this->cache_store)->remember(
            self::PREFIX . '.class_method_map',
            $this->ttl * 60,
            function ()
            {
                return  DB::connection($this->getConnection())->table('permissions')
                    ->select('code', 'class', 'method')
                    ->when($this->hasDeletedAt('permissions'), fn ($q) => $q->whereNull('deleted_at'))
                    ->first();
            }
        );
    }

    /**
     * @return void
     */
    public function refreshClassMethodMap(): void
    {
        Cache::store($this->cache_store)->forget(self::PREFIX . '.class_method_map');
    }

    /**
     * @return void
     */
    public function clearBaseCache(): void
    {
        Cache::store($this->cache_store)->forget(self::PREFIX . '.permissions.all');
        Cache::store($this->cache_store)->forget(self::PREFIX . '.roles.all');
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
