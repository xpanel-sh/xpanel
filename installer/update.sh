#!/usr/bin/env bash
set -euo pipefail

XPANEL_LANG="${1:-en}"
BASE="${XPANEL_BASE:-/opt/xpanel}"
REPO="${XPANEL_REPO:-xpanel-sh/xpanel}"
BRANCH="${XPANEL_BRANCH:-main}"
DIR="$(cd "$(dirname "$0")" && pwd)"
TMP="/tmp/xpanel-update"
CHECK_ONLY="${XPANEL_UPDATE_CHECK_ONLY:-0}"
MODE="${2:-apply}" # apply|rollback
ROLLBACK_FILE="${3:-}"
SNAPSHOT_DIR="$BASE/backups/updates"
# After git pull this script re-execs itself so the new version runs the build phase.
XPANEL_PULLED="${XPANEL_PULLED:-0}"

source "$DIR/lib/i18n.sh"
load_xpanel_lang "$DIR/lang" "$XPANEL_LANG"

version_ge() {
  [ "$1" = "$2" ] && return 0
  [ "$(printf '%s\n%s' "$1" "$2" | sort -V | tail -n1)" = "$1" ]
}

snapshot_before_update() {
  mkdir -p "$SNAPSHOT_DIR"
  local ts file
  ts="$(date +"%Y%m%d-%H%M%S")"
  file="$SNAPSHOT_DIR/xpanel-update-snapshot-$ts.tar.gz"
  tar -czf "$file" \
    "$BASE/installer" \
    "$BASE/panel" \
    "$BASE/daemon-src" \
    "$BASE/daemon" \
    "$BASE/traefik" \
    "$BASE/dns" \
    "$BASE/docker-compose.yml" \
    "$BASE/.env" \
    "$BASE/VERSION" 2>/dev/null || true
  echo "$file"
}

upsert_env_line() {
  local file="$1"
  local key="$2"
  local value="$3"

  touch "$file"
  if grep -q "^${key}=" "$file"; then
    sed -i "s|^${key}=.*|${key}=${value}|g" "$file"
  else
    echo "${key}=${value}" >> "$file"
  fi
}

ensure_daemon_token() {
  local token_file="$BASE/config/daemon.key"
  local panel_env="$BASE/panel/.env"
  local token

  mkdir -p "$BASE/config"
  if [ ! -s "$token_file" ]; then
    if ! command -v openssl >/dev/null 2>&1; then
      echo "[WARN] openssl not found; cannot generate daemon token automatically"
      return 0
    fi
    openssl rand -base64 32 | tr -d '/+=' | cut -c1-40 > "$token_file"
    chmod 600 "$token_file"
  fi

  token="$(tr -d '[:space:]' < "$token_file")"
  if [ -n "$token" ] && [ -f "$panel_env" ]; then
    upsert_env_line "$panel_env" "XPANEL_DAEMON_URL" "http://host.docker.internal:7070"
    upsert_env_line "$panel_env" "XPANEL_DAEMON_TOKEN" "$token"
  fi
}

ensure_acme_email() {
  local env_file="$BASE/.env"
  local access_file="$BASE/config/access.info"
  local email

  [ -f "$env_file" ] || return 0
  if grep -q '^XPANEL_ACME_EMAIL=' "$env_file"; then
    return 0
  fi

  email="$(grep '^USER=' "$access_file" 2>/dev/null | cut -d= -f2- || true)"
  if [ -z "$email" ]; then
    email="admin@$(grep '^XPANEL_DOMAIN=' "$env_file" 2>/dev/null | cut -d= -f2- || echo "example.com")"
  fi

  upsert_env_line "$env_file" "XPANEL_ACME_EMAIL" "$email"
}

ensure_login_paths() {
  local panel_env="$BASE/panel/.env"
  local client_path

  [ -f "$panel_env" ] || return 0

  if ! grep -q '^XPANEL_ADMIN_LOGIN_PATH=' "$panel_env"; then
    upsert_env_line "$panel_env" "XPANEL_ADMIN_LOGIN_PATH" "admin/login"
  fi

  client_path="$(grep '^XPANEL_CLIENT_LOGIN_PATH=' "$panel_env" 2>/dev/null | cut -d= -f2- || true)"
  if [ -z "$client_path" ] || [ "$client_path" = "login" ]; then
    upsert_env_line "$panel_env" "XPANEL_CLIENT_LOGIN_PATH" "client/login"
  fi

  if ! grep -q '^XPANEL_ADMIN_BASE_PATH=' "$panel_env"; then
    upsert_env_line "$panel_env" "XPANEL_ADMIN_BASE_PATH" "admin"
  fi
}

