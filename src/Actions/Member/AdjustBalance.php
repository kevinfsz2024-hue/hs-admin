<?php

namespace Dcat\Admin\Actions\Member;

use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Member\AdjustBalanceForm;
use Dcat\Admin\Widgets\Modal;

class AdjustBalance extends RowAction
{
    protected $title = '调整余额';

    public function render()
    {
        return Modal::make()
            ->lg()
            ->title($this->title)
            ->body(AdjustBalanceForm::make()->withMemberId($this->getKey()))
            ->button($this->title);
    }
}
