FROM php:8.3-fpm-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends ffmpeg unzip git \
    && docker-php-ext-install pdo_mysql && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /opt/onda-deps
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
WORKDIR /app/backend
COPY docker/php/entrypoint.sh /usr/local/bin/onda-entrypoint
RUN chmod +x /usr/local/bin/onda-entrypoint && mkdir -p /var/lib/php/sessions && chown www-data:www-data /var/lib/php/sessions
RUN printf 'session.save_path=/var/lib/php/sessions\nupload_max_filesize=3M\npost_max_size=4M\nexpose_php=Off\ndisplay_errors=Off\nlog_errors=On\n' > /usr/local/etc/php/conf.d/onda.ini
ENTRYPOINT ["onda-entrypoint"]
CMD ["php-fpm"]
