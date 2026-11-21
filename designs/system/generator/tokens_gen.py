import math, json, re
BRAND_HUE = 195
STEPS = [50,100,200,300,400,500,600,700,800,900,950]
L = dict(zip(STEPS,[.975,.95,.915,.855,.77,.68,.58,.50,.42,.34,.25]))
CB = dict(zip(STEPS,[.018,.032,.055,.085,.105,.115,.115,.100,.085,.068,.05]))   # brand chroma
CS = dict(zip(STEPS,[.050,.085,.14,.19,.20,.20,.19,.17,.145,.12,.09]))          # status chroma
NEUT = {0:1.0,50:.982,100:.962,200:.925,300:.875,400:.78,500:.66,600:.53,700:.44,800:.34,900:.26,950:.20,975:.16,990:.125}
HUES = {"green":152,"amber":78,"red":27,"blue":252,"violet":298}

def oklch_to_rgb(l,c,h):
    def conv(c):
        a=c*math.cos(math.radians(h)); b=c*math.sin(math.radians(h))
        l_=l+0.3963377774*a+0.2158037573*b
        m_=l-0.1055613458*a-0.0638541728*b
        s_=l-0.0894841775*a-1.2914855480*b
        l3,m3,s3=l_**3,m_**3,s_**3
        return (4.0767416621*l3-3.3077115913*m3+0.2309699292*s3,
               -1.2684380046*l3+2.6097574011*m3-0.3413193965*s3,
               -0.0041960863*l3-0.7034186147*m3+1.7076147010*s3)
    cc=c
    for _ in range(60):
        r=conv(cc)
        if all(-0.0005<=x<=1.0005 for x in r): break
        cc*=0.95
    r=[min(1,max(0,x)) for x in conv(cc)]
    return r
def to_hex(lin):
    out=[]
    for x in lin:
        v=12.92*x if x<=0.0031308 else 1.055*x**(1/2.4)-0.055
        out.append(round(min(1,max(0,v))*255))
    return "#%02x%02x%02x"%tuple(out)
def lum(hexc):
    h=hexc.lstrip('#'); rgb=[int(h[i:i+2],16)/255 for i in (0,2,4)]
    f=lambda v: v/12.92 if v<=0.04045 else ((v+0.055)/1.055)**2.4
    r,g,b=map(f,rgb); return .2126*r+.7152*g+.0722*b
def ratio(a,b):
    la,lb=lum(a),lum(b); 
    if la<lb: la,lb=lb,la
    return (la+.05)/(lb+.05)

prim={}   # name -> (css value, hex)
def add(name, l,c,hue_expr, hue_num):
    prim[name]=(f"oklch({l:.3f} {c:.3f} {hue_expr})", to_hex(oklch_to_rgb(l,c,hue_num)))
for s in STEPS: add(f"color-brand-{s}",L[s],CB[s],"var(--tkc-brand-hue)",BRAND_HUE)
for s,l in NEUT.items(): add(f"color-neutral-{s}",l,0 if s==0 else 0.009,"var(--tkc-brand-hue)",BRAND_HUE)
prim["color-neutral-0"]=("#ffffff","#ffffff")
for n,h in HUES.items():
    for s in STEPS: add(f"color-{n}-{s}",L[s],CS[s]*(0.85 if n in("amber",) else 1),str(h),h)

