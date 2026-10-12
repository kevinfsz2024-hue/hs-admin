<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 数据回收规则.
 */
class SecurityDataRecycle extends Model
{
    protected $fillable = ['name', 'controller', 'connection', 'data_table', 'table_primary_key', 'status'];

    protected $casts = [
        'status' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable('security_data_recycle');

        $connection = config('admin.database.connection') ?: config('database.default');
        $this->setConnection($connection);

        parent::__construct($attributes);
    }
}
