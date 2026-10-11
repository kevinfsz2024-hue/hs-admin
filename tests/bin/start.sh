#!/usr/bin/env bash
set -e

cd ./laravel-tests
php artisan serve --port=8300 > /dev/null 2>&1 &
