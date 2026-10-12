<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 数据回收站：被删除数据行快照.
 */
class SecurityDataRecycleLog extends Model
{
    protected $fillable = [
        'recycle_rule_id', 'name', 'controller', 'connection', 'data_table',
        'table_primary_key', 'deleted_data', 'admin_id', 'admin_name', 'ip',
    ];

    protected $casts = [
        'deleted_data' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable('security_data_recycle_log');

        $connection = config('admin.database.connection') ?: config('database.default');
        $this->setConnection($connection);

        parent::__construct($attributes);
    }

    public function setUpdatedAt($value)
    {
        // 回收站只有删除时间，不维护更新时间
        return $this;
    }

    public function getUpdatedAtColumn()
    {
        return null;
    }
}
