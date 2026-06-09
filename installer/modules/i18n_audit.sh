#!/usr/bin/env bash
set -e

LANG_DIR="$(cd "$(dirname "$0")/../lang" && pwd)"
EN_FILE="$LANG_DIR/en.sh"

if [ ! -f "$EN_FILE" ]; then
  echo "Missing $EN_FILE"
  exit 1
fi

status=0
for f in "$LANG_DIR"/*.sh; do
  [ -f "$f" ] || continue
  lang="$(basename "$f")"
  [ "$lang" = "en.sh" ] && continue

  missing=$(comm -23 \
    <(grep -oE '^msg_[a-zA-Z0-9_]+' "$EN_FILE" | sort -u) \
    <(grep -oE '^msg_[a-zA-Z0-9_]+' "$f" | sort -u) || true)

  if [ -n "$missing" ]; then
    status=1
    echo "[$lang] Missing keys:"
    echo "$missing"
    echo
  fi
done

if [ "$status" -eq 0 ]; then
  echo "✅ i18n audit passed."
else
  echo "❌ i18n audit found missing keys."
fi

exit "$status"
