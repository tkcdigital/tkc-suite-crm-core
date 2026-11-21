#!/usr/bin/env python3
"""Generate the TKC theme files from designs/system/tokens.json (decision D6).

Outputs (all deterministic, LF, no timestamps):
  A  extensions/tkc/app/src/theme/_tkc-tokens.scss            (Angular, tokens only)
  L  extensions/tkc/legacy-theme/00-tokens.generated.css       (legacy tokens, rem -> px)
  S  public/legacy/custom/themes/suite8/css/Dawn/style.css     (L + hand partials 10-*.css ...)

Usage (run from anywhere):
  python3 -I scripts/theme/build_theme.py            write the three files
  python3 -I scripts/theme/build_theme.py --check    fail if a file differs or a hand file has a raw colour

Only the Python standard library is used. tokens.json is the input contract.
"""
import argparse
import hashlib
import json
import os
import re
import sys
from pathlib import Path

TOKENS_REL = "designs/system/tokens.json"
OUT_SCSS_REL = "extensions/tkc/app/src/theme/_tkc-tokens.scss"
LEGACY_DIR_REL = "extensions/tkc/legacy-theme"
OUT_LEGACY_TOKENS_REL = LEGACY_DIR_REL + "/00-tokens.generated.css"
OUT_STYLE_REL = "public/legacy/custom/themes/suite8/css/Dawn/style.css"
ANGULAR_THEME_DIR_REL = "extensions/tkc/app/src/theme"

FONT_URL_BASE = "/legacy/custom/themes/suite8/fonts/"
LATIN_RANGE = (
    "U+0020-007E, U+00A0-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, "
    "U+2013-2014, U+2018-201A, U+201C-201E, U+2020-2022, U+2026, U+2030, "
    "U+2039-203A, U+2044, U+20AC, U+2122, U+2212, U+FB01-FB02"
)
ROBOTO_LATIN_RANGE = (
    "U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,"
    "U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD"
)
ROBOTO_LATIN_EXT_RANGE = (
    "U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,"
    "U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF"
)
# (family, weight, file, unicode-range). Roboto: variable weight axis from npm
# @fontsource-variable/roboto 5.3.0 (OFL). Plex Mono: Latin subset of the IBM Plex release (OFL).
FONTS = [
    ("Roboto", "400 700", "Roboto-Variable-latin-ext.woff2", ROBOTO_LATIN_EXT_RANGE),
    ("Roboto", "400 700", "Roboto-Variable-latin.woff2", ROBOTO_LATIN_RANGE),
    ("IBM Plex Mono", "400", "IBMPlexMono-Regular-Latin1.woff2", LATIN_RANGE),
]

REF_RE = re.compile(r"\{([A-Za-z0-9_.\-]+)\}")
REM_RE = re.compile(r"(?<![\w.])(-?\d*\.?\d+)rem\b")


class TokenError(Exception):
    pass


# ---------------------------------------------------------------- tokens


def _leaves(node, path=()):
    """Yield (path, leaf) in JSON order. A leaf is a dict with a 'value' key."""
    for key, val in node.items():
        if key.startswith("$"):
            continue
        if not isinstance(val, dict):
            raise TokenError("unexpected non-object at " + ".".join(path + (key,)))
        if "value" in val:
            yield path + (key,), val
        else:
            yield from _leaves(val, path + (key,))


def _group(tokens, *path):
    node = tokens
    for p in path:
        if not isinstance(node, dict) or p not in node:
            raise TokenError("missing token group: " + ".".join(path))
        node = node[p]
    return node


