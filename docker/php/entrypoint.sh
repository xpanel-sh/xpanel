#!/bin/sh
set -e

# Proxy localhost:3306 → xpanel-db:3306 so PHP code can use "localhost" as DB host.
# Runs in background; errors are suppressed (xpanel-db may not be up yet).
socat TCP-LISTEN:3306,fork,reuseaddr TCP:xpanel-db:3306 2>/dev/null &

exec "$@"
