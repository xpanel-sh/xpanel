#!/usr/bin/env bash
set -e
source "$(dirname "$0")/lib/loader.sh"

BASE="${XPANEL_BASE:-/opt/xpanel}"
BACKUP_DIR="$BASE/backups"
ACTION="${1:-create}"
TARGET="$2"
RETENTION_DAYS="${XPANEL_BACKUP_RETENTION_DAYS:-7}"
mkdir -p "$BACKUP_DIR"

create_backup() {
  local date file sum
  date=$(date +"%Y%m%d-%H%M%S")
  file="$BACKUP_DIR/xpanel-backup-$date.tar.gz"

  msg_backup_start
  tar -czf "$file" \
    "$BASE/config" \
    "$BASE/.env" \
    "$BASE/docker-compose.yml" \
    "$BASE/traefik" \
    "$BASE/panel/.env" 2>/dev/null || true

  sum="$(sha256sum "$file" | awk '{print $1}')"
  echo "$sum  $(basename "$file")" > "$file.sha256"
  msg_backup_done "$file"
}

list_backups() {
  ls -1t "$BACKUP_DIR"/*.tar.gz 2>/dev/null || true
}

restore_backup() {
  local file
  file="$1"
  [ -n "$file" ] || { msg_backup_restore_usage; exit 1; }
  [ -f "$file" ] || file="$BACKUP_DIR/$file"
  [ -f "$file" ] || { msg_backup_not_found "$1"; exit 1; }

  if [ -f "$file.sha256" ]; then
    (cd "$(dirname "$file")" && sha256sum -c "$(basename "$file").sha256")
  fi

  tar -xzf "$file" -C /
  msg_backup_restore_done "$file"
}

prune_backups() {
  find "$BACKUP_DIR" -name 'xpanel-backup-*.tar.gz' -mtime +"$RETENTION_DAYS" -delete
  find "$BACKUP_DIR" -name 'xpanel-backup-*.tar.gz.sha256' -mtime +"$RETENTION_DAYS" -delete
}

case "$ACTION" in
  create) create_backup; prune_backups ;;
  list) list_backups ;;
  restore) restore_backup "$TARGET" ;;
  prune) prune_backups ;;
  *) msg_backup_usage; exit 1 ;;
esac
