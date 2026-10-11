# Changelog

## Unreleased

## 3.1.0 (2026-10-11)

### 变更

- **品牌白标**：composer 包名 `dcat/laravel-admin` → `hs/hs-admin`（`replace` 保留了 `dcat/laravel-admin` 别名），
  界面标题 / Logo / 页脚 / 文档全部改为 HS Admin；代码命名空间保持 `Dcat\Admin` 不变，dcat 生态扩展仍可使用。
- 扩展脚手架存根（`admin:extension`）目标包名同步改为 `hs/hs-admin ~3.0`，PHP 下限提升为 `^8.2`。

### 新增

- 包内置**会员管理模块**（会员 / 分组 / 积分规则 / 余额流水 / 积分流水），安装即用，菜单随迁移写入；
  `MemberService` 统一余额/积分调整（事务 + 行锁 + 流水），可通过 `admin.member.enable` 关闭。

### 变更

- **默认安装使用简体中文**：安装种子（菜单、管理员、角色、权限）与首页演示组件全部输出简体中文。
- 修复重置种子时的中断问题（重置数据先清空用户-角色关联，避免唯一键冲突）；
  重置数据后自动补回会员管理菜单。

## 3.0.0 (2026-10-11)

专注支持 Laravel 12.x 的大版本重构（基于上游 dcat-admin 2.2.2-beta，分支 `2.0` 演进而来）。

### 破坏性变更（Breaking changes）

- 仅支持 **Laravel ^12.0** 与 **PHP ^8.2**，不再支持 Laravel 5.5 ~ 11（如需支持旧版本请使用上游 2.x）。
- 移除硬依赖 `doctrine/dbal`（源码零使用，仅 composer.json 遗留）；如业务项目仍需要请自行引入。
- 依赖收窄：`spatie/eloquent-sortable` 由 `3.*|4.*` 收窄为 `^4.0`。
- 测试依赖更新：`orchestra/testbench ^10.0`、`phpunit ^11.5`、`laravel/dusk ^8.0`。

### Laravel 12 适配

- 删除全部 Laravel 5.5 ~ 8 的版本兼容分支（`ExportSeedCommand`、`ModelCreator`、`Translator`、
  `EloquentRepository`、`AdminServiceProvider`、`Grid\Model`、`PublishCommand` 等 7 处）。
- 修复 Symfony 7.4 弃用告警：`Request::get()` → `Request::input()`（36 处，含脚手架 stub）。
- 修复 PHP 8.2 弃用告警：动态属性改为显式声明（`WebUploader`、`BatchAction::$selectorPrefix`、
  `Grid::visibleColumnsFromQuery`）。
- `admin_extensions` 迁移改用 `id()` 替代弃用的 `increments()`。
- `PublishCommand` 补回 Flysystem v3 路径下 `menu.php`/`global.php` 语言文件不被 `--force` 覆盖的保护。

### 公共 API

- 保持 Dcat Admin 2.x 公共 API 不变，现有业务代码无需修改即可升级。

### 测试与 CI

- 新增 `orchestra/testbench` 测试基类（SQLite :memory:），覆盖 helpers、auth guard、RBAC 关联、
  Grid 渲染与分页等核心模块；PHPUnit 开启 `failOnDeprecation`。
- Dusk 浏览器测试适配 Dusk 8 / PHPUnit 11（chromedriver 端口对齐、W3C 协议兼容），在真实
  Laravel 12 宿主应用上 17 个场景全部通过（登录、RBAC、Grid、HasMany 表单、图片上传、菜单管理）。
- CI 重写：`tests.yml`（PHP 8.2/8.3/8.4 × Laravel 12 矩阵）+ `dusk.yml`（Laravel 12 宿主冒烟）。
- 已在 Laravel 12.69 + MySQL 8.4 真实环境完成整体验收，运行期零弃用告警。

### 升级说明（业务项目）

```bash
# composer.json 中指向本 fork 的仓库
composer config repositories.hs-admin '{"type":"vcs","url":"https://github.com/kevinfsz2024-hue/hs-admin.git"}'
composer require dcat/laravel-admin:^3.0 -W
```

> 已知问题：上游遗留的两个 XSS 安全告警（GHSA-mr24-cf69-5chq、GHSA-p74v-mwvg-8ghp）影响 <=2.1.3，
> 本 fork 已包含部分修复（json() XSS），后续版本继续跟进。
