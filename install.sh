#!/usr/bin/env bash
set -euo pipefail

# General installer entrypoint
# Usage:
#   bash install.sh
#   bash install.sh es|en
#   bash install.sh --domain panel.example.com --yes
#   bash install.sh panel.example.com fresh

LANG_ARG=""
PASSTHRU=()
NON_INTERACTIVE=0

while [ $# -gt 0 ]; do
  case "$1" in
    es|ES|1|Español|espanol)
      LANG_ARG="es"
      shift
      ;;
    en|EN|2|English)
      LANG_ARG="en"
      shift
      ;;
    fresh|--fresh)
      PASSTHRU+=("--fresh")
      shift
      ;;
    --domain|--email|--password|--cf-token|--cf-email)
      PASSTHRU+=("$1")
      [ $# -ge 2 ] || { echo "Missing value for $1"; exit 1; }
      PASSTHRU+=("$2")
      shift 2
      ;;
    --yes|--non-interactive)
      PASSTHRU+=("$1")
      NON_INTERACTIVE=1
      shift
      ;;
    *)
      if [[ "$1" =~ ^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$ ]]; then
        PASSTHRU+=("--domain" "$1")
        shift
      else
        echo "Unknown argument: $1"
        echo "Use: bash install.sh [es|en] [--yes] [--fresh] [--domain DOMAIN] [--email EMAIL]"
        exit 1
      fi
      ;;
  esac
done

if [ -z "$LANG_ARG" ] && [ "$NON_INTERACTIVE" -eq 1 ]; then
  LANG_ARG="es"
fi

if [ -z "$LANG_ARG" ]; then
  echo "Select language / Selecciona idioma:"
  echo "  1) Español (es)"
  echo "  2) English (en)"
  read -r -p "Choice [1/2] (default: 1): " LANG_CHOICE
  case "${LANG_CHOICE:-1}" in
    1|es|ES|Español|espanol) LANG_ARG="es" ;;
    2|en|EN|English) LANG_ARG="en" ;;
    *)
      echo "Invalid choice: $LANG_CHOICE"
      echo "Use: bash install.sh [es|en]"
      exit 1
      ;;
  esac
fi

if [ "$LANG_ARG" != "es" ] && [ "$LANG_ARG" != "en" ]; then
  echo "Invalid language: $LANG_ARG"
  echo "Use: bash install.sh [es|en]"
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ -f "$ROOT_DIR/installer/install.sh" ]; then
  exec bash "$ROOT_DIR/installer/install.sh" "$LANG_ARG" "${PASSTHRU[@]}"
else
  echo "Installer not found in current tree: $ROOT_DIR/installer/install.sh"
  exit 1
fi
