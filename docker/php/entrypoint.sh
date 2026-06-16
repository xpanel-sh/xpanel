#!/bin/sh
set -e

# Proxy MySQL connections to xpanel-db so PHP code can use "localhost" or "127.0.0.1".
#
# PHP with host=localhost uses Unix socket; host=127.0.0.1 uses TCP.
# We handle both so any standard connection string works.

mkdir -p /var/run/mysqld

# Unix socket proxy  → host=localhost (PHP default on Linux)
socat UNIX-LISTEN:/var/run/mysqld/mysqld.sock,fork,reuseaddr,unlink-early,mode=777 \
      TCP:xpanel-db:3306 2>/dev/null &

# TCP proxy          → host=127.0.0.1
socat TCP-LISTEN:3306,fork,reuseaddr TCP:xpanel-db:3306 2>/dev/null &

exec "$@"
