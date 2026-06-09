#!/usr/bin/env bash
set -euo pipefail

BASE="${XPANEL_BASE:-/opt/xpanel}"
ACCESS_FILE="$BASE/config/access.info"

if [ ! -f "$ACCESS_FILE" ]; then
  echo "❌ access.info not found at $ACCESS_FILE"
  exit 1
fi

URL_LINE="$(grep '^URL=' "$ACCESS_FILE" | cut -d= -f2-)"
DOMAIN="$(echo "$URL_LINE" | sed 's|^https\?://||;s|/.*||')"

if [ -z "$DOMAIN" ]; then
  echo "❌ Could not parse domain from access.info"
  exit 1
fi

echo "🔎 Smoke test domain: $DOMAIN"

ROOT_CODE="$(curl -s -o /dev/null -w '%{http_code}' -H "Host: $DOMAIN" "http://127.0.0.1/")"
ADMIN_CODE="$(curl -s -o /dev/null -w '%{http_code}' -H "Host: $DOMAIN" "http://127.0.0.1/admin/login")"

echo "Root HTTP code:  $ROOT_CODE"
echo "Admin HTTP code: $ADMIN_CODE"

if [ "$ROOT_CODE" != "200" ] && [ "$ROOT_CODE" != "301" ] && [ "$ROOT_CODE" != "302" ]; then
  echo "❌ Root endpoint check failed"
  exit 1
fi

if [ "$ADMIN_CODE" != "200" ] && [ "$ADMIN_CODE" != "301" ] && [ "$ADMIN_CODE" != "302" ]; then
  echo "❌ Admin login endpoint check failed"
  exit 1
fi

echo "✅ Smoke checks passed"
