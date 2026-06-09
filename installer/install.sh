#!/usr/bin/env bash
set -euo pipefail

# ===============================
# VARIABLES BASE
# ===============================
XPANEL_LANG="en"
BASE="${XPANEL_BASE:-/opt/xpanel}"
DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_ROOT="$(cd "$DIR/.." && pwd)"
PROJECT_VERSION="1.0.0"
if [ -f "$SRC_ROOT/VERSION" ]; then
  PROJECT_VERSION="$(tr -d '[:space:]' < "$SRC_ROOT/VERSION")"
fi

NON_INTERACTIVE=0
DOMAIN_INPUT=""
USER_EMAIL="admin@xpanel.com"
PASS=""
CF_DNS_API_TOKEN="${CF_DNS_API_TOKEN:-}"
CF_API_EMAIL="${CF_API_EMAIL:-}"
FRESH_INSTALL=0
LOCK_FILE="${XPANEL_INSTALL_LOCK:-/tmp/xpanel-install.lock}"

cleanup_lock() {
  rm -f "$LOCK_FILE"
}

acquire_lock() {
  if [ -f "$LOCK_FILE" ]; then
    old_pid="$(cat "$LOCK_FILE" 2>/dev/null || true)"
    if [ -n "$old_pid" ] && kill -0 "$old_pid" 2>/dev/null; then
      echo "[ERROR] Another XPanel install is running (pid: $old_pid, lock: $LOCK_FILE)"
      exit 1
    fi
    echo "[WARN] Removing stale installer lock: $LOCK_FILE"
    rm -f "$LOCK_FILE"
  fi

  echo "$$" > "$LOCK_FILE"
  trap cleanup_lock EXIT INT TERM
}

cleanup_known_stack() {
  local containers=(
    xpanel-web
    xpanel-db
    xpanel-redis
    xpanel-proxy
    xpanel-docker-socket-proxy
    xpanel-dns
  )
  local volumes=(
    xpanel_mysql_data
  )

  docker rm -f "${containers[@]}" >/dev/null 2>&1 || true
  docker volume rm "${volumes[@]}" >/dev/null 2>&1 || true
}

while [ $# -gt 0 ]; do
  case "$1" in
    es|en)
      XPANEL_LANG="$1"
      shift
      ;;
    --domain)
      [ $# -ge 2 ] || { echo "Missing value for --domain"; exit 1; }
      DOMAIN_INPUT="${2:-}"
      shift 2
      ;;
    --email)
      [ $# -ge 2 ] || { echo "Missing value for --email"; exit 1; }
      USER_EMAIL="${2:-}"
      shift 2
      ;;
    --password)
      [ $# -ge 2 ] || { echo "Missing value for --password"; exit 1; }
      PASS="${2:-}"
      shift 2
      ;;
    --cf-token)
      [ $# -ge 2 ] || { echo "Missing value for --cf-token"; exit 1; }
      CF_DNS_API_TOKEN="${2:-}"
      shift 2
      ;;
    --cf-email)
      [ $# -ge 2 ] || { echo "Missing value for --cf-email"; exit 1; }
      CF_API_EMAIL="${2:-}"
      shift 2
      ;;
    --yes|--non-interactive)
      NON_INTERACTIVE=1
      shift
      ;;
    --fresh|fresh)
      FRESH_INSTALL=1
      shift
      ;;
    *)
      echo "Unknown argument: $1"
      echo "Usage: bash installer/install.sh [es|en] [--yes] [--fresh] [--domain DOMAIN] [--email EMAIL] [--password PASS] [--cf-token TOKEN] [--cf-email EMAIL]"
      exit 1
      ;;
  esac
done

# ===============================
# ROOT CHECK
# ===============================
if [ "$EUID" -ne 0 ]; then
  echo "This installer must be run as root."
  exit 1
fi

acquire_lock

# ===============================
# CARGAR IDIOMA
# ===============================
if [ ! -f "$DIR/lang/$XPANEL_LANG.sh" ]; then
  echo "Unsupported language: $XPANEL_LANG"
  exit 1
fi

source "$DIR/lib/i18n.sh"
load_xpanel_lang "$DIR/lang" "$XPANEL_LANG"

msg_start

