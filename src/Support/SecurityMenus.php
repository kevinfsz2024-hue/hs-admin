<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Models\Menu;
use Illuminate\Support\Facades\DB;

/**
 * 数据安全管理模块默认菜单的写入 / 清理.
 *
 * 供数据安全管理模块迁移与安装流程复用，保证重装 / 重置数据后菜单仍完整。
 */
class SecurityMenus
{
    public static function definition(): array
    {
        return [
            'title' => '数据安全管理',
            'icon' => 'shield',
            'uri' => null,
            'children' => [
                ['title' => '数据回收站', 'icon' => 'database', 'uri' => 'security/recycle-logs'],
                ['title' => '敏感数据修改记录', 'icon' => 'lock', 'uri' => 'security/sensitive-data-logs'],
                ['title' => '数据回收规则管理', 'icon' => 'list', 'uri' => 'security/data-recycles'],
                ['title' => '敏感字段规则管理', 'icon' => 'settings', 'uri' => 'security/sensitive-data'],
            ],
        ];
    }

    public static function connection($connection = null)
    {
        return DB::connection($connection ?: (config('admin.database.connection') ?: config('database.default')));
    }

    public static function seed($connection = null): void
    {
        $menu = static::connection($connection)->table('admin_menu');
        $definition = static::definition();

        if ($menu->where('title', $definition['title'])->where('uri', $definition['uri'])->exists()) {
            return;
        }

        $parentId = $menu->insertGetId([
            'parent_id' => 0,
            'order' => 4,
            'title' => $definition['title'],
            'icon' => $definition['icon'],
            'uri' => $definition['uri'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = 1;
        foreach ($definition['children'] as $child) {
            $menu->insert([
                'parent_id' => $parentId,
                'order' => $order++,
                'title' => $child['title'],
                'icon' => $child['icon'],
                'uri' => $child['uri'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        (new Menu())->flushCache();
    }

    public static function remove($connection = null): void
    {
        $menu = static::connection($connection)->table('admin_menu');
        $definition = static::definition();

        $parent = $menu->where('title', $definition['title'])->where('uri', $definition['uri'])->first();
        if ($parent) {
            $menu->where('parent_id', $parent->id)->delete();
            $menu->where('id', $parent->id)->delete();
        }
    }
}
