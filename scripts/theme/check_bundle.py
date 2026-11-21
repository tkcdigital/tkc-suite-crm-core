#!/usr/bin/env python3
"""Build test (SUITE-57): the newest shell CSS bundle holds the TKC theme.

Checks public/dist/styles*.css (newest by mtime):
  1. it contains the sentinel custom property --tkc-theme-build;
  2. a TKC .navbar rule that sets --tkc-navbar-bg comes after the last core .navbar rule.
Exit 0 ok, 1 on a failure. Run: python3 -I scripts/theme/check_bundle.py [dist-dir]
"""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SENTINEL = "--tkc-theme-build"
TKC_NAVBAR = "--tkc-navbar-bg"


def fail(msg):
    print(f"check_bundle: FAIL: {msg}", file=sys.stderr)
    return 1


def main(argv):
    dist = Path(argv[1]) if len(argv) > 1 else ROOT / "public" / "dist"
    files = sorted(dist.glob("styles*.css"), key=lambda p: p.stat().st_mtime, reverse=True)
    if not files:
        return fail(f"no styles*.css in {dist}")
    bundle = files[0]
    css = bundle.read_text(encoding="utf-8", errors="replace")
    if SENTINEL not in css:
        return fail(f"{bundle.name} has no {SENTINEL}: the TKC theme is not in the bundle (S1 styles index?)")
    sent = css.index(SENTINEL)
    # Rule blocks whose selector list holds exactly ".navbar".
    rules = []
    for m in re.finditer(r"(^|\})\s*([^{}]*)\{([^{}]*)\}", css):
        selectors = [s.strip() for s in m.group(2).split(",")]
        if ".navbar" in selectors:
            rules.append((m.start(), m.group(3)))
    tkc = [pos for pos, body in rules if TKC_NAVBAR in body]
    core = [pos for pos, body in rules if TKC_NAVBAR not in body]
    if not tkc:
        return fail(f"{bundle.name}: no .navbar rule with {TKC_NAVBAR}")
    if not core:
        return fail(f"{bundle.name}: no core .navbar rule found (selector changed upstream?)")
    if max(core) > max(tkc):
        return fail(f"{bundle.name}: a core .navbar rule comes after the TKC one (cascade order)")
    print(f"check_bundle: OK {bundle.name} (sentinel at {sent}, core .navbar x{len(core)} before TKC .navbar)")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
