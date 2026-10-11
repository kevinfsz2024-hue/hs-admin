#!/usr/bin/env bash
# 在 Laravel 12 宿主应用中安装 dcat-admin 并准备 Dusk 测试环境
set -e

cd ./laravel-tests
php artisan admin:publish --force
php artisan admin:install
php artisan migrate:rollback
# --detect 按宿主机已安装的 Chrome 版本安装匹配的 chromedriver（不带参数会装最新稳定版导致版本不匹配）
php artisan dusk:chrome-driver --detect

cp -f ./tests/routes.php ./app/Admin/routes.php
cp -f ./tests/resources/config/admin.php ./config/admin.php
cp -f ./tests/resources/config/filesystems.php ./config/filesystems.php
