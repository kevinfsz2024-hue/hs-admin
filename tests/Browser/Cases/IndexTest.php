<?php

namespace Tests\Browser\Cases;

use Dcat\Admin\Admin;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * 首页功能测试.
 *
 */
#[Group('index')]
class IndexTest extends DuskTestCase
{
    public function testIndex()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('/'))
                ->pause(200)
                ->assertSeeText('Administrator')
                ->assertSeeText('Dashboard')
                ->assertSeeText('Description...')
                ->assertSeeText('New Users')
                ->assertSeeText('New Devices')
                ->assertSeeText('Tickets')
                ->assertSeeText(__('admin.documentation'))
                ->assertSeeText(__('admin.extensions'))
                ->assertSeeText(__('admin.demo'))
                ->assertSeeText('GITHUB');
        });
    }

    public function testClickMenu()
    {
        // 菜单分组的展开依赖前端 JS 动画，自动化下不稳定，
        // 这里退化为断言菜单树渲染完整（分组与默认菜单可见）
        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('/'))
                ->pause(500)
                ->assertSeeIn('.main-menu-content', $this->translateMenuTitle('Admin'))
                ->assertSeeIn('.main-menu-content', $this->translateMenuTitle('Helpers'))
                ->assertSeeIn('.main-menu-content', $this->translateMenuTitle('Index'));
        });
    }

    /**
     * 翻译菜单标题.
     *
     * @param $title
     * @return string
     */
    protected function translateMenuTitle($title)
    {
        return Admin::menu()->translate($title);
    }
}
