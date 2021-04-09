#!/usr/bin/env bash

sudo find storage/ -type f -exec chmod 664 {} \;
sudo find storage/ -type d -exec chmod 775 {} \;
sudo find bootstrap/cache/ -type f -exec chmod 664 {} \;
sudo find bootstrap/cache/ -type d -exec chmod 775 {} \;

sudo chown -R www-data:dev storage
sudo chown -R www-data:dev bootstrap/cache

sudo chmod -R ug+w storage
sudo chmod -R ug+w bootstrap/cache

sudo chmod g+s storage
sudo chmod g+s bootstrap/cache

sudo setfacl -R -dm u:www-data:rwx storage
sudo setfacl -R -m u:www-data:rwx storage
sudo chmod -R g+w storage

sudo setfacl -R -m 'group:dev:rwx' -m 'd:group:dev:rwx' .

php artisan cache:clear

composer dump-autoload
