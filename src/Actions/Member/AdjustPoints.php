<?php

namespace Dcat\Admin\Actions\Member;

use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Member\AdjustPointsForm;
use Dcat\Admin\Widgets\Modal;

class AdjustPoints extends RowAction
{
    protected $title = '调整积分';

    public function render()
    {
        return Modal::make()
            ->lg()
            ->title($this->title)
            ->body(AdjustPointsForm::make()->withMemberId($this->getKey()))
            ->button($this->title);
    }
}
