<?php

namespace Dcat\Admin\Widgets\General;

use Dcat\Admin\Models\GeneralConfig;
use Dcat\Admin\Widgets\Form;

/**
 * 基础配置表单.
 */
class BasicConfigForm extends Form
{
    public function form()
    {
        $this->text('site_name', '站点名称')
            ->required()
            ->help('保存后覆盖后台左上角品牌与浏览器标题')
            ->default(general_config('site_name'));

        $this->text('admin_entry', '自定义后台入口')
            ->help('仅允许字母、数字、-、_、/；修改后请使用新地址访问后台（如 /admin 改为 /manage）')
            ->default(general_config('admin_entry') ?: config('admin.route.prefix'));

        $this->text('icp', '备案号')
            ->help('展示在后台页脚与登录页底部，可为空')
            ->default(general_config('icp'));

        $this->text('version', '版本号')
            ->help('展示在后台页脚，留空时显示包版本')
            ->default(general_config('version') ?: 'v'.Dcat\Admin\Admin::VERSION);

        $this->text('service_phone', '客服电话')
            ->help('展示在后台页脚与登录页底部，可为空')
            ->default(general_config('service_phone'));

        $this->select('timezone', '时区')
            ->options(array_combine([
                'Asia/Shanghai',
                'Asia/Hong_Kong',
                'Asia/Singapore',
                'Asia/Tokyo',
                'UTC',
                'Europe/London',
                'Europe/Berlin',
                'America/New_York',
                'America/Los_Angeles',
                'Australia/Sydney',
            ], [
                'Asia/Shanghai',
                'Asia/Hong_Kong',
                'Asia/Singapore',
                'Asia/Tokyo',
                'UTC',
                'Europe/London',
                'Europe/Berlin',
                'America/New_York',
                'America/Los_Angeles',
                'Australia/Sydney',
            ]))
            ->default(general_config('timezone') ?: config('app.timezone'));

        $this->textarea('deny_ip', '禁止访问IP')
            ->rows(3)
            ->help('每行一个，支持 * 通配符（如 192.168.*.*），# 开头为注释，留空不限制')
            ->default(general_config('deny_ip'));
    }

    public function handle(array $input)
    {
        $siteName = trim($input['site_name'] ?? '');
        if ($siteName === '') {
            return $this->response()->error('站点名称不能为空');
        }

        $entry = trim((string) ($input['admin_entry'] ?? ''), "/ \t");
        if ($entry !== '' && ! preg_match('/^[a-zA-Z0-9\-_\/]{2,64}$/', $entry)) {
            return $this->response()->error('后台入口格式不正确：仅允许字母、数字、-、_、/，长度 2~64');
        }

        $phone = trim((string) ($input['service_phone'] ?? ''));
        if ($phone !== '' && ! preg_match('/^[0-9\+\-\s()]{5,30}$/', $phone)) {
            return $this->response()->error('客服电话格式不正确：仅允许数字、+、-、空格和括号，长度 5~30');
        }

        GeneralConfig::store('site_name', $siteName);
        GeneralConfig::store('admin_entry', $entry !== '' ? '/'.$entry : null);
        GeneralConfig::store('icp', trim($input['icp'] ?? '') ?: null);
        GeneralConfig::store('version', trim($input['version'] ?? '') ?: null);
        GeneralConfig::store('service_phone', $phone ?: null);

        GeneralConfig::store('timezone', $input['timezone'] ?? null);
        GeneralConfig::store('deny_ip', trim($input['deny_ip'] ?? '') ?: null);

        return $this->response()->success('保存成功。如修改了后台入口，请改用新地址访问后台');
    }
}
