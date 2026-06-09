#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "$0")/lib/loader.sh"

BASE="${XPANEL_BASE:-/opt/xpanel}"
FAIL=0
WARN=0

ok() { echo "[OK] $1"; }
warn() { echo "[WARN] $1"; WARN=$((WARN+1)); }
fail() { echo "[FAIL] $1"; FAIL=$((FAIL+1)); }

msg_status_start

test -d "$BASE" && ok "Base path exists: $BASE" || fail "Base path missing: $BASE"

if command -v docker >/dev/null 2>&1; then ok "Docker installed"; else fail "Docker not installed"; fi
if docker info >/dev/null 2>&1; then ok "Docker daemon reachable"; else fail "Docker daemon unreachable"; fi

if docker network inspect xpanel-net >/dev/null 2>&1; then ok "Network xpanel-net exists"; else warn "Network xpanel-net missing"; fi
if docker ps --format '{{.Names}}' | grep -q '^xpanel-docker-socket-proxy$'; then
  ok "Docker socket proxy running"
  if docker run --rm --network xpanel-net curlimages/curl:8.11.1 -fsS http://docker-socket-proxy:2375/version >/dev/null 2>&1; then
    ok "Docker socket proxy API reachable from xpanel-net"
  else
    warn "Docker socket proxy API not reachable from xpanel-net"
  fi
else
  warn "Docker socket proxy not running"
fi

if systemctl is-active xpanel-daemon >/dev/null 2>&1; then ok "xpanel-daemon service active"; else warn "xpanel-daemon service not active"; fi

if [ -f "$BASE/docker-compose.yml" ]; then
  ok "docker-compose.yml present"
else
  fail "docker-compose.yml missing"
fi

if [ -f "$BASE/.env" ]; then
  ok ".env present"
  ACME_EMAIL_LINE="$(grep '^XPANEL_ACME_EMAIL=' "$BASE/.env" 2>/dev/null || true)"
  [ -n "$ACME_EMAIL_LINE" ] && ok "ACME email configured" || warn "XPANEL_ACME_EMAIL missing in .env"
  CF_TOKEN_LINE="$(grep '^CF_DNS_API_TOKEN=' "$BASE/.env" 2>/dev/null || true)"
  if [ -n "$CF_TOKEN_LINE" ] && [ "$CF_TOKEN_LINE" != "CF_DNS_API_TOKEN=" ]; then
    ok "Cloudflare DNS token configured for optional DNS challenge"
  else
    ok "Cloudflare DNS token not configured (HTTP challenge is default)"
  fi
else
  warn ".env missing"
fi

if [ -f "$BASE/panel/.env" ]; then
  ok "panel/.env present"
  APP_URL_LINE="$(grep '^APP_URL=' "$BASE/panel/.env" 2>/dev/null || true)"
  [ -n "$APP_URL_LINE" ] && ok "$APP_URL_LINE" || warn "APP_URL missing in panel/.env"
  APP_KEY_LINE="$(grep '^APP_KEY=' "$BASE/panel/.env" 2>/dev/null || true)"
  if [ -n "$APP_KEY_LINE" ] && [ "$APP_KEY_LINE" != "APP_KEY=" ]; then
    ok "APP_KEY configured"
  else
    warn "APP_KEY missing"
  fi
  DAEMON_TOKEN_LINE="$(grep '^XPANEL_DAEMON_TOKEN=' "$BASE/panel/.env" 2>/dev/null || true)"
  if [ -n "$DAEMON_TOKEN_LINE" ] && [ "$DAEMON_TOKEN_LINE" != "XPANEL_DAEMON_TOKEN=" ]; then
    ok "Panel daemon token configured"
  else
    warn "XPANEL_DAEMON_TOKEN missing in panel/.env"
  fi
else
  warn "panel/.env missing"
fi

if [ -f "$BASE/config/daemon.key" ]; then
  ok "daemon.key present"
else
  warn "daemon.key missing"
fi

if [ -f "$BASE/config/daemon.key" ]; then
  DAEMON_TOKEN="$(tr -d '[:space:]' < "$BASE/config/daemon.key" 2>/dev/null || true)"
  if [ -n "$DAEMON_TOKEN" ]; then
    DAEMON_CODE="$(curl -s -o /dev/null -w '%{http_code}' -H "X-XPanel-Token: $DAEMON_TOKEN" "http://127.0.0.1:7070/api/operations" || true)"
    if [ "$DAEMON_CODE" = "200" ]; then
      ok "Protected daemon API reachable"
    else
      warn "Protected daemon API not reachable (HTTP $DAEMON_CODE)"
    fi
  fi
fi

