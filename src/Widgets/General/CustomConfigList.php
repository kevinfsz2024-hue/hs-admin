<?php

namespace Dcat\Admin\Widgets\General;

use Dcat\Admin\Actions\General\EditConfigItem;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Models\GeneralConfig;

/**
 * 自定义配置项列表（系统配置-添加配置项 页签）.
 */
class CustomConfigList extends LazyRenderable
{
    public function grid(): Grid
    {
        return Grid::make(new GeneralConfig(), function (Grid $grid) {
            $grid->setResource('general/configs');

            $grid->column('id', 'ID')->sortable();
            $grid->column('group', '分组')->label();
            $grid->column('name', '配置键');
            $grid->column('value', '配置值')->limit(50);
            $grid->column('description', '说明')->limit(30);
            $grid->column('updated_at', '更新时间')->sortable();

            // 底部快速添加行（Lazy 渲染下 request()->url() 是 render 端点，需显式指定提交地址）
            $grid->quickCreate(function (Grid\Tools\QuickCreate $make) {
                $make->action('general/configs');
                $make->text('group', '分组');
                $make->text('name', '配置键');
                $make->text('value', '配置值');
                $make->text('description', '说明');
            });

            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableQuickEditButton();
            $grid->disableViewButton();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->prepend(new EditConfigItem());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('group', '分组')->select(GeneralConfig::groups());
                $filter->like('name', '配置键');
            });

            $grid->quickSearch('name', 'description');
        });
    }
}
