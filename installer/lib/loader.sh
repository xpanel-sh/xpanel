#!/usr/bin/env bash
set -euo pipefail

BASE="${XPANEL_BASE:-/opt/xpanel}"
CONF="$BASE/config"
LANG_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../lang" && pwd)"

# ========= CONFIG =========
[ -f "$CONF/lang" ] && XPANEL_LANG=$(cat "$CONF/lang") || XPANEL_LANG="en"
[ -f "$CONF/port" ] && XPANEL_PORT=$(cat "$CONF/port") || XPANEL_PORT="8888"

# ========= ACCESS =========
if [ -f "$CONF/access.info" ]; then
  source "$CONF/access.info"
fi

# ========= LANG =========
LANG_FILE="$LANG_DIR/$XPANEL_LANG.sh"
source "$(dirname "${BASH_SOURCE[0]}")/i18n.sh"
load_xpanel_lang "$LANG_DIR" "$XPANEL_LANG"

# ========= HELPERS =========
source "$(dirname "${BASH_SOURCE[0]}")/utils.sh"
