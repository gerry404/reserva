#!/bin/sh
#
# Boot sequence shared by every Réserva container (app, worker, scheduler).
#
# `set -e` matters: if migrations fail the container must die and the deploy
# must be seen to fail, rather than coming up to serve a schema the code does
# not match.
set -e

role="${CONTAINER_ROLE:-app}"
echo "▸ Starting Réserva [${role}]"

# ─── Wait for the database ───────────────────────────────────────────────
# Compose already gates on the postgres healthcheck, but a database can accept
# connections a moment before it accepts queries, and every command below needs
# it. Cheap insurance against a restart loop on a cold boot.
attempt=1
until php -r '
    $dsn = getenv("DB_URL");
    try {
        $dsn
            ? new PDO($dsn)
            : new PDO(
                sprintf("pgsql:host=%s;port=%s;dbname=%s",
                    getenv("DB_HOST") ?: "postgres",
                    getenv("DB_PORT") ?: "5432",
                    getenv("DB_DATABASE")),
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD"),
            );
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    if [ "$attempt" -ge 30 ]; then
        echo "✗ Database unreachable after 30 attempts — aborting."
        exit 1
    fi
    echo "  waiting for database (${attempt}/30)…"
    attempt=$((attempt + 1))
    sleep 2
done

# ─── Caches ──────────────────────────────────────────────────────────────
# Built at boot, not at image build time: they bake in environment values
# (APP_URL, FRONTEND_URL, mail credentials) that only exist at runtime.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ─── Schema ──────────────────────────────────────────────────────────────
# Only the web container migrates. Three processes racing the same migrations
# is how a schema ends up half applied.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "▸ Running migrations"
    php artisan migrate --force --no-interaction
fi

# ─── Uploads ─────────────────────────────────────────────────────────────
# public/storage is a symlink into a mounted volume, so it has to be recreated
# in each new container. Harmless to repeat.
php artisan storage:link --quiet 2>/dev/null || true

echo "▸ Ready"
exec "$@"
