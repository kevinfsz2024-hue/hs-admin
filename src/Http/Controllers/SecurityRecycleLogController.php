<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Actions\Security\BatchRestoreRecycleData;
use Dcat\Admin\Actions\Security\RestoreRecycleData;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\SecurityDataRecycleLog;
use Dcat\Admin\Show;
use Illuminate\Support\Str;

/**
 * 数据回收站.
 */
class SecurityRecycleLogController extends AdminController
{
    protected $title = '数据回收站';

    protected function grid()
    {
        return Grid::make(new SecurityDataRecycleLog(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('admin_name', '操作管理员');
            $grid->column('name', '回收规则名称');
            $grid->column('controller', '控制器')->limit(40);
            $grid->column('connection', '连接标识')->display(function ($value) {
                return $value ?: '默认';
            });
            $grid->column('data_table', '数据表');
            $grid->column('deleted_data', '被删数据')->display(function ($data) {
                return Str::limit(json_encode($data, JSON_UNESCAPED_UNICODE), 30);
            })->modal('被删数据', function () {
                return '<pre style="max-height:420px;overflow:auto;white-space:pre-wrap;">'
                    .e(json_encode($this->deleted_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
                    .'</pre>';
            });
            $grid->column('ip', 'IP');
            $grid->column('created_at', '删除时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '回收规则名称');
                $filter->like('data_table', '数据表');
                $filter->like('admin_name', '操作管理员');
                $filter->between('created_at', '删除时间')->datetime();
            });

            $grid->quickSearch('name', 'data_table', 'admin_name', 'ip');

            $grid->disableCreateButton();
            $grid->disableEditButton();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->append(new RestoreRecycleData());
            });

            $grid->batchActions(function (Grid\Tools\BatchActions $batch) {
                $batch->add(new BatchRestoreRecycleData());
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new SecurityDataRecycleLog(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('admin_name', '操作管理员');
            $show->field('name', '回收规则名称');
            $show->field('controller', '控制器');
            $show->field('connection', '连接标识');
            $show->field('data_table', '数据表');
            $show->field('table_primary_key', '数据表主键');
            $show->field('deleted_data', '被删数据')->json();
            $show->field('ip', 'IP');
            $show->field('created_at', '删除时间');
        });
    }

    protected function form()
    {
        // 回收站只读：仅支持还原 / 彻底删除，不提供编辑
        return Form::make(new SecurityDataRecycleLog(), function (Form $form) {
            $form->display('id', 'ID');
            $form->display('name', '回收规则名称');
            $form->display('data_table', '数据表');
            $form->display('created_at', '删除时间');
        });
    }
}
