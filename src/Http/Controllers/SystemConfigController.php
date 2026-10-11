<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Actions\General\SendTestMail;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Models\GeneralConfig;
use Dcat\Admin\Show;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\General\BasicConfigForm;
use Dcat\Admin\Widgets\General\CustomConfigList;
use Dcat\Admin\Widgets\General\MailConfigForm;
use Dcat\Admin\Widgets\Lazy;
use Dcat\Admin\Widgets\Tab;

/**
 * 系统配置.
 */
class SystemConfigController extends AdminController
{
    protected $title = '系统配置';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->body($this->buildTabs());
    }

    protected function buildTabs()
    {
        $tab = Tab::make();

        $tab->add('基础配置', BasicConfigForm::make(), true);
        $tab->add('邮件配置', $this->mailTab());
        $tab->add('快捷配置入口', $this->quickEntries());
        $tab->add('添加配置项', Lazy::make(CustomConfigList::make()));

        return $tab;
    }

    protected function mailTab()
    {
        return new \Illuminate\Support\HtmlString(
            MailConfigForm::make()->render().(new SendTestMail())->render()
        );
    }

    protected function quickEntries()
    {
        $links = [
            ['菜单管理', 'auth/menu', 'menu'],
            ['权限管理', 'auth/permissions', 'lock'],
            ['管理员', 'auth/users', 'user'],
            ['扩展', 'auth/extensions', 'box'],
            ['附件管理', 'general/attachments', 'folder'],
            ['数据导出管理', 'general/exports', 'download'],
        ];

        $items = '';
        foreach ($links as [$title, $uri, $icon]) {
            $url = admin_url($uri);
            $items .= "<a href=\"{$url}\" class=\"btn btn-outline-primary mr-1 mb-1\"><i data-feather=\"{$icon}\"></i> {$title}</a>";
        }

        return Card::make('快捷配置入口', new \Illuminate\Support\HtmlString($items));
    }

    protected function detail($id)
    {
        return Show::make($id, new GeneralConfig(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('group', '分组');
            $show->field('name', '配置键');
            $show->field('value', '配置值');
            $show->field('description', '说明');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    protected function form()
    {
        // 供「添加配置项」页签的快速添加行（POST general/configs）使用
        return Form::make(new GeneralConfig(), function (Form $form) {
            $form->text('group', '分组')->default('自定义');
            $form->text('name', '配置键')
                ->required()
                ->creationRules('required|string|max:100|unique:general_configs,name')
                ->updateRules('required|string|max:100|unique:general_configs,name,{{id}}');
            $form->textarea('value', '配置值')->rows(3);
            $form->text('description', '说明');
        });
    }
}
