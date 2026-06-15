#!/usr/bin/env bash
set -e

XPANEL_LANG="${1:-en}"
BASE="${XPANEL_BASE:-/opt/xpanel}"
DIR="$(cd "$(dirname "$0")" && pwd)"
source "$DIR/lib/i18n.sh"
load_xpanel_lang "$DIR/lang" "$XPANEL_LANG"

msg_migrate

# Ensure storage permissions before migrating
docker exec xpanel-web sh -lc "
  mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
  chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
" || true

# Run all migration paths (same as install.sh)
docker exec xpanel-web php artisan migrate --force
docker exec xpanel-web php artisan migrate --force --path=database/migrations/admin
docker exec xpanel-web php artisan migrate --force --path=database/migrations/client
docker exec xpanel-web php artisan optimize:clear || true

sleep 1
