<?php

namespace Dcat\Admin\Widgets\General;

use Dcat\Admin\Models\GeneralConfig;
use Dcat\Admin\Widgets\Form;

/**
 * 自定义配置项表单（新增 / 编辑）.
 */
class ConfigItemForm extends Form
{
    /**
     * 由行操作注入的配置项 ID（通过隐藏字段随表单提交）.
     *
     * @var int|null
     */
    protected $itemId;

    public function withItemId($id)
    {
        $this->itemId = $id;

        return $this;
    }

    public function form()
    {
        // 编辑态按注入的 ID 加载已有数据用于回填
        $config = $this->itemId ? GeneralConfig::query()->find($this->itemId) : null;

        $this->hidden('id')->default($this->itemId);

        $this->text('group', '分组')
            ->help('用于自定义配置列表的归类展示')
            ->default($config->group ?? '自定义');

        $this->text('name', '配置键')
            ->required()
            ->help('代码中通过 general_config("配置键") 读取')
            ->default($config->name ?? '');

        $this->textarea('value', '配置值')
            ->rows(3)
            ->default($config->value ?? '');

        $this->text('description', '说明')
            ->default($config->description ?? '');
    }

    public function handle(array $input)
    {
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            return $this->response()->error('配置键不能为空');
        }

        $data = [
            'group' => trim($input['group'] ?? '') ?: '自定义',
            'name' => $name,
            'value' => $input['value'] ?? null,
            'description' => trim($input['description'] ?? '') ?: null,
        ];

        $itemId = $input['id'] ?? $this->itemId;

        if ($itemId) {
            $config = GeneralConfig::query()->find($itemId);

            if (! $config) {
                return $this->response()->error('配置项不存在');
            }

            $duplicated = GeneralConfig::query()
                ->where('name', $name)
                ->where('id', '!=', $itemId)
                ->exists();

            if ($duplicated) {
                return $this->response()->error("配置键 [{$name}] 已存在");
            }

            $config->fill($data)->save();
        } else {
            if (GeneralConfig::query()->where('name', $name)->exists()) {
                return $this->response()->error("配置键 [{$name}] 已存在");
            }

            GeneralConfig::create($data);
        }

        return $this->response()->success('保存成功')->refresh();
    }
}
