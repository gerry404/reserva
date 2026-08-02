#!/usr/bin/env bash
#
# Dump the database to ./backups, and prune old dumps.
#
#   ./scripts/backup-db.sh
#
# Self-hosting means backups are your responsibility. Install this in the host
# crontab so it actually runs:
#
#   0 3 * * * cd /srv/nuvo && ./scripts/backup-db.sh >> /var/log/nuvo-backup.log 2>&1
#
# A backup you have never restored is a hypothesis, not a backup — restore one
# into a scratch database occasionally (see restore-db.sh).
set -euo pipefail

cd "$(dirname "$0")/.."

RETENTION_DAYS="${RETENTION_DAYS:-14}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"

# shellcheck disable=SC1091
set -a; . ./.env; set +a

mkdir -p "$BACKUP_DIR"

stamp="$(date +%Y-%m-%d_%H%M%S)"
target="${BACKUP_DIR}/reserva_${stamp}.sql.gz"

# --clean --if-exists makes the dump replayable onto a non-empty database.
docker compose exec -T -e PGPASSWORD="$DB_PASSWORD" postgres \
    pg_dump \
        --username="$DB_USERNAME" \
        --dbname="$DB_DATABASE" \
        --clean \
        --if-exists \
        --no-owner \
        --no-privileges \
    | gzip -9 > "$target"

# gzip of an empty stream is still ~20 bytes, so a tiny file means pg_dump
# produced nothing — better to fail now than to discover it during a restore.
size="$(wc -c < "$target")"
if [ "$size" -lt 1000 ]; then
    rm -f "$target"
    printf '\033[0;31m✗ Backup looks empty (%s bytes) — aborted.\033[0m\n' "$size" >&2
    exit 1
fi

printf '\033[0;32m✓ %s (%s)\033[0m\n' "$target" "$(du -h "$target" | cut -f1)"

# Prune only our own files, and only after a successful dump.
find "$BACKUP_DIR" -name 'nuvo_*.sql.gz' -type f -mtime "+${RETENTION_DAYS}" -delete

printf '  %s backup(s) kept (retention: %s days)\n' \
    "$(find "$BACKUP_DIR" -name 'nuvo_*.sql.gz' -type f | wc -l | tr -d ' ')" \
    "$RETENTION_DAYS"
