<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Actions\Member\AdjustBalance;
use Dcat\Admin\Actions\Member\AdjustPoints;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Member;
use Dcat\Admin\Models\MemberGroup;
use Dcat\Admin\Show;

/**
 * 会员管理.
 */
class MemberController extends AdminController
{
    protected $title = '会员管理';

    protected function grid()
    {
        return Grid::make(new Member(['group']), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('avatar', '头像')->image(null, 40);
            $grid->column('username', '用户名');
            $grid->column('nickname', '昵称');
            $grid->column('group.name', '分组')->label();
            $grid->column('mobile', '手机号');
            $grid->column('balance', '余额')->sortable();
            $grid->column('points', '积分')->sortable();
            $grid->column('status', '状态')->switch();
            $grid->column('last_login_at', '最后登录');
            $grid->column('created_at', '注册时间')->sortable();

            $grid->model()->orderBy('id', 'desc');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();
                $filter->equal('group_id', '分组')->select(MemberGroup::query()->pluck('name', 'id'));
                $filter->like('username', '用户名');
                $filter->like('nickname', '昵称');
                $filter->like('mobile', '手机号');
                $filter->equal('status', '状态')->select([1 => '正常', 0 => '禁用']);
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->prepend(new AdjustBalance());
                $actions->prepend(new AdjustPoints());
            });

            $grid->quickSearch('username', 'nickname', 'mobile');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new Member(['group', 'balanceLogs', 'pointsLogs']), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('avatar', '头像')->image();
            $show->field('username', '用户名');
            $show->field('nickname', '昵称');
            $show->field('group.name', '分组');
            $show->field('mobile', '手机号');
            $show->field('email', '邮箱');
            $show->field('balance', '余额');
            $show->field('points', '积分');
            $show->field('status', '状态')->using([1 => '正常', 0 => '禁用']);
            $show->field('last_login_at', '最后登录时间');
            $show->field('created_at', '注册时间');
            $show->field('updated_at', '更新时间');
        });
    }

    protected function form()
    {
        return Form::make(new Member(), function (Form $form) {
            $form->display('id', 'ID');

            $form->text('username', '用户名')
                ->required()
                ->creationRules('required|string|unique:member_users,username')
                ->updateRules('required|string|unique:member_users,username,{{id}}');

            $form->text('nickname', '昵称');
            $form->image('avatar', '头像')->autoUpload()->uniqueName();

            $form->mobile('mobile', '手机号');
            $form->email('email', '邮箱');

            if ($form->isEditing()) {
                $form->password('password', '密码')->minLength(6)->help('留空表示不修改密码');
            } else {
                $form->password('password', '密码')->required()->minLength(6);
            }

            $form->select('group_id', '分组')
                ->options(MemberGroup::query()->pluck('name', 'id'));

            $form->switch('status', '状态')->default(1);

            $form->saving(function (Form $form) {
                if (! $form->password) {
                    $form->deleteInput('password');
                }

                if ($form->group_id === '' || $form->group_id === null) {
                    $form->group_id = null;
                }
            });

            $form->display('balance', '余额')->help('余额请通过列表中的「调整余额」操作修改，以保证流水一致');
            $form->display('points', '积分')->help('积分请通过列表中的「调整积分」操作修改，以保证流水一致');

            $form->footer(function (Form\Footer $footer) {
                $footer->disableReset();
            });
        });
    }
}
