# Étape 1: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP sans scripts post-install
# Note: le projet référence parfois des packages qui nécessitent l'extension PHP ext-mongodb
# (par ex. "mongodb/mongodb"). Si l'extension native n'est pas disponible dans l'image
# builder, Composer échoue. Ici on ignore uniquement la contrainte d'extension MongoDB
# pendant la phase d'installation pour produire le dossier vendor. En production vous
# pouvez soit : installer ext-mongodb dans l'image (meilleur), soit garder cette option
# (--ignore-platform-req=ext-mongodb) pour contourner.
RUN set -eux \
        # Installer uniquement les utilitaires nécessaires pour composer (éviter compilation PECL ici)
        # Supporter images basées sur Debian (apt-get) et Alpine (apk)
        && if command -v apt-get >/dev/null 2>&1; then \
                 apt-get update && apt-get install -y --no-install-recommends git unzip ca-certificates && rm -rf /var/lib/apt/lists/*; \
             elif command -v apk >/dev/null 2>&1; then \
                 apk add --no-cache git unzip ca-certificates; \
             else \
                 echo "No supported package manager (apt-get or apk) found" && exit 1; \
             fi

# Installer les dépendances PHP sans exiger l'extension mongodb (sera compilée dans l'image finale)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts \
    --ignore-platform-req=ext-mongodb

# Étape 2: Image finale pour l'application
FROM php:8.3-fpm-alpine

# Utiliser l'image PHP Alpine pour l'exécution
RUN set -eux \
     # Installer les dépendances de compilation (excluant postgresql-dev) en virtual .build-deps
     && apk add --no-cache --virtual .build-deps \
         $PHPIZE_DEPS openssl-dev libzip-dev zlib-dev make gcc g++ autoconf pkgconfig \
     # Installer les paquets PostgreSQL en persistant (ne seront PAS supprimés)
     && apk add --no-cache postgresql-dev postgresql-client \
     && pecl channel-update pecl.php.net || true \
     && pecl install mongodb \
     && docker-php-ext-enable mongodb \
     && docker-php-ext-install pdo pdo_pgsql \
     # Supprimer uniquement les paquets de compilation temporaires
     && apk del .build-deps

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les dépendances installées depuis l'étape de build
COPY --from=composer-build /app/vendor ./vendor

# Copier le reste du code de l'application
COPY . .

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copier le script d'entrée
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Passer à l'utilisateur non-root
USER laravel

# Exposer le port 8000
EXPOSE 8000

# Commande par défaut
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
