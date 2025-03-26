<?php

namespace Meanify\LaravelPermissions\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class PermissionYamlGeneratorService
{
    protected string $output_file;

    /**
     * @param string|null $output_file
     */
    public function __construct(?string $output_file = null)
    {
        $this->output_file = $output_file ?? base_path('permissions.yaml');
        
        File::ensureDirectoryExists(dirname($this->output_file));

    }

    /**
     * @param array $permissions
     * @return void
     */
    public function saveToYaml(array $permissions): void
    {
        $yaml_content = Yaml::dump($permissions, 4, 2);
        File::put($this->output_file, $yaml_content);
    }
}