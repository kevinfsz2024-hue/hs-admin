<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\GeneralAttachment;
use Dcat\Admin\Show;

/**
 * 附件管理.
 */
class AttachmentController extends AdminController
{
    protected $title = '附件管理';

    protected function grid()
    {
        return Grid::make(new GeneralAttachment(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '文件')->display(function ($name, $column, GeneralAttachment $attachment) {
                $url = $attachment->url();

                $preview = $attachment->isImage()
                    ? '<img src="'.$url.'" style="max-width:36px;max-height:36px;border-radius:4px;vertical-align:middle;margin-right:6px">'
                    : '<i data-feather="file" style="width:16px;vertical-align:middle;margin-right:6px"></i>';

                return $preview.'<a href="'.$url.'" target="_blank">'.$name.'</a>';
            });
            $grid->column('size', '大小')->display(function ($size) {
                return format_byte($size);
            });
            $grid->column('mime_type', '类型');
            $grid->column('created_by', '上传人')->display(function ($id) {
                if (! $id) {
                    return '—';
                }

                $name = Admin::user() && Admin::user()->id == $id
                    ? Admin::user()->name
                    : (\Dcat\Admin\Models\Administrator::query()->find($id)->name ?? 'ID:'.$id);

                return $name;
            });
            $grid->column('created_at', '上传时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '文件名');
            });

            $grid->quickSearch('name');
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new GeneralAttachment(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '文件名');
            $show->field('path', '存储路径');
            $show->field('disk', '磁盘');
            $show->field('size', '大小')->as(function ($size) {
                return format_byte($size);
            });
            $show->field('mime_type', '类型');
            $show->field('created_by', '上传人');
            $show->field('created_at', '上传时间');
        });
    }

    protected function form()
    {
        return Form::make(new GeneralAttachment(), function (Form $form) {
            $form->display('id', 'ID');

            $form->file('path', '选择文件')
                ->disk('public')
                ->move('attachments')
                ->required();

            $form->text('name', '文件名')->help('留空则自动使用上传的原始文件名');
        });
    }
}
