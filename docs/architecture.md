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

### Theme generation (D6, SUITE-56)
- `scripts/theme/build_theme.py` (Python standard library only) reads `designs/system/tokens.json`. It writes three files:
  - `extensions/tkc/app/src/theme/_tkc-tokens.scss`
  - `extensions/tkc/legacy-theme/00-tokens.generated.css`
  - `public/legacy/custom/themes/suite8/css/Dawn/style.css` (the legacy token file plus the hand-written `extensions/tkc/legacy-theme/*.css`, joined in name order)
- Never edit a generated file.
- Light tokens are on `:root`. Dark tokens are only under `:root[data-theme="dark"]` (X3). The legacy output uses px (X4).
- `build_theme.py --check` fails in these cases:
  - A generated file differs from the generator output.
  - A hand-written file has a colour literal (hex, `rgb()`, `rgba()`, `hsl()`, `hsla()`).
  - A legacy file has `calc()` with ` + ` (X6).
- The sentinel `--tkc-theme-build` holds a hash of `tokens.json`.
- Fonts: IBM Plex Sans 400/500/600/700 and IBM Plex Mono 400 (Latin1, OFL licence; npm `@ibm/plex-sans` 2.0.0, `@ibm/plex-mono` 2.5.0). They are self-hosted in `public/legacy/custom/themes/suite8/fonts/` (X5). Both UIs use the URL path `/legacy/custom/themes/suite8/fonts/`.

### Angular theme (SUITE-57, SUITE-58, SUITE-59)
- The S1 entry `extensions/tkc/app/src/theme/tkc-theme.scss` (`styles[1]` of the shell build) loads, in this order: the core theme, the generated `_tkc-tokens.scss`, then `_tkc-overrides.scss`.
- `_tkc-overrides.scss` is an index of the partials in `extensions/tkc/app/src/theme/overrides/`:
  - SUITE-57: `_base`, `_navbar`, `_buttons`, `_fields`
  - SUITE-58: `_tabs`, `_panels`, `_list`, `_subpanels`, `_alerts`, `_modals`
  - SUITE-59: `_login`
- Rules in a partial use core selectors and `var(--tkc-*)` only (X8). Use `!important` only against a core `!important`.
- Root-absolute font URLs pass through the Angular builder.
- `scripts/theme/check_bundle.py` (build step 4b) fails in these cases: the newest `public/dist/styles*.css` has no sentinel, or the TKC `.navbar` rule is not after the core rule.
- Upgrade risks (re-check at each upstream merge):
  - The `styles[1]` index.
  - Core selectors that the SUITE-58 partials use: `.tabs-layout .tab-link`, `.light-table .list-view-table`, `.sub-panel`, `.panel-card`, `.alert-fixed`. The ng-bootstrap library supplies the modal classes.
  - The core "Lato" rule (`abstracts/_typography.scss:30`).
  - Core selectors that the other partials use: `.list-view-container`, `.list-view-hr-container`, `.record-view-hr-container`, `.small-font` (core sets `.85em`; TKC sets `--tkc-font-size-md`), `.table td` (core `.797rem`), `.saved-search-container` (core `layout/_saved-search.scss:27`), `.sub-panel-banner` (the "Relationships" banner, matched with `.card:has(> .sub-panel-banner)`), `.alert-fixed .message.shadow`, `.settings-button`.
  - `.btn-outline-danger` is mapped to a primary outline button for the whole app (`_buttons.scss`). Core uses this class only as the accent of the filter panel and the record panel (`list-filter.component.ts:100`, `list-filter.store.ts:293`, `record-panel.component.ts:83`). Risk: if upstream uses it for a destructive action, that action looks like a primary button. Check this at each upstream merge.
  - `!important` exceptions (X8: only against a core `!important`):
    - `_alerts.scss`: `.alert-fixed .message.shadow` `box-shadow: none`. Bootstrap `.shadow` has `!important`.
    - `_subpanels.scss`: `.card:has(> .sub-panel-banner)` border colour and `box-shadow: none`. Bootstrap `.border` and `.shadow-sm` have `!important`.
    - `_panels.scss`: panel shadow. Core sets it with `!important`.
    - `_navbar.scss`: active link colour. Core has `!important`.
    - `_buttons.scss`: `.settings-button` focus ring. Core removes the outline with `!important`.
    - `_base.scss`: `scroll-behavior: auto` in the reduced-motion rule. Core sets `smooth` on `html` and `body`.
  - Toast position: `.alert-fixed` is at the bottom right. On screens narrower than 47.9375rem it is at the top.
