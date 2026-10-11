<?php

namespace Tests\Feature;

use Dcat\Admin\Models\Administrator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('auth')]
class AdminAuthTest extends TestCase
{
    public function testAdminGuardAttempt()
    {
        Administrator::create([
            'username' => 'auth-tester',
            'password' => Hash::make('secret123'),
            'name' => 'Auth Tester',
        ]);

        $this->assertTrue(
            Auth::guard('admin')->attempt(['username' => 'auth-tester', 'password' => 'secret123'])
        );

        $this->assertFalse(
            Auth::guard('admin')->attempt(['username' => 'auth-tester', 'password' => 'wrong-password'])
        );

        $this->assertSame('auth-tester', Auth::guard('admin')->user()->username);
    }

    public function testAdminGuardConfig()
    {
        $this->assertSame('admin', config('admin.auth.guard'));
        $this->assertSame('session', config('admin.auth.guards.admin.driver'));
        $this->assertSame(
            Administrator::class,
            config('admin.auth.providers.admin.model')
        );
    }
}