if [ "$FRESH_INSTALL" -eq 1 ]; then
  msg_fresh_start
  systemctl stop xpanel-daemon 2>/dev/null || true
  systemctl disable xpanel-daemon 2>/dev/null || true
  rm -f /etc/systemd/system/xpanel-daemon.service
  systemctl daemon-reload 2>/dev/null || true

  if [ -f "$BASE/docker-compose.yml" ]; then
    (cd "$BASE" && docker compose down -v --remove-orphans) || true
  fi
  cleanup_known_stack

  if [ "$SRC_ROOT" = "$BASE" ]; then
    rm -rf \
      "$BASE/.env" \
      "$BASE/config" \
      "$BASE/logs" \
      "$BASE/daemon-src" \
      "$BASE/daemon/xpanel-daemon" \
      "$BASE/traefik/acme.json" \
      "$BASE/panel/.env" \
      "$BASE/panel/storage/logs/"* \
      "$BASE/panel/storage/framework/cache/data/"* \
      "$BASE/panel/storage/framework/sessions/"* \
      "$BASE/panel/storage/framework/views/"* 2>/dev/null || true
  else
    rm -rf \
      "$BASE/panel" \
      "$BASE/daemon" \
      "$BASE/daemon-src" \
      "$BASE/config" \
      "$BASE/logs" \
      "$BASE/installer" \
      "$BASE/traefik" \
      "$BASE/.env" \
      "$BASE/docker-compose.yml" \
      "$BASE/VERSION"
  fi
  msg_fresh_done
fi

# ===============================
# VALIDAR ESTRUCTURA FUENTE
# ===============================
if [ ! -f "$SRC_ROOT/panel/.env.example" ] || [ ! -f "$SRC_ROOT/panel/artisan" ] || [ ! -f "$SRC_ROOT/docker-compose.yml" ]; then
  msg_source_incomplete "$SRC_ROOT"
  msg_source_expected
  msg_source_tip_clone
  exit 1
fi

# ===============================
# CHECKS
# ===============================
msg_checks
bash "$DIR/checks.sh"
msg_checks_ok

# ===============================
# CREAR ESTRUCTURA BASE
# ===============================
msg_create_dirs
mkdir -p "$BASE"/{panel,daemon,daemon-src,config,logs,installer,traefik,dns,runtime}
msg_dirs_ok

# ===============================
# GUARDAR CONFIGURACIÓN
# ===============================
msg_load_env
echo "$XPANEL_LANG" > "$BASE/config/lang"
echo "$PROJECT_VERSION" > "$BASE/VERSION"
msg_env_ok

# ===============================
# CREDENCIALES INICIALES
# ===============================
msg_create_access
IP="$(curl -fsS https://ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')"
[ -n "$IP" ] || IP="127.0.0.1"
if [ -z "$PASS" ]; then
  PASS="$(openssl rand -base64 12 | tr -d '/+' | cut -c1-16)"
fi
DB_ROOT_PASS=$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)
DB_PASS=$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)
DAEMON_TOKEN=$(openssl rand -base64 32 | tr -d '/+=' | cut -c1-40)
ACME_EMAIL="$USER_EMAIL"

if [ "$NON_INTERACTIVE" -eq 0 ] && [ -z "$DOMAIN_INPUT" ]; then
  msg_ask_domain
  read -r DOMAIN_INPUT
fi

if [ -z "$DOMAIN_INPUT" ]; then
  echo "A production install requires a real domain for HTTPS certificates. Use --domain panel.example.com."
  exit 1
fi

if [ -n "$DOMAIN_INPUT" ]; then
  if [[ ! "$DOMAIN_INPUT" =~ ^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$ ]]; then
    msg_invalid_domain "$DOMAIN_INPUT"
    exit 1
  fi
  PANEL_DOMAIN="$DOMAIN_INPUT"
  PANEL_URL="https://$PANEL_DOMAIN"
  SESSION_DOMAIN=".$PANEL_DOMAIN"
else
  PANEL_DOMAIN="$IP"
  PANEL_URL="http://$IP"
  SESSION_DOMAIN=""
fi

cat <<EOF > "$BASE/config/access.info"
URL=$PANEL_URL
LOGIN_URL=$PANEL_URL/admin/login
USER=$USER_EMAIL
PASS=$PASS
DOMAIN=$PANEL_DOMAIN
EOF
echo "$DAEMON_TOKEN" > "$BASE/config/daemon.key"
chmod 600 "$BASE/config/daemon.key"
msg_access_ok

# ===============================
# DOCKER
# ===============================
msg_install_docker
if ! command -v docker >/dev/null 2>&1; then
  curl -fsSL https://get.docker.com | bash
fi
if ! docker compose version >/dev/null 2>&1; then
  msg_error_compose
  exit 1
fi
msg_docker_ok

prepull_site_images() {
  local images=(
    nginx:alpine
    node:18-alpine
    python:3.10-alpine
    php:8.0-apache
    php:8.1-apache
    php:8.2-apache
    php:8.3-apache
    serversideup/php:8.0-fpm-nginx
    serversideup/php:8.1-fpm-nginx
    serversideup/php:8.2-fpm-nginx
    serversideup/php:8.3-fpm-nginx
  )

  echo "Pre-pulling supported site images outside HTTP requests..."
  for image in "${images[@]}"; do
    docker image inspect "$image" >/dev/null 2>&1 || docker pull "$image" || echo "[WARN] Could not pre-pull $image; pull it manually before using that stack."
  done
}

