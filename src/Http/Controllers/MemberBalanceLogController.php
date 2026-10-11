<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Grid;
use Dcat\Admin\Models\Member;
use Dcat\Admin\Models\MemberBalanceLog;

/**
 * 会员余额管理（余额流水，只读）.
 */
class MemberBalanceLogController extends AdminController
{
    protected $title = '会员余额管理';

    protected function grid()
    {
        return Grid::make(new MemberBalanceLog(['member']), function (Grid $grid) {
            $grid->disableCreateButton();
            $grid->disableRowSelector();

            $grid->column('id', 'ID')->sortable();
            $grid->column('member.username', '会员');
            $grid->column('type', '类型')->using([
                MemberBalanceLog::TYPE_RECHARGE => '充值',
                MemberBalanceLog::TYPE_CONSUME => '消费',
                MemberBalanceLog::TYPE_ADJUST => '后台调整',
            ])->label([
                MemberBalanceLog::TYPE_RECHARGE => 'success',
                MemberBalanceLog::TYPE_CONSUME => 'danger',
                MemberBalanceLog::TYPE_ADJUST => 'primary',
            ]);
            $grid->column('amount', '变动金额')->display(function ($value) {
                $color = $value >= 0 ? 'success' : 'danger';

                return "<span class='text-{$color}'>".($value >= 0 ? '+' : '').$value.'</span>';
            });
            $grid->column('before', '变动前');
            $grid->column('after', '变动后');
            $grid->column('remark', '备注')->limit(30);
            $grid->column('created_at', '时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id', '会员')
                    ->select(Member::query()->pluck('username', 'id'));
                $filter->equal('type', '类型')->select([
                    MemberBalanceLog::TYPE_RECHARGE => '充值',
                    MemberBalanceLog::TYPE_CONSUME => '消费',
                    MemberBalanceLog::TYPE_ADJUST => '后台调整',
                ]);
                $filter->between('created_at', '时间')->datetime();
            });

            $grid->quickSearch('remark');
        });
    }
}
