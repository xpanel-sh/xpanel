#!/usr/bin/env bash
set -e

BASE="${XPANEL_BASE:-/opt/xpanel}"
LANG_FILE="$BASE/config/lang"
XPANEL_LANG="en"
[ -f "$LANG_FILE" ] && XPANEL_LANG="$(cat "$LANG_FILE")"
source "$BASE/installer/lib/i18n.sh"
load_xpanel_lang "$BASE/installer/lang" "$XPANEL_LANG"

ACTION="$1"
DOMAIN="$2"
TYPE="${3:-php}"
WEB_SERVER="${4:-apache}"
PHP_VERSION="${5:-8.2}"
OUTPUT="${XPANEL_OUTPUT:-text}"

sanitize_domain() {
  echo "$1" | tr '[:upper:]' '[:lower:]' | tr -cd 'a-z0-9.-'
}

container_name_from_domain() {
  local d
  d=$(sanitize_domain "$1")
  echo "xpanel-site-${d//./-}"
}

daemon_token() {
  tr -d '[:space:]' < "$BASE/config/daemon.key" 2>/dev/null || true
}

daemon_post() {
  local path="$1"
  local payload="$2"
  local token
  token="$(daemon_token)"
  [ -n "$token" ] || { echo "Missing daemon token: $BASE/config/daemon.key"; exit 1; }

  curl -fsS -X POST "http://127.0.0.1:7070$path" \
    -H "Content-Type: application/json" \
    -H "X-XPanel-Token: $token" \
    -d "$payload"
}

confirm_destructive() {
  if [ "${XPANEL_YES:-0}" = "1" ]; then
    return 0
  fi
  msg_confirm_prompt
  read -r ans
  [ "$ans" = "yes" ]
}

case "$ACTION" in
  list)
    if [ "$DOMAIN" = "--json" ] || [ "$OUTPUT" = "json" ]; then
      docker ps --filter name=xpanel-site- --format '{{json .}}' | sed '1s/^/[/' | sed '$s/$/]/' | sed '$!s/$/,/'
    else
      docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | (head -n 1 && grep 'xpanel-site-' || true)
    fi
    ;;

  create)
    if [ -z "$DOMAIN" ]; then
      msg_site_usage_create
      exit 1
    fi

    daemon_post "/api/site/create" "{\"name\":\"$(container_name_from_domain "$DOMAIN")\",\"domain\":\"$DOMAIN\",\"type\":\"$TYPE\",\"web_server\":\"$WEB_SERVER\",\"php_version\":\"$PHP_VERSION\"}" \
      && msg_site_provision_sent "$DOMAIN"
    ;;

  restart|reiniciar)
    if [ -z "$DOMAIN" ]; then
      msg_site_usage_delete
      exit 1
    fi
    NAME=$(container_name_from_domain "$DOMAIN")
    daemon_post "/api/site/restart" "{\"name\":\"$NAME\"}" >/dev/null
    msg_site_restarted "$NAME"
    ;;

  delete|remove|eliminar)
    if [ -z "$DOMAIN" ]; then
      msg_site_usage_delete
      exit 1
    fi

    NAME=$(container_name_from_domain "$DOMAIN")
    confirm_destructive || { msg_cancelled; exit 1; }
    daemon_post "/api/site/delete" "{\"name\":\"$NAME\"}" >/dev/null
    msg_site_removed "$NAME"
    ;;

  *)
    msg_site_usage
    exit 1
    ;;
esac
