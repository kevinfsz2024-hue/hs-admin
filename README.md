# HS Admin

HS Admin 是基于 [Dcat Admin](https://github.com/jqhph/dcat-admin) 2.x 二次开发的后台系统构建工具，已完成 **Laravel 12.x** 适配，内置会员管理模块，默认简体中文界面，作为私有 composer 包用于客户项目交付。

## 特性

- 支持 **Laravel ^12.0** / **PHP ^8.2**（旧版本 Laravel 请使用上游 dcat-admin 2.x）
- 保持 Dcat Admin 2.x 公共 API 不变：RBAC 权限、Grid、Form、Show、图表 Widgets、菜单、扩展机制均可照常使用
- 内置**会员管理模块**：会员、会员分组、积分规则、余额流水、积分流水，安装即用
- 内置**数据安全管理模块**：数据回收站（删除数据自动快照、一键还原）、敏感数据修改记录（敏感字段修改前后值审计）、数据回收规则管理、敏感字段规则管理
- 默认安装即为**简体中文**界面（菜单、种子数据、演示组件）

## 环境要求

- PHP >= 8.2
- Laravel >= 12.0
- MySQL 5.7+ / 8.x

## 客户项目接入（私有 GitHub 仓库）

本包托管在私有仓库 `kevinfsz2024-hue/hs-admin`，客户项目按以下步骤接入。

### 1. 生成访问令牌

GitHub → Settings → Developer settings → **Fine-grained personal access tokens**，新建只读令牌：

- Repository access：仅选择 `kevinfsz2024-hue/hs-admin`
- Permissions：**Contents → Read-only**

令牌只保存在各开发机/部署机的 Composer 认证里，**不要**提交进客户项目。

### 2. 客户项目 composer.json

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/kevinfsz2024-hue/hs-admin"
        }
    ],
    "require": {
        "hs/hs-admin": "^3.1"
    }
}
```

### 3. 配置 Composer 认证（客户机执行一次）

```bash
composer config github-oauth.github.com <你的令牌>
```

### 4. 安装并初始化

```bash
composer update
php artisan admin:install
```

`admin:install` 一次性完成：发布配置与静态资源、创建数据表（含会员模块五张表，随包自动迁移，无需 publish）、写入管理员账号与中文菜单种子。

### 5. 更新

```bash
composer update hs/hs-admin
```

## 会员模块

- 开关：`config/admin.php` 中 `'member' => ['enable' => false]`（或环境变量 `ADMIN_MEMBER_ENABLE=false`），关闭后不注册菜单与路由
- 数据表：`member_users` / `member_groups` / `member_rules` / `member_balance_logs` / `member_points_logs`
- 余额/积分调整统一走 `Dcat\Admin\Services\MemberService::adjustBalance()` / `adjustPoints()`，事务 + 行锁保证流水与余额一致

## 数据安全管理模块

- 开关：`config/admin.php` 中 `'security' => ['enable' => false]`（或环境变量 `ADMIN_SECURITY_ENABLE=false`），关闭后不注册路由、菜单与数据捕获
- 数据表：`security_data_recycle`（回收规则）/ `security_sensitive_data`（敏感字段规则）/ `security_data_recycle_log`（回收站）/ `security_sensitive_data_log`（修改记录）
- 捕获机制：监听后台 Form 全局事件（`Form\Events\Deleting` / `Saving`，见 `Dcat\Admin\Services\DataSecurityService`），
  按规则匹配「当前控制器 + 数据表 + 连接标识」后捕获；捕获失败静默，不影响业务操作
- 预置规则：安装 / 重置数据时自动写入核心表的回收规则（管理员 / 角色 / 权限 / 菜单 / 会员 / 系统配置 /
  数据安全模块自监控等 12 条）与敏感字段规则（管理员数据：username、password；会员数据：username、mobile、password、status、email），
  见 `Dcat\Admin\Support\SecurityRules::seed()`，幂等可重复调用，已有安装可手动调用补种
- 控制器匹配：规则表单的「控制器」为下拉选择（扫描 `app/Admin/Controllers` 与包内置 `Http/Controllers` 的 `*Controller.php`，
  分「应用控制器 / 内置控制器」两组）；存储值为相对路径（如 `Member`、`user/User`），匹配忽略命名空间、大小写与 `Controller` 后缀，
  也兼容手写类名 / 路径 / 文件名
- 「连接标识」下拉（默认连接 + `config('database.connections')` 全部连接）；「数据表」下拉列出所选连接的全部表，
  切换连接后经 `security/tables` 接口自动刷新列表
- 数据回收站：删除行完整快照（直查目标表取原始行，含模型 `$hidden` 列），支持单行 / 批量还原（主键冲突时拒绝并提示）
- 敏感数据修改记录：每个敏感字段一条记录，含修改行（主键值）、修改项（优先展示表单字段标签）、修改前后值、操作管理员与 IP
- 浏览器测试：`tests/Browser/Cases/Security/SecurityTest.php`（规则创建、删除捕获、回收站还原、敏感字段审计、停用规则不捕获）

## 本地开发

```bash
# 安装依赖
composer install

# 单元/功能测试（orchestra/testbench）
composer test

# 浏览器测试（Dusk，需要本地 MySQL 与 Chrome）
sh tests/bin/install-dep.sh && sh tests/bin/install-admin.sh && sh tests/bin/start.sh
cd laravel-tests && php artisan dusk
```

开发中的客户项目可用 path 仓库直连本包：

```json
"repositories": [
    { "type": "path", "url": "F:/AI Project/hs-admin" }
]
```

## 版本

- `^3.1`：Laravel 12 适配 + 会员模块 + 简体中文 + 品牌白标
- 版本历史见 [CHANGELOG.md](CHANGELOG.md)

## 许可与致谢

- 本项目基于 [jqhph/dcat-admin](https://github.com/jqhph/dcat-admin)（MIT）二次开发，保留原作者版权声明，见 [LICENSE](LICENSE)
- 详细功能文档可参考 [Dcat Admin 中文文档](https://learnku.com/docs/dcat-admin)；上游原文档备份在 [docs/README-dcat-admin.md](docs/README-dcat-admin.md)
