<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Models\SecurityDataRecycle;
use Dcat\Admin\Models\SecuritySensitiveData;
use Illuminate\Support\Facades\Schema;

/**
 * 数据安全管理模块核心规则的写入.
 *
 * 安装 / 重置数据时预置核心表的回收规则与敏感字段规则（管理员、角色、权限、菜单、会员等），
 * 保证系统装好即具备基础的数据安全审计能力。与 SecurityMenus 一样供迁移与 AdminTablesSeeder 复用，
 * 幂等可重复调用（按 名称 + 控制器 + 数据表 判重）。
 */
class SecurityRules
{
    /**
     * 数据回收规则（删除监控）预置定义.
     *
     * controller 为规则表单存储的相对路径；table 为配置键或字面表名.
     */
    public static function recycleDefinition(): array
    {
        $admin = (array) config('admin.database');
        $member = (array) config('admin.member.table_names');

        return [
            // 系统核心
            ['name' => '管理员', 'controller' => 'UserController', 'data_table' => $admin['users_table'] ?? 'admin_users'],
            ['name' => '角色', 'controller' => 'RoleController', 'data_table' => $admin['roles_table'] ?? 'admin_roles'],
            ['name' => '权限', 'controller' => 'PermissionController', 'data_table' => $admin['permissions_table'] ?? 'admin_permissions'],
            ['name' => '菜单', 'controller' => 'MenuController', 'data_table' => $admin['menu_table'] ?? 'admin_menu'],

            // 会员模块
            ['name' => '会员', 'controller' => 'MemberController', 'data_table' => $member['users'] ?? 'member_users'],
            ['name' => '会员分组', 'controller' => 'MemberGroupController', 'data_table' => $member['groups'] ?? 'member_groups'],
            ['name' => '会员规则', 'controller' => 'MemberRuleController', 'data_table' => $member['rules'] ?? 'member_rules'],

            // 常规管理模块
            ['name' => '系统配置', 'controller' => 'SystemConfigController', 'data_table' => 'general_configs'],
            ['name' => '附件管理', 'controller' => 'AttachmentController', 'data_table' => 'general_attachments'],
            ['name' => '数据导出', 'controller' => 'DataExportController', 'data_table' => 'general_exports'],

            // 数据安全模块自监控（规则本身被删可从回收站还原）
            ['name' => '数据回收规则', 'controller' => 'SecurityDataRecycleController', 'data_table' => 'security_data_recycle'],
            ['name' => '敏感字段规则', 'controller' => 'SecuritySensitiveDataController', 'data_table' => 'security_sensitive_data'],
        ];
    }

    /**
     * 敏感字段规则（修改监控）预置定义.
     */
    public static function sensitiveDefinition(): array
    {
        $admin = (array) config('admin.database');
        $member = (array) config('admin.member.table_names');

        return [
            [
                'name' => '管理员数据',
                'controller' => 'UserController',
                'data_table' => $admin['users_table'] ?? 'admin_users',
                'sensitive_fields' => ['username', 'password'],
            ],
            [
                'name' => '会员数据',
                'controller' => 'MemberController',
                'data_table' => $member['users'] ?? 'member_users',
                'sensitive_fields' => ['username', 'mobile', 'password', 'status', 'email'],
            ],
        ];
    }

    /**
     * 写入预置规则（幂等；目标表不存在时跳过对应规则）.
     */
    public static function seed($connection = null): void
    {
        $connection = $connection
            ?: (config('admin.database.connection') ?: config('database.default'));

        foreach (static::recycleDefinition() as $rule) {
            if (! static::tableExists($rule['data_table'], $connection)) {
                continue;
            }

            SecurityDataRecycle::firstOrCreate(
                [
                    'name' => $rule['name'],
                    'controller' => $rule['controller'],
                    'data_table' => $rule['data_table'],
                ],
                [
                    'connection' => null,
                    'table_primary_key' => 'id',
                    'status' => 1,
                ]
            );
        }

        foreach (static::sensitiveDefinition() as $rule) {
            if (! static::tableExists($rule['data_table'], $connection)) {
                continue;
            }

            SecuritySensitiveData::firstOrCreate(
                [
                    'name' => $rule['name'],
                    'controller' => $rule['controller'],
                    'data_table' => $rule['data_table'],
                ],
                [
                    'connection' => null,
                    'table_primary_key' => 'id',
                    'sensitive_fields' => $rule['sensitive_fields'],
                    'status' => 1,
                ]
            );
        }
    }

    protected static function tableExists(string $table, $connection): bool
    {
        try {
            return Schema::connection($connection)->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
