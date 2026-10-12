<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => 'Dcat\Admin\Http\Controllers',
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    /* @var \Illuminate\Routing\Router $router */
    // 数据表下拉联动接口（供规则表单选择连接后刷新数据表列表），须注册在资源路由之前
    $router->get('security/tables', 'SecurityDataRecycleController@tables');

    // 数据安全管理模块：回收站 / 敏感数据修改记录 / 回收规则 / 敏感字段规则
    $router->resource('security/data-recycles', 'SecurityDataRecycleController');
    $router->resource('security/sensitive-data', 'SecuritySensitiveDataController');
    $router->resource('security/recycle-logs', 'SecurityRecycleLogController');
    $router->resource('security/sensitive-data-logs', 'SecuritySensitiveDataLogController');
});
