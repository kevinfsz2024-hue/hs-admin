<?php

namespace Dcat\Admin\Services;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Form\Events\Saving;
use Dcat\Admin\Form\Events\Deleting;
use Dcat\Admin\Models\SecurityDataRecycle;
use Dcat\Admin\Models\SecurityDataRecycleLog;
use Dcat\Admin\Models\SecuritySensitiveData;
use Dcat\Admin\Models\SecuritySensitiveDataLog;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

/**
 * 数据安全管理服务.
 *
 * 通过监听后台 Form 全局事件实现：
 * - 数据回收：命中回收规则的控制器删除数据时，把被删行快照进数据回收站；
 * - 敏感数据审计：命中敏感字段规则的控制器更新敏感字段时，记录修改前后值。
 *
 * 捕获失败一律静默（审计能力不应影响业务操作本身）。
 */
class DataSecurityService
{
    /**
     * 注册全局 Form 事件监听（由 AdminServiceProvider 在 boot 阶段调用）.
     */
    public static function register(): void
    {
        if (! config('admin.security.enable', true)) {
            return;
        }

        Event::listen(Deleting::class, function (Deleting $event) {
            static::captureDeleted($event->form);
        });

        Event::listen(Saving::class, function (Saving $event) {
            static::captureSensitive($event->form);
        });
    }

