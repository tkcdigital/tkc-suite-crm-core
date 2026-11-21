import json, sys
sys.path.insert(0,'.')
import tokens_gen as t
import os
OUT=os.path.join(os.path.dirname(os.path.abspath(__file__)), "..") + "/"

# ---- non-colour primitives: name -> value (flat, css name = --tkc-<name>)
FONT_SANS='"IBM Plex Sans", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'
FONT_MONO='"IBM Plex Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace'
nc = {
 "font-family-sans":FONT_SANS, "font-family-mono":FONT_MONO,
 "font-weight-regular":"400","font-weight-medium":"500","font-weight-semibold":"600","font-weight-bold":"700",
 "font-size-2xs":"0.6875rem","font-size-xs":"0.75rem","font-size-sm":"0.8125rem","font-size-md":"0.875rem",
 "font-size-lg":"1rem","font-size-xl":"1.125rem","font-size-2xl":"1.375rem","font-size-3xl":"1.75rem","font-size-4xl":"2.25rem",
 "line-height-tight":"1.2","line-height-snug":"1.35","line-height-normal":"1.5","line-height-relaxed":"1.65",
 "letter-spacing-tight":"-0.01em","letter-spacing-normal":"0","letter-spacing-caps":"0.06em",
}
for i,v in {"0":"0","1":"0.25rem","2":"0.5rem","3":"0.75rem","4":"1rem","5":"1.25rem","6":"1.5rem","8":"2rem","10":"2.5rem","12":"3rem","16":"4rem"}.items():
    nc[f"space-{i}"]=v
nc.update({"radius-none":"0","radius-sm":"0.25rem","radius-md":"0.375rem","radius-lg":"0.625rem","radius-xl":"0.875rem","radius-pill":"999px",
 "border-width-thin":"1px","border-width-medium":"2px","border-width-thick":"3px",
 "focus-ring-width":"2px","focus-ring-offset":"2px",
 "duration-instant":"80ms","duration-fast":"120ms","duration-base":"200ms","duration-slow":"320ms",
 "easing-standard":"cubic-bezier(0.2, 0, 0, 1)","easing-enter":"cubic-bezier(0, 0, 0, 1)","easing-exit":"cubic-bezier(0.3, 0, 1, 1)","easing-emphasized":"cubic-bezier(0.3, 0, 0, 1.15)",
 "z-base":"0","z-sticky":"100","z-dropdown":"200","z-navbar":"300","z-drawer":"400","z-modal":"500","z-toast":"600","z-tooltip":"700",
 "breakpoint-sm":"750px","breakpoint-md":"1024px","breakpoint-lg":"1200px","breakpoint-xl":"1536px",
 "size-touch-min":"2.75rem","size-icon-sm":"1rem","size-icon-md":"1.25rem","size-icon-lg":"1.5rem",
 "layout-navbar-height":"3.25rem","layout-sidebar-width":"16rem","layout-content-max":"96rem","layout-field-col-min":"15rem",
 "brand-hue":str(t.BRAND_HUE),
})
# shadows: light & dark differ -> semantic
shadow_l={"1":"0 1px 2px oklch(0.25 0.02 var(--tkc-brand-hue) / 0.08)","2":"0 2px 6px oklch(0.25 0.02 var(--tkc-brand-hue) / 0.10), 0 1px 2px oklch(0.25 0.02 var(--tkc-brand-hue) / 0.06)",
 "3":"0 8px 20px oklch(0.25 0.02 var(--tkc-brand-hue) / 0.14), 0 2px 6px oklch(0.25 0.02 var(--tkc-brand-hue) / 0.08)","4":"0 20px 48px oklch(0.25 0.02 var(--tkc-brand-hue) / 0.22), 0 6px 16px oklch(0.25 0.02 var(--tkc-brand-hue) / 0.10)"}
shadow_d={"1":"0 1px 2px oklch(0 0 0 / 0.5)","2":"0 2px 6px oklch(0 0 0 / 0.5)","3":"0 8px 20px oklch(0 0 0 / 0.55)","4":"0 20px 48px oklch(0 0 0 / 0.65)"}
density={"comfortable":{"control-height":"2.75rem","row-height":"3rem","cell-pad-x":"0.75rem","cell-pad-y":"0.5rem","field-gap":"1rem","panel-pad":"1.25rem","font-size":"0.9375rem","btn-pad-x":"1rem","touch-target":"2.75rem"},
 "compact":{"control-height":"2rem","row-height":"2rem","cell-pad-x":"0.625rem","cell-pad-y":"0.25rem","field-gap":"0.625rem","panel-pad":"0.875rem","font-size":"0.875rem","btn-pad-x":"0.75rem","touch-target":"2rem"}}
# default: compact on fine pointer, comfortable on coarse -> both exposed as --tkc-density-* via data attr / media

