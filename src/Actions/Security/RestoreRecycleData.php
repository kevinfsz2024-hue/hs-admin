<?php

namespace Dcat\Admin\Actions\Security;

use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Services\DataSecurityService;
use Illuminate\Http\Request;

/**
 * 数据回收站-还原单行数据.
 */
class RestoreRecycleData extends RowAction
{
    protected $title = '还原';

    public function render()
    {
        // 稳定的行为标识，便于前端定位与测试
        $this->setHtmlAttribute('data-action', 'restore-recycle');

        return parent::render();
    }

    public function handle(Request $request)
    {
        try {
            [$success, $message] = DataSecurityService::restoreRecycleLog($this->getKey());
        } catch (\Throwable $e) {
            return $this->response()->error('还原失败：'.$e->getMessage());
        }

        return $success
            ? $this->response()->success($message)->refresh()
            : $this->response()->error($message);
    }

    public function confirm()
    {
        return '确认将该条数据还原到原数据表？若原表已存在相同主键的记录将无法还原';
    }
}
