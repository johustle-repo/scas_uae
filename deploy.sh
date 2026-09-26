#!/usr/bin/env bash
#
# Server-side deploy steps, run over SSH from the project directory after the
# code is updated (git pull / Hostinger Git deploy):
#
#   bash deploy.sh
#
# The app needs PHP 8.4+. When the default `php` is older, the script uses
# Hostinger's newer PHP from /opt/alt automatically. Override it with e.g.
#   PHP_BIN=/opt/alt/php85/usr/bin/php bash deploy.sh

set -euo pipefail

MIN_PHP_VERSION_ID=80401

php_is_new_enough() {
    [ -x "$(command -v "$1" 2>/dev/null)" ] \
        && [ "$("$1" -r 'echo PHP_VERSION_ID;')" -ge "$MIN_PHP_VERSION_ID" ]
}

if [ -z "${PHP_BIN:-}" ]; then
    for candidate in php /opt/alt/php85/usr/bin/php /opt/alt/php84/usr/bin/php; do
        if php_is_new_enough "$candidate"; then
            PHP_BIN="$candidate"
            break
        fi
    done
fi

if [ -z "${PHP_BIN:-}" ] || ! php_is_new_enough "$PHP_BIN"; then
    echo "PHP 8.4.1 or newer is required. Set PHP_BIN to a PHP 8.4+ binary." >&2
    exit 1
fi

COMPOSER_BIN="${COMPOSER_BIN:-$(command -v composer)}"

echo "Using $("$PHP_BIN" -r 'echo PHP_VERSION;') ($PHP_BIN)"

if [ ! -f .env ]; then
    echo "Missing .env — copy .env.example to .env and fill in the production values first." >&2
    exit 1
fi

if [ ! -f public/build/manifest.json ]; then
    echo "Warning: public/build is missing. Run 'npm run build' locally, commit public/build and push." >&2
fi

"$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

chmod -R ug+rwX storage bootstrap/cache

echo "Deploy finished."
