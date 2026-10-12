<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\SecuritySensitiveDataLog;
use Dcat\Admin\Show;
use Illuminate\Support\Str;

/**
 * 敏感数据修改记录.
 */
class SecuritySensitiveDataLogController extends AdminController
{
    protected $title = '敏感数据修改记录';

    protected function grid()
    {
        return Grid::make(new SecuritySensitiveDataLog(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('admin_name', '操作管理员');
            $grid->column('name', '规则名称');
            $grid->column('controller', '控制器')->limit(30);
            $grid->column('connection', '连接标识')->display(function ($value) {
                return $value ?: '默认';
            });
            $grid->column('data_table', '数据表');
            $grid->column('data_id', '修改行')->display(function ($value) {
                return 'id='.$value;
            });
            $grid->column('field', '修改项')->display(function () {
                return $this->field_label ?: $this->field;
            });
            $grid->column('original', '修改前')->limit(20);
            $grid->column('changes', '修改后')->limit(20);
            $grid->column('ip', 'IP');
            $grid->column('created_at', '修改时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '规则名称');
                $filter->like('data_table', '数据表');
                $filter->like('field', '修改项');
                $filter->like('admin_name', '操作管理员');
                $filter->between('created_at', '修改时间')->datetime();
            });

            $grid->quickSearch('name', 'data_table', 'field', 'admin_name', 'ip');

            $grid->disableCreateButton();
            $grid->disableEditButton();
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new SecuritySensitiveDataLog(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('admin_name', '操作管理员');
            $show->field('name', '规则名称');
            $show->field('controller', '控制器');
            $show->field('connection', '连接标识');
            $show->field('data_table', '数据表');
            $show->field('data_id', '修改行');
            $show->field('field_label', '修改项');
            $show->field('original', '修改前');
            $show->field('changes', '修改后');
            $show->field('ip', 'IP');
            $show->field('created_at', '修改时间');
        });
    }

    protected function form()
    {
        // 审计记录只读，不允许编辑
        return Form::make(new SecuritySensitiveDataLog(), function (Form $form) {
            $form->display('id', 'ID');
            $form->display('name', '规则名称');
            $form->display('data_table', '数据表');
            $form->display('created_at', '修改时间');
        });
    }
}
