#!/usr/bin/env bash
# Run the TKC tests (SUITE-46).
#   scripts/test.sh [--php] [--ng] [--theme]    (no flag = all parts)
# PHP runs in the app container (needs "docker/dc.sh up -d"); NG and THEME run on the host.
# Exit code is non-zero if any selected part fails.
cd "$(dirname "$0")/.." || exit 1

php=0; ng=0; theme=0
if [ $# -eq 0 ]; then php=1; ng=1; theme=1; fi
for a in "$@"; do
  case "$a" in
    --php) php=1 ;; --ng) ng=1 ;; --theme) theme=1 ;;
    *) echo "Unknown option: $a (use --php, --ng, --theme)" >&2; exit 2 ;;
  esac
done

rc=0; summary=""
run() { # name, command...
  local name="$1"; shift
  echo "=== $name ==="
  if "$@"; then summary+="$name: PASS"$'\n'; else summary+="$name: FAIL"$'\n'; rc=1; fi
}

[ $php -eq 1 ] && run PHP docker/dc.sh exec -T -u www-data app vendor/bin/phpunit -c extensions/tkc/phpunit.xml.dist
[ $ng -eq 1 ] && run NG yarn ng test tkc --watch=false --browsers=ChromeHeadless
theme_tests() {
  python3 -I -m unittest discover scripts/theme/tests || return 1
  python3 -I scripts/theme/check_bundle.py   # fails if public/dist is not built (SUITE-57)
}
[ $theme -eq 1 ] && run THEME theme_tests

echo "=== Summary ==="
printf '%s' "$summary"
exit $rc
