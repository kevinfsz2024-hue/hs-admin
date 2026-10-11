<?php

namespace Dcat\Admin\Widgets\General;

use Dcat\Admin\Models\GeneralConfig;
use Dcat\Admin\Widgets\Form;

/**
 * 邮件配置表单（SMTP）.
 */
class MailConfigForm extends Form
{
    public function form()
    {
        $this->text('smtp_host', 'SMTP 服务器')
            ->help('如 smtp.qq.com、smtp.163.com')
            ->default(general_config('smtp_host'));
        $this->text('smtp_port', 'SMTP 端口')
            ->default(general_config('smtp_port') ?: '465');
        $this->text('smtp_user', 'SMTP 用户名')
            ->default(general_config('smtp_user'));
        $this->password('smtp_pass', 'SMTP 密码')
            ->help('为安全起见不回显已保存的密码；留空则保持不变');
        $this->select('smtp_secure', 'SMTP 验证方式')
            ->options(['ssl' => 'SSL', 'tls' => 'TLS', '' => '无'])
            ->default(general_config('smtp_secure') ?: 'ssl');
        $this->email('smtp_sender_mail', 'SMTP 发件人邮箱')
            ->help('同时作为「测试邮件发送」的收件邮箱')
            ->default(general_config('smtp_sender_mail'));
    }

    public function handle(array $input)
    {
        GeneralConfig::store('smtp_host', trim($input['smtp_host'] ?? '') ?: null, '邮件');
        GeneralConfig::store('smtp_port', (string) (int) ($input['smtp_port'] ?? '') ?: null, '邮件');
        GeneralConfig::store('smtp_user', trim($input['smtp_user'] ?? '') ?: null, '邮件');
        GeneralConfig::store('smtp_secure', $input['smtp_secure'] ?? '', '邮件');
        GeneralConfig::store('smtp_sender_mail', trim($input['smtp_sender_mail'] ?? '') ?: null, '邮件');

        // 密码不回显，仅在填写时更新
        if (($pass = $input['smtp_pass'] ?? '') !== '') {
            GeneralConfig::store('smtp_pass', $pass, '邮件');
        }

        return $this->response()->success('保存成功');
    }
}
