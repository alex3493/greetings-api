#!/usr/bin/env bash
vendor_dir="/var/www/symfony/vendor"
if [ ! "$(ls -A $vendor_dir)" ]; then
  composer install -n
fi
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:database:create --if-not-exists --no-interaction --env=test
php bin/console doctrine:schema:update --no-interaction --force --complete --env=test
exec "$@"
