#!/usr/bin/env python3
"""Build test (SUITE-57): the newest shell CSS bundle holds the TKC theme.

Checks public/dist/styles*.css (newest by mtime):
  1. it contains the sentinel custom property --tkc-theme-build;
  2. a TKC .navbar rule that sets --tkc-navbar-bg comes after the last core .navbar rule;
  3. the last rule for the radioenum inputs that sets display does not set display: none (SUITE-186:
     core hides the radios, the TKC override must keep them in the tab order).
  4. (SUITE-194) the Actions menu items stay readable: (a) no rule that sets colour, fill or background to a
     --tkc-header-action- value hits .settings-button, .action-button, .btn-secondary or .btn-outline-secondary
     without :not(.dropdown-item); (b) the last colour rule of a.dropdown-item in the two menus uses
     var(--tkc-header-action-text); (c) the last background-color rule of the two menus uses
     var(--tkc-header-action-menu-bg); (d) the core selector ".dropdown-button-secondary .dropdown-menu a" exists.
Exit 0 ok, 1 on a failure. Run: python3 -I scripts/theme/check_bundle.py [dist-dir]
"""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SENTINEL = "--tkc-theme-build"
TKC_NAVBAR = "--tkc-navbar-bg"
RADIO = re.compile(r"\.radioenum-input input\[type=['\"]?radio['\"]?\]$")
DISPLAY = re.compile(r"(?:^|;)\s*display\s*:\s*([^;!]+)")


BTN_CLASS = re.compile(r"\.(?:settings-button|action-button|btn-secondary|btn-outline-secondary)(?![\w-])")
DECL = re.compile(r"(?:^|;)\s*([a-z-]+)\s*:\s*([^;]+)")
MENUS = (".button-group-dropdown .dropdown-menu", ".dropdown-button-secondary .dropdown-menu")
CORE_ITEM = ".dropdown-button-secondary .dropdown-menu a"


def norm(sel):
    return re.sub(r"\s+", " ", sel.strip())


def check_menus(rules):
    """Check 4 (SUITE-194). rules: list of (selector list, body) in source order. Returns an error text or None."""
    rules = [([norm(s) for s in sels], body) for sels, body in rules]
    for sels, body in rules:
        decls = DECL.findall(body)
        if not any(p in ("color", "fill", "background", "background-color") and "--tkc-header-action-" in v
                   for p, v in decls):
            continue
        for sel in sels:
            last = re.split(r"[\s>+~]+", sel)[-1]
            if BTN_CLASS.search(last) and ":not(.dropdown-item)" not in last:
                return f"(a) rule '{sel}' sets a --tkc-header-action- value and also hits menu items (a.dropdown-item)"

    def last_value(selector, prop):
        found = None
        for sels, body in rules:
            if selector in sels:
                for p, v in DECL.findall(body):
                    if p == prop:
                        found = v.strip()
        return found

    for menu in MENUS:
        v = last_value(menu + " a.dropdown-item", "color")
        if v is None or "var(--tkc-header-action-text)" not in v:
            return f"(b) last color of '{menu} a.dropdown-item' is {v!r}, not var(--tkc-header-action-text)"
    for menu in MENUS:
        v = last_value(menu, "background-color")
        if v is None or "var(--tkc-header-action-menu-bg)" not in v:
            return f"(c) last background-color of '{menu}' is {v!r}, not var(--tkc-header-action-menu-bg)"
    if not any(CORE_ITEM in sels for sels, _ in rules):
        return f"(d) core selector '{CORE_ITEM}' is missing (changed upstream?)"
    return None


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
    # Rule blocks. The lookbehind does not consume the "}" of the rule before, so back-to-back rules all match.
    rules = []
    radio_display = []
    all_rules = []
    for m in re.finditer(r"(?:^|(?<=\}))\s*([^{}]*)\{([^{}]*)\}", css):
        selectors = [s.strip() for s in m.group(1).split(",")]
        all_rules.append((selectors, m.group(2)))
        if ".navbar" in selectors:
            rules.append((m.start(), m.group(2)))
        if any(RADIO.search(sel) for sel in selectors):
            radio_display += [d.strip() for d in DISPLAY.findall(m.group(2))]
    tkc = [pos for pos, body in rules if TKC_NAVBAR in body]
    core = [pos for pos, body in rules if TKC_NAVBAR not in body]
    if not tkc:
        return fail(f"{bundle.name}: no .navbar rule with {TKC_NAVBAR}")
    if not core:
        return fail(f"{bundle.name}: no core .navbar rule found (selector changed upstream?)")
    if max(core) > max(tkc):
        return fail(f"{bundle.name}: a core .navbar rule comes after the TKC one (cascade order)")
    if not radio_display:
        return fail(f"{bundle.name}: no .radioenum-input radio rule sets display (selector changed upstream?)")
    if radio_display[-1] == "none":
        return fail(f"{bundle.name}: the last .radioenum-input radio rule sets display: none (keyboard cannot reach it)")
    menu_err = check_menus(all_rules)
    if menu_err:
        return fail(f"{bundle.name}: check 4 {menu_err}")
    print(f"check_bundle: OK {bundle.name} (sentinel at {sent}, core .navbar x{len(core)} before TKC .navbar, "
          f"radio display {radio_display[-1]}, menu items ok)")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
