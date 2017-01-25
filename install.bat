
rm bootstrap/cache/*

composer install

composer update

:: run any outstanding migrations
php artisan migrate --force

:: optimize autoloader and compile common classes
php artisan optimize --force

:: clear application cache
php artisan cache:clear

php artisan db:seed

rm storage/framework/down

pause