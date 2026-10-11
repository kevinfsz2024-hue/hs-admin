<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'username', 'nickname', 'avatar', 'mobile', 'email', 'password',
        'group_id', 'status', 'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'status' => 'integer',
        'balance' => 'decimal:2',
        'points' => 'integer',
        'last_login_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('admin.member.table_names.users', 'member_users'));

        parent::__construct($attributes);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MemberGroup::class, 'group_id');
    }

    public function balanceLogs(): HasMany
    {
        return $this->hasMany(MemberBalanceLog::class, 'user_id');
    }

    public function pointsLogs(): HasMany
    {
        return $this->hasMany(MemberPointsLog::class, 'user_id');
    }
}
