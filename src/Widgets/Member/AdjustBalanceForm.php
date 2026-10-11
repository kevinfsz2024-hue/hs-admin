<?php

namespace Dcat\Admin\Widgets\Member;

use Dcat\Admin\Models\Member;
use Dcat\Admin\Models\MemberBalanceLog;
use Dcat\Admin\Services\MemberService;
use Dcat\Admin\Widgets\Form;

class AdjustBalanceForm extends Form
{
    /**
     * 由行操作注入的会员 ID（通过隐藏字段随表单提交）.
     *
     * @var int|null
     */
    protected $memberId;

    public function withMemberId($id)
    {
        $this->memberId = $id;

        return $this;
    }

    public function handle(array $input)
    {
        $member = Member::query()->find($input['id'] ?? 0);

        if (! $member) {
            return $this->response()->error('会员不存在');
        }

        try {
            MemberService::adjustBalance(
                $member,
                (float) ($input['amount'] ?? 0),
                $input['type'] ?? MemberBalanceLog::TYPE_ADJUST,
                $input['remark'] ?? null
            );
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage());
        }

        return $this->response()->success('余额调整成功')->refresh();
    }

    public function form()
    {
        $this->hidden('id')->default($this->memberId);

        $this->radio('type', '变动类型')->options([
            MemberBalanceLog::TYPE_ADJUST => '后台调整',
            MemberBalanceLog::TYPE_RECHARGE => '充值',
            MemberBalanceLog::TYPE_CONSUME => '消费',
        ])->default(MemberBalanceLog::TYPE_ADJUST);

        $this->decimal('amount', '变动金额')->help('正数为增加，负数为扣减')->required();
        $this->text('remark', '备注');
    }
}
