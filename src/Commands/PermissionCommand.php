<?php

namespace Meanify\LaravelPermissions\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Meanify\LaravelPermissions\Services\ClassMethodScannerService;
use Meanify\LaravelPermissions\Services\PermissionYamlGeneratorService;
use Meanify\LaravelPermissions\Services\PermissionYamlSyncerService;

class PermissionCommand extends Command
{
    protected $signature = 'meanify:permissions
                            {--application= : Application\'s name defined in config.meanify-laravel-permissions.applications}
                            {--action= : "import" to import permissions from existing file or "generate" to export permissions to yaml file}
                            {--path= : Base path for scanning classes (applied to "generate" action)}
                            {--file= : Path of the yaml file to be synchronized (applied to "import" and "generate" action)}
                            {--database : Set param to import permissions to database after generate yaml file (applied to "generate" action)}
                            {--connection= : Database connection to use when synchronizing (applied to "import" and "generate" action)}
                            {--force : Ignore prompts and conversations}
                            ';

    protected $description = 'Manages permissions based on PHP classes (Locators, Controllers, etc.)';

    protected static $ACTION_TO_IMPORT   = 'import';
    protected static $ACTION_TO_GENERATE = 'generate';
    protected static $DEFAULT_PATH   = 'app/Http/Controllers';
    protected static $FILE_TYPE_INPUT   = 'input';
    protected static $FILE_TYPE_OUTPUT   = 'output';
    protected static $DEFAULT_FILE_PATH   = 'storage/temp/permissions_{datetime}.yaml';

    /**
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $application        = $this->setApplicationName();
        $role_name          = config('meanify-laravel-permissions.applications.'.$application);
        $action             = $this->setAction();
        $force              = $this->setForce();
        $import_to_database = $this->setImportToDatabase();

        if($action == self::$ACTION_TO_IMPORT)
        {
            $file       = $this->setFile(self::$FILE_TYPE_INPUT);
            $connection = $this->setConnection();

            $this->line("🔍 Here's a summary of what will be executed:");
            $this->newLine();
            $this->table(
                ['Application', 'Action', 'YAML file to import','Connection'],
                [
                    [ $application, $action, $file, $connection]
                ]
            );

            if ($force or $this->confirm("Do you want to import permissions from file to database (connection: $connection)?", true))
            {
                $this->handleSync($file, $application, $connection, $role_name);
            }
            else
            {
                $this->line('❌ Command aborted.');
            }
        }
        else if($action == self::$ACTION_TO_GENERATE)
        {
            $path            = $this->setPath();
            $file            = $this->setFile(self::$FILE_TYPE_OUTPUT);
            $connection      = '(none)';
            if($import_to_database)
            {
                $connection = $this->setConnection();
            }

            $this->line("🔍 Here's a summary of what will be executed:");
            $this->newLine();
            $this->table(
                ['Application', 'Action','Generate from path', 'Output YAML file','Import to database or only file generation','Connection'],
                [
                    [ $application, $action, $path, $file, $import_to_database ? 'Import DB' : 'File generation', $connection]
                ]
            );

            $this->newLine();

            if ($force or $this->confirm('Proceed with generation permissions?', true))
            {
                $this->handleGenerate($path, $application, $file);

                if ($import_to_database and ($force or $this->confirm("Do you want to import permissions from file to database (connection: $connection)?", true)))
                {
                    $this->handleSync($file, $application, $connection, $role_name);
                }
            }
            else
            {
                $this->line('❌ Command aborted.');
            }
        }
    }


    /**
     * @return string
     */
    protected function setApplicationName(): string
    {
        $options = array_keys(config('meanify-laravel-permissions.applications'));

        $application = $this->option('application');

        $success = ($application and in_array($application, $options));

        if(!$success)
        {
            $application = $this->choice(
                'Select application to proceed',
                $options,
            );

            if(!in_array($application, $options))
            {
                abort(500, 'Application not valid. Expected: '. implode(',',$options));
            }
        }

        return $application;
    }


    /**
     * @return string
     */
    protected function setAction(): string
    {
        $options = [self::$ACTION_TO_IMPORT, self::$ACTION_TO_GENERATE];

        $action = $this->option('action');

        $success = ($action and in_array($action, $options));

        if(!$success)
        {
            $action = $this->choice(
                'Select action to proceed',
                $options,
            );

            if(!in_array($action, $options))
            {
                abort(500, 'Action not valid. Expected: '. implode(',',$options));
            }
        }

        return $action;
    }

    /**
     * @return string
     */
    protected function setPath(): string
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
     * @param string $type | input,output
     * @return string
     */
    protected function setFile(string $type): string
    {
        if($type == self::$FILE_TYPE_INPUT)
        {
            $file = $this->option('file');

            setImportFile:

            if(!$file)
            {
                $file = $this->ask('Enter yaml file path with permissions (e.g. storage/temp/permissions.yaml)');
                $file = base_path($file);
            }

            if(!File::exists($file))
            {
                $this->warn('❌ File not found.');
                $this->line('Please insert file path');
                $file = null;

                goto setImportFile;
            }
            else if(!File::isFile($file))
            {
                $this->warn('❌ File is not valid yaml.');
                $this->line('Please insert file path');
                $file = null;

                goto setImportFile;
            }
            else if(File::extension($file) !== 'yaml')
            {
                $this->warn('❌ File is not valid yaml.');
                $this->line('Please insert file path');
                $file = null;

                goto setImportFile;
            }
        }
        else if($type == self::$FILE_TYPE_OUTPUT)
        {
            $file = $this->option('file');

            while (!$file || !$this->validateFile($file))
            {
                $file = $this->ask('Type output yaml file with permissions (e.g. storage/temp/permissions.yaml)', self::$DEFAULT_FILE_PATH);

                if (! $this->validateFile($file))
                {
                    $this->error("Type a valid file name and file path. Must end with .yaml and have no spaces or invalid characters.");
                    $this->newLine();
                }
            }

            $file = str_replace('{datetime}',now()->format('Ymd_His'),$file);
        }
        else
        {
            abort(500, 'Type not valid. Expected: ' . implode(',',[self::$FILE_TYPE_INPUT,self::$FILE_TYPE_OUTPUT]));
        }

        return $file;
    }

    /**
     * @return bool
     */
    protected function setImportToDatabase(): bool
    {
        return (bool) ($this->option('database') ?? false);
    }

    /**
     * @return string
     */
    protected function setConnection(): string
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
     * @return bool
     */
    protected function setForce(): bool
    {
        return (bool) ($this->option('force') ?? false);
    }


    /**
     * @param string $path
     * @param string $application
     * @param string $output_yaml_file
     * @return void
     */
    protected function handleGenerate(string $path, string $application, string $output_yaml_file): void
    {
        $this->info("🔍 Scanning classes in: $path");

        $scanner = new ClassMethodScannerService();
        $permissions = $scanner->scan($path, $application);

        $generator = new PermissionYamlGeneratorService($output_yaml_file);
        $generator->saveToYaml($permissions);

        $this->info('✅ Successfully generated permissions YAML. Output file: ' . $output_yaml_file);
    }

    /**
     * @param string $yaml_file_with_permissions
     * @param string $application
     * @param string|null $connection
     * @param string|null $super_user_role_name
     * @return void
     * @throws \Exception
     */
    protected function handleSync(string $yaml_file_with_permissions, string $application, ?string $connection = null, ?string $super_user_role_name = null): void
    {
        $this->info("📄 Reading file: $yaml_file_with_permissions");

        meanifyPermissions()->clearAll();

        $syncer = new PermissionYamlSyncerService();
        $syncer->syncFromYaml($yaml_file_with_permissions, $application, $connection, $super_user_role_name);

        meanifyPermissions()->refreshAll();

        $this->info('✅ Permissions synchronized with the database.');
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

        if (! File::exists($directory))
        {
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
