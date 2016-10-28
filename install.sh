#!/usr/bin/env bash
set -e

###
# Deployment
###

# download or update composer
if [ ! -f composer.phar ]; then
    curl -sS https://getcomposer.org/installer | php
else
    php composer.phar self-update
fi

# bring the application down for maintenance
touch storage/framework/down
echo "Application is now down."

# remove compiled cache files
rm -f bootstrap/cache/*

# install packages
php composer.phar install --no-dev --no-interaction --prefer-dist

# run any outstanding migrations
php artisan migrate --force

# optimize autoloader and compile common classes
php artisan optimize --force

# clear application cache
php artisan cache:clear

# and bring the application back up
rm -f storage/framework/down
echo "Application is now live."
