#!/bin/bash
#
# Deploys SCAS on the Hostinger server: pull main, install dependencies, migrate, cache.
#
# GitHub Actions streams this file over SSH (see .github/workflows/tests.yml), so the
# version in the commit being deployed is the one that runs. To deploy by hand on the
# server: bash ~/domains/scas.ebnleadgen.online/public_html/deploy/deploy-scas.sh
#
# Built assets in public/build are committed to git. Never run npm run build on the server.

# Wrapped in a function so bash reads the whole script before running any of it,
# even when the script arrives on stdin.
deploy() {
    set -euo pipefail
    exec </dev/null

    APP_DIR=~/domains/scas.ebnleadgen.online/public_html
    P=${SCAS_PHP:-/opt/alt/php84/usr/bin/php}

    cd "$APP_DIR"

    # Safety guard: refuse to run in the wrong app.
    grep -q '^APP_URL=https://scas.ebnleadgen.online' .env || { echo "ABORT: not the SCAS folder"; exit 1; }

    # public/build comes from git, so loose or edited build files on the server are stale: reset them.
    git checkout -- public/build 2>/dev/null || true
    git clean -fdq -- public/build

    # Any other change on the server is someone's edit: stop and show it.
    [ -z "$(git status --short)" ] || { echo "ABORT: uncommitted changes on server:"; git status --short; exit 1; }

    "$P" artisan down --retry=60
    trap '"$P" artisan up' EXIT
    git pull --ff-only
    git log -1 --oneline
    "$P" "$(command -v composer)" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    "$P" artisan migrate --force
    "$P" artisan optimize:clear
    "$P" artisan optimize
    echo "SCAS deployed ✅"
}

deploy "$@"