- Known: the build warns that the initial bundle is over budget (10.79 MB against 9 MiB). QA found the cause is core: `main` is 4.88 MB and `polyfills` is 4.86 MB. It is not TKC code (see Known issues).

### Legacy theme (SUITE-60)
- `build_theme.py` writes `public/legacy/custom/themes/suite8/css/Dawn/style.css`. Nobody edits it by hand.
- `SugarTheme::getCSSURL` adds this file after the core theme CSS. It minifies the file with cssmin and caches the result in `public/legacy/cache/themes/`. cssmin keeps custom properties, `oklch()`, `@media` and `unicode-range`.
- Hand-written files style `body`, not `html` (core sets `html{font-size:10px}`). They use px and no `calc()` with `+`.
- cssmin runs only when the cached `style.css` does not exist. `tkc:repair` clears only `pathCache.php`. Build step 12 therefore deletes the cached file and rebuilds it with one login-page request. Build step 14 fails when the cached file has no `--tkc-theme-build` sentinel (see `LOCAL_SETUP.md` § Theme).
- `build_theme.py` writes its output atomically. `build_theme.py --check` rule X9 fails when a legacy hand file has whitespace before a pseudo-class (cssmin removes it, the rule then matches nothing).
- Known issue: SUITE-181 (see Known issues).
- No change outside `public/legacy/custom/`.

### Login logo (SUITE-59)
- The Angular logo (`scrm-logo-ui` uses `scrm-image image="company_logo"`, also in the navbar) comes from `public/legacy/custom/themes/suite8/images/company_logo.svg`. The file is the TKC Digital logo in one colour. The brand pink is open in SUITE-180.
- SVG wins over PNG (`image_supported_types.yaml`). The custom suite8 path wins (`image_paths.yaml`).
- The SVG uses `fill="currentColor"` because svg-icon inlines it. `_login.scss` sets the colour from tokens and overrides the core grey fill on `rect` and `text`.
- After a logo change, run `console cache:clear` (theme-images cache).
- No core patch. "Supercharged by SuiteCRM" stays in the core footer (AGPL section 7(b)).
- Known core limits: the login form has no visible input labels. The "Forgot password" link has no `href`.

## Build chain (SUITE-44)
- `scripts/build.sh` is the single build entry point. It runs on the host and calls the app container through `docker/dc.sh`.
- Steps: 0 preflight; 0b `build_theme.py --check`; 1-5 Angular on the host (`yarn install --immutable`, `merge-angular-json`, `build:core`, `build:shell`, `build:extension tkc`); 4b `check_bundle.py`; 6 compile the core Dawn CSS with `pscss`; 7 `scrm:copy-legacy-assets`; 8 `tkc:repair --dry-run`; 9 mandatory confirmation; 10 `tkc:repair --execute`; 11 `tkc:config:apply`; 12 `cache:clear`, delete the cached legacy theme CSS and rebuild it; 13 smoke test (HTTP 200); 14 check that the cached legacy CSS has the `--tkc-theme-build` sentinel. The build has 14 steps.
- Exit code 1: the first error, with the step name. Exit code 2: no confirmation is possible.
- Flags: `--yes` (runs the repair SQL unread; reviewed deploys only), `--skip-core`, `--skip-frontend`, `--dry-run-only`.
- `docker/dc.sh console` adds `-T` when stdin is not a TTY.
- Re-check at each upstream merge: the script names in the root `package.json`, the command `scrm:copy-legacy-assets`, and the Dawn SCSS path `public/legacy/themes/suite8/css/Dawn/style.scss`.

## Mail sink (dev, SUITE-45)
- The dev Docker environment has a `mailpit` service (Mailpit v1.21). The system outbound account sends to Mailpit. Personal SMTP accounts bypass the sink; the import guard (SUITE-79) checks all accounts.
- The web UI listens on 127.0.0.1 only (`MAILPIT_UI_PORT`, default 8025) because it has no login. SMTP port 1025 is not published. There is no volume: a restart deletes the held mail.
- The sink host names are one config value: env `TKC_MAIL_SINK_HOSTS` (default `mailpit`, comma-separated) gives the parameter `tkc.mail.sink_hosts` (`extensions/tkc/config/tkc_mail.yaml`).
- `App\Extension\tkc\backend\Mail\MailSinkPolicy` has `isSinkHost()`, `filterNonSink()` and `findNonSinkOutboundAccounts()`. The import guard (SUITE-79) uses it to check all outbound accounts (system, system-override, user).
- `tkc:mail:use-sink` sets the system outbound account to the sink. It is for DEV only and is idempotent.
- The compose file is for dev only and never goes to Azure. The command `tkc:mail:use-sink` ships with the code, but it runs only where `TKC_DEV_MAIL_SINK=1` (set only in the dev `compose.yaml`).
- The compose host ports are parameters (`APP_PORT`, `DB_PORT`, `MAILPIT_UI_PORT`). A second instance can run with `-p <name>` (X7). See `docs/environments.md`.

