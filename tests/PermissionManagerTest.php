<?php

namespace Meanify\LaravelPermissions\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Meanify\LaravelPermissions\Support\PermissionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insert([
            ['id' => 1, 'code' => 'admin.test.view', 'label' => 'View Test', 'group' => 'Test', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'admin.test.edit', 'label' => 'Edit Test', 'group' => 'Test', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('permission_role')->insert([
            ['role_id' => 1, 'permission_id' => 1],
        ]);

        DB::table('users_roles')->insert([
            ['user_id' => 10, 'role_id' => 1],
        ]);
    }

    /**
     * @return void
     */
    public function test_get_all_permissions_and_roles(): void
    {
        $manager = new PermissionManager();

        $permissions = $manager->getAllPermissions();
        $roles = $manager->getAllRoles();

        $this->assertCount(2, $permissions);
        $this->assertCount(1, $roles);
    }

    /**
     * @return void
     */
    public function test_user_has_permission_from_db(): void
    {
        $manager = new PermissionManager('testing');

        $this->assertTrue($manager->forUser(10)->has('admin.test.view'));
        $this->assertFalse($manager->forUser(10)->has('admin.test.edit'));
    }

    /**
     * @return void
     */
    public function test_role_has_permission_from_db(): void
    {
        $manager = new PermissionManager('testing');

        $this->assertTrue($manager->forRole(1)->has('admin.test.view'));
        $this->assertFalse($manager->forRole(1)->has('admin.test.edit'));
    }

    /**
     * @return void
     */
    public function test_user_permission_from_cache(): void
    {
        Cache::flush();

        $manager = new PermissionManager('cache');
        $manager->clearCache();

        $this->assertTrue($manager->forUser(10)->has('admin.test.view'));
        $this->assertFalse($manager->forUser(10)->has('admin.test.edit'));
    }

    /**
     * @return void
     */
    public function test_role_permission_from_cache(): void
    {
        Cache::flush();

        $manager = new PermissionManager('cache');
        $manager->clearCache();

        $this->assertTrue($manager->forRole(1)->has('admin.test.view'));
        $this->assertFalse($manager->forRole(1)->has('admin.test.edit'));
    }
}
