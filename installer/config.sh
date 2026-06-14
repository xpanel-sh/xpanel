#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "$0")/lib/loader.sh"

BASE="${XPANEL_BASE:-/opt/xpanel}"
ENV_FILE="$BASE/.env"
CONF_DIR="$BASE/config"
mkdir -p "$CONF_DIR"

ACTION="${1:-}"
KEY="${2:-}"
VALUE="${3:-}"

usage() {
  msg_config_usage
}

get_key() {
  case "$1" in
    domain) grep -E '^XPANEL_DOMAIN=' "$ENV_FILE" 2>/dev/null | cut -d= -f2- ;;
    port) cat "$CONF_DIR/port" 2>/dev/null || echo "8888" ;;
    lang|language|idioma) cat "$CONF_DIR/lang" 2>/dev/null || echo "en" ;;
    admin-login-path) grep -E '^XPANEL_ADMIN_LOGIN_PATH=' "$BASE/panel/.env" 2>/dev/null | cut -d= -f2- || echo "admin/login" ;;
    client-login-path) grep -E '^XPANEL_CLIENT_LOGIN_PATH=' "$BASE/panel/.env" 2>/dev/null | cut -d= -f2- || echo "client/login" ;;
    *) return 1 ;;
  esac
}

upsert_file_key() {
  local file="$1"
  local key="$2"
  local value="$3"
  [ -f "$file" ] || touch "$file"
  if grep -q "^${key}=" "$file"; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$file"
  else
    echo "${key}=${value}" >> "$file"
  fi
}

normalize_path() {
  echo "$1" | sed 's|^/||;s|/$||'
}

sync_login_url() {
  local admin_path url
  admin_path="$(get_key admin-login-path)"
  url="$(grep '^URL=' "$CONF_DIR/access.info" 2>/dev/null | cut -d= -f2- || true)"
  [ -n "$url" ] || return 0
  upsert_file_key "$CONF_DIR/access.info" "LOGIN_URL" "$url/$admin_path"
}

set_key() {
  case "$1" in
    domain)
      upsert_file_key "$ENV_FILE" "XPANEL_DOMAIN" "$2"
      ;;
    port)
      save_port "$2"
      ;;
    lang|language|idioma)
      set_language "$2"
      ;;
    admin-login-path)
      value="$(normalize_path "$2")"
      [ -n "$value" ] || return 1
      upsert_file_key "$BASE/panel/.env" "XPANEL_ADMIN_LOGIN_PATH" "$value"
      sync_login_url
      docker exec xpanel-web php artisan optimize:clear >/dev/null 2>&1 || true
      ;;
    client-login-path)
      value="$(normalize_path "$2")"
      [ -n "$value" ] || return 1
      upsert_file_key "$BASE/panel/.env" "XPANEL_CLIENT_LOGIN_PATH" "$value"
      docker exec xpanel-web php artisan optimize:clear >/dev/null 2>&1 || true
      ;;
    *) return 1 ;;
  esac
}

case "$ACTION" in
  get)
    [ -n "$KEY" ] || { usage; exit 1; }
    get_key "$KEY" || { msg_config_unknown_key "$KEY"; exit 1; }
    ;;
  set)
    [ -n "$KEY" ] && [ -n "$VALUE" ] || { usage; exit 1; }
    set_key "$KEY" "$VALUE" || { msg_config_unknown_key "$KEY"; exit 1; }
    msg_ok_done
    ;;
  list)
    echo "domain=$(get_key domain || true)"
    echo "port=$(get_key port || true)"
    echo "lang=$(get_key lang || true)"
    echo "admin-login-path=$(get_key admin-login-path || true)"
    echo "client-login-path=$(get_key client-login-path || true)"
    ;;
  *) usage; exit 1 ;;
esac
