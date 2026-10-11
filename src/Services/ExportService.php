<?php

namespace Dcat\Admin\Services;

use Dcat\Admin\Exception\AdminException;
use Dcat\EasyExcel\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 数据导出服务.
 *
 * 导出源在 config/admin.php 的 general.exports.sources 中维护，
 * 每个源 = 模型类 + 导出字段（字段 => 列标题）。
 */
class ExportService
{
    /**
     * 默认导出源：客户项目发布过的 config/admin.php 若未配置
     * general.exports.sources 段，则使用此处默认值（与包内 config 保持一致）.
     */
    protected static function defaultSources(): array
    {
        return [
            '管理员' => [
                'model' => \Dcat\Admin\Models\Administrator::class,
                'columns' => [
                    'id' => 'ID',
                    'username' => '用户名',
                    'name' => '姓名',
                    'created_at' => '创建时间',
                ],
            ],
            '会员' => [
                'requires' => 'member',
                'model' => \Dcat\Admin\Models\Member::class,
                'columns' => [
                    'id' => 'ID',
                    'username' => '用户名',
                    'nickname' => '昵称',
                    'mobile' => '手机号',
                    'balance' => '余额',
                    'points' => '积分',
                    'status' => '状态',
                    'created_at' => '创建时间',
                ],
            ],
        ];
    }

    /**
     * 表单下拉可用的导出源.
     */
    public static function sourceOptions(): array
    {
        return array_keys(static::sources());
    }

    /**
     * 全部导出源（按 requires 过滤已关闭的依赖模块）.
     */
    public static function sources(): array
    {
        $sources = (array) config('admin.general.exports.sources') ?: static::defaultSources();

        return array_filter($sources, function ($source) {
            return ! isset($source['requires']) || config('admin.'.$source['requires'].'.enable', true);
        });
    }

    /**
     * 执行导出，返回 ['path' => 相对 storage/app 的路径, 'rows' => 行数].
     *
     * @throws AdminException
     */
    public static function generate(string $sourceName, string $fileType = 'csv'): array
    {
        $sources = static::sources();

        if (! isset($sources[$sourceName])) {
            throw new AdminException("导出源 [{$sourceName}] 不存在");
        }

        $fileType = in_array($fileType, ['csv', 'xlsx'], true) ? $fileType : 'csv';

        $model = $sources[$sourceName]['model'] ?? null;
        $columns = $sources[$sourceName]['columns'] ?? [];

        if (! $model || ! class_exists($model) || ! is_subclass_of($model, \Illuminate\Database\Eloquent\Model::class)) {
            throw new AdminException("导出源 [{$sourceName}] 配置的模型不存在");
        }

        if (! $columns) {
            throw new AdminException("导出源 [{$sourceName}] 未配置导出字段");
        }

        $file = trim(config('admin.general.exports.dir', 'general-exports'), '/')
            .'/'.date('Ymd-His').'-'.Str::random(6).'.'.$fileType;
        $absolutePath = Storage::path($file);

        try {
            if (! is_dir(dirname($absolutePath))) {
                mkdir(dirname($absolutePath), 0755, true);
            }

            $rows = 0;
            $query = $model::query()->select(array_keys($columns))->orderBy('id');

            // 逐行产出，写入器流式落盘，避免大表撑爆内存
            $generator = (function () use ($query, $columns, &$rows) {
                foreach ($query->cursor() as $record) {
                    $row = [];
                    foreach ($columns as $field => $label) {
                        $row[$label] = $record->{$field};
                    }
                    $rows++;
                    yield $row;
                }
            })();

            Excel::{$fileType}()->data($generator)->store($absolutePath);
        } catch (\Throwable $e) {
            @unlink($absolutePath);

            throw new AdminException('导出失败：'.$e->getMessage());
        }

        return ['path' => $file, 'rows' => $rows];
    }
}
