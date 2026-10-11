<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberGroup extends Model
{
    protected $fillable = ['name', 'description'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('admin.member.table_names.groups', 'member_groups'));

        parent::__construct($attributes);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'group_id');
    }
}
