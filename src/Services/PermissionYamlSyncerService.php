<?php

namespace Meanify\LaravelPermissions\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Yaml\Yaml;

class PermissionYamlSyncerService
{
    protected static $DEFAULT_ROLE_FOR_ADMIN_USER = 'Admin';

    /**
     * @param string $file_path
     * @param bool $dry_run
     * @param string|null $connection
     * @return void
     * @throws \Exception
     */
    public function syncFromYaml(string $file_path, bool $dry_run = false, ?string $connection = null): void
    {
        if (!File::exists($file_path)) {
            throw new \Exception("YAML file not found: $file_path");
        }

        $yaml = Yaml::parseFile($file_path);
        $permissions = collect($yaml)->filter(fn($item) => ($item['code'] ?? null) && ($item['label'] ?? null));

        $conn = $connection ?: config('database.default');

        $now = now();
        $permission_ids = [];

        // ID => code
        $current_permissions = DB::connection($conn)
            ->table('permissions')
            ->when($this->hasDeletedAt('permissions', $conn), fn ($q) => $q->whereNull('deleted_at'))
            ->pluck('code', 'id')
            ->toArray();

        $handled_codes = [];

        foreach ($permissions as $permission)
        {
            $code = $permission['code'];
            $data = [
                'code'       => $code,
                'label'      => $permission['label'],
                'group'      => $permission['group'] ?? null,
                'class'      => $permission['class'] ?? null,
                'method'     => $permission['method'] ?? null,
                'apply'      => $permission['apply'] ?? true,
                'updated_at' => $now,
                'created_at' => $now,
            ];

            $existing_id = array_search($code, $current_permissions);

            if ($existing_id === false) {
                if (!$dry_run) {
                    $id = DB::connection($conn)->table('permissions')->insertGetId($data);
                    $permission_ids[] = $id;
                }
            } else {
                if (!$dry_run) {
                    DB::connection($conn)->table('permissions')
                        ->where('id', $existing_id)
                        ->when($this->hasDeletedAt('permissions', $conn), fn ($q) => $q->whereNull('deleted_at'))
                        ->update($data);
                }
                $permission_ids[] = $existing_id;
                $handled_codes[] = $code;
            }
        }

        if (! $dry_run) {
            // Remove unused permissions
            $unused_ids = array_keys(array_diff($current_permissions, $handled_codes));

            if (!empty($unused_ids)) {
                DB::connection($conn)->table('roles_permissions')
                    ->when($this->hasDeletedAt('roles_permissions', $conn), fn ($q) => $q->whereNull('deleted_at'))
                    ->whereIn('permission_id', $unused_ids)
                    ->delete();

                DB::connection($conn)->table('permissions')
                    ->when($this->hasDeletedAt('permissions', $conn), fn ($q) => $q->whereNull('deleted_at'))
                    ->whereIn('id', $unused_ids)
                    ->delete();
            }

            // Create or get Admin role
            DB::connection($conn)->table('roles')
                ->when($this->hasDeletedAt('roles', $conn), fn ($q) => $q->whereNull('deleted_at'))
                ->updateOrInsert(
                ['name' => self::$DEFAULT_ROLE_FOR_ADMIN_USER],
                ['updated_at' => $now, 'created_at' => $now]
            );

            $role = DB::connection($conn)->table('roles')
                ->when($this->hasDeletedAt('roles', $conn), fn ($q) => $q->whereNull('deleted_at'))
                ->where('name', self::$DEFAULT_ROLE_FOR_ADMIN_USER)
                ->first();

            if ($role) {
                $existing = DB::connection($conn)->table('roles_permissions')
                    ->where('role_id', $role->id)
                    ->when($this->hasDeletedAt('roles_permissions', $conn), fn ($q) => $q->whereNull('deleted_at'))
                    ->pluck('permission_id')
                    ->toArray();

                $to_insert = array_diff($permission_ids, $existing);

                foreach ($to_insert as $permission_id) {
                    DB::connection($conn)->table('roles_permissions')->insert([
                        'role_id'       => $role->id,
                        'permission_id' => $permission_id,
                        'updated_at'    => $now,
                        'created_at'    => $now,
                    ]);
                }
            }
        }
    }


    /**
     * @param string $table
     * @return bool
     */
    protected function hasDeletedAt(string $table, string $conn): bool
    {
        return Schema::connection($conn)->hasColumn($table, 'deleted_at');
    }
}