    /**
     * 删除捕获：被删行写入数据回收站.
     */
    public static function captureDeleted(Form $form): void
    {
        try {
            if (! ($rule = static::matchRule($form, SecurityDataRecycle::class))) {
                return;
            }

            // Form::destroy 在触发 Deleting 前已加载被删行；这里直接查目标表取原始行，
            // 避免模型 $hidden / 序列化造成快照缺列（否则还原时无法完整插回）
            $rows = static::fetchRawRows($form->repository()->model(), $form->getKey());

            [$adminId, $adminName] = static::currentAdmin();
            $ip = static::currentIp();
            $now = now();

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                SecurityDataRecycleLog::create([
                    'recycle_rule_id' => $rule->getKey(),
                    'name' => $rule->name,
                    'controller' => $rule->controller,
                    'connection' => $rule->connection,
                    'data_table' => $rule->data_table,
                    'table_primary_key' => $rule->table_primary_key ?: 'id',
                    // 模型的 array cast 负责编码，这里直接传数组避免双重编码
                    'deleted_data' => $row,
                    'admin_id' => $adminId,
                    'admin_name' => $adminName,
                    'ip' => $ip,
                    'created_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * 敏感字段捕获：更新敏感字段时记录修改前后值（每字段一条）.
     */
    public static function captureSensitive(Form $form): void
    {
        try {
            // 仅审计更新（创建无修改前后可言）
            if (! ($key = $form->getKey())) {
                return;
            }

            if (! ($rule = static::matchRule($form, SecuritySensitiveData::class))) {
                return;
            }

            $fields = (array) ($rule->sensitive_fields ?? []);
            if (! $fields) {
                return;
            }

            // Saving 事件触发时更新尚未执行，直接查目标表取原始行（含模型 $hidden 字段）
            $model = $form->repository()->model();
            $original = static::fetchRawRows($model, $key)[0] ?? [];

            $inputs = (array) $form->input();
            $labels = static::fieldLabels($form);

            [$adminId, $adminName] = static::currentAdmin();
            $ip = static::currentIp();
            $now = now();

            foreach ($fields as $field) {
                if (! array_key_exists($field, $inputs)) {
                    continue;
                }

                $before = $original[$field] ?? null;
                $after = $inputs[$field];

                if (! static::valueChanged($before, $after)) {
                    continue;
                }

                SecuritySensitiveDataLog::create([
                    'sensitive_rule_id' => $rule->getKey(),
                    'name' => $rule->name,
                    'controller' => $rule->controller,
                    'connection' => $rule->connection,
                    'data_table' => $rule->data_table,
                    'data_id' => (string) $key,
                    'field' => $field,
                    'field_label' => $labels[$field] ?? null,
                    'original' => static::stringify($before),
                    'changes' => static::stringify($after),
                    'admin_id' => $adminId,
                    'admin_name' => $adminName,
                    'ip' => $ip,
                    'created_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * 从数据回收站还原一行到原数据表.
     *
     * @return array{0: bool, 1: string} [是否成功, 提示消息]
     */
    public static function restoreRecycleLog($logId): array
    {
        $log = SecurityDataRecycleLog::query()->findOrFail($logId);

        $row = $log->deleted_data;
        if (! is_array($row) || ! $row) {
            return [false, '还原失败：被删数据为空'];
        }

        $pk = $log->table_primary_key ?: 'id';
        if (! array_key_exists($pk, $row)) {
            return [false, "还原失败：被删数据中缺少主键 [{$pk}]"];
        }

        $connection = $log->connection ?: config('database.default');

        try {
            $exists = Schema::connection($connection)->hasTable($log->data_table);
        } catch (\Throwable $e) {
            $exists = false;
        }

        if (! $exists) {
            return [false, "还原失败：数据表 [{$log->data_table}] 不存在"];
        }

        $query = DB::connection($connection)->table($log->data_table);

        if ($query->where($pk, $row[$pk])->exists()) {
            return [false, "还原失败：{$log->data_table}.{$pk}={$row[$pk]} 已存在，主键冲突"];
        }

        $query->insert($row);

        $log->delete();

        return [true, "已还原 {$log->data_table} 中 {$pk}={$row[$pk]} 的数据"];
    }

    /**
     * 匹配当前请求对应的规则：控制器 + 数据表 + 连接标识.
     *
     * @param  Form  $form
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $ruleClass
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected static function matchRule(Form $form, string $ruleClass)
    {
        $controller = static::currentControllerSlug();
        if ($controller === null || $controller === '') {
            return null;
        }

        $model = $form->repository()->model();
        $table = $model->getTable();
        $connection = $model->getConnectionName() ?: config('database.default');

        return $ruleClass::query()
            ->where('status', 1)
            ->where('data_table', $table)
            ->get()
            ->first(function ($rule) use ($controller, $connection) {
                if (static::normalizeController($rule->controller) !== $controller) {
                    return false;
                }

                return in_array($rule->connection, [null, '', $connection], true);
            });
    }

    /**
     * 直查目标表获取原始行（含 $hidden 列，值与数据库一致，供快照 / 前后值比对）.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $keys  单个或逗号分隔的主键
     * @return array<int, array>
     */
    protected static function fetchRawRows($model, $keys): array
    {
        $pk = $model->getKeyName();
        $connection = $model->getConnectionName() ?: config('database.default');

        $ids = collect(explode(',', (string) $keys))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return DB::connection($connection)
            ->table($model->getTable())
            ->whereIn($pk, $ids->all())
            ->get()
            ->map(function ($row) {
                return (array) $row;
            })
            ->all();
    }

    /**
     * 表单构建时取当前模型值（供规则表单下拉选项回填存储值）.
     */
    public static function currentValues(Form $form): array
    {
        try {
            $model = $form->model();

            if ($model instanceof Arrayable) {
                return $model->toArray();
            }

            return (array) $model;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 控制器下拉选项（dcat Select groups 格式：[['label' => 分组, 'options' => [值 => 显示]], ...]）.
     *
     * 扫描 app/Admin/Controllers 与包内置 Http/Controllers 下的 *Controller.php，
     * 值为相对路径（不含 .php，如 Member、user/User），匹配时忽略目录与大小写.
     */
    public static function controllerOptions(): array
    {
        $groups = [
            '应用控制器' => static::scanControllerFiles(app_path('Admin/Controllers')),
            '内置控制器' => static::scanControllerFiles(dirname(__DIR__).'/Http/Controllers'),
        ];

        $result = [];

        foreach ($groups as $label => $items) {
            if ($items) {
                $result[] = ['label' => $label, 'options' => $items];
            }
        }

        return $result;
    }

    /**
     * 数据表下拉选项（指定连接下的所有表）.
     */
    public static function tableOptions(?string $connection = null): array
    {
        $connection = $connection ?: config('database.default');

        // 仅允许使用配置中已定义的连接，避免任意连接串引发异常
        if (! array_key_exists($connection, (array) config('database.connections'))) {
            return [];
        }

        try {
            // Laravel 11+ 使用 getTables()（返回 ['name' => 表名, ...] 结构）
            $tables = Schema::connection($connection)->getTables();
        } catch (\Throwable $e) {
            return [];
        }

        return collect($tables)
            ->map(function ($table) {
                if (is_array($table)) {
                    return (string) ($table['name'] ?? reset($table));
                }

                return (string) $table;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(function ($table) {
                return [$table => $table];
            })
            ->all();
    }

    /**
     * 连接标识下拉选项（'' 即默认连接）.
     */
    public static function connectionOptions(): array
    {
        $options = ['' => '默认连接'];

        foreach (array_keys((array) config('database.connections')) as $name) {
            $options[$name] = $name;
        }

        return $options;
    }

    /**
     * 编辑已有规则时，把存储值并入控制器分组选项（值可能已不在扫描结果中）.
     */
    public static function ensureControllerOption(array $groups, $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $groups;
        }

        foreach ($groups as $group) {
            if (isset($group['options'][$value])) {
                return $groups;
            }
        }

        $groups[] = ['label' => '其他', 'options' => [$value => $value]];

        return $groups;
    }

    /**
     * 编辑已有规则时，把存储值并入平铺选项.
     */
    public static function ensureFlatOption(array $options, $value): array
    {
        $value = trim((string) $value);

        if ($value === '' || isset($options[$value])) {
            return $options;
        }

        $options[$value] = $value;

        return $options;
    }

    /**
     * 扫描目录下的 *Controller.php，返回 [相对路径(不含.php) => 相对路径].
     */
    protected static function scanControllerFiles(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $base = rtrim(str_replace('\\', '/', realpath($dir) ?: $dir), '/');
        $options = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if (! $file->isFile() || ! preg_match('/Controller\.php$/i', $file->getFilename())) {
                    continue;
                }

                $path = str_replace('\\', '/', $file->getPathname());
                $value = preg_replace('/\.php$/i', '', str_starts_with($path, $base.'/') ? substr($path, \strlen($base) + 1) : $file->getFilename());

                if ($value !== null && $value !== '') {
                    $options[$value] = $value;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        ksort($options);

        return $options;
    }

    /**
     * 当前请求路由对应的控制器标识（小写短名，无 Controller 后缀）.
     */
    protected static function currentControllerSlug(): ?string
    {
        try {
            $route = request()->route();
            $action = $route ? $route->getActionName() : null;

            if (! $action || ! str_contains($action, '@')) {
                return null;
            }

            // getActionName 形如 App\Admin\Controllers\MemberController@destroy
            [$class] = explode('@', $action, 2);

            return static::normalizeController($class);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 归一化控制器标识.
     *
     * 兼容以下写法（均归一为 user）：
     *   UserController / App\Admin\Controllers\UserController
     *   user/User.php / user/UserController.php / auth/Admin.php
     */
    public static function normalizeController($value): string
    {
        $name = str_replace('\\', '/', trim((string) $value));

        // 取最后一段路径；注意 strrpos 找不到 / 时返回 false，不能作为 substr 起点偏移
        $pos = strrpos($name, '/');
        $name = $pos === false ? $name : substr($name, $pos + 1);

        $name = preg_replace('/\.php$/i', '', $name);
        $name = preg_replace('/controller$/i', '', $name);

        return strtolower(trim($name));
    }

    /**
     * 表单字段名 => 展示标签（修改项列展示用，尽力而为）.
     */
    protected static function fieldLabels(Form $form): array
    {
        $labels = [];

        try {
            foreach ($form->builder()->fields() as $field) {
                $label = null;
                try {
                    $label = $field->label();
                } catch (\Throwable $e) {
                }

                foreach ((array) $field->column() as $column) {
                    if (is_string($label) && $label !== '') {
                        $labels[$column] = $label;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return $labels;
    }

    /**
     * 值是否发生变化（字符串化后比较）.
     */
    protected static function valueChanged($before, $after): bool
    {
        return static::stringify($before) !== static::stringify($after);
    }

    /**
     * 任意值转可比对/可存储的字符串.
     */
    protected static function stringify($value): string
    {
        if (is_array($value) || $value instanceof Arrayable) {
            $value = json_encode(
                $value instanceof Arrayable ? $value->toArray() : $value,
                JSON_UNESCAPED_UNICODE
            );
        }

        return (string) ($value ?? '');
    }

    /**
     * @return array{0: ?int, 1: ?string} [管理员ID, 管理员名称]
     */
    protected static function currentAdmin(): array
    {
        try {
            $user = Admin::user();

            return [
                $user?->getKey(),
                $user?->name ?: $user?->username,
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    protected static function currentIp(): ?string
    {
        try {
            return request()->getClientIp();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
