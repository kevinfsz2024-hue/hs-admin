<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Models\Menu;
use Illuminate\Support\Facades\DB;

/**
 * 常规管理模块默认菜单的写入 / 清理.
 *
 * 供常规管理模块迁移与 AdminTablesSeeder 复用，保证重装 / 重置数据后菜单仍完整。
 */
class GeneralMenus
{
    public static function definition(): array
    {
        return [
            'title' => '常规管理',
            'icon' => 'settings',
            'uri' => null,
            'children' => [
                ['title' => '系统配置', 'icon' => 'settings', 'uri' => 'general/configs'],
                ['title' => '附件管理', 'icon' => 'folder', 'uri' => 'general/attachments'],
                ['title' => '个人资料', 'icon' => 'user', 'uri' => 'auth/setting'],
                ['title' => '数据导出管理', 'icon' => 'download', 'uri' => 'general/exports'],
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
            'order' => 3,
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
