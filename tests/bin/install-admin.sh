#!/usr/bin/env bash
# 在 Laravel 12 宿主应用中安装 dcat-admin 并准备 Dusk 测试环境
set -e

cd ./laravel-tests
php artisan admin:publish --force
php artisan admin:install
php artisan migrate:rollback
php artisan dusk:chrome-driver

cp -f ./tests/routes.php ./app/Admin/routes.php
cp -f ./tests/resources/config/admin.php ./config/admin.php
cp -f ./tests/resources/config/filesystems.php ./config/filesystems.php
