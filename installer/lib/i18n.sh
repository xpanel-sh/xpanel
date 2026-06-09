#!/usr/bin/env bash

load_xpanel_lang() {
  local lang_dir="$1"
  local active_lang="$2"

  if [ ! -f "$lang_dir/en.sh" ]; then
    echo "❌ Missing base language file: $lang_dir/en.sh"
    exit 1
  fi

  # Load base language first (fallback), then active language overrides.
  source "$lang_dir/en.sh"
  if [ "$active_lang" != "en" ] && [ -f "$lang_dir/$active_lang.sh" ]; then
    source "$lang_dir/$active_lang.sh"
  fi
}

# Prevent hard crash when a msg_* key is missing in both languages.
command_not_found_handle() {
  local cmd="$1"
  shift || true

  case "$cmd" in
    msg_*)
      echo "⚠️ [i18n] Missing message key: $cmd"
      return 0
      ;;
    *)
      echo "bash: $cmd: command not found" >&2
      return 127
      ;;
  esac
}

