<?php

namespace Tests;

use Dcat\Admin\AdminServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [AdminServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        // dcat-admin 没有 mergeConfigFrom，需要在测试环境手动注入包默认配置
        $admin = require __DIR__.'/../config/admin.php';

        $overrides = test_resource_path('config/admin.php');
        if (is_file($overrides)) {
            $admin = array_merge($admin, require $overrides);
        }

        $app['config']->set('admin', $admin);

        $filesystems = test_resource_path('config/filesystems.php');
        if (is_file($filesystems)) {
            $app['config']->set('filesystems', array_merge(
                $app['config']->get('filesystems', []),
                require $filesystems
            ));
        }

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        $app['view']->addNamespace('admin-tests', test_resource_path('views'));
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }
}
