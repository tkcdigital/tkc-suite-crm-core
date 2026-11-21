# Design system

`tokens.json` (single token source for the Angular and legacy themes), `components.md`,
`design-system.html` (published as an artifact). Owner: ux-designer. Rules: `.claude/rules/design-system.md`.

## Status (v0.2, brand colour approved in SUITE-180)
- `tokens.json`: 154 primitive + 101 semantic (x2 modes) + 9 density CSS custom properties (264 unique `--tkc-*`). The generator prints: primitive 154, semantic light 101, dark 101, density 18 (9 x 2 modes).
- Brand colour is APPROVED (SUITE-180, 2026-11-21, option A): primary hue 195 (`--tkc-brand-hue`); brand pink `#e95295` (`--tkc-color-brand-pink`) only for the logo through `--tkc-logo`; never text.
- `design-system.html` is generated from `tokens.json`; the style guide has a live brand-hue slider and contrast recalculation.

## Published
Style guide (artifact, v0.2): https://claude.ai/artifact/G5DqeywArbgBvJbrnM9ott — republish after changes (the published copy is the generated HTML without the document skeleton).
