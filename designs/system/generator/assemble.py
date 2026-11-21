import sys; sys.path.insert(0,'.')
import tokens_gen as t, build_tokens as b
css=open('tokens.css').read()
scope=b.css_block('.scope-light',0,b.sh_l)+"\n"+b.css_block('.scope-dark',1,b.sh_d)+"\n"
css+="\n/* doc-only scopes: show both modes side by side */\n"+scope
tpl=open('template.html').read()
def txt(h):
    return '#fff' if t.lum(h)<0.18 else '#111'
# ramps
names=[("brand","Brand (hue from --tkc-brand-hue)",t.STEPS),("neutral","Neutral (tinted with brand hue)",[0,50,100,200,300,400,500,600,700,800,900,950,975,990])]+[(n,n.title()+" (fixed hue)",t.STEPS) for n in t.HUES]
r=[]
for n,title,steps in names:
    r.append(f'<p class="cap">{title}</p><div class="ramp">')
    for s in steps:
        k=f"color-{n}-{s}"; h=t.prim[k][1]
        r.append(f'<div class="chip" style="background:var(--tkc-{k});color:{txt(h)}"><b>{s}</b>{h}</div>')
    r.append('</div>')
ramps="\n".join(r)
# semantic
rows=['<div class="stage scroll-x" style="padding:0;max-height:34rem;overflow:auto"><table class="tbl-doc"><thead><tr><th>Token</th><th>Light</th><th>Dark</th><th>Maps to (light / dark)</th></tr></thead><tbody>']
for k,(l,d) in t.sem.items():
    h0,h1=t.semhex[k]
    rows.append(f'<tr><td><code>--tkc-{k}</code></td><td><span class="scope-light" style="background:none"><span class="sw" style="background:var(--tkc-{k})"></span></span> <code>{h0}</code></td><td><span class="scope-dark" style="background:none"><span class="sw" style="background:var(--tkc-{k})"></span></span> <code>{h1}</code></td><td><code>{l}</code> / <code>{d}</code></td></tr>')
rows.append('</tbody></table></div>')
semantic="\n".join(rows)
# contrast
byl={}
for lab,fg,bg,mode,rt,mn,ok in t.results: byl.setdefault((lab,fg,bg,mn),{})[mode]=(rt,ok)
c=['<div class="stage scroll-x" style="padding:0;max-height:40rem;overflow:auto"><table class="tbl-doc"><thead><tr><th>Pair</th><th>Needs</th><th>Light</th><th>Dark</th></tr></thead><tbody>']
for (lab,fg,bg,mn),m in byl.items():
    def cell(mode):
        rt,ok=m[mode]
        return f'<td class="scope-{mode}" style="background:none"><span class="pair" style="color:var(--tkc-{fg});background:var(--tkc-{bg})">Aa</span> <span data-fg="--tkc-{fg}" data-bg="--tkc-{bg}" data-min="{mn}">{rt:.2f}:1</span> <span class="res {"pass" if ok else "fail"}">{"Pass" if ok else "Check"}</span></td>'
    c.append(f'<tr><td>{lab}<br><code>{fg}</code> on <code>{bg}</code></td><td>{mn}:1</td>{cell("light")}{cell("dark")}</tr>')
c.append('</tbody></table></div>')
import brand_options as bo
bo_html,_=bo.section()
tpl=tpl.replace('<!--BRANDOPTIONS-->',bo_html).replace('/*TOKENS*/',css).replace('<!--RAMPS-->',ramps).replace('<!--SEMANTIC-->',semantic).replace('<!--CONTRAST-->',"\n".join(c))
open(b.OUT+'design-system.html','w').write(tpl)
print(len(tpl))

# Also write the shared stylesheet for screen mockups (tokens + component classes, no doc chrome).
_s=tpl
_css=_s[_s.index('<style>')+7:_s.index('</style>')]
_a=_css.find('/* ===== DOC CHROME ===== */'); _b=_css.find('/* ===== COMPONENT LIBRARY (.tkc-*) ===== */')
if _a!=-1 and _b!=-1: _css=_css[:_a]+_css[_b:]
_css=_css.replace('/* Live theming for doc scopes (documentation only) */','')  # doc-page marker, not part of the stylesheet
HEADER=('/* TKC design system v0.2 — tokens + component classes (.tkc-*).\n'
        '   Generated from design-system.html by generator/assemble.py; do not edit by hand.\n'
        '   Used by the screen mockups in designs/screens/<screen>/mockup.html. */\n')
open(b.OUT+'tkc-ds.css','w').write(HEADER+_css)
