#!/usr/bin/env bash
#
# Deploy the current branch to this VPS.
#
#   ./scripts/deploy.sh
#
# Takes a database snapshot first, then rebuilds and rolls the containers. If
# anything fails the script stops immediately: a half-finished deploy that
# reports success is worse than one that stops loudly.
set -euo pipefail

cd "$(dirname "$0")/.."

info()  { printf '\033[0;36m▸ %s\033[0m\n' "$1"; }
ok()    { printf '\033[0;32m✓ %s\033[0m\n' "$1"; }
fail()  { printf '\033[0;31m✗ %s\033[0m\n' "$1" >&2; exit 1; }

[ -f .env ] || fail "No .env file. Copy .env.example and fill it in."

# A blank APP_KEY produces a container that boots and then fails on the first
# encrypted value; catch it here instead.
grep -q '^APP_KEY=.\+' .env || fail "APP_KEY is empty. Run: docker compose run --rm app php artisan key:generate --show"
grep -q '^DB_PASSWORD=.\+' .env || fail "DB_PASSWORD is empty."

# ─── Safety net ──────────────────────────────────────────────────────────
# Only if the stack is already up: on a first deploy there is nothing to back up.
if docker compose ps --status running --quiet postgres 2>/dev/null | grep -q .; then
    info "Backing up the database before touching anything"
    ./scripts/backup-db.sh
else
    info "First deploy, no database to back up yet"
fi

# ─── Code ────────────────────────────────────────────────────────────────
if [ -d .git ]; then
    info "Fetching latest code"
    git pull --ff-only
fi

# ─── Build ───────────────────────────────────────────────────────────────
# Built before anything is stopped, so a compile error costs no downtime.
info "Building images"
docker compose build

info "Rolling out"
docker compose up -d --remove-orphans

# ─── Verify ──────────────────────────────────────────────────────────────
# `up -d` returns as soon as containers start, not when the app is serving.
# Migrations run at boot, so the first healthy response is the real signal.
info "Waiting for the API to report healthy"
for attempt in $(seq 1 60); do
    status="$(docker compose ps app --format '{{.Health}}' 2>/dev/null || true)"
    if [ "$status" = "healthy" ]; then
        ok "API is healthy"
        break
    fi
    if [ "$attempt" -eq 60 ]; then
        docker compose logs --tail=50 app
        fail "API never became healthy. See the logs above. Previous data is intact; roll back with: docker compose down && git checkout <previous-commit> && ./scripts/deploy.sh"
    fi
    sleep 2
done

# Old images pile up fast on a small VPS disk.
info "Pruning dangling images"
docker image prune -f >/dev/null

ok "Deployed."
docker compose ps
