<?php

namespace Meanify\LaravelPermissions\Tests;

use Illuminate\Support\Facades\File;
use Meanify\LaravelPermissions\Services\PermissionYamlGeneratorService;
use Meanify\LaravelPermissions\Services\PermissionYamlSyncerService;

class PermissionServiceTest extends TestCase
{
    protected string $yaml_file = __DIR__.'/tmp/permissions.test.yaml';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(dirname($this->yaml_file));

    }

    /**
     * @return void
     */
    public function test_it_generates_yaml_file(): void
    {
        $permissions = [
            'admin.test.view' => [
                'code' => 'admin.test.view',
                'label' => 'Show test',
                'group' => 'Tests'
            ]
        ];

        $generator = new PermissionYamlGeneratorService($this->yaml_file);
        $generator->saveToYaml($permissions);

        $this->assertFileExists($this->yaml_file);
        $this->assertStringContainsString('admin.test.view', file_get_contents($this->yaml_file));
    }

    /**
     * @return void
     */
    public function test_it_syncs_permissions_from_yaml(): void
    {
        $yaml_content = <<<YAML
admin.users.create:
  code: admin.users.create
  label: Create user
  group: Users
YAML;


        File::put($this->yaml_file, $yaml_content);

        $syncer = new PermissionYamlSyncerService();
        $syncer->syncFromYaml($this->yaml_file, true);

        $this->assertDatabaseHas('permissions', [
            'code' => 'admin.users.create',
            'label' => 'Create user',
            'group' => 'Users',
        ]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if (file_exists($this->yaml_file)) {
            unlink($this->yaml_file);
        }

        parent::tearDown();
    }
}
