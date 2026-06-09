#!/usr/bin/env bash
set -e

ACTION="$1"
ARG1="$2"
ARG2="$3"
BASE="${XPANEL_BASE:-/opt/xpanel}"
ENV_FILE="$BASE/.env"
LANG_FILE="$BASE/config/lang"
XPANEL_LANG="en"
[ -f "$LANG_FILE" ] && XPANEL_LANG="$(cat "$LANG_FILE")"
source "$BASE/installer/lib/i18n.sh"
load_xpanel_lang "$BASE/installer/lang" "$XPANEL_LANG"

check_dns() {
  local d="$1"
  [ -n "$d" ] || d="$(grep '^XPANEL_DOMAIN=' "$ENV_FILE" | cut -d= -f2-)"
  [ -n "$d" ] || { msg_ssl_domain_not_set; return 1; }
  if getent ahosts "$d" >/dev/null 2>&1; then
    msg_ssl_dns_ok "$d"
  else
    msg_ssl_dns_fail "$d"
    return 1
  fi
}

case "$ACTION" in
  status)
    DOMAIN="$ARG1"
    if [ -f "$BASE/traefik/acme.json" ] && [ -s "$BASE/traefik/acme.json" ]; then
      msg_ssl_acme_present
    else
      msg_ssl_acme_missing
    fi

    if [ -n "$DOMAIN" ] && [ -f "$BASE/traefik/acme.json" ]; then
      if grep -q "$DOMAIN" "$BASE/traefik/acme.json"; then
        msg_ssl_cert_found "$DOMAIN"
      else
        msg_ssl_cert_not_found "$DOMAIN"
      fi
    fi
    ;;

  check|verificar)
    check_dns "$ARG1"
    if docker ps --format '{{.Names}}' | grep -Fxq xpanel-proxy; then
      msg_ssl_proxy_running
    else
      msg_ssl_proxy_not_running
      exit 1
    fi
    ;;

  setup)
    EMAIL="$ARG1"
    TOKEN="$ARG2"
    if [ -z "$EMAIL" ] || [ -z "$TOKEN" ]; then
      msg_ssl_usage_setup
      exit 1
    fi

    if [ ! -f "$ENV_FILE" ]; then
      msg_ssl_env_missing "$ENV_FILE"
      exit 1
    fi

    if grep -q '^XPANEL_ACME_EMAIL=' "$ENV_FILE"; then
      sed -i "s|^XPANEL_ACME_EMAIL=.*|XPANEL_ACME_EMAIL=$EMAIL|" "$ENV_FILE"
    else
      echo "XPANEL_ACME_EMAIL=$EMAIL" >> "$ENV_FILE"
    fi
    if grep -q '^CF_API_EMAIL=' "$ENV_FILE"; then
      sed -i "s|^CF_API_EMAIL=.*|CF_API_EMAIL=$EMAIL|" "$ENV_FILE"
    else
      echo "CF_API_EMAIL=$EMAIL" >> "$ENV_FILE"
    fi
    if grep -q '^CF_DNS_API_TOKEN=' "$ENV_FILE"; then
      sed -i "s|^CF_DNS_API_TOKEN=.*|CF_DNS_API_TOKEN=$TOKEN|" "$ENV_FILE"
    else
      echo "CF_DNS_API_TOKEN=$TOKEN" >> "$ENV_FILE"
    fi

    msg_ssl_saved "$ENV_FILE"
    ;;

  renew)
    cd "$BASE"
    docker compose up -d proxy
    docker compose restart proxy
    msg_ssl_restarted
    ;;

  *)
    msg_ssl_usage
    exit 1
    ;;
esac
