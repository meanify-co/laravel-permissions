<?php

namespace Meanify\LaravelPermissions\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Meanify\LaravelPermissions\Attributes\Permission as PermissionAttribute;

class ClassMethodScannerService
{
    /**
     * @param string $base_path
     * @param string $prefix
     * @return array
     */
    public function scan(string $base_path, string $prefix = ''): array
    {
        $php_files   = File::allFiles($base_path);
        $permissions = [];

        foreach ($php_files as $file)
        {
            $relative_path = Str::after($file->getPathname(), base_path() . DIRECTORY_SEPARATOR);
            $class = $this->getClassFromFile($relative_path);

            if (!$class || !class_exists($class))
            {
                continue;
            }

            try
            {
                $reflection = new ReflectionClass($class);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            }
            catch (\Throwable $e)
            {
                continue;
            }

            foreach ($methods as $method)
            {
                if ($method->class !== $class)
                {
                    continue;
                }

                $method_name = $method->getName();

                if (Str::startsWith($method_name, '__'))
                {
                    continue;
                }

                $default_code  = ($prefix ? "$prefix." : '') . $method_name;
                $default_group = class_basename($class);
                $label         = Str::headline($method_name);
                $apply         = true;

                try {
                    $attributes = $method->getAttributes(PermissionAttribute::class);

                    if (!empty($attributes))
                    {
                        $attr = $attributes[0]->newInstance();

                        if (!$attr->apply())
                        {
                            continue;
                        }

                        $code   = $attr->code() ?? $default_code;
                        $group  = $attr->group() ?? $default_group;
                        $label  = $attr->label() ?? $label;
                        $apply  = $attr->apply();
                    }
                    else
                    {
                        $code  = $default_code;
                        $group = $default_group;
                    }
                }
                catch (\Throwable $e)
                {
                    $code  = $default_code;
                    $group = $default_group;
                }

                if (isset($permissions[$code]))
                {
                    echo "⚠️  Duplicate permission code '{$code}' found in {$class}::{$method_name}. Only the first occurrence will be kept.\n";
                    continue;
                }

                $permissions[$code] = [
                    'code'   => $code,
                    'label'  => $label,
                    'group'  => $group,
                    'class'  => $class,
                    'method' => $method_name,
                    'apply'  => $apply,
                ];
            }
        }

        ksort($permissions);
        return $permissions;
    }


    /**
     * @param string $relative_path
     * @return string|null
     */
    protected function getClassFromFile(string $relative_path): ?string
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        if (!isset($composer['autoload']['psr-4']))
        {
            return null;
        }

        foreach ($composer['autoload']['psr-4'] as $namespace => $path)
        {
            $base_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($path, '/'));

            if (Str::startsWith($relative_path, $base_path))
            {
                $sub_path = Str::after($relative_path, $base_path);
                $sub_path = str_replace(['/', '.php'], ['\\', ''], $sub_path);
                return rtrim($namespace, '\\') . $sub_path;
            }
        }

        return null;
    }
}