#!/bin/sh
set -e

cd /var/www/html

# Self-heal: older images may lack intl (required by Filament /admin/tenants).
if ! php -r 'exit(extension_loaded("intl") ? 0 : 1);'; then
    echo "Installing missing PHP intl extension..."
    apt-get update -qq
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq libicu-dev
    docker-php-ext-install intl
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -f .env ]; then
    if [ -f .env.docker ]; then
        cp .env.docker .env
    else
        cp .env.example .env
    fi
fi

# Generate key when missing (fresh clone copies .env.example with empty APP_KEY)
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

exec "$@"
