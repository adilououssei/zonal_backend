# syntax=docker/dockerfile:1

# --- Étape 1 : on récupère uniquement le binaire Composer depuis l'image
# officielle (elle embarque sa propre version de PHP, qu'on n'utilise pas
# pour installer les dépendances : c'est PHP 8.4 ci-dessous qui s'en charge,
# pour que les vérifications de version PHP de Composer soient correctes).
FROM composer:2 AS composer_bin

# --- Étape 2 : l'image finale, PHP-FPM + Alpine (légère) ---
FROM php:8.4-fpm-alpine AS backend

# Extensions système nécessaires : pdo_mysql (connexion MySQL), intl
# (symfony/intl), zip (composer/archives), opcache (perf en prod).
RUN apk add --no-cache icu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql intl zip opcache

# Réglages Opcache recommandés par Symfony en production
RUN { \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/zz-zonal-uploads.ini
COPY --from=composer_bin /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV APP_ENV=prod \
    COMPOSER_ALLOW_SUPERUSER=1

# Dépendances installées avant de copier le reste du code : tant que
# composer.json/composer.lock ne changent pas, Docker réutilise cette couche
# en cache et les reconstructions suivantes sont bien plus rapides.
# --no-scripts : on ne veut pas exécuter les scripts Symfony (cache:clear...)
# à ce stade, la base de données n'existe pas encore pendant le build.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p var public/uploads \
    && chown -R www-data:www-data var public/uploads \
    && chmod -R 775 var public/uploads

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
