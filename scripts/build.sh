#!/usr/bin/env bash
# One build chain: host Angular build, Dawn CSS, legacy assets, schema repair, config, cache, smoke test.
#   scripts/build.sh [--yes] [--skip-core] [--skip-frontend] [--dry-run-only]
#     --yes            skip the confirmation before "tkc:repair --execute" (reviewed deploys only: runs SQL unread)
#     --skip-core      skip "yarn build:core" (core Angular code did not change)
#     --skip-frontend  skip all Angular builds (steps 1-5)
#     --dry-run-only   stop after the "tkc:repair --dry-run" step
# Exit codes: 0 ok, 1 a step failed, 2 no confirmation possible (no TTY and no --yes) or bad flag.
# Never prints docker/.env or config files. Do not add "set -x".
set -euo pipefail

cd "$(dirname "$0")/.." || exit 1

ASSUME_YES=0
SKIP_CORE=0
SKIP_FRONTEND=0
DRY_RUN_ONLY=0
for arg in "$@"; do
  case "$arg" in
    --yes) ASSUME_YES=1 ;;
    --skip-core) SKIP_CORE=1 ;;
    --skip-frontend) SKIP_FRONTEND=1 ;;
    --dry-run-only) DRY_RUN_ONLY=1 ;;
    -h|--help) sed -n '2,9p' "$0"; exit 0 ;;
    *) echo "Unknown flag: $arg" >&2; exit 2 ;;
  esac
done

TOTAL=14
STEP_NO=0
STEP_NAME="start"
APP_URL="http://localhost:${APP_PORT:-8081}/"

trap 'echo "FAILED at step ${STEP_NO}: ${STEP_NAME}" >&2' ERR

step() {
  STEP_NO="$1"
  STEP_NAME="$2"
  echo "==> [${STEP_NO}/${TOTAL}] ${STEP_NAME}"
}

skipped() {
  echo "    skipped (${1})"
}

DC=docker/dc.sh

# 0. Preflight
step 0 "Preflight"
command -v docker >/dev/null || { echo "docker not found" >&2; false; }
command -v node >/dev/null || { echo "node not found" >&2; false; }
command -v yarn >/dev/null || { echo "yarn not found (corepack enable)" >&2; false; }
command -v python3 >/dev/null || { echo "python3 not found" >&2; false; }
command -v curl >/dev/null || { echo "curl not found" >&2; false; }
test -f docker/.env || { echo "docker/.env missing: run docker/init.sh" >&2; false; }
test -f public/legacy/config.php || { echo "public/legacy/config.php missing: run the installer (LOCAL_SETUP.md)" >&2; false; }
running="$($DC ps --status running --services 2>/dev/null || true)"
echo "$running" | grep -qx app || { echo "app container is not running: docker/dc.sh up -d" >&2; false; }
step 0b "Theme files match tokens.json (build_theme.py --check)"
python3 -I -B scripts/theme/build_theme.py --check

if [ "$SKIP_FRONTEND" -eq 1 ]; then
  for n in 1 2 3 4 5; do step "$n" "Angular build step $n"; skipped "--skip-frontend"; done
else
  step 1 "yarn install --immutable"
  yarn install --immutable

  step 2 "yarn merge-angular-json"
  yarn merge-angular-json

  step 3 "yarn build:core"
  if [ "$SKIP_CORE" -eq 1 ]; then skipped "--skip-core"; else yarn build:core; fi

  step 4 "yarn build:shell"
  yarn build:shell
  step 4b "TKC theme is in the shell bundle (check_bundle.py)"
  python3 -I -B scripts/theme/check_bundle.py

  step 5 "yarn build:extension tkc"
  yarn build:extension tkc
fi

step 6 "Compile the core Dawn CSS (pscss)"
css=public/legacy/themes/suite8/css/Dawn/style
$DC exec -T app vendor/bin/pscss --style=compressed "${css}.scss" > "${css}.css.tmp"
mv "${css}.css.tmp" "${css}.css"

