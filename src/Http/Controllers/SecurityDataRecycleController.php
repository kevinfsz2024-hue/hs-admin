<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\SecurityDataRecycle;
use Dcat\Admin\Services\DataSecurityService;
use Dcat\Admin\Show;
use Illuminate\Http\Request;

/**
 * 数据回收规则管理.
 */
class SecurityDataRecycleController extends AdminController
{
    protected $title = '数据回收规则管理';

    /**
     * 数据表下拉联动接口：?q=连接名（空为默认连接），返回 [{id, text}].
     */
    public function tables(Request $request)
    {
        $tables = DataSecurityService::tableOptions(trim((string) $request->get('q')));

        $data = [];
        foreach ($tables as $table) {
            $data[] = ['id' => $table, 'text' => $table];
        }

        return response()->json($data);
    }

    protected function grid()
    {
        return Grid::make(new SecurityDataRecycle(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '规则名称');
            $grid->column('controller', '控制器')->limit(50);
            $grid->column('connection', '连接标识')->display(function ($value) {
                return $value ?: '默认连接';
            });
            $grid->column('data_table', '数据表');
            $grid->column('table_primary_key', '数据表主键');
            $grid->column('status', '状态')->using([1 => '删除监控中', 0 => '已停用'])->label(['success', 'default']);
            $grid->column('updated_at', '修改时间')->sortable();
            $grid->column('created_at', '创建时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '规则名称');
                $filter->like('controller', '控制器');
                $filter->equal('data_table', '数据表');
                $filter->equal('status', '状态')->select([1 => '删除监控中', 0 => '已停用']);
            });

            $grid->quickSearch('name', 'controller', 'data_table');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new SecurityDataRecycle(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '规则名称');
            $show->field('controller', '控制器');
            $show->field('connection', '连接标识');
            $show->field('data_table', '数据表');
            $show->field('table_primary_key', '数据表主键');
            $show->field('status', '状态')->using([1 => '删除监控中', 0 => '已停用']);
            $show->field('updated_at', '修改时间');
            $show->field('created_at', '创建时间');
        });
    }

    protected function form()
    {
        return Form::make(new SecurityDataRecycle(), function (Form $form) {
            $form->display('id', 'ID');

            $values = DataSecurityService::currentValues($form);

            $controllerOptions = DataSecurityService::controllerOptions();
            if (! empty($values['controller'])) {
                $controllerOptions = DataSecurityService::ensureControllerOption($controllerOptions, $values['controller']);
            }

            $tableOptions = DataSecurityService::tableOptions($values['connection'] ?? null);
            if (! empty($values['data_table'])) {
                $tableOptions = DataSecurityService::ensureFlatOption($tableOptions, $values['data_table']);
            }

            $form->text('name', '规则名称')
                ->required()
                ->help('仅用于展示，会作为快照记录在数据回收站中');

            $form->select('controller', '控制器')
                ->required()
                ->groups($controllerOptions)
                ->help('该控制器发起的删除将被回收（选项含应用控制器与内置控制器）');

            $form->select('connection', '连接标识')
                ->options(DataSecurityService::connectionOptions())
                ->load('data_table', 'security/tables')
                ->help('目标表使用的数据库连接；切换后数据表列表自动刷新，留空为默认连接');

            $form->select('data_table', '数据表')
                ->required()
                ->options($tableOptions)
                ->help('需要回收的数据表');

            $form->text('table_primary_key', '数据表主键')
                ->default('id')
                ->help('还原被删数据时使用，默认 id');

            $form->switch('status', '删除监控')
                ->default(1)
                ->help('开启后该控制器删除数据时自动存入数据回收站');
        });
    }
}
