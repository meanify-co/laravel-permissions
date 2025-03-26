<?php

namespace Meanify\LaravelPermissions\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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

        $existing_codes = DB::connection($conn)->table('permissions')->pluck('code')->toArray();
        $permission_ids = [];

        foreach ($permissions as $permission)
        {

            $data = [
                'code'   => $permission['code'],
                'label'  => $permission['label'],
                'group'  => $permission['group'] ?? null,
                'class'  => $permission['class'] ?? null,
                'method' => $permission['method'] ?? null,
                'apply' => $permission['apply'] ?? true,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            if (!in_array($data['code'], $existing_codes))
            {
                if (!$dry_run)
                {
                    $id = DB::connection($conn)->table('permissions')->insertGetId($data);
                    $permission_ids[] = $id;
                }
            }
            else
            {
                $id = DB::connection($conn)->table('permissions')->where('code', $data['code'])->value('id');

                if(!$dry_run)
                {
                    DB::connection($conn)->table('permissions')->where('id', $id)->update($data);
                }

                $permission_ids[] = $id;
            }
        }

        if(!$dry_run)
        {
            $role_id = DB::connection($conn)->table('roles')->updateOrInsert(
                ['name' => self::$DEFAULT_ROLE_FOR_ADMIN_USER],
                ['updated_at' => now(), 'created_at' => now()]
            );

            $role = DB::connection($conn)->table('roles')->where('name', self::$DEFAULT_ROLE_FOR_ADMIN_USER)->first();

            if($role)
            {
                $existing = DB::connection($conn)->table('roles_permissions')
                    ->where('role_id', $role->id)
                    ->pluck('permission_id')
                    ->toArray();

                $to_insert = array_diff($permission_ids, $existing);

                foreach ($to_insert as $permission_id)
                {
                    DB::connection($conn)->table('roles_permissions')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permission_id,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
                }
            }
        }
    }
}
