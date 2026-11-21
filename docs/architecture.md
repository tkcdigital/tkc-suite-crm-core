# Architecture

> Owner: document-agent (content decided by the architect). Draft — completed in phase 5.

## Stack
SuiteCRM 8.10 — Symfony 6.4 backend (`core/backend`), Angular 18 frontend (`core/app`), legacy
SuiteCRM 7 layer (`public/legacy`), MySQL 8. Fork of salesagility/SuiteCRM-Core.

## Customization strategy
See `.claude/rules/suitecrm-customization.md`. Summary: configuration → metadata in
`public/legacy/custom/` → extension code in `extensions/tkc/` → core patch (exception).

## Extension layout
`extensions/tkc/` — `app/` (Angular), `backend/` (Symfony), `config/`. Scaffolded from `defaultExt` (key and remote `tkc`, enabled). `defaultExt` stays disabled. Build output `extensions/tkc/public/` is gitignored; `php bin/console cache:clear` copies it to `public/extensions/tkc/`.

### Theme mechanism (S1)
Result of spike SUITE-9 S1 (2026-11-20).
- **Works: option (a).** `extensions/tkc/app/angular.json` replaces the shell `styles` entry for the core theme (`core/app/shell/src/themes/suite8/css/style.scss`, array index 1) with `extensions/tkc/app/src/theme/tkc-theme.scss`. The `merge-angular-json` merge combines arrays by index, so a replaced entry keeps its position.
- `tkc-theme.scss` imports the full core `style.scss`, then `_tkc-tokens.scss` (CSS custom properties) and `_tkc-overrides.scss` (override rules). All output is in one bundle, so TKC rules come after the core rules and win the cascade.
- **Limit:** core SCSS variables (`$midnight-blue`, `$main`, and so on) have no `!default`. TKC cannot set them before the core import. TKC overrides with CSS custom properties and rules instead.
- **Proof:** `--tkc-navbar-bg: #ff00aa` changed `.navbar` background in the built `styles.*.css` (rule order checked: core rule at offset 297207, TKC rule at 385431). The test colour is reverted to the core value `#534d64`. No browser check was possible (Chrome extension not connected).
- **Workflow:** a theme change needs a shell rebuild (`yarn build:shell`, about 25 s). The extension build alone (`yarn build:extension tkc`, about 6 s) does not rebuild the shell styles. Run `yarn merge-angular-json` first when `extensions/tkc/app/angular.json` changes.
- **Upgrade risk:** the index-1 replacement depends on the order of `styles` in `core/angular/base-angular.json`. Re-check at each upstream merge. The import path to core `style.scss` must also stay valid.
- Option (b) (runtime stylesheet injection) was not tried because (a) works.

## Approved decisions (2026-11-20)
From `docs/assessment/customization-feasibility.md` §9:
- **D1** Extension `extensions/tkc/` scaffolded from `defaultExt` (key/remote `tkc`); `defaultExt` stays disabled.
- **D2** TKC fields are extension vardefs on the **main table** — no Studio fields, no `_cstm`.
- **D3** Naming (refined by A8, see `.claude/rules/suitecrm-customization.md` § Database): fields on core modules `tkc_<name>_c` (import key `osc_id_c`), lists `tkc_<name>_list`, labels `LBL_TKC_<NAME>`, modules `tkc_<Name>`, relationships `tkc_<lhs>_<rhs>`, files `tkc_<topic>.php`.
- **D4** One versioned detailviewdefs per redesigned module (detail = edit = create; edit differences via `displayLogic` modes).
- **D5** Symfony parameter changes as read-modify-write deltas in `extensions/tkc/config/*.php`.
- **D6** One token generator → Angular theme entry + legacy Dawn override.
- **D7** Dropdowns/labels via `custom/Extension/.../Language/`; never rename core list keys.
- **D8** OSC import = Symfony console command in `extensions/tkc/backend/` with notification/AOW/geocode guards.
- **D9** Docker dev environment (PHP 8.3 + MySQL 8) before phase 8.
- **D10** `tkc:repair` command (dry-run SQL, then execute) used in every deploy.
- **D11** New Module Builder modules `public/legacy/modules/tkc_*` count as Level 2.
- **D12** Secret-free settings template in `scripts/config/`, merged into `config_override.php` per environment.
- Doctrine migrations: data/config seeding only, never schema.

## Studio and layouts (S3)
Spike SUITE-10 ran the Studio save path from PHP CLI: `ParserFactory::getParser(MB_DETAILVIEW, 'Accounts')` then `handleSave()`. The input was a custom `detailviewdefs.php` with test keys. A save without a layout change gave a byte-identical file. A save with a changed cell order (the `_REQUEST` slot form that Studio sends) gave the results below.

| Key | Result | Evidence |
|---|---|---|
| `recordActions`, `sidebarWidgets`, `topWidget` | Kept | `GridLayoutMetaDataParser::handleSave()` replaces only `panels`; it saves all other top-level keys (`GridLayoutMetaDataParser.php:159-162`). |
| `headerWidgets`, `bottomWidgets`, other top-level or `templateMeta` keys | Kept | Same code path. The test run kept them. |
| Cell-level `displayLogic`, `logic`, `fieldActions` | Kept | `_convertToCanonicalForm()` copies the full old cell from the previous layout by field name (`:572-640`). The test run kept the keys, also on a moved cell. |
| Panel-level `templateMeta.tabDefs[PANEL].displayLogic` | **Dropped** | `_populateFromRequest()` builds new tab definitions with only `newTab` and `panelDefault` (`:515-535`, `setTabDefs()` at `:234`). |

Other effects of a Studio save:
- Studio writes `custom/modules/<M>/metadata/detailviewdefs.php` (the whole file) and a copy of the previous file in `custom/history/modules/<M>/metadata/` (gitignored). `custom/working/` is removed on deploy.
- Studio writes the file with `var_export`. Comments and formatting are lost, so a Git diff is large.
- A Studio save also overwrites any manual edit made after Studio loaded the layout.
- A cell that is removed in Studio loses its keys. If it is added again, the key comes back only from the history or original layout.

Decision: Studio is **allowed with a check** for the four redesign modules. Rules:
1. Panel-level `displayLogic` must not be used in `tabDefs`. Put the logic on cells, or accept that Studio removes it.
2. After a Studio save, review the Git diff of the `custom/modules/<M>/metadata/` file and confirm the SuiteCRM 8 keys are still there.
3. Commit the Studio result as a normal change. Do not edit the same file by hand and in Studio at the same time.
Not tested: the Studio UI itself, the Edit view and Quick create files, and a browser run.

## Console commands (D10, D12)
- `php bin/console tkc:repair [--dry-run|--execute] [--module=<Module>]`: Quick Repair and Rebuild from the CLI. Dry run is the default and shows the SQL.
- `php bin/console tkc:config:apply [--dry-run]`: merges `scripts/config/config_override.template.php` (secret-free) into `public/legacy/config_override.php`.
- Code in `extensions/tkc/backend/Console/` and `Config/`. Details in `scripts/config/README.md`.

## Core patches
| Patch | File | Reason | Issue | Re-check on upgrade |
|---|---|---|---|---|
| — | | | | |

## Overrides register
This register lists every file that overrides a core file or a core parameter. Re-check every row at each upstream merge. Add a row in the same story that adds the override.
Type values: viewdef full copy · core list override · core vardef override · D5 parameter delta.

| File | Module | Type | Reason | Upstream source to diff | Check at merge |
|---|---|---|---|---|---|
| — | | | | | |

## Environments
Local: see `LOCAL_SETUP.md`. Azure: phase later — `docs/environments.md`.
