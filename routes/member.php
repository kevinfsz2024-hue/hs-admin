<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => 'Dcat\Admin\Http\Controllers',
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    /* @var \Illuminate\Routing\Router $router */
    // 注意：子资源必须注册在 `member` 资源之前，
    // 否则 member/{member} 的 show 路由会吞掉 member/groups 等路径
    $router->resource('member/groups', 'MemberGroupController');
    $router->resource('member/rules', 'MemberRuleController');

    // 余额 / 积分流水为只读列表
    $router->resource('member/balance-logs', 'MemberBalanceLogController', ['only' => ['index']]);
    $router->resource('member/points-logs', 'MemberPointsLogController', ['only' => ['index']]);

    $router->resource('member', 'MemberController');
});