def collect(tokens):
    """Validate tokens and return resolved ordered lists of (css_name, css_value)."""
    primitive_index = {}
    for path, leaf in _leaves(_group(tokens, "primitive"), ("primitive",)):
        if "css" not in leaf:
            raise TokenError("leaf without css name: " + ".".join(path))
        primitive_index[".".join(path)] = leaf["css"]

    def resolve(value, where):
        def sub(match):
            ref = match.group(1)
            if ref not in primitive_index:
                raise TokenError("unresolved reference {%s} at %s" % (ref, where))
            return "var(%s)" % primitive_index[ref]

        return REF_RE.sub(sub, str(value))

    def block(path_prefix):
        out, seen = [], set()
        for path, leaf in _leaves(_group(tokens, *path_prefix), tuple(path_prefix)):
            where = ".".join(path)
            if "css" not in leaf:
                raise TokenError("leaf without css name: " + where)
            name = leaf["css"]
            if name in seen:
                raise TokenError("duplicate css name %s at %s" % (name, where))
            seen.add(name)
            out.append((name, resolve(leaf["value"], where)))
        if not out:
            raise TokenError("empty token group: " + ".".join(path_prefix))
        return out

    primitives = block(("primitive",))
    light = block(("semantic", "light"))
    dark = block(("semantic", "dark"))
    light_names = {n for n, _ in light}
    for name, _ in dark:
        if name not in light_names:
            raise TokenError("dark token %s has no light counterpart" % name)
    compact = block(("density", "compact"))
    comfortable = block(("density", "comfortable"))
    if [n for n, _ in compact] != [n for n, _ in comfortable]:
        raise TokenError("density compact and comfortable define different tokens")
    return {
        "primitives": primitives,
        "light": light,
        "dark": dark,
        "compact": compact,
        "comfortable": comfortable,
    }


# ---------------------------------------------------------------- rendering


def _decls(items, indent):
    pad = " " * indent
    return "".join("%s%s: %s;\n" % (pad, n, v) for n, v in items)


def _font_faces(font_url_base=FONT_URL_BASE):
    out = []
    for family, weight, fname, urange in FONTS:
        out.append(
            "@font-face {\n"
            '  font-family: "%s";\n'
            "  font-style: normal;\n"
            "  font-weight: %s;\n"
            "  font-display: swap;\n"
            '  src: url("%s%s") format("woff2");\n'
            "  unicode-range: %s;\n"
            "}\n" % (family, weight, font_url_base, fname, urange)
        )
    return "\n".join(out)


def render_tokens_css(data, digest, dark_auto=False):
    """Render the token blocks as plain CSS (valid SCSS too). Order per components.md 16, X3."""
    parts = []
    parts.append(
        ":root {\n"
        + _decls(data["primitives"], 2)
        + '  --tkc-theme-build: "%s";\n' % digest
        + "}\n"
    )
    parts.append(":root {\n" + _decls(data["light"], 2) + "  color-scheme: light;\n}\n")
    if dark_auto:
        parts.append(
            "@media (prefers-color-scheme: dark) {\n"
            '  :root:not([data-theme="light"]) {\n'
            + _decls(data["dark"], 4)
            + "    color-scheme: dark;\n  }\n}\n"
        )
    parts.append(
        ':root[data-theme="dark"] {\n' + _decls(data["dark"], 2) + "  color-scheme: dark;\n}\n"
    )
    parts.append(":root {\n" + _decls(data["compact"], 2) + "}\n")
    parts.append(':root[data-density="comfortable"] {\n' + _decls(data["comfortable"], 2) + "}\n")
    parts.append(
        "@media (pointer: coarse) {\n"
        '  :root:not([data-density="compact"]) {\n' + _decls(data["comfortable"], 4) + "  }\n}\n"
    )
    durations = [n for n, _ in data["primitives"] if n.startswith("--tkc-duration-")]
    if durations:
        parts.append(
            "@media (prefers-reduced-motion: reduce) {\n  :root {\n"
            + "".join("    %s: 1ms;\n" % n for n in durations)
            + "  }\n}\n"
        )
    parts.append(_font_faces())
    return "\n".join(parts)


def rem_to_px(css, base=16):
    """Replace every <n>rem by the px value (X4: legacy sets html font-size 10px)."""

    def sub(match):
        px = float(match.group(1)) * base
        text = ("%.4f" % px).rstrip("0").rstrip(".")
        return (text if text not in ("", "-0") else "0") + "px"

    return REM_RE.sub(sub, css)