prepull_site_images

# ===============================
# COPIAR SISTEMA
# ===============================
msg_copy_files
if [ "$SRC_ROOT" != "$BASE" ]; then
  cp -a "$DIR/." "$BASE/installer/"
  cp -a "$SRC_ROOT/panel/." "$BASE/panel/"
  cp -a "$SRC_ROOT/traefik/." "$BASE/traefik/"
  cp -a "$SRC_ROOT/dns/." "$BASE/dns/" 2>/dev/null || true
  cp -a "$SRC_ROOT/daemon/." "$BASE/daemon-src/"
  cp -a "$SRC_ROOT/docker-compose.yml" "$BASE/"
  cp -a "$SRC_ROOT/VERSION" "$BASE/" 2>/dev/null || true
fi
msg_files_ok

# Archivo .env para Docker Compose (secretos y dominio)
cat <<EOF > "$BASE/.env"
XPANEL_DOMAIN=$PANEL_DOMAIN
XPANEL_ACME_EMAIL=$ACME_EMAIL
CF_API_EMAIL=$CF_API_EMAIL
CF_DNS_API_TOKEN=$CF_DNS_API_TOKEN
MYSQL_ROOT_PASSWORD=$DB_ROOT_PASS
MYSQL_DATABASE=xpanel
MYSQL_USER=xpanel
MYSQL_PASSWORD=$DB_PASS
EOF

# Permisos para certificados TLS de Traefik
touch "$BASE/traefik/acme.json"
chmod 600 "$BASE/traefik/acme.json"

# ===============================
# CONFIGURAR PANEL (LARAVEL .ENV)
# ===============================
msg_init_laravel
PANEL_DIR="$BASE/panel"
if [ ! -f "$PANEL_DIR/.env" ]; then
  cp "$PANEL_DIR/.env.example" "$PANEL_DIR/.env"
fi

upsert_env() {
  local key="$1"
  local value="$2"
  local file="$3"
  if grep -q "^${key}=" "$file"; then
    sed -i "s|^${key}=.*|${key}=${value}|g" "$file"
  else
    echo "${key}=${value}" >> "$file"
  fi
}

