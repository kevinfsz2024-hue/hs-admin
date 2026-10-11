<?php

namespace Tests\Feature;

use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Models\Member;
use Dcat\Admin\Models\MemberBalanceLog;
use Dcat\Admin\Models\MemberPointsLog;
use Dcat\Admin\Services\MemberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('member')]
class MemberModuleTest extends TestCase
{
    public function testMemberTablesCreated()
    {
        foreach (['member_users', 'member_groups', 'member_rules', 'member_balance_logs', 'member_points_logs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "table [$table] should exist");
        }
    }

    public function testMemberMenusCreated()
    {
        $parent = DB::table('admin_menu')->where('title', '会员管理')->whereNull('uri')->first();
        $this->assertNotNull($parent, '会员管理菜单分组应存在');
        $this->assertSame(5, DB::table('admin_menu')->where('parent_id', $parent->id)->count());
        $this->assertNotNull(DB::table('admin_menu')->where('uri', 'member')->where('parent_id', $parent->id)->first());
    }

    public function testMemberWithGroupRelation()
    {
        $group = \Dcat\Admin\Models\MemberGroup::create(['name' => 'vip', 'description' => 'VIP 会员']);

        $member = Member::create([
            'username' => 'member01',
            'password' => Hash::make('secret123'),
            'nickname' => '会员一号',
            'group_id' => $group->id,
        ]);

        $member->refresh();

        $this->assertSame('vip', $member->group->name);
        $this->assertEquals(0, $member->balance);
        $this->assertEquals(0, $member->points);
        $this->assertNotSame('secret123', $member->password, '密码应已哈希存储');
        $this->assertSame('member01', $member->username);
    }

    public function testAdjustBalanceWritesLog()
    {
        $member = Member::create([
            'username' => 'balance01',
            'password' => Hash::make('secret123'),
        ]);

        MemberService::adjustBalance($member, 100.5, MemberBalanceLog::TYPE_RECHARGE, '首充');
        MemberService::adjustBalance($member->id, -0.5);

        $member->refresh();

        $this->assertEquals(100.0, $member->balance);
        $this->assertSame(2, MemberBalanceLog::query()->where('user_id', $member->id)->count());

        $log = MemberBalanceLog::query()->where('user_id', $member->id)->orderBy('id')->first();
        $this->assertEquals(100.5, $log->amount);
        $this->assertEquals(0, $log->before);
        $this->assertEquals(100.5, $log->after);
        $this->assertSame('首充', $log->remark);
    }

    public function testAdjustBalanceRejectsNegativeResult()
    {
        $member = Member::create([
            'username' => 'balance02',
            'password' => Hash::make('secret123'),
        ]);

        $this->expectException(AdminException::class);
        $this->expectExceptionMessage('扣减后余额不能为负数');

        MemberService::adjustBalance($member, -1);
    }

    public function testAdjustBalanceRejectsZero()
    {
        $member = Member::create([
            'username' => 'balance03',
            'password' => Hash::make('secret123'),
        ]);

        $this->expectException(AdminException::class);

        MemberService::adjustBalance($member, 0);
    }

    public function testAdjustPointsWritesLog()
    {
        $member = Member::create([
            'username' => 'points01',
            'password' => Hash::make('secret123'),
        ]);

        MemberService::adjustPoints($member, 50, MemberPointsLog::TYPE_RULE, '注册奖励');
        MemberService::adjustPoints($member->id, -20);

        $member->refresh();

        $this->assertEquals(30, $member->points);

        $log = MemberPointsLog::query()->where('user_id', $member->id)->orderBy('id')->first();
        $this->assertEquals(50, $log->score);
        $this->assertEquals(0, $log->before);
        $this->assertEquals(50, $log->after);
    }
}
