<?php

namespace Meanify\LaravelPermissions\Tests;

class PermissionCommandTest extends TestCase
{
    public function testPermissionCommandIsRegistered()
    {
        $this->artisan('meanify:permissions --help')
            ->assertSuccessful()
            ->expectsOutputToContain('Manages permissions');
    }
}
