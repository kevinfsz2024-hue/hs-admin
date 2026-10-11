<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelperTest extends TestCase
{
    public function testAdminBasePath()
    {
        $this->assertSame('/admin', admin_base_path());
        $this->assertSame('/admin/auth/users', admin_base_path('auth/users'));
        $this->assertSame('/admin/auth/users', admin_base_path('/auth/users'));
    }

    public function testAdminPath()
    {
        $normalize = function ($path) {
            return str_replace('\\', '/', $path);
        };

        $this->assertSame($normalize(app_path('Admin')), $normalize(admin_path()));
        $this->assertSame(
            $normalize(app_path('Admin')).'/bootstrap.php',
            $normalize(admin_path('bootstrap.php'))
        );
    }

    public function testFormatByte()
    {
        $this->assertSame('1024B', format_byte(1024));
        $this->assertSame('2KB', format_byte(2048));
        $this->assertSame('1024KB', format_byte(1024 * 1024));
    }
}
