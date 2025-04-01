<?php

namespace Meanify\LaravelPermissions\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Meanify\LaravelPermissions\Providers\MeanifySupportCommandsServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MeanifySupportCommandsServiceProvider::class,
        ];
    }
}
