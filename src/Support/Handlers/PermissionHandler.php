<?php

namespace Meanify\LaravelPermissions\Support\Handlers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PermissionHandler
{
    protected string $application;
    protected string $source;
    protected string $cache_store;
    protected int $ttl;
    protected const PREFIX = 'mfy_permissions';

    public function __construct(string $application, string $source, string $cache_store, int $ttl)
    {
        $this->application             = $application;
        $this->source                  = $source;
        $this->cache_store             = $cache_store;
        $this->ttl                     = $ttl;
        $this->cache_key_classes       = self::PREFIX . "::".$application."::classes";
        $this->cache_key_permissions   = self::PREFIX . "::".$application."::permissions_all";
        $this->cache_key_roles         = self::PREFIX . "::".$application."::roles_all";
        $this->logger                  = Config::get('meanify-laravel-permissions.log.active', false);
    }

    /**
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        $this->writeLog("getting all permissions");

        return Cache::store($this->cache_store)->remember(
            $this->cache_key_permissions,
            $this->ttl * 60,
            function(){
                $this->writeLog("caching all permissions");

                return DB::connection($this->getConnection())->table('permissions')
                    ->where('application', $this->application)
                    ->when($this->hasDeletedAt('permissions'), fn ($q) => $q->whereNull('deleted_at'))
                    ->get();
            }
        );
    }

    /**
     * @return Collection
     */
    public function getAllRoles(): Collection
    {
        $this->writeLog("getting all roles");

        return Cache::store($this->cache_store)->remember(
            $this->cache_key_roles,
            $this->ttl * 60,
            function(){
                $this->writeLog("caching all roles");

                return DB::connection($this->getConnection())->table('roles')
                    ->where('application', $this->application)
                    ->when($this->hasDeletedAt('roles'), fn ($q) => $q->whereNull('deleted_at'))
                    ->get();
            }
        );
    }

    /**
     * @return array|Collection
     */
    public function getAllClassesAndMethods(): array|Collection
    {
        $this->writeLog("getting all classes and methods");

        return Cache::store($this->cache_store)->remember(
            $this->cache_key_classes,
            $this->ttl * 60,
            function(){
                $this->writeLog("caching all classes and methods");

                return DB::connection($this->getConnection())->table('permissions')
                    ->where('application', $this->application)
                    ->when($this->hasDeletedAt('permissions'), fn ($q) => $q->whereNull('deleted_at'))
                    ->get()
                    ->groupBy('class')
                    ->map(function ($items) {
                        return $items->mapWithKeys(function ($item) {
                            return [$item->method => $item->code];
                        });
                    })
                    ->toArray();
            }
        );
    }


    /**
     * @param string $class
     * @param string $method
     * @return mixed
     */
    public function getClassMethodPermissionCode(string $class, string $method): mixed
    {
        $all = $this->getAllClassesAndMethods();

        try
        {
            return $all[$class][$method];
        }
        catch (\Throwable $exception)
        {
            return null;
        }
    }

    /**
     * @return void
     */
    public function refreshBaseCache(): void
    {
        $this->clearBaseCache();

        $this->getAllRoles();
        $this->getAllPermissions();
        $this->getAllClassesAndMethods();
    }

    /**
     * @return void
     */
    public function clearBaseCache(): void
    {
        Cache::store($this->cache_store)->forget($this->cache_key_classes);
        Cache::store($this->cache_store)->forget($this->cache_key_permissions);
        Cache::store($this->cache_store)->forget($this->cache_key_roles);
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
            logger()->info("[Permissions::PermissionHandler][application::$this->application] $message");
        }
    }
}
