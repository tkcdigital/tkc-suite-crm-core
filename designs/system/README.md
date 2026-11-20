# Design system

`tokens.json` (single token source for the Angular and legacy themes), `components.md`,
`design-system.html` (published as an artifact). Owner: ux-designer. Rules: `.claude/rules/design-system.md`.

## Status (SUITE-16, draft v0.1)
- `tokens.json`: 153 primitive + 100 semantic (x2 modes) + 9 density CSS custom properties (262 unique `--tkc-*`).
- Brand colour is a proposal: change `--tkc-brand-hue` (default 195) only.
- `design-system.html` is generated from `tokens.json`; the style guide has a live brand-hue slider and contrast recalculation.

## Published
Style guide (artifact, draft v0.1): https://claude.ai/artifact/G5DqeywArbgBvJbrnM9ott — republish after changes (the published copy is the generated HTML without the document skeleton).