do_rollback() {
  [ -n "$ROLLBACK_FILE" ] || ROLLBACK_FILE="$(ls -1t "$SNAPSHOT_DIR"/xpanel-update-snapshot-*.tar.gz 2>/dev/null | head -n1)"
  [ -n "$ROLLBACK_FILE" ] || { msg_rollback_not_found; exit 1; }
  [ -f "$ROLLBACK_FILE" ] || { msg_backup_not_found "$ROLLBACK_FILE"; exit 1; }

  tar -xzf "$ROLLBACK_FILE" -C /
  cd "$BASE"
  docker compose up -d --build
  systemctl daemon-reload || true
  systemctl restart xpanel-daemon || true
  msg_rollback_complete "$ROLLBACK_FILE"
}

[ "$XPANEL_PULLED" = "0" ] && msg_update_start

if [ "$MODE" = "rollback" ]; then
  do_rollback
  exit 0
fi

# ── Phase 1: fetch new code (only when not already done by a prior exec) ──────
if [ "$XPANEL_PULLED" = "0" ]; then
  LOCAL_VERSION="0.0.0"
  [ -f "$BASE/VERSION" ] && LOCAL_VERSION="$(tr -d '[:space:]' < "$BASE/VERSION")"

  REMOTE_VERSION="$(curl -fsSL -H "Cache-Control: no-cache" -H "Pragma: no-cache" \
    "https://raw.githubusercontent.com/$REPO/$BRANCH/VERSION?_=$(date +%s)" 2>/dev/null || echo "")"
  REMOTE_VERSION="$(echo "$REMOTE_VERSION" | tr -d '[:space:]')"

  if [ -z "$REMOTE_VERSION" ]; then
    msg_update_remote_fetch_error "$REPO" "$BRANCH"
    exit 1
  fi

  msg_update_local_version "$LOCAL_VERSION"
  msg_update_remote_version "$REMOTE_VERSION"

  if version_ge "$LOCAL_VERSION" "$REMOTE_VERSION"; then
    msg_update_uptodate
    exit 0
  fi

  if [ "$CHECK_ONLY" = "1" ] || [ "$MODE" = "dry-run" ]; then
    msg_update_available "$LOCAL_VERSION" "$REMOTE_VERSION"
    exit 10
  fi

  snapshot_file="$(snapshot_before_update)"
  msg_snapshot_created "$snapshot_file"

  if [ -d "$BASE/.git" ]; then
    git -C "$BASE" fetch --all --tags
    if [ -n "$(git -C "$BASE" status --porcelain)" ]; then
      STASH_NAME="xpanel-auto-stash-$(date +"%Y%m%d-%H%M%S")"
      git -C "$BASE" stash push -u -m "$STASH_NAME" >/dev/null
      echo "Local changes were stashed before update: $STASH_NAME"
    fi
    git -C "$BASE" checkout "$BRANCH"
    git -C "$BASE" pull --ff-only origin "$BRANCH"

    # Restore execute bits on all shell scripts (git may strip +x on pull)
    find "$BASE/installer" -name "*.sh" -exec chmod +x {} \;
    chmod +x "$BASE/install.sh" 2>/dev/null || true
    # Re-ensure the CLI symlink target is executable
    [ -L /usr/local/bin/xpanel ] || ln -sf "$BASE/installer/cli.sh" /usr/local/bin/xpanel
    chmod +x /usr/local/bin/xpanel 2>/dev/null || true
  else
    rm -rf "$TMP"
    mkdir -p "$TMP"
    curl -fsSL "https://codeload.github.com/$REPO/tar.gz/refs/heads/$BRANCH" -o "$TMP/repo.tgz"
    tar -xzf "$TMP/repo.tgz" -C "$TMP"

    SRC="$TMP/xpanel-$BRANCH"
    [ -d "$SRC" ] || SRC="$(find "$TMP" -maxdepth 1 -type d -name 'xpanel-*' | head -n1)"
    if [ -z "$SRC" ] || [ ! -d "$SRC" ]; then
      msg_update_payload_error
      exit 1
    fi

    mkdir -p "$BASE"/{installer,panel,daemon-src,traefik,dns}
    if command -v rsync >/dev/null 2>&1; then
      rsync -a --delete "$SRC/installer/" "$BASE/installer/"
      rsync -a --delete "$SRC/panel/" "$BASE/panel/"
      rsync -a --delete "$SRC/daemon/" "$BASE/daemon-src/"
      rsync -a --delete "$SRC/traefik/" "$BASE/traefik/"
      rsync -a --delete "$SRC/dns/" "$BASE/dns/" 2>/dev/null || true
    else
      rm -rf "$BASE/installer" "$BASE/panel" "$BASE/daemon-src" "$BASE/traefik" "$BASE/dns"
      mkdir -p "$BASE"/{installer,panel,daemon-src,traefik,dns}
      cp -a "$SRC/installer/." "$BASE/installer/"
      cp -a "$SRC/panel/." "$BASE/panel/"
      cp -a "$SRC/daemon/." "$BASE/daemon-src/"
      cp -a "$SRC/traefik/." "$BASE/traefik/"
      cp -a "$SRC/dns/." "$BASE/dns/" 2>/dev/null || true
    fi
    cp -f "$SRC/docker-compose.yml" "$BASE/docker-compose.yml"
    cp -f "$SRC/VERSION" "$BASE/VERSION"
  fi

  # Re-exec this script from the freshly-pulled version so the build phase
  # always runs the latest code regardless of bash's read-ahead buffering.
  export XPANEL_PULLED=1
  exec bash "$BASE/installer/update.sh" "$XPANEL_LANG" "$MODE"
