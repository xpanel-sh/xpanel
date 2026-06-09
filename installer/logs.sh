#!/usr/bin/env bash
set -e
source "$(dirname "$0")/lib/loader.sh"

BASE="${XPANEL_BASE:-/opt/xpanel}"
FOLLOW=0
SINCE=""
LINES=""
SERVICE=""

map_service() {
  case "$1" in
    panel|web) echo "panel" ;;
    db|database|mariadb) echo "db" ;;
    redis|cache) echo "redis" ;;
    proxy|traefik) echo "proxy" ;;
    *) echo "$1" ;;
  esac
}

while [ $# -gt 0 ]; do
  case "$1" in
    -f|--follow) FOLLOW=1 ;;
    --since) shift; SINCE="$1" ;;
    --lines|-n) shift; LINES="$1" ;;
    *) [ -z "$SERVICE" ] && SERVICE="$1" ;;
  esac
  shift || true
done

msg_logs_start
cd "$BASE"

args=(logs)
[ "$FOLLOW" -eq 1 ] && args+=( -f )
[ -n "$SINCE" ] && args+=( --since "$SINCE" )
[ -n "$LINES" ] && args+=( --tail "$LINES" )

if [ -n "$SERVICE" ]; then
  SERVICE="$(map_service "$SERVICE")"
  args+=( "$SERVICE" )
fi

docker compose "${args[@]}"
