#!/usr/bin/env bash
set -e
source "$(dirname "$0")/lib/loader.sh"

BASE="${XPANEL_BASE:-/opt/xpanel}"
JSON=0
[ "$1" = "--json" ] && JSON=1

version="$(cat "$BASE/VERSION" 2>/dev/null || echo "unknown")"
docker_status="stopped"
if systemctl is-active docker >/dev/null 2>&1; then
  docker_status="ok"
fi

services_raw=""
if [ -f "$BASE/docker-compose.yml" ]; then
  services_raw="$(cd "$BASE" && docker compose ps --format json 2>/dev/null || true)"
fi

port="$(cat "$BASE/config/port" 2>/dev/null || echo "8888")"

if [ "$JSON" -eq 1 ]; then
  printf '{"version":"%s","docker":"%s","port":"%s","services":%s}\n' \
    "$version" "$docker_status" "$port" "${services_raw:-[]}"
  exit 0
fi

msg_status_start
echo "📦 XPanel version: $version"
if [ "$docker_status" = "ok" ]; then
  echo "🐳 Docker: OK"
else
  echo "🐳 Docker: STOPPED"
fi

echo "🌐 Panel port: $port"

if [ -f "$BASE/docker-compose.yml" ]; then
  echo "🚀 Services:"
  (cd "$BASE" && docker compose ps) || true
else
  echo "⚠️ docker-compose.yml not found in $BASE"
fi
