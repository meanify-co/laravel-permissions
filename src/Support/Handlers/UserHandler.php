<?php

namespace Meanify\LaravelPermissions\Support\Handlers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserHandler
{
    protected $user_id;
    protected string $application;
    protected string $source;
    protected string $cache_store;
    protected int $ttl;
    protected const PREFIX = 'mfy_permissions';

    /**
     * @param int|string $user_id
     * @param string $application
     * @param string $source
     * @param string $cache_store
     * @param int $ttl
     */
    public function __construct(int|string $user_id, string $application, string $source, string $cache_store, int $ttl)
    {
        $this->user_id     = $user_id;
        $this->application = $application;
        $this->source      = $source;
        $this->cache_store = $cache_store;
        $this->ttl         = $ttl;
        $this->cache_key   = self::PREFIX . "::".$application."::user::{$this->user_id}";
        $this->logger      = Config::get('meanify-laravel-permissions.log.active', false);
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
        $this->writeLog("getting permissions for user_id={$this->user_id}");

        return Cache::store($this->cache_store)->remember(
            $this->cache_key,
            $this->ttl * 60,
            function () {
                $this->writeLog("caching permissions for user_id={$this->user_id}");

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
        $this->clearCache();

        $this->permissions();
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        Cache::store($this->cache_store)->forget($this->cache_key);
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

    /**
     * @param $message
     * @return void
     */
    protected function writeLog($message)
    {
        if($this->logger)
        {
            logger()->info("[Permissions::UserHandler][application::$this->application] $message");
        }
    }
}
