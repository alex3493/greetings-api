#!/usr/bin/env bash
composer install -n
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:migrations:migrate --no-interaction
exec "$@"