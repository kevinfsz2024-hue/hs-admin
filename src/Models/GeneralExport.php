<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GeneralExport extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_DONE = 1;
    const STATUS_FAILED = 2;

    public static $statusMap = [
        self::STATUS_PENDING => '处理中',
        self::STATUS_DONE => '已完成',
        self::STATUS_FAILED => '失败',
    ];

    protected $fillable = ['name', 'source', 'file_type', 'status', 'file_path', 'rows', 'created_by'];

    protected $casts = [
        'status' => 'integer',
        'rows' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable('general_exports');

        parent::__construct($attributes);
    }

    protected static function booted()
    {
        // 删除任务时同步清理导出文件
        static::deleted(function (GeneralExport $export) {
            if ($export->file_path) {
                Storage::delete($export->file_path);
            }
        });
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE && $this->file_path && is_file(Storage::path($this->file_path));
    }

    public function downloadName(): string
    {
        return $this->name.'.'.pathinfo((string) $this->file_path, PATHINFO_EXTENSION);
    }
}
