#!/usr/bin/env bash
set -e

XPANEL_LANG="${1:-en}"
shift || true

BASE="${XPANEL_BASE:-/opt/xpanel}"
DIR="$(cd "$(dirname "$0")" && pwd)"
source "$DIR/lib/i18n.sh"
load_xpanel_lang "$DIR/lang" "$XPANEL_LANG"

MODE="safe"   # safe | panel-only | full
DRY_RUN=0

for arg in "$@"; do
  case "$arg" in
    safe|panel-only|full) MODE="$arg" ;;
    --dry-run|dry-run|simular) DRY_RUN=1 ;;
    *) ;;
  esac
done

print_plan() {
  msg_uninstall_start
  msg_uninstall_mode "$MODE"
  if [ "$DRY_RUN" -eq 1 ]; then
    msg_uninstall_dryrun
  fi

  echo "- stop and disable xpanel-daemon service"
  echo "- remove xpanel systemd unit"
  echo "- remove xpanel CLI symlink and bash completion"

  if [ "$MODE" = "safe" ] || [ "$MODE" = "panel-only" ]; then
    echo "- docker compose down (panel stack only, keep volumes)"
    echo "- remove $BASE"
    echo "- keep xpanel-site-* containers (user websites)"
  fi

  if [ "$MODE" = "full" ]; then
    echo "- docker compose down -v --remove-orphans"
    echo "- remove xpanel-site-* containers (user websites)"
    echo "- remove xpanel-related volumes and network"
    echo "- remove $BASE"
  fi
}

confirm_full() {
  [ "${XPANEL_YES:-0}" = "1" ] && return 0
  msg_uninstall_confirm_full
  read -r answer
  [ "$answer" = "YES" ]
}

print_plan

if [ "$DRY_RUN" -eq 1 ]; then
  exit 0
fi

if [ "$MODE" = "full" ]; then
  confirm_full || { msg_cancelled; exit 1; }
fi

# stop daemon service
systemctl stop xpanel-daemon 2>/dev/null || true
systemctl disable xpanel-daemon 2>/dev/null || true
rm -f /etc/systemd/system/xpanel-daemon.service
systemctl daemon-reload 2>/dev/null || true

# stop/remove stack containers
if [ -f "$BASE/docker-compose.yml" ]; then
  cd "$BASE"
  if [ "$MODE" = "full" ]; then
    docker compose down -v --remove-orphans || true
  else
    docker compose down --remove-orphans || true
  fi
fi

if [ "$MODE" = "full" ]; then
  # remove website containers created by daemon
  docker ps -a --format '{{.Names}}' | grep '^xpanel-site-' | xargs -r docker rm -f || true

  # remove related volumes/networks
  docker volume ls --format '{{.Name}}' | grep -E '(^xpanel_|_xpanel_)|mysql_data' | xargs -r docker volume rm || true
  docker network rm xpanel-net 2>/dev/null || true
fi

# remove global cli and completion
rm -f /usr/local/bin/xpanel
rm -f /etc/bash_completion.d/xpanel

# remove xpanel directory
rm -rf "$BASE"

msg_done
