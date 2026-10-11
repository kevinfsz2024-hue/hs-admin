<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\GeneralConfig;
use Dcat\Admin\Show;

/**
 * 系统配置.
 */
class SystemConfigController extends AdminController
{
    protected $title = '系统配置';

    protected function grid()
    {
        return Grid::make(new GeneralConfig(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('group', '分组')->label();
            $grid->column('name', '配置键');
            $grid->column('value', '配置值')->limit(50);
            $grid->column('description', '说明')->limit(30);
            $grid->column('updated_at', '更新时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('group', '分组')->select(GeneralConfig::groups());
                $filter->like('name', '配置键');
            });

            $grid->quickSearch('name', 'description');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new GeneralConfig(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('group', '分组');
            $show->field('name', '配置键');
            $show->field('value', '配置值');
            $show->field('description', '说明');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    protected function form()
    {
        return Form::make(new GeneralConfig(), function (Form $form) {
            $form->display('id', 'ID');

            $form->text('group', '分组')->default('基础')->required()->help('用于配置界面的归类展示');
            $form->text('name', '配置键')
                ->required()
                ->help('代码中通过 general_config("配置键") 读取')
                ->creationRules('required|string|max:100|unique:general_configs,name')
                ->updateRules('required|string|max:100|unique:general_configs,name,{{id}}');
            $form->textarea('value', '配置值')->rows(3);
            $form->text('description', '说明');
        });
    }
}
