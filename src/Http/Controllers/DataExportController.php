<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\GeneralExport;
use Dcat\Admin\Services\ExportService;
use Dcat\Admin\Show;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * 数据导出管理.
 */
class DataExportController extends AdminController
{
    protected $title = '数据导出管理';

    protected function grid()
    {
        return Grid::make(new GeneralExport(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '导出名称');
            $grid->column('source', '导出源');
            $grid->column('file_type', '格式')->label();
            $grid->column('rows', '数据行数');
            $grid->column('status', '状态')->using(GeneralExport::$statusMap)->label([
                GeneralExport::STATUS_DONE => 'success',
                GeneralExport::STATUS_FAILED => 'danger',
                GeneralExport::STATUS_PENDING => 'primary',
            ]);
            $grid->column('download', '文件')->display(function ($value, $column, GeneralExport $export) {
                if (! $export->isDone()) {
                    return '—';
                }

                return '<a class="btn btn-xs btn-outline-primary" href="'
                    .admin_url('general/exports/'.$export->id.'/download').'"><i data-feather="download"></i> 下载</a>';
            });
            $grid->column('created_at', '创建时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '导出名称');
                $filter->equal('status', '状态')->select(GeneralExport::$statusMap);
            });

            $grid->quickSearch('name', 'source');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new GeneralExport(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '导出名称');
            $show->field('source', '导出源');
            $show->field('file_type', '格式');
            $show->field('rows', '数据行数');
            $show->field('status', '状态')->using(GeneralExport::$statusMap);
            $show->field('created_at', '创建时间');
        });
    }

    protected function form()
    {
        return Form::make(new GeneralExport(), function (Form $form) {
            $form->select('source', '导出数据')
                ->options(ExportService::sourceOptions())
                ->required()
                ->help('可用的导出源在 config/admin.php 的 general.exports.sources 中维护');
            $form->select('file_type', '文件格式')
                ->options(['csv' => 'CSV 文件', 'xlsx' => 'Excel 文件 (xlsx)'])
                ->default('csv')
                ->required();

            $form->hidden('name');
            $form->hidden('status');
            $form->hidden('file_path');
            $form->hidden('rows');
            $form->hidden('created_by');

            // 导出在保存请求内同步完成，直接写入任务结果字段
            $form->saving(function (Form $form) {
                $result = ExportService::generate($form->input('source'), $form->input('file_type') ?: 'csv');

                $form->input('name', $form->input('source').'-'.date('YmdHis'));
                $form->input('status', GeneralExport::STATUS_DONE);
                $form->input('file_path', $result['path']);
                $form->input('rows', $result['rows']);
                $form->input('created_by', Admin::user()?->id);
            });
        });
    }

    /**
     * 下载导出文件.
     */
    public function download($id): BinaryFileResponse
    {
        /* @var GeneralExport $export */
        $export = GeneralExport::query()->findOrFail($id);

        abort_unless($export->isDone(), 404, '导出文件不存在或未生成完成');

        return response()->download(Storage::path($export->file_path), $export->downloadName());
    }
}
