#!/usr/bin/env bash
# Create docker/.env (gitignored) with random dev-only passwords. Idempotent.
set -euo pipefail
cd "$(dirname "$0")"
if [ ! -e .env ]; then
  gen() { LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24 || true; }
  sed -e "s/^DB_PASSWORD=.*/DB_PASSWORD=$(gen)/" \
      -e "s/^DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=$(gen)/" \
      -e "s/^SITE_ADMIN_PASSWORD=.*/SITE_ADMIN_PASSWORD=$(gen)/" .env.example > .env
  chmod 600 .env
  echo "Created docker/.env with random dev-only passwords."
fi
