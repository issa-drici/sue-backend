# Remplace le build Nixpacks (cf. nixpacks.toml, gardé pour rollback) : même
# runtime (nginx + php-fpm + 12 workers queue:work sous supervisor), mais sur
# l'image officielle php:*-fpm, que l'on peut rebuild dès qu'un correctif
# PHP/Debian est publié sans attendre Nixpacks.
# Version exacte (pas "8.4") : un tag flottant peut être servi depuis un vieux
# cache Docker. 8.4.27 = premier patch 8.4 corrigeant PHP2Shell
# (CVE-2026-109618 / CVE-2026-109619). Ne pas descendre en dessous.
ARG PHP_VERSION=8.4.27

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

FROM php:${PHP_VERSION}-fpm-bookworm

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends nginx supervisor \
    && install-php-extensions pdo_pgsql pgsql bcmath intl pcntl zip opcache \
    && rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zzz-app.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY . .

# laravel.log est créé ici, au nom de www-data : la tâche Coolify schedule:run
# s'exécute en root via docker exec, et si elle créait ce fichier en premier,
# php-fpm (www-data) ne pourrait plus y écrire.
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && touch storage/logs/laravel.log \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 80

CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
