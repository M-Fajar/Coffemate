#!/bin/bash
php artisan migrate --force
php artisan passport:keys
