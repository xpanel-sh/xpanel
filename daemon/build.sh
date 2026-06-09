#!/usr/bin/env bash
set -e

echo "🔨 Building XPanel daemon..."

GOOS=linux GOARCH=amd64 CGO_ENABLED=0 \
go build -o xpanel-daemon ./cmd/daemon

echo "✅ Done: xpanel-daemon"
