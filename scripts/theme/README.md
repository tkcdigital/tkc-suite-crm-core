# Theme generator (SUITE-56, D6)

`build_theme.py` reads `designs/system/tokens.json` and writes three files. Do not edit them by hand.

| Output | Content |
|---|---|
| `extensions/tkc/app/src/theme/_tkc-tokens.scss` | Angular: tokens as CSS custom properties |
| `extensions/tkc/legacy-theme/00-tokens.generated.css` | Legacy: the same tokens, rem converted to px (base 16) |
| `public/legacy/custom/themes/suite8/css/Dawn/style.css` | Legacy theme file: the generated tokens, then `extensions/tkc/legacy-theme/*.css` in name order |

## Use

```
python3 -I scripts/theme/build_theme.py          # write the files
python3 -I scripts/theme/build_theme.py --check  # exit 1 on a difference or a raw colour
python3 -I -m unittest discover scripts/theme/tests
```

After a token change, run the generator and commit the three files with `tokens.json`.
Edit tokens in `designs/system/generator/`, then regenerate `tokens.json`, then run this script.

## Rules the script enforces

- Every `{primitive...}` reference must resolve. Every leaf must have a `css` name. Else exit 1.
- Dark tokens are written only under `:root[data-theme="dark"]` (X3). `--dark-auto` is reserved and off.
- Legacy output has no `rem` (X4). Legacy hand files must not use `calc()` with ` + ` (X6).
- Legacy hand-file selectors must not have whitespace before a `:` pseudo (`body :is(`, `.a :hover`). Legacy cssmin strips the space and the rule matches nothing (X9). Use `body *:is(`, or start with the element and repeat the pseudo.
- Hand files must not set `z-index` with an overlay token (`--tkc-z-dropdown`, `-drawer`, `-modal`, `-toast`, `-tooltip`). Core overlays stack at 1000-2000, above the token scale (X10). Leave core overlay z-index values as they are.
- `--check` also fails when a hand-written file has a hex colour or an `rgb(`, `rgba(`, `hsl(`, `hsla(` literal.
  Hand files: `extensions/tkc/app/src/theme/**/*.scss` (not the generated file) and `extensions/tkc/legacy-theme/*.css` (not `00-tokens.generated.css`).
- The sentinel `--tkc-theme-build` holds the first 12 hex characters of the SHA-256 of `tokens.json`.
- Output has LF line ends, a final newline and no timestamps, so a second run changes nothing.

## Fonts

Roboto (one variable file per subset, `font-weight: 400 700`) and IBM Plex Mono 400 are in
`public/legacy/custom/themes/suite8/fonts/`. Roboto files: `Roboto-Variable-latin.woff2` and
`Roboto-Variable-latin-ext.woff2`, licence `Roboto-OFL.txt`. Source: npm
`@fontsource-variable/roboto@5.3.0` (`files/roboto-latin-wght-normal.woff2`,
`files/roboto-latin-ext-wght-normal.woff2`; `unicode-range` copied from `wght.css`). Plex Mono (Latin1
subset, `OFL.txt`): npm `@ibm/plex-mono@2.5.0`. The `@font-face` rules use the path
`/legacy/custom/themes/suite8/fonts/`. There is no font CDN (X5).