def sha12(raw):
    return hashlib.sha256(raw).hexdigest()[:12]


def render_all(root, dark_auto=False):
    """Return {relative path: text} for the three generated files."""
    root = Path(root)
    raw = (root / TOKENS_REL).read_bytes()
    try:
        tokens = json.loads(raw.decode("utf-8"))
    except ValueError as exc:
        raise TokenError("tokens.json is not valid JSON: %s" % exc)
    data = collect(tokens)
    digest = sha12(raw)
    body = render_tokens_css(data, digest, dark_auto)

    scss = (
        "// GENERATED by scripts/theme/build_theme.py from designs/system/tokens.json.\n"
        "// Do not edit. Run: python3 -I scripts/theme/build_theme.py\n\n" + body
    )
    legacy_header = (
        "/* GENERATED by scripts/theme/build_theme.py from designs/system/tokens.json.\n"
        "   Do not edit. All lengths are px (converted from root-relative units at base 16). */\n\n"
    )
    legacy_tokens = legacy_header + rem_to_px(body)

    legacy_dir = root / LEGACY_DIR_REL
    gen_name = Path(OUT_LEGACY_TOKENS_REL).name
    partials = sorted(
        p for p in legacy_dir.glob("*.css") if p.name != gen_name and p.is_file()
    ) if legacy_dir.is_dir() else []
    style = legacy_tokens
    for p in partials:
        text = p.read_text(encoding="utf-8").replace("\r\n", "\n")
        if not text.endswith("\n"):
            text += "\n"
        style += "\n/* ==== %s ==== */\n%s" % (p.name, text)
    return {OUT_SCSS_REL: scss, OUT_LEGACY_TOKENS_REL: legacy_tokens, OUT_STYLE_REL: style}


# ---------------------------------------------------------------- lint

HEX_RE = re.compile(r"(?<![\w&])#(?:[0-9a-fA-F]{8}|[0-9a-fA-F]{6}|[0-9a-fA-F]{4}|[0-9a-fA-F]{3})(?![\w-])")
FUNC_RE = re.compile(r"\b(?:rgba?|hsla?)\s*\(", re.IGNORECASE)
CALC_PLUS_RE = re.compile(r"calc\([^;{}]*\s\+\s", re.IGNORECASE)
# X9: whitespace, then ":" in a selector (descendant combinator + pseudo, "body :is("). Legacy cssmin strips the space.
# A comma before the space is fine ("a, :hover" is not a descendant pattern).
SPACE_PSEUDO_RE = re.compile(r"[^\s,]\s+:")
# X10: the token z-scale (0-700) is below the core overlay scale (dropdown 1000, modal backdrop 1050 !important,
# modal 1055, toast 1510). A hand file must not put an overlay on a token z-index (SUITE-58: the modal window went
# under its backdrop). Local layers (base, sticky, navbar inside the fixed top bar) stay allowed.
Z_OVERLAY_RE = re.compile(r"z-index\s*:[^;{}]*--tkc-z-(?:dropdown|drawer|modal|toast|tooltip)\b", re.IGNORECASE)


def _strip_comments(text, line_comments):
    text = re.sub(r"/\*.*?\*/", lambda m: re.sub(r"[^\n]", " ", m.group(0)), text, flags=re.S)
    if line_comments:
        text = re.sub(r"(?<![:\w])//[^\n]*", "", text)
    return text


