<?php

namespace Tests\Feature;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Grid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('grid')]
class AdminGridTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Grid 渲染依赖请求上下文
        $this->app->instance(
            'request',
            Request::create('/admin/auth/users', 'GET')
        );

        Administrator::query()->delete();

        for ($i = 1; $i <= 3; $i++) {
            Administrator::create([
                'username' => 'grid-user-'.$i,
                'password' => Hash::make('secret123'),
                'name' => 'Grid User '.$i,
            ]);
        }
    }

    public function testGridRendersRows()
    {
        $grid = Grid::make(new Administrator());

        $html = $grid->render();

        $this->assertIsString($html);
        $this->assertStringContainsString('Grid User 1', $html);
        $this->assertStringContainsString('Grid User 3', $html);
    }

    public function testGridPaginates()
    {
        $grid = Grid::make(new Administrator());
        $grid->model()->setCurrentPage(1)->paginate(2);

        $data = $grid->model()->buildData();

        $this->assertCount(2, $data);
    }
}
