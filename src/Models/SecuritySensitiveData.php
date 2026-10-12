<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 敏感字段规则.
 */
class SecuritySensitiveData extends Model
{
    protected $fillable = ['name', 'controller', 'connection', 'data_table', 'table_primary_key', 'sensitive_fields', 'status'];

    protected $casts = [
        'sensitive_fields' => 'array',
        'status' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable('security_sensitive_data');

        $connection = config('admin.database.connection') ?: config('database.default');
        $this->setConnection($connection);

        parent::__construct($attributes);
    }
}
