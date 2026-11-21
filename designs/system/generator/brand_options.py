"""SUITE-180: brand colour options section for design-system.html (proposal only).

Builds the HTML for the "Brand colour options" section. Colours are oklch tuples, the same
way tokens_gen.py defines the production ramps; hex is computed for the contrast checks.
Nothing here feeds tokens.json. The preview variables (--bo-*) are scoped to the section.
"""
import os, re
import tokens_gen as t

_LOGO_SVG = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "..",
                         "public", "legacy", "custom", "themes", "suite8", "images", "company_logo.svg")

def _logo_paths():
    """Inner <path> elements of the real TKC Digital logo (fill=currentColor in the wrapper)."""
    src = open(_LOGO_SVG, encoding="utf-8").read()
    paths = re.findall(r'<path d="[^"]+"\s*/>', src)
    assert len(paths) == 5, "company_logo.svg changed: expected 5 paths"
    return "".join(paths)

def logo_svg(px, label=False):
    attrs = (f'role="img" aria-label="TKC Digital"' if label else 'aria-hidden="true" focusable="false"')
    return (f'<svg {attrs} viewBox="0 0 1200 1200" width="{px}" height="{px}" fill="currentColor" class="bo-logo-svg">'
            + _logo_paths() + '</svg>')

PINK_HEX = t.PINK_HEX         # TKC Digital logo pink (SUITE-59): one definition, in tokens_gen.py
PINK_H = t.PINK_H
PINK = t.PINK
GUIDE_PINK_HEX = "#ea4d94"    # brand guide 2024 value, close to the logo
GUIDE_GREY_HEX = "#737373"    # brand guide grey

STEPS = t.STEPS
def ramp(hue, chroma):
    return {s: (t.L[s], chroma[s], hue) for s in STEPS}

def neutral(hue, c):
    d = {k: (v, 0 if k == 0 else c, hue) for k, v in t.NEUT.items()}
    d[0] = (1.0, 0, hue)
    return d

def css(c):
    l, ch, h = c
    return f"oklch({l:.3f} {ch:.3f} {h:g})"

def hexof(c):
    return t.to_hex(t.oklch_to_rgb(*c))

def role_set(spec):
    return spec

def build_options():
    """Return list of dict(option) with light/dark role colour tuples."""
    teal = ramp(195, t.CB)
    nA = neutral(195, .009)
    opts = []

    # --- Option A: teal UI primary + brand pink accent tokens (logo only)
    opts.append(dict(
        key="a", name="Option A (approved): teal UI, pink only for the logo",
        blurb="The UI keeps the teal primary (hue 195). The brand pink enters the tokens as the primitive <code>--tkc-color-brand-pink</code> (fixed hue) and the semantic <code>--tkc-logo</code>. Pink colours the logo only. It is never used for text, borders, focus rings or buttons.",
        light=dict(surface=nA[0], canvas=nA[50], text=nA[900], primary=teal[700], hover=teal[800], on=nA[0],
                   subtle_bg=teal[50], subtle_text=teal[800], link=teal[700], focus=teal[600],
                   nav_bg=teal[900], nav_text=nA[50], nav_ind=teal[300], logo=PINK),
        dark=dict(surface=nA[950], canvas=nA[975], text=nA[50], primary=teal[400], hover=teal[300], on=nA[990],
                  subtle_bg=teal[950], subtle_text=teal[200], link=teal[300], focus=teal[300],
                  nav_bg=nA[990], nav_text=nA[100], nav_ind=teal[400], logo=PINK),
        tokens="<code>brand</code> ramp stays hue 195. New: <code>--tkc-color-brand-pink</code> (<code>oklch(0.660 0.195 356.5)</code>, <code>#e95295</code>) and <code>--tkc-logo</code> (both modes refer to it)."))

    return opts