fi

# ── Phase 2: build & deploy (runs from the newly-pulled update.sh) ─────────
# Detect daemon source: git installs use BASE/daemon, tar installs use BASE/daemon-src
DAEMON_SRC_DIR=""
if [ -f "$BASE/daemon/go.mod" ]; then
  DAEMON_SRC_DIR="$BASE/daemon"
elif [ -f "$BASE/daemon-src/go.mod" ]; then
  DAEMON_SRC_DIR="$BASE/daemon-src"
fi

if command -v go >/dev/null 2>&1 && [ -n "$DAEMON_SRC_DIR" ]; then
  (
    cd "$DAEMON_SRC_DIR"
    go mod download all || true
    if go build -o xpanel-daemon-new .; then
      mkdir -p "$BASE/daemon"
      mv -f xpanel-daemon-new "$BASE/daemon/xpanel-daemon"
      chmod +x "$BASE/daemon/xpanel-daemon"
      echo "Daemon compilado correctamente."
    else
      rm -f xpanel-daemon-new
      echo "[WARN] No se pudo compilar el daemon; se conserva el binario anterior."
    fi
  ) || true
elif command -v go >/dev/null 2>&1; then
  echo "[WARN] No se encontró go.mod del daemon; se omite compilación."
fi

bash "$BASE/installer/migrate.sh" "$XPANEL_LANG" || true
ensure_daemon_token
ensure_acme_email
ensure_login_paths

cd "$BASE"
docker compose up -d --build
systemctl daemon-reload || true
systemctl restart xpanel-daemon || true

# Rebuild PHP site images so pdo_mysql and other extensions stay up to date
PHP_DOCKERFILE="$BASE/docker/php/Dockerfile"
if [ -f "$PHP_DOCKERFILE" ]; then
  for PHP_VERSION in 8.1 8.2 8.3 8.4; do
    docker build --build-arg PHP_VERSION="$PHP_VERSION" \
      -t "xpanel-php:$PHP_VERSION-apache" \
      -f "$PHP_DOCKERFILE" "$(dirname "$PHP_DOCKERFILE")" \
      >/dev/null 2>&1 && echo "  ✓ xpanel-php:$PHP_VERSION-apache" || echo "  ⚠ xpanel-php:$PHP_VERSION-apache falló"
  done
fi

msg_done
