#!/usr/bin/env bash
# 将本包以 path 仓库方式安装进 Laravel 12 宿主应用（CI Dusk 流程）
set -e

mkdir ./laravel-tests/hs-admin
cp -rf ./src ./laravel-tests/hs-admin
cp -rf ./config ./laravel-tests/hs-admin
cp -rf ./database ./laravel-tests/hs-admin
cp -rf ./resources ./laravel-tests/hs-admin
cp -f ./composer.json ./laravel-tests/hs-admin

rm -rf ./laravel-tests/tests
cp -rf ./tests ./laravel-tests/tests
cp -f ./phpunit.dusk.xml ./laravel-tests
cp -f ./.env.testing ./laravel-tests/.env

cd ./laravel-tests
composer config repositories.hs-admin '{"type":"path","url":"./hs-admin","options":{"symlink":false}}'
composer require hs/hs-admin:*@dev --no-interaction --no-progress
composer require laravel/dusk --dev --no-interaction --no-progress
