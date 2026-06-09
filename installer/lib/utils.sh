xpanel_access() {
  ACCESS_URL="${LOGIN_URL:-$URL/admin/login}"
  msg_access
  echo "-------------------------------------------------------"
  echo -e "\033[1;34mURL:      \033[1;37m$ACCESS_URL\033[0m"
  echo -e "\033[1;34mUSUARIO:  \033[1;37m$USER\033[0m"
  echo -e "\033[1;34mPASSWORD: \033[1;37m$PASS\033[0m"
  echo "-------------------------------------------------------"
}

reset_admin_password() {
  BASE="${XPANEL_BASE:-/opt/xpanel}"
  PANEL_DIR="$BASE/panel"
  NEW_PASS="${1:-}"

  if [ -z "$NEW_PASS" ]; then
    NEW_PASS="$(openssl rand -base64 12 | tr -d '/+' | cut -c1-16)"
  fi

  docker exec \
    -e XPANEL_ADMIN_EMAIL="admin@xpanel.com" \
    -e XPANEL_ADMIN_PASSWORD="$NEW_PASS" \
    xpanel-web php artisan tinker --execute='App\Models\User::updateOrCreate(["email" => getenv("XPANEL_ADMIN_EMAIL")], ["name" => "Admin", "role" => "admin", "password" => bcrypt(getenv("XPANEL_ADMIN_PASSWORD"))]);' >/dev/null

  ACCESS_FILE="$BASE/config/access.info"
  [ -f "$ACCESS_FILE" ] || touch "$ACCESS_FILE"
  if grep -q '^PASS=' "$ACCESS_FILE"; then
    sed -i "s|^PASS=.*|PASS=$NEW_PASS|g" "$ACCESS_FILE"
  else
    echo "PASS=$NEW_PASS" >> "$ACCESS_FILE"
  fi

  if grep -q '^USER=' "$ACCESS_FILE"; then
    sed -i "s|^USER=.*|USER=admin@xpanel.com|g" "$ACCESS_FILE"
  else
    echo "USER=admin@xpanel.com" >> "$ACCESS_FILE"
  fi

  URL="$(grep '^URL=' "$ACCESS_FILE" 2>/dev/null | cut -d= -f2- || true)"
  if [ -n "$URL" ]; then
    if grep -q '^LOGIN_URL=' "$ACCESS_FILE"; then
      sed -i "s|^LOGIN_URL=.*|LOGIN_URL=$URL/admin/login|g" "$ACCESS_FILE"
    else
      echo "LOGIN_URL=$URL/admin/login" >> "$ACCESS_FILE"
    fi
  fi

  echo "✅ Admin password reset successfully."
  xpanel_access
}

set_language() {
  NEW_LANG="$1"
  LANG_DIR="$(cd "$(dirname "$0")/../lang" && pwd)"
  CONF="${XPANEL_BASE:-/opt/xpanel}/config"

  if [ ! -f "$LANG_DIR/$NEW_LANG.sh" ]; then
    msg_language_not_supported "$NEW_LANG"
    exit 1
  fi

  mkdir -p "$CONF"
  echo "$NEW_LANG" > "$CONF/lang"

  msg_language_changed "$NEW_LANG"
}

save_port() {
  NEW_PORT="$1"
  CONF="${XPANEL_BASE:-/opt/xpanel}/config"

  if ! [[ "$NEW_PORT" =~ ^[0-9]+$ ]] || [ "$NEW_PORT" -lt 1 ] || [ "$NEW_PORT" -gt 65535 ]; then
    msg_invalid_port "$NEW_PORT"
    exit 1
  fi

  mkdir -p "$CONF"
  echo "$NEW_PORT" > "$CONF/port"
}
