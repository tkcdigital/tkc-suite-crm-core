#!/usr/bin/env bash
# docker compose with the right env file (the repo root .env belongs to Symfony).
#   docker/dc.sh up -d | down | logs -f app | ...
#   docker/dc.sh console <args>   run "php bin/console <args>" in the app container as www-data
#   docker/dc.sh sh               shell in the app container as www-data
# Console and shell run as www-data, so cache/log files stay writable for Apache.
cd "$(dirname "$0")/.." || exit 1
case "${1:-}" in
  console) shift
           tflag=""; [ -t 0 ] || tflag="-T"   # no TTY (script, CI, pipe): docker compose exec needs -T
           # shellcheck disable=SC2086
           exec docker compose --env-file docker/.env exec $tflag -u www-data app php bin/console "$@" ;;
  sh)      exec docker compose --env-file docker/.env exec -u www-data app bash ;;
  *)       exec docker compose --env-file docker/.env "$@" ;;
esac
