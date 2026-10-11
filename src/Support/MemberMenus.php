<?php

namespace Dcat\Admin\Support;

use Illuminate\Support\Facades\DB;

/**
 * 会员管理模块默认菜单的写入 / 清理.
 *
 * 供会员模块迁移与 AdminTablesSeeder 复用，保证重装 / 重置数据后菜单仍完整。
 */
class MemberMenus
{
    public static function definition(): array
    {
        return [
            'title' => '会员管理',
            'icon' => 'users',
            'uri' => null,
            'children' => [
                ['title' => '会员管理', 'icon' => 'user', 'uri' => 'member'],
                ['title' => '会员分组管理', 'icon' => 'users', 'uri' => 'member/groups'],
                ['title' => '会员规则管理', 'icon' => 'list', 'uri' => 'member/rules'],
                ['title' => '会员余额管理', 'icon' => 'credit-card', 'uri' => 'member/balance-logs'],
                ['title' => '会员积分管理', 'icon' => 'gift', 'uri' => 'member/points-logs'],
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
            'order' => 2,
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
