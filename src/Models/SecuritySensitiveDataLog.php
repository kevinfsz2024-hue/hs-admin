<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 敏感数据修改记录.
 */
class SecuritySensitiveDataLog extends Model
{
    protected $fillable = [
        'sensitive_rule_id', 'name', 'controller', 'connection', 'data_table',
        'data_id', 'field', 'field_label', 'original', 'changes',
        'admin_id', 'admin_name', 'ip',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable('security_sensitive_data_log');

        $connection = config('admin.database.connection') ?: config('database.default');
        $this->setConnection($connection);

        parent::__construct($attributes);
    }

    public function setUpdatedAt($value)
    {
        // 修改记录只有修改时间，不维护更新时间
        return $this;
    }

    public function getUpdatedAtColumn()
    {
        return null;
    }
}
