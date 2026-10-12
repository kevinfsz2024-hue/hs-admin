<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\SecuritySensitiveData;
use Dcat\Admin\Services\DataSecurityService;
use Dcat\Admin\Show;

/**
 * 敏感字段规则管理.
 */
class SecuritySensitiveDataController extends AdminController
{
    protected $title = '敏感字段规则管理';

    protected function grid()
    {
        return Grid::make(new SecuritySensitiveData(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '规则名称');
            $grid->column('controller', '控制器')->limit(40);
            $grid->column('connection', '连接标识')->display(function ($value) {
                return $value ?: '默认连接';
            });
            $grid->column('data_table', '数据表');
            $grid->column('table_primary_key', '数据表主键');
            $grid->column('sensitive_fields', '敏感字段')->badge('primary');
            $grid->column('status', '状态')->using([1 => '修改监控中', 0 => '已停用'])->label(['success', 'default']);
            $grid->column('updated_at', '修改时间')->sortable();
            $grid->column('created_at', '创建时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '规则名称');
                $filter->like('controller', '控制器');
                $filter->equal('data_table', '数据表');
                $filter->equal('status', '状态')->select([1 => '修改监控中', 0 => '已停用']);
            });

            $grid->quickSearch('name', 'controller', 'data_table');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new SecuritySensitiveData(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '规则名称');
            $show->field('controller', '控制器');
            $show->field('connection', '连接标识');
            $show->field('data_table', '数据表');
            $show->field('table_primary_key', '数据表主键');
            $show->field('sensitive_fields', '敏感字段')->badge();
            $show->field('status', '状态')->using([1 => '修改监控中', 0 => '已停用']);
            $show->field('updated_at', '修改时间');
            $show->field('created_at', '创建时间');
        });
    }

    protected function form()
    {
        return Form::make(new SecuritySensitiveData(), function (Form $form) {
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
                ->help('仅用于展示，会作为快照记录在敏感数据修改记录中');

            $form->select('controller', '控制器')
                ->required()
                ->groups($controllerOptions)
                ->help('该控制器发起的更新将被监控（选项含应用控制器与内置控制器）');

            $form->select('connection', '连接标识')
                ->options(DataSecurityService::connectionOptions())
                ->load('data_table', 'security/tables')
                ->help('目标表使用的数据库连接；切换后数据表列表自动刷新，留空为默认连接');

            $form->select('data_table', '数据表')
                ->required()
                ->options($tableOptions)
                ->help('需要监控的数据表');

            $form->text('table_primary_key', '数据表主键')
                ->default('id')
                ->help('修改记录中定位数据行使用，默认 id');

            $form->tags('sensitive_fields', '敏感字段')
                ->required()
                ->help('需要审计的字段名（数据表列名），如 username、mobile、password；修改这些字段时记录修改前后的值');

            $form->switch('status', '修改监控')
                ->default(1)
                ->help('开启后该控制器更新敏感字段时自动记录修改前后值');
        });
    }
}