def lint_text(text, legacy, scss):
    """Return a list of problem strings for one hand-written file's text."""
    clean = _strip_comments(text, scss)
    problems = []
    for match in re.finditer(r"[^;{}]*([;{}]|$)", clean):
        chunk, term = match.group(0), match.group(1)
        start = match.start()
        if term == "{" and legacy and not chunk.strip().startswith("@") and SPACE_PSEUDO_RE.search(chunk.strip()):
            line = clean.count("\n", 0, start + len(chunk) - len(chunk.lstrip())) + 1
            problems.append("line %d: selector with whitespace before ':' (X9)" % line)
        if term == "{" or not chunk.strip():
            continue  # selector or at-rule prelude: not a declaration
        line = clean.count("\n", 0, start + len(chunk) - len(chunk.lstrip())) + 1
        for m in HEX_RE.finditer(chunk):
            problems.append("line %d: raw hex colour %s" % (line, m.group(0)))
        for m in FUNC_RE.finditer(chunk):
            problems.append("line %d: raw colour function %s" % (line, m.group(0).strip()))
        if legacy and CALC_PLUS_RE.search(chunk):
            problems.append("line %d: calc() with ' + ' (X6)" % line)
        if Z_OVERLAY_RE.search(chunk):
            problems.append("line %d: z-index with an overlay token, below the core overlay scale (X10)" % line)
    return problems


def hand_files(root):
    root = Path(root)
    scss_files = [
        p
        for p in sorted((root / ANGULAR_THEME_DIR_REL).rglob("*.scss"))
        if p.name != Path(OUT_SCSS_REL).name
    ]
    legacy_dir = root / LEGACY_DIR_REL
    gen_name = Path(OUT_LEGACY_TOKENS_REL).name
    css_files = (
        [p for p in sorted(legacy_dir.glob("*.css")) if p.name != gen_name]
        if legacy_dir.is_dir()
        else []
    )
    return scss_files, css_files


def lint_all(root):
    root = Path(root)
    scss_files, css_files = hand_files(root)
    report = []
    for p in scss_files:
        for prob in lint_text(p.read_text(encoding="utf-8"), legacy=False, scss=True):
            report.append("%s: %s" % (p.relative_to(root), prob))
    for p in css_files:
        for prob in lint_text(p.read_text(encoding="utf-8"), legacy=True, scss=False):
            report.append("%s: %s" % (p.relative_to(root), prob))
    return report


# ---------------------------------------------------------------- main


def write_outputs(root, outputs):
    for rel, text in outputs.items():
        path = Path(root) / rel
        path.parent.mkdir(parents=True, exist_ok=True)
        # Atomic: legacy SugarTheme caches the CSS forever, and a request that reads a truncated file
        # would cache CSS without the TKC rules (SUITE-60). Write a temp file, then replace.
        tmp = path.with_name(path.name + ".tmp")
        with open(tmp, "w", encoding="utf-8", newline="\n") as fh:
            fh.write(text)
        os.replace(tmp, path)


def differing(root, outputs):
    diff = []
    for rel, text in outputs.items():
        path = Path(root) / rel
        if not path.is_file() or path.read_bytes() != text.encode("utf-8"):
            diff.append(rel)
    return diff


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__.split("\n")[0])
    ap.add_argument("--check", action="store_true", help="do not write; fail on a difference or a lint error")
    ap.add_argument("--dark-auto", action="store_true", help="reserved: also emit the prefers-color-scheme dark block (default off, X3)")
    ap.add_argument("--root", default=str(Path(__file__).resolve().parents[2]), help="repository root")
    args = ap.parse_args(argv)
    root = Path(args.root)

    try:
        outputs = render_all(root, args.dark_auto)
    except (TokenError, OSError) as exc:
        print("build_theme: ERROR: %s" % exc, file=sys.stderr)
        return 1

    if args.check:
        failed = False
        diff = differing(root, outputs)
        if diff:
            failed = True
            print("build_theme: generated file differs from the generator output:", file=sys.stderr)
            for rel in diff:
                print("  " + rel, file=sys.stderr)
            print("  run: python3 -I scripts/theme/build_theme.py", file=sys.stderr)
        problems = lint_all(root)
        if problems:
            failed = True
            print("build_theme: hand-written files contain raw colours or forbidden CSS:", file=sys.stderr)
            for prob in problems:
                print("  " + prob, file=sys.stderr)
        if failed:
            return 1
        print("build_theme: --check OK (%d generated files, hand files clean)" % len(outputs))
        return 0

    write_outputs(root, outputs)
    for rel in outputs:
        print("build_theme: wrote " + rel)
    return 0


if __name__ == "__main__":
    sys.exit(main())