def pairs(r):
    """(label, fg, bg, min) for one mode's role dict"""
    return [
        ("Label on primary button", r["on"], r["primary"], 4.5),
        ("Label on primary button, hover", r["on"], r["hover"], 4.5),
        ("Link text on surface", r["link"], r["surface"], 4.5),
        ("Link text on canvas", r["link"], r["canvas"], 4.5),
        ("Subtle text on subtle background", r["subtle_text"], r["subtle_bg"], 4.5),
        ("Body text on canvas", r["text"], r["canvas"], 4.5),
        ("Focus ring vs surface (UI 3:1)", r["focus"], r["surface"], 3),
        ("Primary fill vs surface (UI 3:1)", r["primary"], r["surface"], 3),
        ("Navbar text on navbar", r["nav_text"], r["nav_bg"], 4.5),
        ("Navbar active indicator vs navbar (UI 3:1)", r["nav_ind"], r["nav_bg"], 3),
        ("Logo pink vs navbar (graphic 3:1)", r["logo"], r["nav_bg"], 3),
        ("Logo pink vs login surface (graphic 3:1)", r["logo"], r["surface"], 3),
    ]

def ratio_of(a, b):
    return t.ratio(hexof(a), hexof(b))

RAW = [  # raw brand colours used as-is: shows why they need a darker step for text/labels
    ("White label on brand pink", "#ffffff", PINK_HEX, 4.5),
    ("Brand pink as text on white", PINK_HEX, "#ffffff", 4.5),
    ("Brand pink as text on canvas (neutral-50)", PINK_HEX, None, 4.5),
    ("Brand pink as UI fill / graphic on white (3:1)", PINK_HEX, "#ffffff", 3),
    ("Brand grey #737373 as text on white", GUIDE_GREY_HEX, "#ffffff", 4.5),
    ("Brand grey #737373 as text on canvas (neutral-50)", GUIDE_GREY_HEX, None, 4.5),
    ("Guide pink #ea4d94 as text on white (guide value)", GUIDE_PINK_HEX, "#ffffff", 4.5),
]

def txt(c):
    return "#fff" if t.lum(hexof(c)) < 0.18 else "#111"

def vars_for(r):
    m = dict(surface="surface", canvas="canvas", text="text", primary="primary", hover="hover", on="on",
             subtle_bg="subtle-bg", subtle_text="subtle-text", link="link", focus="focus",
             nav_bg="nav-bg", nav_text="nav-text", nav_ind="nav-ind", logo="logo")
    return ";".join(f"--bo-{v}:{css(r[k])}" for k, v in m.items())

def badge(ok):
    return f'<span class="res {"pass" if ok else "fail"}">{"Pass" if ok else "Fail"}</span>'

def pane(o, mode):
    r = o[mode]
    sw = [("primary-default", r["primary"]), ("primary-hover", r["hover"]), ("primary-subtle-bg", r["subtle_bg"]),
          ("primary-subtle-text", r["subtle_text"]), ("primary-on", r["on"])]
    chips = "".join(
        f'<div class="bo-chip" style="background:{css(c)};color:{txt(c)}"><b>{n}</b><span>{hexof(c)}</span></div>'
        for n, c in sw)
    return f'''<div class="bo-pane" data-mode="{mode}" style="{vars_for(r)}">
<p class="bo-mode">{mode.title()} mode</p>
<div class="bo-chips">{chips}</div>
<div class="bo-nav"><span class="bo-logo">{logo_svg(32, label=(o["key"]=="a" and mode=="light"))}</span><span class="bo-navitem">Accounts</span><span class="bo-navitem is-active">Cases</span><span class="bo-navitem">Reports</span></div>
<div class="bo-login" role="group" aria-label="Login card preview"><span class="bo-logo bo-logo--login">{logo_svg(64)}</span><div class="bo-login-body"><b>Sign in</b><span class="bo-login-field">User name</span><button type="button" class="bo-btn">Sign in</button></div></div>
<div class="bo-demo">
<button type="button" class="bo-btn">Save case</button>
<button type="button" class="bo-btn bo-btn--focus">Focus ring</button>
<a href="#brand-options" class="bo-link">Open case CS-260114-000231</a>
<span class="bo-pill">In progress</span>
</div></div>'''