step 7 "scrm:copy-legacy-assets"
$DC console scrm:copy-legacy-assets

step 7b "cache:clear (so the console knows new or changed tkc commands and services)"
# The prod container cache lists the console commands. Without this clear, the first build that adds a tkc:* command
# fails with "no commands defined" (found in SUITE-181). Step 12 clears again after the config and version change.
$DC exec -T app chown -R www-data:www-data cache logs var
$DC console cache:clear

step 8 "tkc:repair --dry-run (review the SQL)"
$DC console tkc:repair --dry-run
if [ "$DRY_RUN_ONLY" -eq 1 ]; then
  echo "Stopped after the dry run (--dry-run-only)."
  exit 0
fi

step 9 "Confirm tkc:repair --execute"
if [ "$ASSUME_YES" -eq 1 ]; then
  echo "    --yes: running without review"
elif [ -t 0 ]; then
  read -r -p "Run tkc:repair --execute? [y/N] " answer
  case "$answer" in
    y|Y|yes|YES) ;;
    *) echo "Not confirmed. Stopped." >&2; exit 2 ;;
  esac
else
  echo "No TTY and no --yes: cannot ask for confirmation." >&2
  exit 2
fi

step 10 "tkc:repair --execute"
$DC console tkc:repair --execute

step 11 "tkc:config:apply"
$DC console tkc:config:apply

step 11b "tkc:legacy:asset-version (js_custom_version from the legacy theme content, SUITE-181)"
$DC console tkc:legacy:asset-version

step 12 "cache:clear (Symfony cache and the cached legacy theme CSS)"
# A root run (for example composer install without -u www-data) leaves root-owned files in the cache volumes;
# cache:clear as www-data then fails. Give the volumes back to www-data first (SUITE-44 fresh-install run).
$DC exec -T app chown -R www-data:www-data cache logs var
$DC console cache:clear
# SugarTheme caches the Dawn CSS forever and never checks its content (SUITE-60). A request that reads the custom
# file while it is empty or missing writes a copy without the TKC rules. So: delete the copy, let one session-less
# request to the login page rebuild it, and check the sentinel. Retry once, then fail the build.
legacy_css=public/legacy/cache/themes/suite8/css/Dawn/style.css
legacy_css_ok=0
for attempt in 1 2; do
  $DC exec -T -u www-data app rm -f "$legacy_css"
  curl -s -o /dev/null --max-time 60 "${APP_URL}legacy/index.php?module=Users&action=Login" || true
  if $DC exec -T -u www-data app grep -q -- '--tkc-theme-build' "$legacy_css"; then
    legacy_css_ok=1; break
  fi
  echo "    cached legacy CSS has no --tkc-theme-build (attempt ${attempt})" >&2
done

step 13 "Smoke test ${APP_URL}"
code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$APP_URL" || true)"
echo "    HTTP ${code}"
[ "$code" = "200" ]

step 14 "Cached legacy CSS contains --tkc-theme-build and the legacy login page links Dawn/style.css?v="
if [ "${legacy_css_ok}" -ne 1 ]; then
  echo "The cached legacy CSS ${legacy_css} has no TKC rules. Delete it and rebuild." >&2
  exit 1
fi
$DC exec -T -u www-data app grep -q -- '--tkc-theme-build' "$legacy_css"
# The Angular login page has no legacy CSS link. The legacy login page needs no session and renders the theme CSS.
legacy_link="$(curl -s --max-time 30 "${APP_URL}legacy/index.php?module=Users&action=Login" | grep -o 'Dawn/style\.css?v=[A-Za-z0-9_-]*' | head -n 1 || true)"
if [ -z "$legacy_link" ]; then
  echo "The legacy login page has no Dawn/style.css?v= link." >&2
  exit 1
fi
echo "    legacy CSS link: ${legacy_link}"
echo "    OK"

trap - ERR
echo "Build chain finished."
