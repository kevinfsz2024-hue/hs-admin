<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMemberTables extends Migration
{
    /**
     * 随迁移写入的默认菜单（会员管理分组）.
     *
     * @var array
     */
    protected $menus = [
        'title' => '会员管理',
        'icon' => 'users',
        'uri' => null,
        'children' => [
            ['title' => '会员管理', 'icon' => 'user', 'uri' => 'member'],
            ['title' => '会员分组管理', 'icon' => 'users', 'uri' => 'member/groups'],
            ['title' => '会员规则管理', 'icon' => 'list', 'uri' => 'member/rules'],
            ['title' => '会员余额管理', 'icon' => 'credit-card', 'uri' => 'member/balance-logs'],
            ['title' => '会员积分管理', 'icon' => 'gift', 'uri' => 'member/points-logs'],
        ],
    ];

    public function getConnection()
    {
        return config('admin.database.connection') ?: config('database.default');
    }

    public function up()
    {
        Schema::create('member_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('分组名称');
            $table->string('description', 255)->nullable()->comment('描述');
            $table->timestamps();
        });

        Schema::create('member_users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique()->comment('用户名');
            $table->string('nickname', 50)->nullable()->comment('昵称');
            $table->string('avatar', 255)->nullable()->comment('头像');
            $table->string('mobile', 20)->nullable()->comment('手机号');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('password', 255)->comment('密码');
            $table->unsignedBigInteger('group_id')->nullable()->index()->comment('分组');
            $table->decimal('balance', 12, 2)->default(0)->comment('余额');
            $table->integer('points')->default(0)->comment('积分');
            $table->tinyInteger('status')->default(1)->comment('状态:1正常 0禁用');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->timestamps();
        });

        Schema::create('member_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('规则标识');
            $table->string('title', 50)->comment('规则名称');
            $table->string('type', 20)->default('custom')->comment('行为类型:register/login/consume/custom');
            $table->integer('score')->default(0)->comment('变动积分');
            $table->string('frequency', 10)->default('each')->comment('频次:once仅一次/daily每天一次/each每次');
            $table->tinyInteger('status')->default(1)->comment('状态:1启用 0停用');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
        });

        Schema::create('member_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('会员');
            $table->string('type', 20)->default('adjust')->comment('类型:recharge/consume/adjust');
            $table->decimal('amount', 12, 2)->comment('变动金额');
            $table->decimal('before', 12, 2)->default(0)->comment('变动前余额');
            $table->decimal('after', 12, 2)->default(0)->comment('变动后余额');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
        });

        Schema::create('member_points_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('会员');
            $table->string('type', 20)->default('adjust')->comment('类型:rule/adjust');
            $table->integer('score')->comment('变动积分');
            $table->integer('before')->default(0)->comment('变动前积分');
            $table->integer('after')->default(0)->comment('变动后积分');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamps();
        });

        $this->insertMenus();
    }

    public function down()
    {
        Schema::dropIfExists('member_points_logs');
        Schema::dropIfExists('member_balance_logs');
        Schema::dropIfExists('member_rules');
        Schema::dropIfExists('member_users');
        Schema::dropIfExists('member_groups');

        $menu = $this->menus;
        $parent = DB::connection($this->getConnection())
            ->table('admin_menu')
            ->where('title', $menu['title'])
            ->where('uri', $menu['uri'])
            ->first();
        if ($parent) {
            DB::connection($this->getConnection())
                ->table('admin_menu')
                ->where('parent_id', $parent->id)
                ->delete();
            DB::connection($this->getConnection())
                ->table('admin_menu')
                ->where('id', $parent->id)
                ->delete();
        }
    }

    protected function insertMenus()
    {
        $connection = DB::connection($this->getConnection());
        $menuTable = $connection->table('admin_menu');

        if ($menuTable->where('title', $this->menus['title'])->where('uri', $this->menus['uri'])->exists()) {
            return;
        }

        $parentId = $menuTable->insertGetId([
            'parent_id' => 0,
            'order' => 2,
            'title' => $this->menus['title'],
            'icon' => $this->menus['icon'],
            'uri' => $this->menus['uri'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = 1;
        foreach ($this->menus['children'] as $child) {
            $menuTable->insert([
                'parent_id' => $parentId,
                'order' => $order++,
                'title' => $child['title'],
                'icon' => $child['icon'],
                'uri' => $child['uri'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
