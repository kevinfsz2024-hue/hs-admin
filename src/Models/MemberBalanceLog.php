<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberBalanceLog extends Model
{
    public const TYPE_RECHARGE = 'recharge';
    public const TYPE_CONSUME = 'consume';
    public const TYPE_ADJUST = 'adjust';

    protected $fillable = ['user_id', 'type', 'amount', 'before', 'after', 'remark'];

    protected $casts = [
        'amount' => 'decimal:2',
        'before' => 'decimal:2',
        'after' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('admin.member.table_names.balance_logs', 'member_balance_logs'));

        parent::__construct($attributes);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }
}
