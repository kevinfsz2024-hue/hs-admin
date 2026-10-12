<?php

use Dcat\Admin\Support\SecurityMenus;
use Dcat\Admin\Support\SecurityRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSecurityTables extends Migration
{
    public function getConnection()
    {
        return config('admin.database.connection') ?: config('database.default');
    }

    public function up()
    {
        // 数据回收规则：控制器发起的删除被回收进数据回收站
        Schema::create('security_data_recycle', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('规则名称');
            $table->string('controller', 200)->comment('控制器');
            $table->string('connection', 50)->nullable()->comment('连接标识，空为默认连接');
            $table->string('data_table', 150)->comment('数据表');
            $table->string('table_primary_key', 50)->default('id')->comment('数据表主键');
            $table->tinyInteger('status')->default(1)->comment('状态:1删除监控中 0已停用');
            $table->timestamps();
            $table->index('controller');
            $table->index('data_table');
        });

        // 敏感字段规则：控制器发起的更新涉及敏感字段时记录修改前后值
        Schema::create('security_sensitive_data', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('规则名称');
            $table->string('controller', 200)->comment('控制器');
            $table->string('connection', 50)->nullable()->comment('连接标识，空为默认连接');
            $table->string('data_table', 150)->comment('数据表');
            $table->string('table_primary_key', 50)->default('id')->comment('数据表主键');
            $table->text('sensitive_fields')->nullable()->comment('敏感字段(json数组)');
            $table->tinyInteger('status')->default(1)->comment('状态:1修改监控中 0已停用');
            $table->timestamps();
            $table->index('controller');
            $table->index('data_table');
        });

        // 数据回收站：被删除的数据行快照
        Schema::create('security_data_recycle_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recycle_rule_id')->nullable()->comment('回收规则ID');
            $table->string('name', 100)->comment('回收规则名称');
            $table->string('controller', 200)->comment('控制器');
            $table->string('connection', 50)->nullable()->comment('连接标识，空为默认连接');
            $table->string('data_table', 150)->comment('数据表');
            $table->string('table_primary_key', 50)->default('id')->comment('数据表主键');
            $table->longText('deleted_data')->comment('被删数据(json)');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('操作管理员ID');
            $table->string('admin_name', 100)->nullable()->comment('操作管理员');
            $table->string('ip', 50)->nullable()->comment('IP');
            $table->timestamp('created_at')->nullable()->comment('删除时间');
            $table->index(['data_table', 'created_at']);
        });

        // 敏感数据修改记录：敏感字段修改前后的值
        Schema::create('security_sensitive_data_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sensitive_rule_id')->nullable()->comment('敏感字段规则ID');
            $table->string('name', 100)->comment('规则名称');
            $table->string('controller', 200)->comment('控制器');
            $table->string('connection', 50)->nullable()->comment('连接标识，空为默认连接');
            $table->string('data_table', 150)->comment('数据表');
            $table->string('data_id', 50)->comment('修改行主键值');
            $table->string('field', 100)->comment('修改项字段名');
            $table->string('field_label', 100)->nullable()->comment('修改项展示标签');
            $table->longText('original')->nullable()->comment('修改前');
            $table->longText('changes')->nullable()->comment('修改后');
            $table->unsignedBigInteger('admin_id')->nullable()->comment('操作管理员ID');
            $table->string('admin_name', 100)->nullable()->comment('操作管理员');
            $table->string('ip', 50)->nullable()->comment('IP');
            $table->timestamp('created_at')->nullable()->comment('修改时间');
            $table->index(['data_table', 'created_at']);
        });

        SecurityMenus::seed($this->getConnection());

        // 预置核心表的回收规则与敏感字段规则（管理员 / 角色 / 权限 / 菜单 / 会员 / 系统配置等）
        SecurityRules::seed($this->getConnection());
    }

    public function down()
    {
        Schema::dropIfExists('security_sensitive_data_log');
        Schema::dropIfExists('security_data_recycle_log');
        Schema::dropIfExists('security_sensitive_data');
        Schema::dropIfExists('security_data_recycle');

        SecurityMenus::remove($this->getConnection());
    }
}