# ---------------- tokens.json
tok={"$schema":"tkc-tokens/1","$description":"TKC design tokens. Each leaf has a 'value'; its CSS custom property is '--tkc-' + path joined by '-' (without the top-level group name), see css field. Colours: 'value' is the CSS colour (oklch with the brand hue variable), 'hex' is the resolved sRGB value at the default brand hue, used for contrast checks and for engines without oklch.",
 "$meta":{"cssPrefix":"--tkc-","brandHueToken":"--tkc-brand-hue","defaultBrandHue":t.BRAND_HUE,"themeSelector":{"light":":root","dark":":root[data-theme=\"dark\"]"},"densitySelector":"[data-density=\"compact\"|\"comfortable\"] (default compact on pointer:fine, comfortable on pointer:coarse)","status":"APPROVED (SUITE-180, 2026-11-21): primary hue 195; brand pink #e95295 for the logo only (--tkc-logo); never text"}}
prim={}
def put(d,path,val):
    for p in path[:-1]: d=d.setdefault(p,{})
    d[path[-1]]=val
for k,(v,h) in t.prim.items():
    parts=k.split('-'); # color-brand-700
    leaf={"value":v,"hex":h,"css":f"--tkc-{k}"}; put(prim,parts,leaf)
for k,v in nc.items():
    grp=k.split('-')[0]
    put(prim,k.split('-'),{"value":v,"css":f"--tkc-{k}"})
tok["primitive"]=prim
def semtree(i):
    d={}
    for k,(l,dk) in t.sem.items():
        ref=(l,dk)[i]; put(d,k.split('-'),{"value":"{primitive.color."+ref.replace('-','.',1)+"}","hex":t.semhex[k][i],"css":f"--tkc-{k}"})
    return d
sh_l={f"shadow-{k}":v for k,v in shadow_l.items()}; sh_d={f"shadow-{k}":v for k,v in shadow_d.items()}
sem_l=semtree(0); sem_d=semtree(1)
for k,v in sh_l.items(): put(sem_l,k.split('-'),{"value":v,"css":f"--tkc-{k}"})
for k,v in sh_d.items(): put(sem_d,k.split('-'),{"value":v,"css":f"--tkc-{k}"})
tok["semantic"]={"light":sem_l,"dark":sem_d}
dd={}
for mode,vals in density.items():
    for k,v in vals.items(): put(dd,[mode,k],{"value":v,"css":f"--tkc-density-{k}"})
tok["density"]=dd
tok["$notes"]={"aliasing":"Semantic values reference primitives as {primitive.color.<ramp>.<step>}. The generator resolves them to var(--tkc-color-<ramp>-<step>).",
 "legacyDawn":"Same property names are emitted for the Dawn override; Dawn rules map selectors to var(--tkc-*)."}
json.dump(tok,open(OUT+"tokens.json","w"),indent=2)
# counts
def count(d):
    return sum(1 if isinstance(v,dict) and "value" in v else count(v) for v in d.values())
cprim=count(prim); csl=count(sem_l); csd=count(sem_d); cd=count(dd)
print("primitive",cprim,"semantic light",csl,"dark",csd,"density",cd)
colors_prim=sum(1 for k in t.prim); print("colour prims",colors_prim,"semantic colours",len(t.sem))

# ---------------- CSS
def css_block(sel,i,shadows):
    lines=[f"{sel} {{"]
    for k,(l,d) in t.sem.items():
        ref=(l,d)[i]; lines.append(f"  --tkc-{k}: var(--tkc-color-{ref});")
    for k,v in shadows.items(): lines.append(f"  --tkc-{k}: {v};")
    lines.append(f"  color-scheme: {'light' if i==0 else 'dark'};")
    lines.append("}"); return "\n".join(lines)
prim_css=[":root {"]
for k,(v,h) in t.prim.items(): prim_css.append(f"  --tkc-color-{k[6:]}: {v};")
for k,v in nc.items(): prim_css.append(f"  --tkc-{k}: {v};")
prim_css.append("}")
dens_css=[]
for k,v in density["compact"].items(): dens_css.append(f"  --tkc-density-{k}: {v};")
css="/* primitives */\n"+"\n".join(prim_css)+"\n\n/* semantic - light (default) */\n"+css_block(":root",0,sh_l)+"\n\n/* semantic - dark: follows OS unless data-theme=light */\n@media (prefers-color-scheme: dark) {\n"+css_block(':root:not([data-theme="light"])',1,sh_d)+"\n}\n/* semantic - dark: forced */\n"+css_block(':root[data-theme="dark"]',1,sh_d)+"\n\n/* density */\n:root {\n"+"\n".join(dens_css)+"\n}\n"
comf="\n".join("  --tkc-density-%s: %s;"%(k,v) for k,v in density["comfortable"].items())
css+=":root[data-density=\"comfortable\"] {\n"+comf+"\n}\n@media (pointer: coarse) {\n  :root:not([data-density=\"compact\"]) {\n"+comf.replace("  --","    --")+"\n  }\n}\n"
css+="@media (prefers-reduced-motion: reduce) {\n  :root { --tkc-duration-instant: 1ms; --tkc-duration-fast: 1ms; --tkc-duration-base: 1ms; --tkc-duration-slow: 1ms; }\n}\n"
open("tokens.css","w").write(css)
json.dump(t.results,open("contrast.json","w"))
print(len(css.splitlines()),"css lines")
