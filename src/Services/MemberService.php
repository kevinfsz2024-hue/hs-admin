<?php

namespace Dcat\Admin\Services;

use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Models\Member;
use Dcat\Admin\Models\MemberBalanceLog;
use Dcat\Admin\Models\MemberPointsLog;
use Illuminate\Support\Facades\DB;

/**
 * 会员余额 / 积分调整服务.
 *
 * 所有余额与积分的变动都应通过本服务进行，以保证流水与字段一致。
 */
class MemberService
{
    /**
     * 调整会员余额.
     *
     * @param  Member|int  $member
     * @param  float  $amount 变动金额，正数为增加、负数为扣减
     * @param  string  $type
     * @param  string|null  $remark
     * @return Member
     *
     * @throws AdminException
     */
    public static function adjustBalance($member, float $amount, string $type = MemberBalanceLog::TYPE_ADJUST, ?string $remark = null): Member
    {
        $amount = round($amount, 2);

        if ($amount == 0.0) {
            throw new AdminException('变动金额不能为 0');
        }

        return DB::transaction(function () use ($member, $amount, $type, $remark) {
            /* @var Member $member */
            $member = Member::query()->lockForUpdate()->findOrFail(is_object($member) ? $member->id : $member);

            $before = (float) $member->balance;
            $after = round($before + $amount, 2);

            if ($after < 0) {
                throw new AdminException('扣减后余额不能为负数');
            }

            $member->forceFill(['balance' => $after])->save();

            MemberBalanceLog::query()->create([
                'user_id' => $member->id,
                'type' => $type,
                'amount' => $amount,
                'before' => $before,
                'after' => $after,
                'remark' => $remark,
            ]);

            return $member;
        });
    }

    /**
     * 调整会员积分.
     *
     * @param  Member|int  $member
     * @param  int  $score 变动积分，正数为增加、负数为扣减
     * @param  string  $type
     * @param  string|null  $remark
     * @return Member
     *
     * @throws AdminException
     */
    public static function adjustPoints($member, int $score, string $type = MemberPointsLog::TYPE_ADJUST, ?string $remark = null): Member
    {
        if ($score === 0) {
            throw new AdminException('变动积分不能为 0');
        }

        return DB::transaction(function () use ($member, $score, $type, $remark) {
            /* @var Member $member */
            $member = Member::query()->lockForUpdate()->findOrFail(is_object($member) ? $member->id : $member);

            $before = (int) $member->points;
            $after = $before + $score;

            if ($after < 0) {
                throw new AdminException('扣减后积分不能为负数');
            }

            $member->forceFill(['points' => $after])->save();

            MemberPointsLog::query()->create([
                'user_id' => $member->id,
                'type' => $type,
                'score' => $score,
                'before' => $before,
                'after' => $after,
                'remark' => $remark,
            ]);

            return $member;
        });
    }
}
