<?php

namespace Dcat\Admin\Models;

use Dcat\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GeneralAttachment extends Model
{
    protected $fillable = ['name', 'path', 'disk', 'size', 'mime_type', 'created_by'];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable('general_attachments');

        parent::__construct($attributes);
    }

    protected static function booted()
    {
        // dcat 文件字段默认按原始文件名落盘，元信息从存储层自动补全
        static::saving(function (GeneralAttachment $attachment) {
            $attachment->disk = $attachment->disk ?: 'public';

            if ($attachment->path && ! $attachment->name) {
                $attachment->name = pathinfo($attachment->path, PATHINFO_BASENAME);
            }

            if ($attachment->path && $attachment->isDirty('path')) {
                $disk = Storage::disk($attachment->disk);

                if ($disk->exists($attachment->path)) {
                    $attachment->size = $disk->size($attachment->path);
                    $attachment->mime_type = $disk->mimeType($attachment->path);
                }
            }

            if (! $attachment->created_by) {
                $attachment->created_by = Admin::user()?->id;
            }
        });

        // 删除记录时同步清理物理文件
        static::deleted(function (GeneralAttachment $attachment) {
            if ($attachment->path) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function ext(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function isImage(): bool
    {
        return in_array($this->ext(), ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp']);
    }
}
