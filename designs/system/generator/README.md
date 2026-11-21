# Design-system generator

Builds `../tokens.json` and `../design-system.html` from Python source (moved from the ux-designer's scratchpad, 2026-11-20).

Run from this folder: `python3 -I build_tokens.py && python3 -I assemble.py`
(`build_tokens.py` writes `tokens.json` to `OUT` plus the intermediate `tokens.css` / `contrast.json` here; `assemble.py` fills `template.html`.)

Edit tokens here, not in the generated files. The app token generator (decision D6) reads `../tokens.json`.