## Tests (SUITE-46)
- `scripts/test.sh [--php] [--ng] [--theme]` runs the TKC tests.
- PHP: PHPUnit 10 in the app container. Config `extensions/tkc/phpunit.xml.dist` has the suites `unit` and `contract`. The bootstrap `backend/Tests/bootstrap.php` loads only `vendor/autoload.php`, not `tests/bootstrap.php`.
- NG: Karma with ChromeHeadless on the host (needs Google Chrome), project `tkc`. The spec build uses `extensions/tkc/app/webpack.config.spec.js`, because module federation breaks Karma. There is no `main` or `test.ts`: Angular 18 finds the specs through `include`.
- THEME: `unittest` of `scripts/theme/tests`, then `check_bundle.py`. It fails when `public/dist` is not built.
- Test directories are named `Tests`. The file `config/core_services.yaml` excludes `Tests` from `App\Extension\` autowiring.
- `extensions/tkc/app/tsconfig.spec.json` sets its own `typeRoots`, because the `typeRoots` in the root `tsconfig.json` do not resolve. It is not a core override.
- Known issue: SUITE-176 (see Known issues).

## Metadata contract test (SUITE-47)
- `extensions/tkc/backend/Tests/Contract/ModuleMetadataContractTest.php` checks the layouts of each module in `Tests/Contract/modules.php` against its vardefs and dropdown lists.
- Rules R1 to R4 are in the pure class `Metadata/Contract/MetadataContractChecker`. The loader `Metadata/Contract/LegacyMetadataLoader` reads metadata only, custom layouts first.
- The test boots `App\Kernel` in `dev`, because the `test` env does not boot (SUITE-176). It loads `.env` and `.env.local` into the process.
- Precondition: `tkc:repair` has run.
- Each module story adds its module to `modules.php`.
- Core-layout violations go in `known-core-violations.php` with a reason. The list is empty now.
- Upgrade risk: medium, on purpose.

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
- `php bin/console tkc:mail:use-sink [--dry-run]`: sets the system outbound account to the mail sink. DEV only, idempotent (SUITE-45).
- `php bin/console tkc:users:repair-number-separators [--dry-run]`: removes the user preferences `dec_sep` and `num_grp_sep` that equal the other effective separator (SUITE-179). Run it after `tkc:config:apply`. It writes through the legacy `UserPreference` bean. A signed-in user must sign out and in again (the session writes the old values back). Code in `extensions/tkc/backend/Users/` (`Console/RepairNumberSeparatorsCommand`, `NumberSeparatorPolicy`, `LegacyPreferenceStore`). Upgrade risk: it depends on the key names `dec_sep` and `num_grp_sep`, on the config keys `default_decimal_seperator` and `default_number_grouping_seperator`, and on the base64-serialized format of `user_preferences.contents`. Re-check at each upstream merge.
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

## Known issues
| Issue | Description | Effect |
|---|---|---|
| SUITE-176 | The Symfony `test` env does not boot. The core file `config/packages/test/framework.yaml` uses the removed option `storage_id`. | The contract test boots the `dev` env. Kernel-based PHPUnit tests cannot use the `test` env. |
| SUITE-175 | Resolved with option B: `lcobucci/clock` is 3.5.0 (upstream has 3.0.0). `composer.json` keeps the PHP 8.3 platform. | Upstream-merge risk: at each merge, compare `lcobucci/clock` in `composer.lock` with upstream and keep 3.5.0 while the platform is PHP 8.3. |
| SUITE-181 | The legacy CSS URL version does not change with the content. | After a theme deploy, browsers keep the old legacy CSS. Do a hard reload. |
| Bundle budget | The Angular build warns "initial bundle exceeded budget" (10.79 MB against 9 MiB). | Cause is core (`main` 4.88 MB, `polyfills` 4.86 MB), not TKC. No action. |

## Environments
Local: see `LOCAL_SETUP.md`. Azure: phase later — `docs/environments.md`.
