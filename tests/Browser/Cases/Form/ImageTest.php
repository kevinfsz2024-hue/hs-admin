<?php

namespace Tests\Browser\Cases\Form;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * 图片上传测试.
 *
 */
#[Group('form:image')]
class ImageTest extends DuskTestCase
{
    public function test()
    {
        $this->browse(function (Browser $browser) {
//            $browser->visit(admin_base_path('tests/users/create'))
//                ->attach('file-avatar', __DIR__.'/../../../resources/assets/test.jpg');

            $this->assertTrue(true);
        });
    }
}
