<?php

namespace Tests\Browser\Cases;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * 鉴权登陆功能测试.
 *
 */
#[Group('auth')]
class AuthTest extends DuskTestCase
{
    protected $login = false;

    public function testLoginPage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('auth/login'))
                ->assertSeeText(__('admin.login'));
        });
    }

    public function testVisitWithoutLogin()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('/'))
                ->assertPathIs(admin_base_path('auth/login'))
                ->assertGuest('admin');
        });
    }

    public function testLogin()
    {
        $this->browse(function (Browser $browser) {
            $credentials = ['username' => 'admin', 'password' => 'admin'];

            $browser->visit(admin_base_path('auth/login'))
                ->assertPathIs(admin_base_path('auth/login'))
                ->assertSeeText(__('admin.login'))
                ->type('username', $credentials['username'])
                ->type('password', $credentials['password'])
                ->press(__('admin.login'))
                ->waitForLocation(admin_base_path('/'), 3)
                ->assertPathIs(admin_base_path('/'))
                ->assertSeeText('管理员')
                ->assertSeeText('仪表盘')
                ->assertSeeText('数据概览')
                ->assertSeeText('新增用户')
                ->assertSeeText('新增设备')
                ->assertSeeText('工单')
                ->assertSeeText((__('admin.documentation')))
                ->assertSeeText('GITHUB');

            $browser->within('.main-menu-content', function (Browser $browser) {
                $browser->assertSeeText('系统')
                    ->clickLink('系统');
            });
        });
    }

    public function testLogout()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('auth/logout'))
                ->assertPathIs(admin_base_path('auth/login'))
                ->assertGuest('admin');
        });
    }
}