def table(o):
    pl = pairs(o["light"]); pd = pairs(o["dark"])
    rows = []
    fails = 0
    for (lab, f1, b1, mn), (_, f2, b2, _) in zip(pl, pd):
        r1 = ratio_of(f1, b1); r2 = ratio_of(f2, b2)
        o1, o2 = r1 >= mn, r2 >= mn
        fails += (not o1) + (not o2)
        rows.append(f'<tr><td>{lab}</td><td>{mn}:1</td><td>{r1:.2f}:1 {badge(o1)}</td><td>{r2:.2f}:1 {badge(o2)}</td></tr>')
    o["fails"] = fails
    return ('<div class="stage scroll-x" style="padding:0"><table class="tbl-doc"><thead><tr><th>Pair</th><th>Needs</th><th>Light</th><th>Dark</th></tr></thead><tbody>'
            + "".join(rows) + '</tbody></table></div>')

def raw_table(canvas_light):
    rows = []
    for lab, f, b, mn in RAW:
        bg = b or hexof(canvas_light)
        r = t.ratio(f, bg)
        rows.append(f'<tr><td>{lab}</td><td><code>{f}</code> on <code>{bg}</code></td><td>{mn}:1</td><td>{r:.2f}:1 {badge(r >= mn)}</td></tr>')
    return ('<div class="stage scroll-x" style="padding:0"><table class="tbl-doc"><thead><tr><th>Raw brand colour use</th><th>Pair</th><th>Needs</th><th>Ratio</th></tr></thead><tbody>'
            + "".join(rows) + '</tbody></table></div>')

CSS = '''
.bo-opt{margin-top:var(--tkc-space-8)}
.bo-opt>h4{font-size:var(--tkc-font-size-2xl);margin-bottom:var(--tkc-space-1)}
.bo-opt>p{max-width:62rem;margin:var(--tkc-space-1) 0 var(--tkc-space-3)}
.bo-panes{display:grid;grid-template-columns:repeat(auto-fit,minmax(21rem,1fr));gap:var(--tkc-space-4);margin-bottom:var(--tkc-space-4)}
.bo-pane{background:var(--bo-surface);color:var(--bo-text);border:1px solid var(--tkc-border-default);border-radius:var(--tkc-radius-lg);padding:var(--tkc-space-4);display:grid;gap:var(--tkc-space-3);align-content:start}
.bo-mode{margin:0;font-size:var(--tkc-font-size-xs);text-transform:uppercase;letter-spacing:var(--tkc-letter-spacing-caps);font-weight:var(--tkc-font-weight-semibold);color:var(--bo-text)}
.bo-chips{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:var(--tkc-space-1)}
.bo-chip{border-radius:var(--tkc-radius-sm);padding:var(--tkc-space-2);font-size:var(--tkc-font-size-2xs);line-height:1.25;display:grid;gap:2px;min-height:4.25rem;align-content:end;border:1px solid oklch(0.5 0 0 / .25);overflow-wrap:anywhere}
.bo-chip b{font-weight:var(--tkc-font-weight-semibold)}
.bo-nav{display:flex;align-items:center;gap:var(--tkc-space-4);background:var(--bo-nav-bg);color:var(--bo-nav-text);border-radius:var(--tkc-radius-md);padding:0 var(--tkc-space-3);height:var(--tkc-layout-navbar-height);overflow:hidden}
.bo-logo{display:inline-flex;align-items:center;gap:var(--tkc-space-2);color:var(--bo-logo);white-space:nowrap}
.bo-logo{line-height:0}
.bo-logo-svg{display:block}
.bo-login{display:flex;align-items:center;gap:var(--tkc-space-5);background:var(--bo-canvas);border-radius:var(--tkc-radius-md);padding:var(--tkc-space-4)}
.bo-login-body{display:grid;gap:var(--tkc-space-2);flex:1;min-width:0;background:var(--bo-surface);border:1px solid var(--tkc-border-default);border-radius:var(--tkc-radius-lg);padding:var(--tkc-space-3)}
.bo-login-field{border:1px solid var(--bo-text);border-radius:var(--tkc-radius-md);padding:var(--tkc-space-2) var(--tkc-space-3);font-size:var(--tkc-font-size-sm);color:var(--bo-text)}
.bo-navitem{font-size:var(--tkc-font-size-sm);height:100%;display:inline-flex;align-items:center;border-bottom:3px solid transparent}
.bo-navitem.is-active{border-bottom-color:var(--bo-nav-ind);font-weight:var(--tkc-font-weight-semibold)}
.bo-demo{display:flex;flex-wrap:wrap;align-items:center;gap:var(--tkc-space-3) var(--tkc-space-4)}
.bo-btn{font:inherit;font-weight:var(--tkc-font-weight-semibold);font-size:var(--tkc-font-size-md);min-height:var(--tkc-density-touch-target);padding:0 var(--tkc-density-btn-pad-x);border:0;border-radius:var(--tkc-radius-md);background:var(--bo-primary);color:var(--bo-on);cursor:pointer}
.bo-btn:hover{background:var(--bo-hover)}
.bo-btn:focus-visible,.bo-btn--focus{outline:var(--tkc-focus-ring-width) solid var(--bo-focus);outline-offset:var(--tkc-focus-ring-offset)}
.bo-link{color:var(--bo-link);font-size:var(--tkc-font-size-md)}
.bo-link:focus-visible{outline:var(--tkc-focus-ring-width) solid var(--bo-focus);outline-offset:var(--tkc-focus-ring-offset)}
.bo-pill{background:var(--bo-subtle-bg);color:var(--bo-subtle-text);border-radius:var(--tkc-radius-pill);padding:var(--tkc-space-1) var(--tkc-space-3);font-size:var(--tkc-font-size-xs);font-weight:var(--tkc-font-weight-semibold)}
.bo-verdict{font-size:var(--tkc-font-size-sm);color:var(--tkc-text-muted);margin:var(--tkc-space-2) 0 0}
@media (max-width:40rem){.bo-chips{grid-template-columns:repeat(2,minmax(0,1fr))}}
'''

