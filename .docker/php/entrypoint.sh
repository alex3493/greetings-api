#!/usr/bin/env bash
vendor_dir="/var/www/symfony/vendor"
if [ ! "$(ls -A $vendor_dir)" ]; then
  composer install -n
fi
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:migrations:migrate --no-interaction
exec "$@"