def P(name): return prim[f"color-{name}"][1]
# semantic: name -> (light ref, dark ref)
sem = {}
def S(n,l,d): sem[n]=(l,d)
S("surface-canvas","neutral-50","neutral-975")
S("surface-default","neutral-0","neutral-950")
S("surface-raised","neutral-0","neutral-900")
S("surface-sunken","neutral-100","neutral-990")
S("surface-overlay","neutral-900","neutral-700")
S("text-on-overlay","neutral-0","neutral-50")
S("text-default","neutral-900","neutral-50")
S("text-muted","neutral-600","neutral-400")
S("text-placeholder","neutral-600","neutral-400")
S("text-disabled","neutral-400","neutral-600")
S("text-inverse","neutral-0","neutral-950")
S("text-link-default","brand-700","brand-300")
S("text-link-hover","brand-800","brand-200")
S("border-default","neutral-200","neutral-800")
S("border-strong","neutral-500","neutral-500")
S("border-subtle","neutral-100","neutral-900")
S("focus-ring","brand-600","brand-300")
S("focus-offset","neutral-0","neutral-950")
S("primary-default","brand-700","brand-400")
S("primary-hover","brand-800","brand-300")
S("primary-active","brand-900","brand-200")
S("primary-on","neutral-0","neutral-990")
S("primary-subtle-bg","brand-50","brand-950")
S("primary-subtle-text","brand-800","brand-200")
S("primary-subtle-border","brand-200","brand-800")
for g in ["success","warning","danger","info","accent"]:
    col={"success":"green","warning":"amber","danger":"red","info":"blue","accent":"violet"}[g]
    S(f"{g}-solid",f"{col}-700" if g!="warning" else "amber-600",f"{col}-400")
    S(f"{g}-on-solid","neutral-0" if g!="warning" else "neutral-990","neutral-990")
    S(f"{g}-subtle-bg",f"{col}-50",f"{col}-950")
    S(f"{g}-subtle-text",f"{col}-800" if g!="warning" else "amber-900",f"{col}-200")
    S(f"{g}-subtle-border",f"{col}-200",f"{col}-800")
S("neutral-subtle-bg","neutral-100","neutral-900")
S("neutral-subtle-text","neutral-700","neutral-300")
S("neutral-subtle-border","neutral-300","neutral-700")
# fields
S("field-bg-default","neutral-0","neutral-975")
S("field-text","neutral-900","neutral-50")
S("field-border-default","neutral-500","neutral-500")
S("field-border-hover","neutral-700","neutral-300")
S("field-border-focus","brand-600","brand-300")
S("field-bg-readonly","neutral-100","neutral-950")
S("field-bg-disabled","neutral-100","neutral-900")
S("field-border-error","red-700","red-400")
S("field-bg-error","red-50","red-950")
S("field-label","neutral-700","neutral-300")
S("field-toggle-off","neutral-500","neutral-500")
# table
S("table-header-bg","neutral-100","neutral-900")
S("table-header-text","neutral-700","neutral-300")
S("table-row-default","neutral-0","neutral-950")
S("table-row-hover","brand-50","neutral-900")
S("table-row-selected","brand-100","brand-950")
S("table-row-border","neutral-200","neutral-800")
# navbar
S("navbar-bg","brand-900","neutral-990")
S("navbar-text-default","neutral-50","neutral-100")
S("navbar-text-muted","brand-200","neutral-400")
S("navbar-item-hover","brand-800","neutral-900")
S("navbar-item-active","brand-700","neutral-800")
S("navbar-indicator","brand-300","brand-400")
S("navbar-search-bg","brand-800","neutral-900")
S("navbar-search-text","neutral-0","neutral-100")
S("navbar-search-placeholder","brand-200","neutral-400")
# header actions
S("header-action-bg","neutral-0","neutral-900")
S("header-action-text","neutral-800","neutral-100")
S("header-action-border","neutral-400","neutral-600")   # decorative edge; control also has text label
S("header-action-hover","neutral-100","neutral-800")
S("header-action-danger-text","red-700","red-300")
S("header-action-menu-bg","neutral-0","neutral-900")
# thread
S("thread-customer-bg","blue-50","blue-950")
S("thread-customer-border","blue-200","blue-800")
S("thread-customer-accent","blue-700","blue-400")
S("thread-agent-bg","brand-50","brand-950")
S("thread-agent-border","brand-200","brand-800")
S("thread-agent-accent","brand-700","brand-400")
S("thread-internal-bg","amber-50","amber-950")
S("thread-internal-border","amber-300","amber-800")
S("thread-internal-accent","amber-700","amber-400")
S("thread-meta-text","neutral-700","neutral-300")
S("backdrop","neutral-900","neutral-990")

def res(ref): return P(ref)
semhex={k:(res(v[0]),res(v[1])) for k,v in sem.items()}

