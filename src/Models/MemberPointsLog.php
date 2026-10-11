<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberPointsLog extends Model
{
    public const TYPE_RULE = 'rule';
    public const TYPE_ADJUST = 'adjust';

    protected $fillable = ['user_id', 'type', 'score', 'before', 'after', 'remark'];

    protected $casts = [
        'score' => 'integer',
        'before' => 'integer',
        'after' => 'integer',
            'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('admin.member.table_names.points_logs', 'member_points_logs'));

        parent::__construct($attributes);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }
}
