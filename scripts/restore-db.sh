#!/usr/bin/env bash
#
# Restore a dump produced by backup-db.sh.
#
#   ./scripts/restore-db.sh backups/nuvo_2026-08-02_030000.sql.gz
#
# This overwrites the live database. It asks for confirmation and takes a
# safety dump first, because the moment you need this script is the moment you
# are least inclined to be careful.
set -euo pipefail

cd "$(dirname "$0")/.."

archive="${1:-}"

if [ -z "$archive" ]; then
    echo "Usage: $0 <backup.sql.gz>" >&2
    echo >&2
    echo "Available backups:" >&2
    ls -1t ./backups/nuvo_*.sql.gz 2>/dev/null | head -20 >&2 || echo "  (none)" >&2
    exit 1
fi

[ -f "$archive" ] || { echo "✗ No such file: $archive" >&2; exit 1; }

# shellcheck disable=SC1091
set -a; . ./.env; set +a

printf '\033[0;31m⚠  This replaces the contents of "%s" with %s.\033[0m\n' "$DB_DATABASE" "$archive"
printf 'Type the database name to confirm: '
read -r answer
[ "$answer" = "$DB_DATABASE" ] || { echo "Aborted."; exit 1; }

echo "▸ Taking a safety dump of the current state first"
BACKUP_DIR=./backups/pre-restore ./scripts/backup-db.sh

# Stop everything that writes, so nothing races the restore.
echo "▸ Pausing application containers"
docker compose stop app worker scheduler

echo "▸ Restoring"
gunzip -c "$archive" | docker compose exec -T -e PGPASSWORD="$DB_PASSWORD" postgres \
    psql --username="$DB_USERNAME" --dbname="$DB_DATABASE" --quiet

echo "▸ Restarting"
docker compose start app worker scheduler

printf '\033[0;32m✓ Restored from %s\033[0m\n' "$archive"
