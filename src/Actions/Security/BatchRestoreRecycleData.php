<?php

namespace Dcat\Admin\Actions\Security;

use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Services\DataSecurityService;
use Illuminate\Http\Request;

/**
 * 数据回收站-批量还原.
 */
class BatchRestoreRecycleData extends BatchAction
{
    protected $title = '批量还原';

    public function handle(Request $request)
    {
        $ids = collect(explode(',', (string) $this->getKey()))->filter()->map('intval')->unique()->values();

        if ($ids->isEmpty()) {
            return $this->response()->error('请先选择要还原的数据');
        }

        $restored = 0;
        $failed = [];

        foreach ($ids as $id) {
            try {
                [$success, $message] = DataSecurityService::restoreRecycleLog($id);

                if ($success) {
                    $restored++;
                } else {
                    $failed[] = "ID {$id}：{$message}";
                }
            } catch (\Throwable $e) {
                $failed[] = 'ID '.$id.'：'.$e->getMessage();
            }
        }

        $total = $ids->count();

        if ($failed && ! $restored) {
            return $this->response()->error("还原失败，0/{$total}：\n".implode("\n", array_slice($failed, 0, 5)));
        }

        $message = "成功还原 {$restored}/{$total} 条".($failed ? "，失败 ".count($failed)." 条：\n".implode("\n", array_slice($failed, 0, 5)) : '');

        return $this->response()->success($message)->refresh();
    }

    public function confirm()
    {
        return '确认将选中的数据还原到原数据表？';
    }
}
