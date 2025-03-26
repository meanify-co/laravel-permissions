<?php

namespace Meanify\LaravelPermissions\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Meanify\LaravelPermissions\Services\ClassMethodScannerService;
use Meanify\LaravelPermissions\Services\PermissionYamlGeneratorService;
use Meanify\LaravelPermissions\Services\PermissionYamlSyncerService;

class PermissionCommand extends Command
{
    protected $signature = 'meanify:permissions
                            {--sync : Synchronize YAML with the database}
                            {--path= : Base path for scanning classes}
                            {--prefix= : Prefix for permission codes}
                            {--file= : Path of the YAML file to be synchronized}
                            {--dry-run : Simulates synchronization without changing the database}
                            {--connection= : Database connection to use when syncing}
                            {--non-interactive : Skips confirmation prompts}';

    protected $description = 'Manages permissions based on PHP classes (Locators, Controllers, etc.)';

    protected static $DEFAULT_PATH   = 'app/Http/Controllers';
    protected static $DEFAULT_PREFIX = 'meanify';
    protected static $DEFAULT_FILE   = 'storage/temp/permissions_{datetime}.yaml';

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $path            = $this->resolvePath();
        $prefix          = $this->resolvePrefix();
        $file            = str_replace('{datetime}',now()->format('Ymd_His'),$this->resolveOutputFile());
        $dry_run         = $this->option('dry-run') ?? false;
        $connection      = $this->resolveConnection();
        $sync            = $this->option('sync') ?? false;
        $non_interactive = $this->option('non-interactive') ?? false;

        $this->line("🔍 Here's a summary of what will be executed:");
        $this->newLine();
        $this->line("- Scan path : $path");
        $this->line("- Prefix: $prefix");
        $this->line("- Output YAML File: $file");

        if ($dry_run)
        {
            $this->line("- Dry Run enabled: permissions will be generated but NOT synced to the database");
        }
        elseif ($sync)
        {
            $this->line("- Database will be synchronized (connection: $connection)");
        }

        $this->newLine();

        if ($non_interactive || $this->confirm('Proceed with generation permissions?', true))
        {
            $this->handleGenerate($path, $prefix, $file);

            if ($sync && ! $dry_run)
            {
                $this->handleSync($file, $dry_run, $connection);
            }
            elseif (!$sync && ! $dry_run && ! $non_interactive && $this->confirm("Do you also want to synchronize with the database (connection: $connection)?", true))
            {
                $this->handleSync($file, $dry_run, $connection);
            }
        }
        else
        {
            $this->line('❌ Command aborted.');
        }
    }

    /**
     * @return string
     */
    protected function resolvePath(): string
    {
        $path = $this->option('path');

        while (!$path || ! File::exists(base_path($path)))
        {
            $path = $this->anticipate('Type path to scan and generate permissions', [
                'app/Http/Controllers',
                'app/Http/Locators',
                'app/Services',
            ], self::$DEFAULT_PATH);

            if (! File::exists(base_path($path)))
            {
                $this->error("The path does not exist (full path: " . base_path($path) . ")");
                $this->newLine();
            }
        }

        return $path;
    }

    /**
     * @return string
     */
    protected function resolvePrefix(): string
    {
        $prefix = $this->option('prefix');

        while (!$prefix || $prefix !== $this->normalizePrefix($prefix))
        {
            $prefix = $this->ask('Type prefix for permissions codes (only letters and lowercase)', self::$DEFAULT_PREFIX);

            if ($prefix !== $this->normalizePrefix($prefix))
            {
                $this->error("Only lowercase letters are allowed in prefix");
                $this->newLine();
            }
        }

        return $prefix;
    }

    /**
     * @return string
     */
    protected function resolveOutputFile(): string
    {
        $file = $this->option('file');

        while (!$file || !$this->validateFile($file))
        {
            $file = $this->ask('Type output yaml file with permissions (e.g. storage/temp/permissions.yaml)', self::$DEFAULT_FILE);

            if (! $this->validateFile($file))
            {
                $this->error("Type a valid file name and file path. Must end with .yaml and have no spaces or invalid characters.");
                $this->newLine();
            }
        }

        return $file;
    }

    /**
     * @return string
     */
    protected function resolveConnection(): string
    {
        $connection = $this->option('connection');

        while (!$connection || ! $this->validateConnection($connection))
        {
            $connection = $this->anticipate('Type connection to sync permissions', array_keys(config('database.connections')), config('database.default'));

            if (! $this->validateConnection($connection))
            {
                $this->error("Connection '$connection' is not configured.");
                $this->newLine();
            }
        }

        return $connection;
    }

    /**
     * @param string $path
     * @param string $prefix
     * @param string $output_yaml_file
     * @return void
     */
    protected function handleGenerate(string $path, string $prefix, string $output_yaml_file): void
    {
        $this->info("🔍 Scanning classes in: $path");

        $scanner = new ClassMethodScannerService();
        $permissions = $scanner->scan($path, $prefix);

        $generator = new PermissionYamlGeneratorService($output_yaml_file);
        $generator->saveToYaml($permissions);

        $this->info('✅ Successfully generated permissions YAML. Output file: ' . $output_yaml_file);
    }

    /**
     * @param string $yaml_file_with_permissions
     * @param bool $dry_run
     * @param string|null $connection
     * @return void
     * @throws \Exception
     */
    protected function handleSync(string $yaml_file_with_permissions, bool $dry_run = false, ?string $connection): void
    {
        $this->info("📄 Reading file: $yaml_file_with_permissions");

        $syncer = new PermissionYamlSyncerService();
        $syncer->syncFromYaml($yaml_file_with_permissions, $dry_run, $connection);

        if ($dry_run) {
            $this->info('🔎 Simulation completed. No changes were made.');
        } else {
            $this->info('✅ Permissions synchronized with the database.');
        }
    }

    /**
     * @param string $prefix
     * @return string
     */
    protected function normalizePrefix(string $prefix): string
    {
        $prefix = strtolower($prefix);
        $prefix = str_replace(['Ç', 'ç'], ['c', 'c'], $prefix);

        return preg_replace("/[^a-z]/", '', $prefix);
    }

    /**
     * @param string|null $file
     * @return bool
     */
    protected function validateFile(?string $file): bool
    {
        if (! $file || str_contains($file, ' ') || ! str_ends_with($file, '.yaml')) {
            return false;
        }

        $full_path = base_path($file);
        $directory = dirname($full_path);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return true;
    }

    /**
     * @param string $connection
     * @return bool
     */
    protected function validateConnection(string $connection): bool
    {
        return array_key_exists($connection, config('database.connections'));
    }
}
