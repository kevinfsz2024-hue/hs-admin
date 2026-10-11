<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => 'Dcat\Admin\Http\Controllers',
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    /* @var \Illuminate\Routing\Router $router */
    // 注意：下载路由必须注册在 `general/exports` 资源之前
    $router->get('general/exports/{export}/download', 'DataExportController@download');

    $router->resource('general/configs', 'SystemConfigController');
    $router->resource('general/attachments', 'AttachmentController');
    $router->resource('general/exports', 'DataExportController', ['only' => ['index', 'destroy']]);
});
