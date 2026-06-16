#!/usr/bin/env bash
set -euo pipefail

BASE="${XPANEL_BASE:-/opt/xpanel}"
LANG_FILE="$BASE/config/lang"
XPANEL_LANG="en"

[ -f "$LANG_FILE" ] && XPANEL_LANG=$(cat "$LANG_FILE")

SELF_PATH="$0"
if command -v readlink >/dev/null 2>&1; then
  RESOLVED_PATH="$(readlink -f "$SELF_PATH" 2>/dev/null || true)"
  if [ -n "${RESOLVED_PATH:-}" ]; then
    SELF_PATH="$RESOLVED_PATH"
  fi
fi
DIR="$(cd "$(dirname "$SELF_PATH")" && pwd)"

source "$DIR/lib/loader.sh"

CMD="${1:-}"
shift || true

case "$CMD" in
  git|sync|forzar)
    echo "⬇️  Sincronizando desde git (sin validar versión)..."
    git -C "$BASE" pull --ff-only origin "$(git -C "$BASE" rev-parse --abbrev-ref HEAD)"
    DAEMON_SRC=""
    [ -f "$BASE/daemon/go.mod" ]     && DAEMON_SRC="$BASE/daemon"
    [ -f "$BASE/daemon-src/go.mod" ] && DAEMON_SRC="$BASE/daemon-src"
    if command -v go >/dev/null 2>&1 && [ -n "$DAEMON_SRC" ]; then
      echo "🔨 Compilando daemon..."
      (
        cd "$DAEMON_SRC"
        if go build -o xpanel-daemon-new ./cmd/daemon 2>/dev/null || go build -o xpanel-daemon-new .; then
          mv -f xpanel-daemon-new "$BASE/daemon/xpanel-daemon"
          chmod +x "$BASE/daemon/xpanel-daemon"
          echo "✅ Daemon compilado."
        else
          rm -f xpanel-daemon-new
          echo "⚠️  Error compilando daemon."
        fi
      )
    fi
    bash "$DIR/migrate.sh" "$XPANEL_LANG" || true
    systemctl restart xpanel-daemon 2>/dev/null || true
    echo "✅ Sincronización completa."
    ;;

  update|actualizar)
    if [ "${1:-}" = "check" ] || [ "${1:-}" = "verificar" ]; then
      XPANEL_UPDATE_CHECK_ONLY=1 bash "$DIR/update.sh" "$XPANEL_LANG"
    elif [ "${1:-}" = "--dry-run" ] || [ "${1:-}" = "dry-run" ] || [ "${1:-}" = "simular" ]; then
      bash "$DIR/update.sh" "$XPANEL_LANG" "dry-run"
    elif [ "${1:-}" = "--rollback" ] || [ "${1:-}" = "rollback" ] || [ "${1:-}" = "revertir" ]; then
      shift || true
      bash "$DIR/update.sh" "$XPANEL_LANG" "rollback" "${1:-}"
    else
      bash "$DIR/update.sh" "$XPANEL_LANG"
    fi
    ;;

  doctor|diagnostico|diagnóstico)
    bash "$DIR/doctor.sh"
    ;;

  uninstall|eliminar)
    bash "$DIR/uninstall.sh" "$XPANEL_LANG" "$@"
    ;;

  reinstall|reinstalar)
    bash "$DIR/install.sh" "$XPANEL_LANG" --fresh
    ;;

  idioma|language|lang)
    if [ -z "${1:-}" ]; then
      msg_current_language "$XPANEL_LANG"
    elif [ "${1:-}" = "list" ]; then
      echo "🌐 Idiomas disponibles:"
      ls "$DIR/lang/" | sed 's/\.sh//g'
    else
      set_language "${1:-}"
    fi
    ;;

  config|configuracion|configuration)
    bash "$DIR/config.sh" "$@"
    ;;

  status|estado)
    bash "$DIR/status.sh" "$@"
    ;;

  logs|log)
    bash "$DIR/logs.sh" "$@"
    ;;

  backup|respaldo)
    bash "$DIR/backup.sh" "$@"
    ;;

  site|sitio)
    bash "$DIR/modules/site.sh" "$@"
    ;;

  ssl)
    bash "$DIR/modules/ssl.sh" "$@"
    ;;

  components|componentes)
    bash "$DIR/modules/components.sh" "$@"
    ;;

  i18n-audit|i18n_audit|i18n-lint|auditar-idioma|auditar_idioma)
    bash "$DIR/modules/i18n_audit.sh"
    ;;

  version|vs)
    cat "$BASE/VERSION"
    ;;

  access|acceso|auth)
    case "${1:-show}" in
      show|"")
        xpanel_access
        ;;
      reset-password|reset_pass|resetpass)
        shift || true
        reset_admin_password "${1:-}"
        ;;
      *)
        echo "Usage: xpanel access [show|reset-password [new_password]]"
        exit 1
        ;;
    esac
    ;;

  port|puerto)
    save_port "${1:-}"
    msg_port "${1:-}"
    ;;

  help|ayuda|"")
    msg_help
    ;;

  *)
    msg_unknown "$CMD"
    ;;
esac
