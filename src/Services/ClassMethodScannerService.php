<?php

namespace Meanify\LaravelPermissions\Services;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Illuminate\Filesystem\Filesystem;
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
        $permissions = [];
        $files = (new Finder)->files()->in($base_path)->name('*.php')->sortByName();

        foreach ($files as $file) {
            $relative_path = Str::after($file->getPathname(), base_path() . DIRECTORY_SEPARATOR);
            $class = $this->getClassFromFile($relative_path);

            if ($class && class_exists($class)) {
                $reflection = new ReflectionClass($class);
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $method) {
                    if ($method->class !== $class || $method->name === '__construct') {
                        continue;
                    }

                    $method_name = $method->name;
                    $default_group = class_basename($class);
                    $default_code = $prefix ? "$prefix.$default_group.$method_name" : "$default_group.$method_name";

                    $apply = true;
                    $attributes = $method->getAttributes(PermissionAttribute::class);

                    try
                    {
                        $attr = $attributes[0]->newInstance() ?? null;

                        if ($attr)
                        {
                            if (!$attr->apply())
                            {
                                continue;
                            }

                            $group = $attr->group() ?? $default_group;
                            $code = $attr->code() ?? $default_code;
                            $class_name = $attr->class() ?? $class;
                            $method_ref = $attr->method() ?? $method_name;
                        }
                        else
                        {
                            $group = $default_group;
                            $code = $default_code;
                            $class_name = $class;
                            $method_ref = $method_name;
                        }
                    }
                    catch (\Exception $e)
                    {

                        $group = $default_group;
                        $code = $default_code;
                        $class_name = $class;
                        $method_ref = $method_name;
                    }

                    $permissions[$code] = [
                        'code' => $code,
                        'label' => Str::headline($method_name),
                        'group' => $group,
                        'class' => $class_name,
                        'method' => $method_ref,
                        'apply' => $apply,
                    ];
                }
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

        if (!isset($composer['autoload']['psr-4'])) {
            return null;
        }

        foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
            $base_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($path, '/'));

            if (Str::startsWith($relative_path, $base_path)) {
                $sub_path = Str::after($relative_path, $base_path);
                $sub_path = str_replace(['/', '.php'], ['\\', ''], $sub_path);
                return rtrim($namespace, '\\') . $sub_path;
            }
        }

        return null;
    }
}