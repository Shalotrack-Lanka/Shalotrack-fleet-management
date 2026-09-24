#!/bin/sh
# ShaloTrack Fleet — container entrypoint
#
# 1. Fail fast if required runtime config is missing (a crash-loop is visible;
#    a half-configured portal silently serving errors is not).
# 2. Build Laravel's config/route/view/event caches from the REAL runtime env.
#    (Must happen here, not at image build time — the build has no env.)
# 3. Run PHP-FPM + nginx. If either one dies, the container exits so Docker's
#    restart policy brings the whole thing back cleanly.

set -eu

cd /var/www/html

missing=""
for var in APP_KEY APP_URL FIREBASE_PROJECT_ID FIREBASE_API_KEY FIREBASE_AUTH_DOMAIN FIREBASE_APP_ID GOOGLE_MAPS_API_KEY; do
    eval "val=\${$var:-}"
    if [ -z "$val" ]; then
        missing="$missing $var"
    fi
done

if [ -n "$missing" ]; then
    echo "[entrypoint] FATAL: required environment variables not set:$missing" >&2
    exit 1
fi

# Runtime dirs — needed when storage/framework/sessions is a fresh named volume.
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/fonts

echo "[entrypoint] Building Laravel caches..."
php artisan optimize --no-ansi

echo "[entrypoint] Starting PHP-FPM and nginx..."
php-fpm -F &
FPM_PID=$!

nginx -e /dev/stderr -g 'daemon off;' &
NGINX_PID=$!

shutdown() {
    kill -TERM "$FPM_PID" "$NGINX_PID" 2>/dev/null || true
    wait 2>/dev/null || true
}
trap 'shutdown; exit 0' TERM INT

# Block until EITHER process exits, then take the other one down with it.
while kill -0 "$FPM_PID" 2>/dev/null && kill -0 "$NGINX_PID" 2>/dev/null; do
    sleep 2
done

echo "[entrypoint] A child process exited unexpectedly — stopping container." >&2
shutdown
exit 1