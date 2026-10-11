<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class MemberRule extends Model
{
    public const TYPE_REGISTER = 'register';
    public const TYPE_LOGIN = 'login';
    public const TYPE_CONSUME = 'consume';
    public const TYPE_CUSTOM = 'custom';

    public const FREQUENCY_ONCE = 'once';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_EACH = 'each';

    protected $fillable = ['name', 'title', 'type', 'score', 'frequency', 'status', 'remark'];

    protected $casts = [
        'score' => 'integer',
        'status' => 'integer',
            'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('admin.member.table_names.rules', 'member_rules'));

        parent::__construct($attributes);
    }
}
