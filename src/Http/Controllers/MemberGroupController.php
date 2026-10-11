<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\MemberGroup;
use Dcat\Admin\Show;

/**
 * 会员分组管理.
 */
class MemberGroupController extends AdminController
{
    protected $title = '会员分组管理';

    protected function grid()
    {
        return Grid::make(new MemberGroup(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '分组名称');
            $grid->column('description', '描述')->limit(50);
            $grid->column('members_count', '会员数')->count('members');
            $grid->column('created_at', '创建时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '分组名称');
            });

            $grid->quickSearch('name');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new MemberGroup(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '分组名称');
            $show->field('description', '描述');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    protected function form()
    {
        return Form::make(new MemberGroup(), function (Form $form) {
            $form->display('id', 'ID');

            $form->text('name', '分组名称')
                ->required()
                ->creationRules('required|string|unique:member_groups,name')
                ->updateRules('required|string|unique:member_groups,name,{{id}}');

            $form->textarea('description', '描述')->rows(3);
        });
    }
}
