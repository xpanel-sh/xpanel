#!/usr/bin/env bash
set -euo pipefail

if [ "$EUID" -ne 0 ]; then
  msg_error_root
  exit 1
fi

if ! command -v curl >/dev/null 2>&1; then
  msg_missing_dependency "curl"
  exit 1
fi

if ! command -v openssl >/dev/null 2>&1; then
  msg_missing_dependency "openssl"
  exit 1
fi

if ! grep -qi "ubuntu\|debian" /etc/os-release; then
  msg_error_os
  exit 1
fi
