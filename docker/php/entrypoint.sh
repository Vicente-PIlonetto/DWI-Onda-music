#!/bin/sh
set -eu
rm -f /tmp/ready
mkdir -p vendor
mkdir -p /app/storage/covers
chown www-data:www-data /app/storage/covers
cp -a /opt/onda-deps/vendor/. vendor/
php scripts/migrate.php
php scripts/seed.php
touch /tmp/ready
exec docker-php-entrypoint "$@"
