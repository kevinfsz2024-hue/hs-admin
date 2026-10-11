<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\MemberRule;
use Dcat\Admin\Show;

/**
 * 会员规则管理（积分行为规则）.
 */
class MemberRuleController extends AdminController
{
    protected $title = '会员规则管理';

    protected function grid()
    {
        return Grid::make(new MemberRule(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '规则标识');
            $grid->column('title', '规则名称');
            $grid->column('type', '行为类型')->using([
                MemberRule::TYPE_REGISTER => '注册',
                MemberRule::TYPE_LOGIN => '登录',
                MemberRule::TYPE_CONSUME => '消费',
                MemberRule::TYPE_CUSTOM => '自定义',
            ])->label([
                MemberRule::TYPE_REGISTER => 'success',
                MemberRule::TYPE_LOGIN => 'primary',
                MemberRule::TYPE_CONSUME => 'info',
                MemberRule::TYPE_CUSTOM => 'default',
            ]);
            $grid->column('score', '变动积分');
            $grid->column('frequency', '频次')->using([
                MemberRule::FREQUENCY_ONCE => '仅一次',
                MemberRule::FREQUENCY_DAILY => '每天一次',
                MemberRule::FREQUENCY_EACH => '每次触发',
            ])->label();
            $grid->column('status', '状态')->switch();
            $grid->column('remark', '备注')->limit(30);
            $grid->column('updated_at', '更新时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('title', '规则名称');
                $filter->equal('type', '行为类型')->select([
                    MemberRule::TYPE_REGISTER => '注册',
                    MemberRule::TYPE_LOGIN => '登录',
                    MemberRule::TYPE_CONSUME => '消费',
                    MemberRule::TYPE_CUSTOM => '自定义',
                ]);
            });

            $grid->quickSearch('name', 'title');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new MemberRule(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '规则标识');
            $show->field('title', '规则名称');
            $show->field('type', '行为类型');
            $show->field('score', '变动积分');
            $show->field('frequency', '频次');
            $show->field('status', '状态')->using([1 => '启用', 0 => '停用']);
            $show->field('remark', '备注');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    protected function form()
    {
        return Form::make(new MemberRule(), function (Form $form) {
            $form->display('id', 'ID');

            $form->text('name', '规则标识')
                ->required()
                ->help('业务代码中通过该标识获取规则，如 register、daily_login')
                ->creationRules('required|string|unique:member_rules,name')
                ->updateRules('required|string|unique:member_rules,name,{{id}}');

            $form->text('title', '规则名称')->required();

            $form->radio('type', '行为类型')->options([
                MemberRule::TYPE_REGISTER => '注册',
                MemberRule::TYPE_LOGIN => '登录',
                MemberRule::TYPE_CONSUME => '消费',
                MemberRule::TYPE_CUSTOM => '自定义',
            ])->default(MemberRule::TYPE_CUSTOM);

            $form->number('score', '变动积分')->help('正数为奖励、负数为扣减')->default(0);

            $form->radio('frequency', '触发频次')->options([
                MemberRule::FREQUENCY_ONCE => '仅一次',
                MemberRule::FREQUENCY_DAILY => '每天一次',
                MemberRule::FREQUENCY_EACH => '每次触发',
            ])->default(MemberRule::FREQUENCY_EACH);

            $form->switch('status', '状态')->default(1);
            $form->textarea('remark', '备注')->rows(2);
        });
    }
}
