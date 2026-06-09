#!/usr/bin/env bash
set -e

XPANEL_LANG="${1:-en}"
BASE="${XPANEL_BASE:-/opt/xpanel}"
DIR="$(cd "$(dirname "$0")" && pwd)"
source "$DIR/lib/i18n.sh"
load_xpanel_lang "$DIR/lang" "$XPANEL_LANG"

msg_migrate

# Ejecutar migraciones y seeds de Laravel vía Docker
docker exec xpanel-web php artisan migrate --force
docker exec xpanel-web php artisan db:seed --force

sleep 1
