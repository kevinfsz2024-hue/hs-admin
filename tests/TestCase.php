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

        // 包的 AdminServiceProvider 在 testbench 中先于 defineEnvironment 注册，
        // 需要在这里补上 auth guard/provider 注入（等价于 loadAdminAuthConfig）
        foreach (($admin['auth']['guards'] ?? []) as $key => $guard) {
            $app['config']->set("auth.guards.{$key}", $guard);
        }
        foreach (($admin['auth']['providers'] ?? []) as $key => $provider) {
            $app['config']->set("auth.providers.{$key}", $provider);
        }

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
        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/members'));
        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/generals'));
        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }
}
