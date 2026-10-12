<?php

namespace Tests\Browser\Cases\Security;

use Dcat\Admin\Models\Member;
use Dcat\Admin\Models\SecurityDataRecycle;
use Dcat\Admin\Models\SecurityDataRecycleLog;
use Dcat\Admin\Models\SecuritySensitiveData;
use Dcat\Admin\Models\SecuritySensitiveDataLog;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

/**
 * 数据安全管理模块端到端测试.
 *
 * 覆盖：规则管理页渲染与创建、删除回收捕获、回收站还原、敏感字段修改审计。
 *
 * 说明：演示环境行操作为 DropdownActions（⋮ 下拉），删除/还原需先展开下拉。
 */
#[Group('security')]
class SecurityTest extends DuskTestCase
{
    /**
     * @var string
     */
    protected $memberTable;

    public function setUp(): void
    {
        parent::setUp();

        $this->memberTable = (new Member())->getTable();

        // 清理历史测试数据与预置的会员表规则，保证列表/选择器唯一、捕获行为可预期
        Member::query()->where('username', 'like', 'sec_test_%')->get()->each->forceDelete();
        SecurityDataRecycleLog::query()->delete();
        SecuritySensitiveDataLog::query()->delete();
        SecurityDataRecycle::query()->where('data_table', $this->memberTable)->delete();
        SecuritySensitiveData::query()->where('data_table', $this->memberTable)->delete();
    }

    public function tearDown(): void
    {
        Member::query()->where('username', 'like', 'sec_test_%')->get()->each->forceDelete();

        parent::tearDown();
    }

