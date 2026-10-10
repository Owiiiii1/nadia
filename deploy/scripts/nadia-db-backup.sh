#!/bin/bash
set -euo pipefail

creds=/home/deploy/.nadia-db-credentials
if [[ ! -r "$creds" ]]; then
  echo "credentials file is not readable: $creds" >&2
  exit 1
fi

set -a
# shellcheck disable=SC1090
source "$creds"
set +a

: "${DB_DATABASE:?}"
: "${DB_USERNAME:?}"
: "${DB_PASSWORD:?}"

outdir=/var/backups/nadia
mkdir -p "$outdir"
chmod 700 "$outdir"

stamp=$(date -u +%Y%m%d)
outfile="${outdir}/nadia-${stamp}.sql.gz"

cnf=$(mktemp)
chmod 600 "$cnf"
trap 'rm -f "$cnf"' EXIT

cat > "$cnf" <<EOF
[client]
host=127.0.0.1
user=${DB_USERNAME}
password=${DB_PASSWORD}
EOF

# --no-tablespaces: MySQL 8 требует PROCESS, чтобы читать tablespaces.
# У пользователя приложения этого права нет, дамп данных от него не зависит.
mysqldump --defaults-extra-file="$cnf" --single-transaction --no-tablespaces "$DB_DATABASE" | gzip -c > "$outfile"
chmod 600 "$outfile"

mapfile -t old < <(ls -1t "${outdir}"/nadia-*.sql.gz | tail -n +15)
if ((${#old[@]})); then
  rm -f "${old[@]}"
fi
