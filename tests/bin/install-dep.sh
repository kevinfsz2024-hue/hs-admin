#!/usr/bin/env bash
# 将本包以 path 仓库方式安装进 Laravel 12 宿主应用（CI Dusk 流程）
set -e

mkdir ./laravel-tests/dcat-admin
cp -rf ./src ./laravel-tests/dcat-admin
cp -rf ./config ./laravel-tests/dcat-admin
cp -rf ./database ./laravel-tests/dcat-admin
cp -rf ./resources ./laravel-tests/dcat-admin
cp -f ./composer.json ./laravel-tests/dcat-admin

rm -rf ./laravel-tests/tests
cp -rf ./tests ./laravel-tests/tests
cp -f ./phpunit.dusk.xml ./laravel-tests
cp -f ./.env.testing ./laravel-tests/.env

cd ./laravel-tests
composer config repositories.dcat-admin '{"type":"path","url":"./dcat-admin","options":{"symlink":false}}'
composer require dcat/laravel-admin:*@dev --no-interaction --no-progress
composer require laravel/dusk --dev --no-interaction --no-progress
