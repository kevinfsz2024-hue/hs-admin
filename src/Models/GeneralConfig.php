<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralConfig extends Model
{
    protected $fillable = ['group', 'name', 'value', 'description'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 请求级配置缓存（写入 / 删除时自动清空）.
     */
    protected static array $valueCache = [];

    public function __construct(array $attributes = [])
    {
        $this->setTable('general_configs');

        parent::__construct($attributes);
    }

    /**
     * 读取系统配置值.
     *
     * @param  string  $name
     * @param  mixed  $default
     * @return mixed
     */
    public static function value($name, $default = null)
    {
        if (array_key_exists($name, static::$valueCache)) {
            return static::$valueCache[$name];
        }

        $value = static::query()->where('name', $name)->value('value');

        return static::$valueCache[$name] = $value ?? $default;
    }

    /**
     * 所有配置分组.
     */
    public static function groups(): array
    {
        return static::query()->distinct()->orderBy('group')->pluck('group', 'group')->all();
    }

    protected static function booted()
    {
        static::saved(function () {
            static::$valueCache = [];
        });

        static::deleted(function () {
            static::$valueCache = [];
        });
    }
}
