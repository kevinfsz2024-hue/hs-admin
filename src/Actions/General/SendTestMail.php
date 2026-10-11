<?php

namespace Dcat\Admin\Actions\General;

use Dcat\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * 使用邮件配置发送一封测试邮件.
 */
class SendTestMail extends Action
{
    protected $title = '测试邮件发送';

    public function handle(Request $request)
    {
        $host = general_config('smtp_host');
        $user = general_config('smtp_user');
        $pass = general_config('smtp_pass');
        $to = general_config('smtp_sender_mail');

        if (! $host || ! $user || ! $pass || ! $to) {
            return $this->response()->error('请先在邮件配置中填写并保存 SMTP 服务器、用户名、密码与发件人邮箱');
        }

        config(['mail.mailers.hs_admin_test' => [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int) general_config('smtp_port', 465),
            'encryption' => general_config('smtp_secure') ?: null,
            'username' => $user,
            'password' => $pass,
            'timeout' => 15,
        ]]);

        try {
            Mail::mailer('hs_admin_test')->raw(
                '这是一封来自 HS Admin 系统配置的 SMTP 测试邮件，收到即说明邮件配置正确。',
                function ($message) use ($to) {
                    $message->to($to)->subject('HS Admin SMTP 测试邮件');
                    $message->from($to, 'HS Admin');
                }
            );
        } catch (\Throwable $e) {
            return $this->response()->error('发送失败：'.$e->getMessage());
        }

        return $this->response()->success("测试邮件已发送至 {$to}，请查收");
    }

    protected function html()
    {
        $this->appendHtmlAttribute('class', 'btn btn-outline-primary');

        return '<a '.$this->formatHtmlAttributes().'><i data-feather="send"></i> '.$this->title().'</a>';
    }
}