def section():
    opts = build_options()
    parts = [f'<style id="bo-style">{CSS}</style>',
             '<section class="sec" id="brand-options"><h2>Brand colour (SUITE-180, approved: option A) <small>The UI keeps the teal primary. The brand pink is used for the logo only, through <code>--tkc-logo</code>. Options B and C rejected 2026-11-21 (commit e5f6f2d8a). Previews use local <code>--bo-*</code> variables scoped to this section.</small></h2>',
             '<div class="alert-brand tkc-alert tkc-alert--info" role="note"><svg aria-hidden="true" class="i"><use href="#i-info"/></svg><div><b>Source of the brand colours</b>The logo pink is <code>#e95295</code> (oklch 0.66 0.195 356.5). The 2024 brand guide lists pink <code>#EA4D94</code> and grey <code>#737373</code> and the typeface Roboto. The logo file wins for the logo. The navbar and login previews use the real logo file (<code>company_logo.svg</code>, one colour, <code>currentColor</code>) in the logo pink.</div></div>',
             '<h3 class="sub">Raw brand colours: why pink is for the logo only</h3>',
             '<p class="note">The brand pink and grey cannot be used as-is for text or button labels.</p>',
             raw_table(opts[0]["light"]["canvas"])]
    for o in opts:
        tbl = table(o)
        parts.append(f'<div class="bo-opt" id="bo-{o["key"]}"><h4>{o["name"]}</h4><p>{o["blurb"]}</p><p class="note"><b>Token impact.</b> {o["tokens"]}</p>')
        parts.append('<div class="bo-panes">' + pane(o, "light") + pane(o, "dark") + '</div>')
        parts.append(tbl)
        parts.append(f'<p class="bo-verdict">{"All pairs pass WCAG 2.2 AA." if o["fails"] == 0 else str(o["fails"]) + " pair(s) fail. See the rows marked Fail."}</p></div>')
    parts.append('</section>')
    return "\n".join(parts), opts