upsert_env "APP_URL" "$PANEL_URL" "$PANEL_DIR/.env"
upsert_env "APP_ENV" "production" "$PANEL_DIR/.env"
upsert_env "APP_DEBUG" "false" "$PANEL_DIR/.env"
upsert_env "LOG_LEVEL" "warning" "$PANEL_DIR/.env"
upsert_env "DB_HOST" "db" "$PANEL_DIR/.env"
upsert_env "DB_PORT" "3306" "$PANEL_DIR/.env"
upsert_env "DB_DATABASE" "xpanel" "$PANEL_DIR/.env"
upsert_env "DB_USERNAME" "xpanel" "$PANEL_DIR/.env"
upsert_env "DB_PASSWORD" "$DB_PASS" "$PANEL_DIR/.env"
upsert_env "SESSION_DRIVER" "file" "$PANEL_DIR/.env"
upsert_env "SESSION_DOMAIN" "$SESSION_DOMAIN" "$PANEL_DIR/.env"
upsert_env "SESSION_SAME_SITE" "lax" "$PANEL_DIR/.env"
if [[ "$PANEL_URL" == https://* ]]; then
  upsert_env "SESSION_SECURE_COOKIE" "true" "$PANEL_DIR/.env"
else
  upsert_env "SESSION_SECURE_COOKIE" "false" "$PANEL_DIR/.env"
fi
upsert_env "XPANEL_ADMIN_LOGIN_PATH" "admin/login" "$PANEL_DIR/.env"
upsert_env "XPANEL_CLIENT_LOGIN_PATH" "login" "$PANEL_DIR/.env"
upsert_env "XPANEL_ADMIN_BASE_PATH" "admin" "$PANEL_DIR/.env"
upsert_env "XPANEL_DAEMON_URL" "http://host.docker.internal:7070" "$PANEL_DIR/.env"
upsert_env "XPANEL_DAEMON_TOKEN" "$DAEMON_TOKEN" "$PANEL_DIR/.env"

# Laravel runtime directories must be writable by the web process.
mkdir -p "$PANEL_DIR/storage" "$PANEL_DIR/bootstrap/cache"
chown -R 33:33 "$PANEL_DIR/storage" "$PANEL_DIR/bootstrap/cache" 2>/dev/null || true
chmod -R ug+rwX "$PANEL_DIR/storage" "$PANEL_DIR/bootstrap/cache" 2>/dev/null || true

# ===============================
# COMANDO GLOBAL
# ===============================
msg_create_cli
ln -sf "$BASE/installer/cli.sh" /usr/local/bin/xpanel
chmod +x /usr/local/bin/xpanel
if [ -d /etc/bash_completion.d ]; then
  cp -f "$BASE/installer/xpanel-completion.bash" /etc/bash_completion.d/xpanel
fi
msg_cli_ok

# ===============================
# INSTALAR GO
# ===============================
msg_install_go
if ! command -v go >/dev/null 2>&1; then
  curl -fsSL https://go.dev/dl/go1.21.5.linux-amd64.tar.gz -o /tmp/go.tar.gz
  rm -rf /usr/local/go
  tar -C /usr/local -xzf /tmp/go.tar.gz
  ln -sf /usr/local/go/bin/go /usr/local/bin/go
fi
msg_go_ok

# ===============================
# COMPILAR DAEMON
# ===============================
DAEMON_SRC="$BASE/daemon-src"
if [ ! -f "$DAEMON_SRC/go.mod" ]; then
  DAEMON_SRC="$SRC_ROOT/daemon"
fi

if [ -d "$DAEMON_SRC" ]; then
  msg_build_daemon
  cd "$DAEMON_SRC"
  go mod download || true
  go build -o xpanel-daemon
  mkdir -p "$BASE/daemon"
  SRC_BIN="$(pwd)/xpanel-daemon"
  DST_BIN="$BASE/daemon/xpanel-daemon"
  if [ "$SRC_BIN" != "$DST_BIN" ]; then
    cp xpanel-daemon "$BASE/daemon/"
  fi
  chmod +x "$BASE/daemon/xpanel-daemon"
  msg_daemon_ok
fi

# ===============================
# SYSTEMD SERVICE
# ===============================
msg_create_service

cat <<EOF > /etc/systemd/system/xpanel-daemon.service
[Unit]
Description=XPanel Daemon
After=network.target docker.service
Requires=docker.service

[Service]
Type=simple
ExecStart=$BASE/daemon/xpanel-daemon
Restart=always
RestartSec=5
User=root
Environment=XPANEL_DAEMON_PORT=7070
Environment=XPANEL_VERSION=$PROJECT_VERSION
Environment=XPANEL_DAEMON_TOKEN=$DAEMON_TOKEN
Environment=XPANEL_BASE=$BASE
NoNewPrivileges=true
PrivateTmp=true
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable xpanel-daemon
systemctl start xpanel-daemon
msg_service_ok

# ===============================
# DOCKER COMPOSE
# ===============================
msg_start_services
cd "$BASE"

# Crear red externa si no existe
docker network inspect xpanel-net >/dev/null 2>&1 || docker network create xpanel-net

docker compose up -d
msg_services_ok

# ===============================
# INICIALIZAR BASE DE DATOS Y ADMIN
# ===============================
msg_migrate
for i in $(seq 1 60); do
  if docker exec xpanel-db mariadb-admin ping -h 127.0.0.1 -u root "-p$DB_ROOT_PASS" --silent >/dev/null 2>&1; then
    break
  fi
  if [ "$i" -eq 60 ]; then
    echo "❌ MariaDB did not become ready in time"
    exit 1
  fi
  sleep 2
done
docker exec xpanel-web composer install --no-interaction --prefer-dist || true
docker exec xpanel-web sh -lc "mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache" || true
docker exec xpanel-web php artisan key:generate --force
docker exec xpanel-web php artisan migrate --force
docker exec xpanel-web php artisan migrate --force --path=database/migrations/admin
docker exec xpanel-web php artisan migrate --force --path=database/migrations/client
docker exec xpanel-web php artisan db:seed --force

# Crear usuario Admin con la contraseña generada. Pasamos credenciales por entorno
# para evitar problemas con caracteres especiales en Bash/PHP inline.
docker exec \
  -e XPANEL_ADMIN_EMAIL="$USER_EMAIL" \
  -e XPANEL_ADMIN_PASSWORD="$PASS" \
  xpanel-web php artisan tinker --execute='App\Models\User::updateOrCreate(["email" => getenv("XPANEL_ADMIN_EMAIL")], ["name" => "Admin", "password" => bcrypt(getenv("XPANEL_ADMIN_PASSWORD")), "role" => "admin"]);'
docker exec xpanel-web php artisan optimize:clear || true

# Smoke check for ingress/login routes.
bash "$DIR/smoke.sh" || true

# ===============================
# FINAL
# ===============================
echo -e "\033[0;32m"
msg_done
echo -e "\033[0m"
echo "-------------------------------------------------------"
msg_access
echo "-------------------------------------------------------"
echo -e "\033[1;34mURL:      \033[1;37m$PANEL_URL/admin/login\033[0m"
echo -e "\033[1;34mUSUARIO:  \033[1;37m$USER_EMAIL\033[0m"
echo -e "\033[1;34mPASSWORD: \033[1;37m$PASS\033[0m"
echo "-------------------------------------------------------"
echo
msg_help
msg_thanks
