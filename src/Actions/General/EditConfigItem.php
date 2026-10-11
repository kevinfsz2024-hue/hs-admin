<?php

namespace Dcat\Admin\Actions\General;

use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\General\ConfigItemForm;
use Dcat\Admin\Widgets\Modal;

class EditConfigItem extends RowAction
{
    protected $title = '编辑';

    public function render()
    {
        return Modal::make()
            ->lg()
            ->title('编辑配置项')
            ->body(ConfigItemForm::make()->withItemId($this->getKey()))
            ->button($this->title());
    }
}