# contrast pairs: (label, fg, bg, min, kind)
pairs=[
("Body text on surface","text-default","surface-default",4.5),
("Body text on canvas","text-default","surface-canvas",4.5),
("Body text on sunken","text-default","surface-sunken",4.5),
("Muted text on surface","text-muted","surface-default",4.5),
("Muted text on canvas","text-muted","surface-canvas",4.5),
("Muted text on sunken","text-muted","surface-sunken",4.5),
("Placeholder on field","text-placeholder","field-bg-default",4.5),
("Link on surface","text-link-default","surface-default",4.5),
("Link on canvas","text-link-default","surface-canvas",4.5),
("Primary button label","primary-on","primary-default",4.5),
("Primary button label (hover)","primary-on","primary-hover",4.5),
("Primary button label (active)","primary-on","primary-active",4.5),
("Primary subtle text on subtle bg","primary-subtle-text","primary-subtle-bg",4.5),
("Navbar text on navbar","navbar-text-default","navbar-bg",4.5),
("Navbar muted text on navbar","navbar-text-muted","navbar-bg",4.5),
("Navbar text on hover item","navbar-text-default","navbar-item-hover",4.5),
("Navbar text on active item","navbar-text-default","navbar-item-active",4.5),
("Navbar search text","navbar-search-text","navbar-search-bg",4.5),
("Navbar search placeholder","navbar-search-placeholder","navbar-search-bg",4.5),
("Navbar indicator vs navbar","navbar-indicator","navbar-bg",3),
("Field text on field","field-text","field-bg-default",4.5),
("Field label on surface","field-label","surface-default",4.5),
("Field border vs surface (UI 3:1)","field-border-default","surface-default",3),
("Field border vs field bg (UI 3:1)","field-border-default","field-bg-default",3),
("Toggle off track vs surface","field-toggle-off","surface-default",3),
("Field error border vs surface","field-border-error","surface-default",3),
("Field read-only text","field-text","field-bg-readonly",4.5),
("Focus ring vs surface (UI 3:1)","focus-ring","surface-default",3),
("Focus ring vs canvas (UI 3:1)","focus-ring","surface-canvas",3),
("Focus ring vs navbar (UI 3:1)","navbar-indicator","navbar-bg",3),
("Table header text on header","table-header-text","table-header-bg",4.5),
("Body text on row hover","text-default","table-row-hover",4.5),
("Body text on row selected","text-default","table-row-selected",4.5),
("Link on row selected","text-link-default","table-row-selected",4.5),
("Muted text on row hover","text-muted","table-row-hover",4.5),
("Header action text","header-action-text","header-action-bg",4.5),
("Header action danger text","header-action-danger-text","header-action-bg",4.5),
("Thread meta on customer","thread-meta-text","thread-customer-bg",4.5),
("Thread meta on agent","thread-meta-text","thread-agent-bg",4.5),
("Thread meta on internal note","thread-meta-text","thread-internal-bg",4.5),
("Body text on customer entry","text-default","thread-customer-bg",4.5),
("Body text on agent entry","text-default","thread-agent-bg",4.5),
("Body text on internal note","text-default","thread-internal-bg",4.5),
("Customer accent vs surface (UI 3:1)","thread-customer-accent","surface-default",3),
("Agent accent vs surface (UI 3:1)","thread-agent-accent","surface-default",3),
("Internal accent vs surface (UI 3:1)","thread-internal-accent","surface-default",3),
("Muted text on raised surface","text-muted","surface-raised",4.5),
("Text on overlay/tooltip","text-on-overlay","surface-overlay",4.5),
]
for g in ["success","warning","danger","info","accent","neutral"]:
    pairs.append((f"{g.title()} pill text on subtle bg",f"{g}-subtle-text",f"{g}-subtle-bg",4.5))
    if g!="neutral":
        pairs.append((f"{g.title()} solid label on solid",f"{g}-on-solid",f"{g}-solid",4.5))
        pairs.append((f"{g.title()} solid vs surface (UI 3:1)",f"{g}-solid","surface-default",3))
results=[]
for lab,fg,bg,mn in pairs:
    for mode,i in (("light",0),("dark",1)):
        r=ratio(semhex[fg][i],semhex[bg][i]); results.append((lab,fg,bg,mode,round(r,2),mn,r>=mn))
if __name__=="__main__":
    bad=[x for x in results if not x[6]]
    print(len(results),"checks;",len(bad),"fail")
    for b in bad: print(b)
