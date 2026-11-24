#!/bin/sh
set -eu
rm -f /tmp/ready
mkdir -p vendor
mkdir -p /app/storage/covers
chown www-data:www-data /app/storage/covers
# Faixas enviadas pelo painel ficam numa subpasta gravável pelo PHP.
mkdir -p /app/storage/audio/uploads
chown www-data:www-data /app/storage/audio/uploads
cp -a /opt/onda-deps/vendor/. vendor/
php scripts/migrate.php
php scripts/seed.php
touch /tmp/ready
exec docker-php-entrypoint "$@"
