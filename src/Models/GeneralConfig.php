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
     * 注意：方法名不能与表字段同名（如 value），否则会被表单仓库层误判为关联方法.
     *
     * @param  string  $name
     * @param  mixed  $default
     * @return mixed
     */
    public static function getValue($name, $default = null)
    {
        if (array_key_exists($name, static::$valueCache)) {
            return static::$valueCache[$name];
        }

        $value = static::query()->where('name', $name)->value('value');

        return static::$valueCache[$name] = $value ?? $default;
    }

    /**
     * 批量读取配置值（返回 name => value）.
     */
    public static function values(array $names): array
    {
        return static::query()->whereIn('name', $names)->pluck('value', 'name')->all();
    }

    /**
     * 写入 / 更新配置.
     */
    public static function store($name, $value, $group = '基础'): void
    {
        $config = static::query()->where('name', $name)->first();

        if ($config) {
            $config->value = $value;
            $config->save();

            return;
        }

        static::create(['group' => $group, 'name' => $name, 'value' => $value]);
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
