<?php

use Dcat\Admin\Support\GeneralMenus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralTables extends Migration
{
    public function getConnection()
    {
        return config('admin.database.connection') ?: config('database.default');
    }

    public function up()
    {
        Schema::create('general_configs', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('基础')->comment('配置分组');
            $table->string('name', 100)->unique()->comment('配置键');
            $table->longText('value')->nullable()->comment('配置值');
            $table->string('description', 255)->nullable()->comment('说明');
            $table->timestamps();
        });

        Schema::create('general_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->comment('文件名');
            $table->string('path', 500)->comment('存储路径');
            $table->string('disk', 50)->default('public')->comment('存储磁盘');
            $table->unsignedBigInteger('size')->default(0)->comment('大小(字节)');
            $table->string('mime_type', 150)->nullable()->comment('MIME 类型');
            $table->unsignedBigInteger('created_by')->nullable()->comment('上传人');
            $table->timestamps();
        });

        Schema::create('general_exports', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('导出名称');
            $table->string('source', 50)->comment('导出源');
            $table->string('file_type', 10)->default('csv')->comment('文件类型:csv/xlsx');
            $table->tinyInteger('status')->default(0)->comment('状态:0处理中 1已完成 2失败');
            $table->string('file_path', 500)->nullable()->comment('文件路径');
            $table->unsignedBigInteger('rows')->default(0)->comment('数据行数');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->timestamps();
        });

        GeneralMenus::seed($this->getConnection());
    }

    public function down()
    {
        Schema::dropIfExists('general_exports');
        Schema::dropIfExists('general_attachments');
        Schema::dropIfExists('general_configs');

        GeneralMenus::remove($this->getConnection());
    }
}
