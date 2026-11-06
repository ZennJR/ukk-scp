#!/usr/bin/env bash
# Tunnel cepat tanpa akun/domain (URL acak *.trycloudflare.com)
set -e
cd "$(dirname "$0")/../.."
php artisan serve --host=127.0.0.1 --port=8000 &
SERVER_PID=$!
trap 'kill $SERVER_PID' EXIT
sleep 2
cloudflared tunnel --url http://localhost:8000
