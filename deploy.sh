#!/usr/bin/env bash
#
# Server-side deploy steps, run over SSH from the project directory after the
# code is updated (git pull / Hostinger Git deploy):
#
#   bash deploy.sh
#
# Set PHP to the right binary if the default CLI version is too old, e.g.
#   PHP=/opt/alt/php83/usr/bin/php bash deploy.sh

set -euo pipefail

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

if [ ! -f .env ]; then
    echo "Missing .env — copy .env.example to .env and fill in the production values first." >&2
    exit 1
fi

if [ ! -f public/build/manifest.json ]; then
    echo "Warning: public/build is missing. Run 'npm run build' locally and upload public/build." >&2
fi

"$COMPOSER" install --no-dev --optimize-autoloader --no-interaction

"$PHP" artisan migrate --force
"$PHP" artisan optimize:clear
"$PHP" artisan optimize

chmod -R ug+rwX storage bootstrap/cache

echo "Deploy finished."