    protected function makeMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'username' => 'sec_test_'.Str::random(6),
            'nickname' => '安全测试昵称',
            'mobile' => '13800001111',
            'password' => 'secret123',
            'group_id' => 0,
            'status' => 1,
        ], $attributes));
    }

    /**
     * 展开行操作下拉并点击目标操作.
     */
    protected function clickRowAction(Browser $browser, string $actionSelector): void
    {
        $browser->within('td.grid__actions__', function (Browser $browser) use ($actionSelector) {
            $browser->click('.icon-more-vertical')
                ->whenAvailable('.dropdown-menu', function (Browser $browser) use ($actionSelector) {
                    $browser->click($actionSelector);
                }, 3);
        });
    }

    /**
     * 确认 SweetAlert 弹窗.
     */
    protected function confirmSwal(Browser $browser): void
    {
        $browser->whenAvailable('.swal2-popup', function (Browser $browser) {
            $browser->click('.swal2-confirm');
        }, 5);
    }

    /**
     * 数据安全管理的 4 个页面均可正常渲染.
     */
    public function testSecurityPagesRender()
    {
        SecurityDataRecycle::create([
            'name' => '测试回收规则',
            'controller' => 'MemberController',
            'data_table' => $this->memberTable,
            'table_primary_key' => 'id',
            'status' => 1,
        ]);

        SecuritySensitiveData::create([
            'name' => '测试敏感规则',
            'controller' => 'MemberController',
            'data_table' => $this->memberTable,
            'table_primary_key' => 'id',
            'sensitive_fields' => ['nickname'],
            'status' => 1,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('security/recycle-logs'))
                ->assertTitleContains('数据回收站');

            $browser->visit(admin_base_path('security/sensitive-data-logs'))
                ->assertTitleContains('敏感数据修改记录');

            $browser->visit(admin_base_path('security/data-recycles'))
                ->assertTitleContains('数据回收规则管理')
                ->assertSee('测试回收规则')
                ->assertSee('删除监控中');

            $browser->visit(admin_base_path('security/sensitive-data'))
                ->assertTitleContains('敏感字段规则管理')
                ->assertSee('测试敏感规则')
                ->assertSee('修改监控中')
                ->assertSee('nickname');
        });
    }

    /**
     * 通过后台表单创建数据回收规则（控制器/连接/数据表均为下拉，连接切换联动刷新数据表）.
     */
    public function testCreateRecycleRuleViaForm()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('security/tables'))
                ->assertSee($this->memberTable);

            $browser->visit(admin_base_path('security/data-recycles/create'))
                ->type('name', '测试表单创建规则');

            // 控制器下拉：选择应用控制器 AuthController
            $browser->script("$('select[name=\"controller\"]').val('AuthController').trigger('change');");

            // 连接标识下拉：选 mysql，联动刷新数据表列表
            $browser->script("$('select[name=\"connection\"]').val('mysql').trigger('change');");
            $browser->pause(2000);

            // 数据表下拉（联动后已刷新）：选择会员表
            $browser->script("$('select[name=\"data_table\"]').val('{$this->memberTable}').trigger('change');");

            $browser->type('table_primary_key', 'id')
                ->press(__('admin.submit'))
                ->waitForText(__('admin.save_succeeded'), 10);

            $this->seeInDatabase('security_data_recycle', [
                'name' => '测试表单创建规则',
                'controller' => 'AuthController',
                'connection' => 'mysql',
                'data_table' => $this->memberTable,
                'table_primary_key' => 'id',
                'status' => 1,
            ]);
        });
    }

    /**
     * 删除数据 -> 自动进入回收站 -> 还原后数据恢复.
     */
    public function testDeleteCaptureAndRestore()
    {
        $member = $this->makeMember();
        $id = $member->id;

        SecurityDataRecycle::create([
            'name' => '测试删除回收',
            'controller' => 'MemberController',
            'data_table' => $this->memberTable,
            'table_primary_key' => 'id',
            'status' => 1,
        ]);

        $this->browse(function (Browser $browser) use ($id) {
            // 后台列表中删除该会员
            $browser->visit(admin_base_path('member'));

            $this->clickRowAction($browser, 'a[data-action="delete"]');
            $this->confirmSwal($browser);
            $browser->pause(1500);

            // 数据行已被删除
            $this->assertNull(Member::query()->find($id));

            // 回收站中出现被删数据快照
            $log = SecurityDataRecycleLog::query()->where('data_table', $this->memberTable)->latest('id')->first();
            $this->assertNotNull($log, '删除未被回收站捕获');
            $this->assertSame(
                $id,
                (int) ($log->deleted_data['id'] ?? 0),
                '被删数据快照与删除的行不一致，实际 JSON: '.substr($log->deleted_data ? json_encode($log->deleted_data, JSON_UNESCAPED_UNICODE) : 'null', 0, 400)
            );
            $this->assertSame('测试删除回收', $log->name);
            $this->assertSame('MemberController', $log->controller);
            $this->assertNotNull($log->admin_name, '操作管理员未记录');
            $this->assertNotNull($log->ip, 'IP 未记录');

            // 进入回收站点击还原
            $browser->visit(admin_base_path('security/recycle-logs'));

            $this->clickRowAction($browser, 'a[data-action="restore-recycle"]');
            $this->confirmSwal($browser);
            $browser->pause(1500);

            // 数据行已恢复，回收站记录被清除
            $this->assertNotNull(Member::query()->find($id), '还原后数据行未恢复');
            $this->assertSame(
                '安全测试昵称',
                Member::query()->find($id)->nickname,
                '还原后的数据内容与删除前不一致'
            );
            $this->assertEquals(0, SecurityDataRecycleLog::query()->where('id', $log->id)->count());
        });
    }

    /**
     * 更新敏感字段 -> 记录修改前后值；未变化的字段不记录.
     */
    public function testSensitiveFieldUpdateCapture()
    {
        $member = $this->makeMember();
        $id = $member->id;

        SecuritySensitiveData::create([
            'name' => '测试敏感修改',
            'controller' => 'MemberController',
            'data_table' => $this->memberTable,
            'table_primary_key' => 'id',
            'sensitive_fields' => ['nickname', 'mobile'],
            'status' => 1,
        ]);

        $this->browse(function (Browser $browser) use ($id) {
            $browser->visit(admin_base_path('member/'.$id.'/edit'))
                ->type('nickname', '改名后的昵称')
                ->press(__('admin.submit'))
                ->waitForLocation(admin_base_path('member'), 15);
        });

        // nickname 变更被记录
        $log = SecuritySensitiveDataLog::query()
            ->where('data_table', $this->memberTable)
            ->where('data_id', (string) $id)
            ->where('field', 'nickname')
            ->latest('id')
            ->first();

        $this->assertNotNull($log, '敏感字段修改未被记录');
        $this->assertSame('安全测试昵称', $log->original);
        $this->assertSame('改名后的昵称', $log->changes);
        $this->assertSame('昵称', $log->field_label, '修改项未取到表单字段标签');
        $this->assertSame('测试敏感修改', $log->name);
        $this->assertNotNull($log->admin_name);
        $this->assertNotNull($log->ip);

        // mobile 未变化，不应产生记录
        $this->assertEquals(
            0,
            SecuritySensitiveDataLog::query()->where('field', 'mobile')->count(),
            '未变化的敏感字段不应记录'
        );
    }

    /**
     * 规则停用后不再捕获.
     */
    public function testDisabledRuleNotCaptured()
    {
        $member = $this->makeMember();
        $id = $member->id;

        SecurityDataRecycle::create([
            'name' => '测试停用规则',
            'controller' => 'MemberController',
            'data_table' => $this->memberTable,
            'table_primary_key' => 'id',
            'status' => 0,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit(admin_base_path('member'));

            $this->clickRowAction($browser, 'a[data-action="delete"]');
            $this->confirmSwal($browser);
            $browser->pause(1500);
        });

        $this->assertNull(Member::query()->find($id));
        $this->assertEquals(
            0,
            SecurityDataRecycleLog::query()->where('data_table', $this->memberTable)->count(),
            '停用的规则不应捕获删除数据'
        );
    }
}
