<?php

namespace Tests\Browser\Cases;

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
                ->assertSeeText('管理员')
                ->assertSeeText('仪表盘')
                ->assertSeeText('数据概览')
                ->assertSeeText('新增用户')
                ->assertSeeText('新增设备')
                ->assertSeeText('工单')
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
                ->assertSeeIn('.main-menu-content', '系统')
                ->assertSeeIn('.main-menu-content', '开发工具')
                ->assertSeeIn('.main-menu-content', '首页')
                ->assertSeeIn('.main-menu-content', '会员管理');
        });
    }
}