if [ -d "$BASE/panel/storage" ] && [ -d "$BASE/panel/bootstrap/cache" ]; then
  ok "Laravel writable directories present"
else
  warn "Laravel writable directories missing"
fi

if [ -f "$BASE/dns/coredns/Corefile" ]; then
  ok "Optional CoreDNS profile files present"
else
  warn "Optional CoreDNS profile files missing"
fi

if [ -d "$BASE/runtime/daemon/dns/zones" ]; then
  ZONE_COUNT="$(find "$BASE/runtime/daemon/dns/zones" -maxdepth 1 -type f -name '*.zone' 2>/dev/null | wc -l)"
  ok "DNS zone artifact directory present ($ZONE_COUNT zones)"
else
  ok "DNS zone artifact directory not created yet"
fi

if docker ps --format '{{.Names}}' | grep -q '^xpanel-dns$'; then
  ok "Optional xpanel-dns service running"
else
  ok "Optional xpanel-dns service not running (enable with docker compose --profile dns up -d dns)"
fi

if [ -f "$BASE/config/access.info" ]; then
  ok "access.info present"
  URL_LINE="$(grep '^URL=' "$BASE/config/access.info" 2>/dev/null || true)"
  DOMAIN="$(echo "$URL_LINE" | sed 's|^URL=https\?://||;s|/.*||')"
  if [ -n "$DOMAIN" ]; then
    if getent ahosts "$DOMAIN" >/dev/null 2>&1; then ok "Domain resolves: $DOMAIN"; else warn "Domain does not resolve yet: $DOMAIN"; fi
  fi
else
  warn "access.info missing"
fi

if [ -f "$BASE/docker-compose.yml" ]; then
  cd "$BASE"
  if docker compose ps >/dev/null 2>&1; then
    ok "docker compose metadata accessible"
    DOWN_COUNT="$(docker compose ps --status exited --status dead --status restarting --services 2>/dev/null | wc -l)"
    [ "$DOWN_COUNT" -eq 0 ] && ok "No stopped/restarting services detected" || warn "Some services are stopped or restarting"

    UNHEALTHY_COUNT="$(docker ps --filter "name=xpanel-" --filter "health=unhealthy" --format '{{.Names}}' 2>/dev/null | wc -l)"
    [ "$UNHEALTHY_COUNT" -eq 0 ] && ok "No unhealthy containers detected" || warn "Some containers are unhealthy"
  else
    warn "docker compose ps failed"
  fi
fi

if docker ps --format '{{.Names}}' | grep -q '^xpanel-web$'; then
  if docker exec xpanel-web php artisan route:list --name=admin.login 2>/dev/null | grep -q 'admin.login'; then
    ok "Admin login route present"
  else
    fail "Admin login route missing"
  fi

  if docker exec xpanel-web php artisan route:list --name=client.login 2>/dev/null | grep -q 'client.login'; then
    ok "Client login route present"
  else
    fail "Client login route missing"
  fi

  if docker exec xpanel-web test -w storage/logs -a -w storage/framework/sessions -a -w bootstrap/cache 2>/dev/null; then
    ok "Laravel storage writable inside container"
  else
    warn "Laravel storage may not be writable inside container"
  fi

  URL_LINE="$(grep '^URL=' "$BASE/config/access.info" 2>/dev/null || true)"
  DOMAIN="$(echo "$URL_LINE" | sed 's|^URL=https\?://||;s|/.*||')"
  if [ -n "$DOMAIN" ]; then
    ADMIN_CODE="$(curl -s -o /dev/null -w '%{http_code}' -H "Host: $DOMAIN" "http://127.0.0.1/admin/login" || true)"
    if [ "$ADMIN_CODE" = "200" ] || [ "$ADMIN_CODE" = "302" ]; then
      ok "Admin login endpoint reachable via proxy"
    else
      warn "Admin login endpoint not reachable via proxy (HTTP $ADMIN_CODE)"
    fi
  fi
fi

if docker ps --format '{{.Names}}' | grep -q '^xpanel-db$'; then
  MYSQL_ROOT_PASSWORD="$(grep '^MYSQL_ROOT_PASSWORD=' "$BASE/.env" 2>/dev/null | cut -d= -f2- || true)"
  if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
    if docker exec xpanel-db mariadb-admin ping -h 127.0.0.1 -u root "-p$MYSQL_ROOT_PASSWORD" --silent >/dev/null 2>&1; then
      ok "MariaDB root access works for daemon database operations"
    else
      warn "MariaDB root access failed; database provisioning may fail"
    fi
  else
    warn "MYSQL_ROOT_PASSWORD missing in .env"
  fi
fi

msg_doctor_summary "$FAIL" "$WARN"
[ "$FAIL" -eq 0 ]
