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

Angular folders in `extensions/tkc/app/src/`:
- `theme/`: the Angular theme (see Angular theme).
- `fields/<type>/`: Angular field components that replace a core field component (SUITE-186). Each folder has the component (`.ts`, `.html`), a module and a spec.
  - Today: `fields/radioenum/` (`TkcRadioEnumEditFieldComponent`).
  - `extension.module.ts` imports the field module. Its constructor registers the component with `FieldRegistry.register('default', <type>, <mode>, <class>)`. The extension module and the core share one `FieldRegistry` singleton (the `core` package is shared in the extension webpack config). A `register()` call from the extension replaces the core entry.
  - Register a replaced component only for the keys that need it (for example `radioenum.edit`). Add a row to the Overrides register in the same story.

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
- The design-system generator in `designs/system/generator/`: `build_tokens.py` writes `tokens.json` from `tokens_gen.py`; `assemble.py` writes `design-system.html` and `tkc-ds.css`. Since SUITE-188, `assemble.py` writes the committed `tkc-ds.css` header and removes a doc-only comment. Generated design-system files come only from the committed generator. Do not use a scratch copy of the generator.
- Fonts (SUITE-189, owner decision 2026-11-21, brand guide font): Roboto and IBM Plex Mono 400. Both are self-hosted in `public/legacy/custom/themes/suite8/fonts/` (X5). Both UIs use the URL path `/legacy/custom/themes/suite8/fonts/`. The app loads no external font.
  - Roboto is a variable font (SIL OFL 1.1). Source: npm `@fontsource-variable/roboto` 5.3.0, integrity checked. The package is not in `package.json`.
  - Files: `Roboto-Variable-latin.woff2`, `Roboto-Variable-latin-ext.woff2` and the licence `Roboto-OFL.txt`. The two files use the `unicode-range` values from the package. The files are 72528 bytes in total. The old IBM Plex Sans files were 87844 bytes. They are deleted.
  - Each `@font-face` rule has `font-weight: 400 700`. The weight axis is real, so semibold (600) is a true weight.
  - IBM Plex Mono 400 (Latin1 subset, `OFL.txt`) stays. Source: npm `@ibm/plex-mono` 2.5.0.
  - The font stack is `"Roboto", system-ui, -apple-system, "Segoe UI", "Helvetica Neue", Arial, sans-serif`.
  - The design system and the 7 mockups use Roboto. Their Google Fonts link is an exception for the style guide only.
  - Rule: a font file with new content must get a new file name until SUITE-190 is done. Reason: the legacy `.htaccess` caches woff2 files for 1 month (see Legacy theme).

### Angular theme (SUITE-57, SUITE-58, SUITE-59)
- The S1 entry `extensions/tkc/app/src/theme/tkc-theme.scss` (`styles[1]` of the shell build) loads, in this order: the core theme, the generated `_tkc-tokens.scss`, then `_tkc-overrides.scss`.
- `_tkc-overrides.scss` is an index of the partials in `extensions/tkc/app/src/theme/overrides/`:
  - SUITE-57: `_base`, `_navbar`, `_buttons`, `_fields`
  - SUITE-58: `_tabs`, `_panels`, `_list`, `_subpanels`, `_alerts`, `_modals`
  - SUITE-59: `_login`
  - SUITE-186: `_radio`
  - SUITE-194: `_menus`
- Rules in a partial use core selectors and `var(--tkc-*)` only (X8). Use `!important` only against a core `!important`.
- Root-absolute font URLs pass through the Angular builder.
- `scripts/theme/check_bundle.py` (build step 4b) fails in these cases:
  - The newest `public/dist/styles*.css` has no sentinel.
  - The TKC `.navbar` rule is not after the core rule.
  - The last `.radioenum-input input[type='radio']` rule that sets `display` sets `display: none` (SUITE-186). It also fails when no such rule sets `display` (the core selector changed). The script reads back-to-back rules too (a regex fix in SUITE-186).
  - Check 4 (SUITE-194): the Actions menu items must stay readable. It fails in these cases:
    - (a) A rule sets `color`, `fill`, `background` or `background-color` to a `--tkc-header-action-` value. The last compound of a selector has `.settings-button`, `.action-button`, `.btn-secondary` or `.btn-outline-secondary` without `:not(.dropdown-item)`.
    - (b) The last rule that sets `color` for `a.dropdown-item` in `.button-group-dropdown .dropdown-menu` or `.dropdown-button-secondary .dropdown-menu` does not use `var(--tkc-header-action-text)`.
    - (c) The last rule that sets `background-color` for these two menus does not use `var(--tkc-header-action-menu-bg)`.
    - (d) The core selector `.dropdown-button-secondary .dropdown-menu a` is missing (an upstream change).
- Radio inputs (SUITE-186):
  - Core `_radio.scss:27` hides the radio inputs with `display: none`. A keyboard user cannot reach them.
  - `_radio.scss` (TKC) sets the visually-hidden pattern on `.radioenum-input input[type='radio']`. The inputs stay in the tab order and in the accessibility tree.
  - The rule has the same specificity as the core rule. It wins by order. It must reset `display: inline-block`, because the hidden pattern alone does not remove `display: none`. `check_bundle.py` checks this reset in the built bundle.
  - The focus ring is on `input:focus-visible + label`. It uses the tokens `--tkc-focus-ring-width`, `--tkc-focus-ring`, `--tkc-focus-ring-offset` and `--tkc-radius-sm`.
  - The Angular field override `fields/radioenum/` adds `role="radiogroup"`, an `aria-label` and the label "Not set" for the empty option (see Overrides register).
  - Scope: the Angular UI only. The legacy UI shows native radios.
  - The filter panel radios (for example "Direction" in the list filter) use the same edit component. They get the same behaviour.
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
  - z-index scale mismatch: the token scale (`--tkc-z-*`, 0-700) is below the core overlay scale (dropdown 1000, `.modal` 1050, core `.modal-backdrop` 1050 with `!important`, toast `.alert-fixed` 1510). The theme does not set the z-index of `.modal`, `.modal-backdrop` or `.alert-fixed`. They keep the core values. The modal tokens put the backdrop over the modal window. Re-check the core overlay z-index values at each upstream merge. The only TKC z-index is `.navbar` (`--tkc-z-navbar`, inside the core `.top-panel.fixed-top` at 1030).
  - Radio inputs (SUITE-186): core `_radio.scss:27-60` (`core/app/shell/src/themes/suite8/css/components/_radio.scss`) and the selector `.radioenum-input input[type='radio']`. Core hides the inputs there with `display: none`. A change of that selector or of the markup (`.radioenum-input`, `input + label`) breaks `_radio.scss`. `check_bundle.py` fails when the selector is gone.
  - Radio field override (SUITE-186): see the Overrides register row for `fields/radioenum/`.
  - Actions menu items (SUITE-194): the selectors `.dropdown-item`, `.button-group-dropdown`, `.dropdown-button-secondary` and `.dropdown-menu a`, and core `core/app/shell/src/themes/suite8/css/components/_dropdown.scss:497-525, 614-640`. Core puts the button class (`settings-button`, `action-button`) on the collapsed menu items too (`action-group-menu.component.ts:282`, `button-group.component.ts:157`). The TKC button rules exclude `.dropdown-item` with `:where(:not())`, which keeps the specificity. `_menus.scss` styles the items. A change of these selectors or of the `a ngbDropdownItem` markup in `dropdown-button.component.html` breaks the menu. `check_bundle.py` check 4 fails when the core selector is gone or when a button rule hits the items again.
  - Toast position: `.alert-fixed` is at the bottom right. On screens narrower than 47.9375rem it is at the top.
- Known: the build warns that the initial bundle is over budget (10.79 MB against 9 MiB). QA found the cause is core: `main` is 4.88 MB and `polyfills` is 4.86 MB. It is not TKC code (see Known issues).

### Legacy theme (SUITE-60)
- `build_theme.py` writes `public/legacy/custom/themes/suite8/css/Dawn/style.css`. Nobody edits it by hand.
- `SugarTheme::getCSSURL` adds this file after the core theme CSS. It minifies the file with cssmin and caches the result in `public/legacy/cache/themes/`. cssmin keeps custom properties, `oklch()`, `@media` and `unicode-range`.
- Hand-written files style `body`, not `html` (core sets `html{font-size:10px}`). They use px and no `calc()` with `+`.
- cssmin runs only when the cached `style.css` does not exist. `tkc:repair` clears only `pathCache.php`. Build step 12 therefore deletes the cached file and rebuilds it with one login-page request. Build step 14 fails when the cached file has no `--tkc-theme-build` sentinel (see `LOCAL_SETUP.md` § Theme).
- `build_theme.py` writes its output atomically. `build_theme.py --check` rule X9 fails when a legacy hand file has whitespace before a pseudo-class (cssmin removes it, the rule then matches nothing).
- `build_theme.py --check` rule X10 (`Z_OVERLAY_RE`) fails when a hand file sets `z-index` with an overlay token (`--tkc-z-dropdown`, `-drawer`, `-modal`, `-toast`, `-tooltip`). Local layers (`base`, `sticky`, `navbar`) are allowed. Reason: the token scale is below the core overlay scale (see Angular theme upgrade risks).
- Asset URL version (SUITE-181):
  - Core `getVersionedPath` (`public/legacy/include/utils.php:2934-2961`) builds the `?v=` value from a hash of `js_version_key|js_custom_version|dev`. It does not hash the file content. Before SUITE-181 the URL of the legacy CSS did not change after a theme change.
  - `public/legacy/.htaccess` (core, not changed) caches css and js for 1 year and woff2 for 1 month. A URL that does not change therefore kept the old file in the browser.
  - The command `tkc:legacy:asset-version` sets `js_custom_version` in `public/legacy/config_override.php` (through `ConfigOverrideMerger`). The value is `tkc` and 12 hex characters of a sha256 hash. The hash covers these inputs, sorted by relative path, with each path and each content:
    - core `public/legacy/themes/suite8/css/Dawn/style.css`
    - `public/legacy/include/javascript/yui/build/base/base-min.css`
    - every `*.css` file under `public/legacy/custom/themes/suite8/css/` (recursive)
    - every file in `public/legacy/custom/themes/suite8/fonts/`
    - `composer.lock` (SUITE-183)
    - `public/legacy/suitecrm_version.php` (SUITE-183)
  - Why `composer.lock` and `suitecrm_version.php` (SUITE-183, decision 5): core caches legacy JS for 1 year (`public/legacy/.htaccess:71`). Core builds `?v=` only from `js_custom_version` (`getVersionedPath()`, `utils.php:2934-2961`). Without these inputs, a browser keeps an old vendor script (for example TinyMCE) after a vendor update, or an old core script after an upstream release. A missing input stops the command with exit code 1.
  - Upgrade re-check: at each upstream merge, check `getVersionedPath()` and the `.htaccess` JS expiry again.
  - Code: `extensions/tkc/backend/Config/LegacyAssetVersion.php` (pure logic) and `extensions/tkc/backend/Console/LegacyAssetVersionCommand.php`. The command prints only that key and the result.
  - Side effect: the value is global. It also versions the legacy JS, the sprites and `yui.css`.
  - The build runs the command in step 11b and checks the result in step 14. A browser asks for a new URL after each theme or font change. A hard reload is not needed.
  - Azure deploy: run the command after `tkc:config:apply` in each environment. Reload php-fpm when `opcache.validate_timestamps=0`. A CDN must use the query string in its cache key.
  - `tkc:config:apply` does not set `js_custom_version`. The template has a comment that says so.
- No change outside `public/legacy/custom/`.

### Login logo (SUITE-59)
- The Angular logo (`scrm-logo-ui` uses `scrm-image image="company_logo"`, also in the navbar) comes from `public/legacy/custom/themes/suite8/images/company_logo.svg`. The file is the TKC Digital logo in one colour.
- Logo colour (SUITE-180, owner decision 2026-11-21, option A): the UI keeps the teal primary (hue 195). The primitive token `--tkc-color-brand-pink` is oklch(0.660 0.195 356.5), that is #e95295, with a fixed hue. The semantic token `--tkc-logo` is the brand pink in light and dark mode. `_login.scss` sets `color` on the login `.logo` and on `.navbar .logo` with `var(--tkc-logo)`. Before, it used `--tkc-primary-default` and `--tkc-navbar-text-default`.
- Use rule: use `--tkc-logo` only for `color` or `fill` of `.logo`. Never use it for text, borders, focus rings, links or buttons. Pink text on a light surface has a contrast of 3.44:1 and fails WCAG AA.
- Logo contrast (3:1 rule for graphics): navbar 3.33 light and 5.88 dark; login card 3.44 light and 4.52 dark.
- The legacy UI has the token in its generated file. It does not use the token, because the legacy UI has no themed logo.
- SVG wins over PNG (`image_supported_types.yaml`). The custom suite8 path wins (`image_paths.yaml`).
- The SVG uses `fill="currentColor"` because svg-icon inlines it. `_login.scss` sets the colour from tokens and overrides the core grey fill on `rect` and `text`.
- After a logo change, run `console cache:clear` (theme-images cache).
- No core patch. "Supercharged by SuiteCRM" stays in the core footer (AGPL section 7(b)).
- Known core limits: the login form has no visible input labels. The "Forgot password" link has no `href`.

## Build chain (SUITE-44)
- `scripts/build.sh` is the single build entry point. It runs on the host and calls the app container through `docker/dc.sh`.
- Steps: 0 preflight; 0b `build_theme.py --check`; 1-5 Angular on the host (`yarn install --immutable`, `merge-angular-json`, `build:core`, `build:shell`, `build:extension tkc`); 4b `check_bundle.py`; 6 compile the core Dawn CSS with `pscss`; 7 `scrm:copy-legacy-assets`; 7b `chown` of `cache`, `logs` and `var` to `www-data`, then `cache:clear` (so the console knows new or changed `tkc:*` commands; the prod container cache lists the commands, and without this step the first build that adds a command fails with "no commands defined"); 8 `tkc:repair --dry-run`; 9 mandatory confirmation; 10 `tkc:repair --execute`; 11 `tkc:config:apply`; 11b `tkc:legacy:asset-version` (sets `js_custom_version`, SUITE-181; it runs before step 12); 12 `cache:clear`, delete the cached legacy theme CSS and rebuild it; 13 smoke test (HTTP 200); 14 check that the cached legacy CSS has the `--tkc-theme-build` sentinel and that the session-less legacy login page `legacy/index.php?module=Users&action=Login` links `Dawn/style.css?v=`. The build has 14 numbered steps and the sub-steps 0b, 4b, 7b and 11b.
- Exit code 1: the first error, with the step name. Exit code 2: no confirmation is possible.
- Flags: `--yes` (runs the repair SQL unread; reviewed deploys only), `--skip-core`, `--skip-frontend`, `--dry-run-only`.
- `docker/dc.sh console` adds `-T` when stdin is not a TTY.
- Re-check at each upstream merge: the script names in the root `package.json`, the command `scrm:copy-legacy-assets`, and the Dawn SCSS path `public/legacy/themes/suite8/css/Dawn/style.scss`.
- Re-check for SUITE-181 at each upstream merge:
  - `getVersionedPath` still uses `js_custom_version` (`public/legacy/include/utils.php:2953`).
  - The inputs of `getCSSURL` (`SugarTheme.php:1022-1064`) are still the files that the hash covers.
  - The `.htaccess` cache times for css, js and woff2.

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
- PHP: PHPUnit 10 in the app container. Config `extensions/tkc/phpunit.xml.dist` has the suites `unit`, `contract` and `integration` (SUITE-62). The `integration` suite is in `backend/Tests/Integration`. It needs the database, and the table `tkc_sequences` must exist, so run `tkc:repair --execute` first. It loads the legacy entry point directly, because the Symfony `test` env does not boot (SUITE-176). `scripts/test.sh --php` runs all three suites. The bootstrap `backend/Tests/bootstrap.php` loads only `vendor/autoload.php`, not `tests/bootstrap.php`.
- NG: Karma with ChromeHeadless on the host (needs Google Chrome), project `tkc`. The spec build uses `extensions/tkc/app/webpack.config.spec.js`, because module federation breaks Karma. There is no `main` or `test.ts`: Angular 18 finds the specs through `include`.
- THEME: `unittest` of `scripts/theme/tests` (since SUITE-194 this includes `test_check_bundle.py`, which tests check 4 with inline CSS samples), then `check_bundle.py` (sentinel, `.navbar` order, since SUITE-186 the radio `display` rule, and since SUITE-194 the Actions menu check; see Angular theme). It fails when `public/dist` is not built.
- Test directories are named `Tests`. The file `config/core_services.yaml` excludes `Tests` from `App\Extension\` autowiring.
- `extensions/tkc/app/tsconfig.spec.json` sets its own `typeRoots`, because the `typeRoots` in the root `tsconfig.json` do not resolve. It is not a core override.
- Known issue: SUITE-176 (see Known issues).

## Metadata contract test (SUITE-47)
- `extensions/tkc/backend/Tests/Contract/ModuleMetadataContractTest.php` checks the layouts of each module in `Tests/Contract/modules.php` against its vardefs and dropdown lists.
- Rules R1 to R4 are in the pure class `Metadata/Contract/MetadataContractChecker`. The loader `Metadata/Contract/LegacyMetadataLoader` reads metadata only, custom layouts first.
- The test boots `App\Kernel` in `dev`, because the `test` env does not boot (SUITE-176). It loads `.env` and `.env.local` into the process.
- Precondition: `tkc:repair` has run.
- Each module story adds its module to `modules.php`.
- Core-layout violations go in `known-core-violations.php` with a reason. Three entries exist. TKC changes none of these core layouts.
  - SUITE-66: the core Cases subpanel `ForEmails` lists `reply_to_status`, which is an Emails field. Core has no Cases vardef for it. The test showed it when `Cases` joined `modules.php`.
  - SUITE-68: the core `detailviewdefs` of `AOP_Case_Updates` (path `panels.default.2.1`) lists `aop_case_updates_contacts_1_name`. The module has no vardef for it. The test showed it when `AOP_Case_Updates` joined `modules.php`.
  - SUITE-65: the core Contacts subpanel `ForContacts` (path `contact_id`) lists `contact_id`. Contacts has no vardef for it. The test showed it when `Contacts` joined `modules.php`.
- QA hint for layouts: the Angular record view shows a new field only inside an existing panel of `detailviewdefs`. After a layout change, run `cache:pool:clear cache.app cache.system` and reload the page. Do not run a cache clear or `chown` in the container while `scripts/test.sh` runs. The Repair-based tests use the same caches.
- Upgrade risk: medium, on purpose.

## Field manifest test (SUITE-63)
- `extensions/tkc/backend/Tests/Contract/FieldManifestTest.php` checks the compiled vardefs and lists against field manifests (C11). It is in the `contract` suite.
- It reads every file `extensions/tkc/backend/Tests/Contract/Fields/*.php`. One file for each story, named `suite<n>_<module>.php`. The first one is `Fields/suite63_accounts.php`.
- A manifest file returns a list of entries. Each entry has this format:
  - `module`: the module name, for example `Accounts`.
  - `fields`: `[field name => [type, len, options, default, dbType, audited, required, massupdate, importable, indexed, displayType]]`. The test checks only the attributes that an entry names. `dbType` (SUITE-68) is optional. `massupdate` (SUITE-65) is optional: `false` means that the vardef must set `massupdate` to false, because core makes `enum` and similar types mass-updatable when the vardef does not set it. `importable` (SUITE-67) is optional in the same way: `false` means that the vardef must set `importable` to false. It checks the column type of a field, for example `longtext` for `AOP_Case_Updates.description`.
  - `absent`: field names that must not exist.
  - `lists`: `[list name => ordered keys]`.
  - `labels` (optional): `[list name => [key => label]]`.
  - `keyPatterns` (optional, SUITE-66): `[list name => regex that every key of the list must match]`. Example: `'case_status_dom' => '/^(Open|Closed)_/'`.
  - `values` (optional, SUITE-66): `[app string name => expected scalar value]`. Example: `'case_priority_default_key' => ''`.
- The test checks these items:
  - The vardef attributes of each field.
  - A one-field index named `idx_<table>_<field>` when `indexed` is true.
  - The list keys, in order.
  - The empty first key, with an empty label.
  - That the absent fields do not exist.
- `LegacyMetadataLoader::load()` has a new key `indices`. It is `$GLOBALS['dictionary'][<object name>]['indices']`.
- Precondition: `tkc:repair` has run, so that `Ext/` is compiled. The test boots `App\Kernel` in `dev` (SUITE-176).
- The test does not check the database. The second `tkc:repair --dry-run` proves that the column exists: it must print "Database is in sync: no SQL."
- A new story adds a manifest file in the same story.
- `LegacyMetadataLoader::load()` has a new key `modStrings`. It is the result of `return_module_language('en_us', <module>)`. `FieldManifestTest` uses it to check that the label (`vname`) of each manifest field loads.

## Finance module tests (SUITE-64)
- `Tests/Unit/AccountFinance/FinanceSaveActionTest.php` (unit): drift guard. It compares the `save` action copy in the Finance detail viewdef with the core `actions.yaml` entry.
- `Tests/Integration/Migrations/SeedFinanceAclTest.php` (integration): runs `up()` of the seed. It saves a snapshot of the rows and restores it. It covers the empty case, the allow-all case (89/90) and a -98 row.
- `Tests/Integration/Migrations/FinanceAclDenyTest.php` (integration): reads the real ACL rows. `access` must be 89 and the seven other actions must be -99. A helper case shows that -98 fails the check.
- `Tests/Unit/Console/MigrationsMigrateCommandTest.php` (unit): the filter keeps only TKC versions. With none left the command says "No new TKC migrations." and exits with 0.
- `Tests/Contract/FieldManifestTest::testAuditTableExistsForAuditedFields`: an audited field needs its audit table. The manifest is `Tests/Contract/Fields/suite64_accountfinance.php`.

## Case thread tests (SUITE-68)
- `Tests/Integration/Thread/LongDescriptionTest.php` (integration): a synthetic text of 1,048,576 characters is saved in `AOP_Case_Updates.description` through the bean. The test reads it back and compares the SHA-256 hash. It uses no case and no contact. It deletes the row at the end. It needs `tkc:repair --execute` first, so that the column is `longtext`, and a MySQL `max_allowed_packet` of 16 MB or more (see `docs/environments.md`).
- `Tests/Contract/Fields/suite68_aop_case_updates.php` is the manifest of the thread fields. `testAuditTableExistsForAuditedFields` skips it, because it has no audited field. This is expected.
- `tkc_AccountFinance` is in `Tests/Contract/modules.php`.

## Opportunity probability test (SUITE-69)
- `extensions/tkc/backend/Tests/Contract/OpportunityProbabilityLogicTest.php` is in the `contract` suite.
- The test checks that `Opportunity.probability.logic` has one entry for each key of `sales_stage_dom`. It checks that the `targetValue` of each entry is the value of `sales_probability_dom` for that key.
- Precondition: `tkc:repair` has run, so that `Ext/` is compiled.
- `Opportunities` is in `Tests/Contract/modules.php`. The manifest is `Tests/Contract/Fields/suite69_opportunities.php`.

## Import key tests (SUITE-71)
- `extensions/tkc/backend/Tests/Unit/Config/DuplicateIgnoreDeltaTest.php` (unit): it applies the D5 delta `tkc_duplicate_ignore.php` to a `ContainerBuilder`. It checks four things: the `default` list has the three fields; existing entries and module lists stay; there are no duplicates; a second run changes nothing. It also covers a missing parameter.
- `Tests/Contract/Fields/suite71_osc_id.php` is the manifest of `osc_id_c` for Accounts, Contacts, Cases and Opportunities (varchar 20, not audited, not required, indexed).
- `Contacts` is in `Tests/Contract/modules.php` since SUITE-65. The manifest of its fields is `Tests/Contract/Fields/suite65_contacts.php`.

## Primary contact relationship test (SUITE-67)
- `extensions/tkc/backend/Tests/Contract/PrimaryContactRelationshipTest.php` is in the `contract` suite.
- The test reads only `relationship_name` and `relationship_type` from the `relationships` table. It reads no records.
- It checks two items: `tkc_contacts_cases_primary` is `one-to-many`, and the core relationship `contacts_cases` exists.
- Precondition: `tkc:repair` has run, so that it writes the `relationships` rows. The database is reachable.
- The manifest is `Tests/Contract/Fields/suite67_primary_contact.php`. It uses the `massupdate` key (see § Field manifest test). It covers the Cases fields and the Contacts link.
- No core override. No row in the Overrides register.

## Repair language cache test (SUITE-204)
- `extensions/tkc/backend/Tests/Contract/RepairLanguageCacheTest.php` is in the `contract` suite.
- The test writes a stale `cache/modules/Accounts/language/en_us.lang.php` with a synthetic key. It then runs `php bin/console tkc:repair --dry-run` as a subprocess. The command must exit with 0.
- The test checks these items: the stale file is rebuilt; the loader `modStrings` has no stale key; every Accounts `Ext/Language` label loads with its value; `cache_rebuild` has a `rebuild_all` row.
- The test always removes the stale file when it fails.
- Preconditions: `tkc:repair` has run, so that `Ext/` is compiled. The database is reachable. The dry run writes one `cache_rebuild` row and no schema change.

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

### TKC data migrations (SUITE-64)
- TKC data migrations run only through `tkc:migrations:migrate` (`extensions/tkc/backend/Console/MigrationsMigrateCommand.php`). The command uses the Doctrine `DependencyFactory`. It runs the new versions in `App\Extension\tkc\Migrations`. `--dry-run` lists them and runs nothing.
- Never run `doctrine:migrations:migrate` or `doctrine:migrations:version --add --all` in the build or in a deploy.
  - The SuiteCRM installer never records the core migrations. After an install, 39 show as "New". This is the normal upstream state.
  - The core migrations have side effects. They rewrite `config.php`, append `APP_SECRET` to `.env.<env>.local`, move media folders and create manual-migration tasks.
  - `version --add --all` also marks TKC migrations that never ran. This is a security gap.
- A seed migration must be an upsert. Core code (`ACLAction::addActions`, `create_tables`, "Repair Roles") inserts allow-all rows and never updates them.
- The core migrations at an upstream merge are open work: SUITE-206.
- Risk: the command depends on the Doctrine Migrations 3.x API. Check it at each upstream merge.

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
- `php bin/console tkc:repair [--dry-run|--execute] [--module=<Module>]`: Quick Repair and Rebuild from the CLI. Dry run is the default and shows the SQL. "Dry run" means that the database schema is not changed. The caches are still cleared and rebuilt (SUITE-204).
  - After the extension rebuild, the command clears the legacy module language cache (`cache/modules/<Module>/language/`), the app and list strings cache and the JS language files. It does this for all modules and all languages. It calls `LanguageManager::clearLanguageCache()` of the core. The core `RepairAndClear::clearLanguageCache()` skips the module cache for an empty module list, so the call is needed.
  - The command then checks that every label of the compiled `Ext/Language` files loads with the same value. The output shows module, language and keys, never values. The command exits with 1 when a label does not match.
  - The command writes a `rebuild_all` row to the table `cache_rebuild`. The web process then clears `cache.app` on the next request. This clears the Angular language strings. The dry run also writes this row.
  - Upgrade re-checks: the signature of `LanguageManager::clearLanguageCache()`; the path `cache/modules/<Module>/language/`; the `cache_rebuild` and `rebuild_all` contract of `CacheManagerInterface::markAsNeedsUpdate()`; the cache key `app-metadata-language-strings-<lang>`.
  - Azure risk: with APCu, the CLI cannot clear the `sugar_cache` of PHP-FPM. The deploy must clear it after a repair (for example with a PHP-FPM reload; SUITE-164 decides the method).
  - Audit tables (SUITE-64): the command creates each missing audit table of an audited module. The dry run shows `/* MISSING AUDIT TABLE: <name> */`. The core `rebuildAuditTables()` does nothing with an empty module list, so the command does the check itself.
- `php bin/console tkc:migrations:migrate [--dry-run]`: runs the new TKC data migrations (SUITE-64). It does not run core migrations. The output lists the versions and "N core migrations not run (upstream-merge flow only)". It never prints record contents. It exits with 1 on an exception. See § TKC data migrations. Build step 8 runs it with `--dry-run`. Build step 10b runs it after `tkc:repair --execute`.
- `php bin/console tkc:config:apply [--dry-run]`: merges `scripts/config/config_override.template.php` (secret-free) into `public/legacy/config_override.php`.
- `php bin/console tkc:mail:use-sink [--dry-run]`: sets the system outbound account to the mail sink. DEV only, idempotent (SUITE-45).
- `php bin/console tkc:users:repair-number-separators [--dry-run]`: removes the user preferences `dec_sep` and `num_grp_sep` that equal the other effective separator (SUITE-179). Run it after `tkc:config:apply`. It writes through the legacy `UserPreference` bean. A signed-in user must sign out and in again (the session writes the old values back). Code in `extensions/tkc/backend/Users/` (`Console/RepairNumberSeparatorsCommand`, `NumberSeparatorPolicy`, `LegacyPreferenceStore`). Upgrade risk: it depends on the key names `dec_sep` and `num_grp_sep`, on the config keys `default_decimal_seperator` and `default_number_grouping_seperator`, and on the base64-serialized format of `user_preferences.contents`. Re-check at each upstream merge.
- `php bin/console tkc:legacy:asset-version [--dry-run] [--target=<file>]`: sets `js_custom_version` in `public/legacy/config_override.php` to `tkc` and 12 hex characters of a sha256 hash over the legacy theme inputs, `composer.lock` and `public/legacy/suitecrm_version.php` (SUITE-181, SUITE-183). It prints only that key and the result ("No change", "Would apply" or "Applied"). It exits with 1 when an input is missing or the file cannot be written. Run it after `tkc:config:apply` (build step 11b). Details in § Legacy theme.
- `php bin/console tkc:sequence <show|next|set> <name> <year> [<value>] [--count=N] [--allow-lower] [--execute]`: shows, advances or sets a counter of the sequence service (SUITE-62). Details in § Sequences.
- Code in `extensions/tkc/backend/Console/` and `Config/`. Details in `scripts/config/README.md`.

## Sequences (SUITE-62)
The sequence service gives numbers that are unique for each counter name and year. The table is `tkc_sequences` (see `docs/data-model.md` § Tables). Quote numbers and case references use it later.

### Service
- Code in `extensions/tkc/backend/Sequence/`: the final class `App\Extension\tkc\backend\Sequence\Sequence`, the interface `SequenceStore`, the class `LegacyDbSequenceStore`, the final class `SequenceInput` and the exception `LowerValueException`.
- `SequenceStore` and `LegacyDbSequenceStore` are `@internal` ("Use Sequence"). Callers use `Sequence` only.
- `LegacyDbSequenceStore` gets the connection from `DBManagerFactory::getInstance()` when it needs it. Its constructor has no arguments.
- `config/core_services.yaml` autowires everything under `extensions/*`. No `services.yaml` entry exists.
- The code is in `extensions/tkc/backend`, not in `custom/include/TKC`. Reason: the legacy `entryPoint.php` loads the root Composer autoloader. Shared TKC PHP code therefore lives in `extensions/tkc/backend`.
- Symfony code autowires `Sequence`. Hooks and legacy code call the static `Sequence::legacy()`. The legacy layer must be loaded.

### API
| Method | Result |
|---|---|
| `next(string $name, int $year): int` | The last value plus one. |
| `current(string $name, int $year): ?int` | The last value. `null` when the counter does not exist. |
| `setLastValue(string $name, int $year, int $value, bool $allowLower = false): void` | Sets the last value. Without `$allowLower`, it calls `SequenceStore::setIfNotLower()` and throws `LowerValueException` when the stored value is above `$value`. An equal value is a success. With `$allowLower`, it calls `SequenceStore::forceSet()`. The value must be from 0 to 2147483646. |

- The name must match `^[a-z][a-z0-9_]{0,39}$`. The year must be from 2000 to 2099. Wrong input throws `InvalidArgumentException`.
- `SequenceInput` (final, `@internal`) holds the checks: `checkKey()` (name and year) and `checkValue()` (0 to 2147483646). `Sequence`, `LegacyDbSequenceStore` and the console command call it. The store therefore refuses bad input before it opens a database connection.
- Store methods (SUITE-205):
  - `forceSet(string $name, int $year, int $value): void` (renamed from `set()`). It makes no check. Numbers above `$value` can repeat. `Sequence::setLastValue()` calls it only when `$allowLower` is true.
  - `setIfNotLower(string $name, int $year, int $value): int` creates the row at `$value` when it is missing. It sends the stored value from before the call. It changes nothing when that value is above `$value`.
- `LowerValueException` extends `\InvalidArgumentException`. It has `public readonly int $current`. The E4 import can catch it as "the counter is already ahead". A re-run of the import stays idempotent.
- `Sequence::setLastValue()` no longer reads the value with `current()` first. The read and the write are one atomic statement in the store.

### Guarantee
- Every value from `next()` is unique for its name and year.
- The series can have gaps. Example: a save fails after `next()` gave a number. Do not build logic that needs a series without gaps.
- `setLastValue()` never lowers a counter without `$allowLower`. This is also true when other processes call `next()` or `setLastValue()` at the same time (SUITE-205). Before the fix, a PHP pre-read and a later write could lower a counter, and numbers repeated.
- At the INT maximum (2147483647), `next()` throws a `RuntimeException`. The UPDATE has the guard `AND seq_value < 2147483647`, and `next()` checks that exactly one row changed (QA finding D1). Without the guard, a non-strict MySQL session clamps the column at the maximum. `LAST_INSERT_ID()` would then give a larger value that is not stored, and a number could repeat.

### Concurrency recipe
`next()` runs three statements on one connection. It uses no explicit transaction.
1. `INSERT … ON DUPLICATE KEY UPDATE seq_name = seq_name` creates the row once. The primary key absorbs a race of two first calls.
2. `UPDATE … SET seq_value = LAST_INSERT_ID(seq_value + 1) … AND seq_value < 2147483647` is atomic and takes the InnoDB row lock. The code checks that exactly one row changed.
3. `SELECT LAST_INSERT_ID()` reads the value of this connection.

`setIfNotLower()` (used by `setLastValue()` without `$allowLower`) also runs three statements on one connection, with no explicit transaction (SUITE-205):
1. `INSERT INTO tkc_sequences (seq_name, seq_year, seq_value, date_modified) VALUES (name, year, v, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE seq_name = seq_name` creates the row at `v` when it is missing.
2. `UPDATE tkc_sequences SET date_modified = IF(seq_value <= v, UTC_TIMESTAMP(), date_modified), seq_value = GREATEST(LAST_INSERT_ID(seq_value), v) WHERE seq_name = … AND seq_year = …` is atomic and takes the row lock. Keep the order of the assignments: MySQL runs them from left to right.
3. `SELECT LAST_INSERT_ID()` gives the value from before the change.

- Do not use the affected-row count. An equal value gives 0 rows, but it is a success.
- Engine re-check: this recipe depends on `LAST_INSERT_ID(expr)` and on the left-to-right order of the UPDATE assignments. Re-check it when the database engine changes (the Azure plan is managed MySQL 8.0, SUITE-162; re-check if it ever moves to another engine such as MariaDB).
- Evidence (SUITE-205): on the old code, the parallel test repeated values (2546 twice, 10212 twice). After the fix, 6 of 6 runs gave 40000 unique values. A QA run of 90000 values had 0 duplicates.

Risk: inside a legacy transaction on the same connection, the row lock stays until the commit. Saves that use the same counter then run one after the other.

Evidence (QA): 8 parallel processes with 250 calls each gave 2000 different values. Counters of two years are independent. Input that fails validation writes nothing.

### Console
- `tkc:sequence` runs in the CLI only. It has no web route.
- `show <name> <year>` prints the last value, or "no counter".
- `next <name> <year> [--count=N]` prints one value on each line. `--count` must be from 1 to 100000.
- `set <name> <year> <value> [--allow-lower] [--execute]` writes nothing without `--execute`. It exits with 0 in a dry run.
- The dry run checks the range first. A value outside 0 to 2147483646 gives exit 1 and the text "The value must be from 0 to 2147483646."
- Dry-run texts (nothing is written):
  - `Dry run: <name> <year> would change from <current|no counter> to <v>. Use --execute to write.`
  - For a lower value without `--allow-lower`: `Warning: 100 is lower than the current value 120. --execute refuses it without --allow-lower.`
  - For a lower value with `--allow-lower`: `Warning: 100 is lower than the current value 120. The numbers 101 to 120 can be given again. Use --allow-lower only for a test or a recovery, after a pre-check.`
  - The last line is always: `The counter can change before you run --execute.`
- `--execute` with a lower value and without `--allow-lower` gives exit 1 and the `LowerValueException` message. `--execute --allow-lower` prints the warning, then `last value set to …`.
- Rule: use `--allow-lower` only in a test or a recovery. Stop all callers of this counter first. Make sure that no record has a number above the new value.
- The output has counter names and numbers only.
- All legacy database sessions run MySQL in non-strict mode: console, web, and PHPUnit tests that load the legacy layer. `MysqliManager::connect()` removes `STRICT_TRANS_TABLES` from `sql_mode` on each connection (`public/legacy/include/database/MysqliManager.php:378`). The server then clamps a number or truncates a string and gives only a warning. Validate all input in PHP before the SQL. `Sequence` and `LegacyDbSequenceStore` both do this. A guard test (`@@SESSION.sql_mode` has no `STRICT_TRANS_TABLES`) fails if upstream changes this behaviour.

### Later use
- E4 import: calls `setLastValue()` with the highest imported number for each year.
- E10 quote number: the counter `quote` replaces the OSC `CRM.offerteNr` (see `docs/osc-mapping/opportunity.md` § 6.2).
- E8 case reference (decision A12): the counter name and the format are not decided yet.
- No row in the Overrides register: the service does not override core code.

## Core patches
| Patch | File | Reason | Issue | Re-check on upgrade |
|---|---|---|---|---|
| — | | | | |

## Overrides register
This register lists every file that overrides a core file or a core parameter. Re-check every row at each upstream merge. Add a row in the same story that adds the override.
Type values: viewdef full copy · core list override · core vardef override · D5 parameter delta · core theme rule override · Angular field override · dependency version (lock) · dependency resolution (package.json) · core action definition copy.
- "Core action definition copy": a module viewdef holds a full copy of a core record action entry, with one changed parameter. A module action replaces the core action with the same key. The copy does not follow later core changes, so a drift guard test checks it (SUITE-64).
- "Dependency version (lock)": `composer.lock` or `yarn.lock` holds a newer version than upstream. The constraint in `composer.json` or `package.json` does not change. This is not a core patch (SUITE-183, decision 1).
- "Dependency resolution (package.json)": a `resolutions` entry in `package.json` forces a version. Use it only when a parent range blocks a lock-only fix. No row uses this type yet. A change to a constraint in `composer.json` or to a range in `package.json` is Level 5 and needs the architect.

| File | Module | Type | Reason | Upstream source to diff | Check at merge |
|---|---|---|---|---|---|
| `extensions/tkc/app/src/theme/overrides/_radio.scss` (SUITE-186) | all (Angular) | core theme rule override | Core hides the radio inputs with `display: none`. A keyboard user cannot reach them. The override resets `display` and hides the inputs visually. It adds the focus ring on the label. | `core/app/shell/src/themes/suite8/css/components/_radio.scss:27-60` | The selector `.radioenum-input input[type='radio']` and the markup are the same. `scripts/theme/check_bundle.py` passes (check 3). |
| `extensions/tkc/app/src/theme/overrides/_menus.scss` (SUITE-194) | all (Angular) | core theme rule override | Core puts button classes on the collapsed Actions menu items, so the TKC button rules made them white on white. The button rules now exclude `.dropdown-item`. This partial sets the menu and item colours, hover and focus from tokens, in light and dark mode. | `core/app/shell/src/themes/suite8/css/components/_dropdown.scss:497-525, 614-640` | The wrapper classes (`.button-group-dropdown`, `.dropdown-button-secondary`) and the `a ngbDropdownItem` markup in `dropdown-button.component.html` are the same. `scripts/theme/check_bundle.py` passes (check 4). |
| `extensions/tkc/app/src/fields/radioenum/tkc-radioenum-edit.*` (SUITE-186) | all (Angular) | Angular field override | Core has no `role` and no accessible name on the radio group, no label for the empty option, and the DOM `name` is not set. Registered for `radioenum.edit` and `enum-radio.edit` (same core class). Three lines differ from the core template, each marked `TKC SUITE-186`: (1) `role="radiogroup" [attr.aria-label]="groupLabel \|\| null"` on `div.radioenum`; (2) `[attr.name]="field.name"` on the input (see the note below); (3) `{{ optionLabel(item) }}` in the label. The class extends the core class and has no own constructor. | `core/app/core/src/lib/fields/radioenum/templates/edit/radioenum.component.html` and `radioenum.component.ts` | Diff the core template and `.ts` (`getId`, `buildOptionsArray`). The keys in `core/app/core/src/lib/fields/base-fields.manifest.ts` are the same. The `FieldRegistry.register` signature is the same. `getAppString` and `getFieldLabel` of the language service exist. The `core` package is still a singleton in the extension webpack config. |
| `extensions/tkc/config/tkc_tinymce.php` | Angular edit field (tinymce) | D5 parameter delta | Removes the TinyMCE `media` plugin from the Angular edit field. npm `tinymce` 7.9.3 has advisory GHSA-mf2p-h6hf-fcwm and no 7.x fix (SUITE-183). | `config/services/ui/ui.yaml` key `tinymce.edit.plugins` (line 21) | Check `ui.yaml` `tinymce.edit.plugins` and `core/app/core/src/lib/fields/tinymce/templates/edit/tinymce.component.ts:74-78`. The component merges the list with lodash merge, index by index. Keep 3 or more entries. The core `plugins` setting stays a list of strings. The delta treats spaces and commas as separators (security review F4, commit 736e0c496). If the setting became one string, the container build fails (fail closed). Remove the file when upstream ships a fixed npm TinyMCE. |
| `extensions/tkc/config/tkc_duplicate_ignore.php` (SUITE-71) | all (Angular Duplicate action) | D5 parameter delta | Read-modify-write of `module.recordview.duplicate_ignore`. It adds `osc_id_c`, `tkc_reference_c` and `tkc_case_ref_c` to the `default` list, without duplicates, and drops the null entry of the core YAML. The compiled value is `{"default":["osc_id_c","tkc_reference_c","tkc_case_ref_c"]}`. The Angular Duplicate action copies every field. A copy of `osc_id_c` would break idempotent re-imports. A copy of the quote number or the case reference would give two records the same number (SUITE-69 security C2c). Only the `default` list works: the per-module list is added as one nested array (architect E3 finding 11). The server handler `GetDuplicateRecordHandler` also reads per-module lists. | `config/services/module/recordview/duplicate_ignore.yaml`; `core/app/core/src/lib/.../record-duplicate.service.ts:55` | Check the YAML and `record-duplicate.service.ts:55`. When upstream fixes the per-module list, move the delta to per-module lists. `DuplicateIgnoreDeltaTest` passes. |
| `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_account_type_dom.php` (SUITE-63) | Accounts | core list override | Replaces the whole core list `account_type_dom` with the six TKC account types (`tkc_lead`, `Prospect`, `Customer`, `tkc_supplier`, `tkc_relation`, `tkc_former_customer`). It has no empty key. The core keys Analyst, Competitor, Integrator, Investor, Partner, Press, Reseller and Other are hidden. Upgrade risk: medium. | `public/legacy/include/language/en_us.lang.php` (`account_type_dom`) | Compare the core list with the TKC file. A new upstream key stays hidden until you add it here. Core keys must not be renamed (D7). |
| `public/legacy/custom/Extension/modules/Accounts/Ext/Vardefs/tkc_account_type_default.php` (SUITE-63) | Accounts | core vardef override | Sets `$dictionary['Account']['fields']['account_type']['default'] = 'tkc_relation'`. New accounts start as "Relation" (OSC default "Relatie"). Repair gives no SQL for it. | `public/legacy/include/SugarObjects/templates/company/vardefs.php:62-66` (`<object>_type`, built from the object name) | The field `account_type` still uses `account_type_dom`. The key `tkc_relation` is still in the TKC list. |
| `public/legacy/modules/tkc_AccountFinance/metadata/detailviewdefs.php` `recordActions.actions.save` (SUITE-64) | tkc_AccountFinance | core action definition copy | A full copy of the core `save` entry with `params.reloadSubpanels` set to false. The core `record-view.store.ts` `reloadSubpanels()` throws for a module without subpanels, because `subpanels` is never initialised. Without the override the save never goes back to the record (D2b). Latent: `actions.adapter.ts:150` has the same flaw. Finance does not reach it. Every future TKC module without subpanels needs the same override. | `config/services/module/recordview/actions.yaml` `default.actions.save`; `core/app/core/src/lib/views/record/store/record-view/record-view.store.ts` | `FinanceSaveActionTest` (drift guard) passes. Remove the override when upstream guards the null. |
| `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_case_core_lists.php` (SUITE-66) | Cases | core list override | One file with four overrides. It replaces the whole core lists `case_status_dom` (12 keys in OSC order; keys keep the `Open_` or `Closed_` prefix, A2), `case_priority_dom` (`''`, `P1`, `P2`, `P3`, `tkc_P4`) and `case_type_dom` (`''` and four `tkc_*` keys). It also sets the string `case_priority_default_key` to `''` (core: `P2`). Hidden core keys: `Closed_Rejected`, `Closed_Duplicate` (status); Administration, Product, User (type). No core key is renamed (D7). The values are in `docs/data-model.md` § Core overrides. Upgrade risk: medium. | `public/legacy/include/language/en_us.lang.php` (`case_status_dom`, `case_priority_dom`, `case_priority_default_key`, `case_type_dom`) | Compare each core list with the TKC file. A new upstream key stays hidden until you add it here. `modules/AOP_Case_Updates/CaseUpdatesHook.php:308` still derives `state` from the key prefix. `modules/Cases/CasesQuickCreate.php:61` is still the only reader of `case_priority_default_key`. The core "open only" search filter and the My Cases dashlet still use the core status keys (SUITE-200, SUITE-201). |
| `public/legacy/custom/Extension/modules/AOP_Case_Updates/Ext/Vardefs/tkc_description_longtext.php` (SUITE-68) | AOP_Case_Updates | core vardef override | Sets `$dictionary['AOP_Case_Updates']['fields']['description']['dbType'] = 'longtext'`. OSC thread texts can be longer than `text` (64 KB). The field type stays `text`. Repair changes the column (one `MODIFY`). Upgrade risk: medium. | `public/legacy/include/SugarObjects/templates/basic/vardefs.php` (`description`) and the core vardefs of `AOP_Case_Updates` | The core `description` definition of AOP_Case_Updates and of the Basic template still has the type `text`. `LongDescriptionTest` and the manifest check `dbType` pass. |
| `public/legacy/custom/Extension/modules/Opportunities/Ext/Vardefs/tkc_name_len.php` (SUITE-69) | Opportunities | core vardef override | Sets `$dictionary['Opportunity']['fields']['name']['len'] = '255'`. OSC opportunity names are longer than the core 50 characters. Repair changes only the length (one `MODIFY`). Upgrade risk: low. | `public/legacy/modules/Opportunities/vardefs.php` (`name`) | The core `name` field is still a varchar of 50 and has no other change. The manifest `suite69_opportunities.php` passes. |
| `public/legacy/custom/Extension/modules/Opportunities/Ext/Vardefs/tkc_probability_logic.php` (SUITE-69, AC 3) | Opportunities | core vardef override | Adds only the key `logic` to `$dictionary['Opportunity']['fields']['probability']`: 8 `updateValue` entries, one for each sales stage key (spike S11, no hook). The probability follows the stage in the Angular edit and create view. Repair gives no SQL. Accepted gap: a stage change outside the Angular edit and create view does not recompute the probability. Upgrade risk: medium. | `public/legacy/modules/Opportunities/vardefs.php` (`probability`) and the Angular `update-value.action.ts` | The core `probability` definition has no own `logic` key. The `updateValue` logic action and its params (`fieldDependencies`, `activeOnFields`, `targetValue`) are the same. `OpportunityProbabilityLogicTest` passes. |
| `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_sales_stage_dom.php` (SUITE-69), list `sales_stage_dom` | Opportunities | core list override | Replaces the whole core list with 8 keys: the core keys `Prospecting`, `Qualification`, `Needs Analysis`, `Negotiation/Review`, `Closed Won`, `Closed Lost` and the new keys `tkc_quote_multi` and `tkc_quote_single` (A13). Hidden core keys: `Value Proposition`, `Id. Decision Makers`, `Perception Analysis`, `Proposal/Price Quote`. No core key is renamed (D7). The values are in `docs/data-model.md` § Core overrides. Upgrade risk: medium. | `public/legacy/include/language/en_us.lang.php` (`sales_stage_dom`) | Compare the core list with the TKC file. A new upstream key stays hidden until you add it here. `modules/AOS_Quotes/createOpportunity.php:49-50` still writes the hidden `Proposal/Price Quote` (SUITE-202). The My Opportunities dashlet still filters on core stage keys (SUITE-201). |
| same file (SUITE-69), list `sales_probability_dom` | Opportunities | core list override | Replaces the whole core list. It has the same 8 keys as `sales_stage_dom`. Values: 0, 5, 10, 25, 50, 80, 100 and 0. Core `Opportunity::save()` fills an empty probability from this list. Upgrade risk: medium. | `public/legacy/include/language/en_us.lang.php` (`sales_probability_dom`) | The keys of both lists are the same. The values match `targetValue` in `tkc_probability_logic.php` (`OpportunityProbabilityLogicTest`). |
| `composer.lock` | lock | dependency version (lock) | `lcobucci/clock` 3.5.0 (SUITE-175). Upstream has 3.0.0. `composer.json` keeps the PHP 8.3 platform. | `composer.lock` of the upstream tag | Keep 3.5.0 while the platform is PHP 8.3. |
| `composer.lock` | lock | dependency version (lock) | `guzzlehttp/guzzle` 7.16.5 (upstream 7.12.1): 7 advisories (1 high, 6 medium). Title: noncanonical host bypass. | `composer.lock` of the upstream tag | Keep the TKC version or higher. Drop the row when upstream reaches it. |
| `composer.lock` | lock | dependency version (lock) | `guzzlehttp/psr7` 2.13.5 (upstream 2.12.1): 1 medium advisory (host confusion). | same | same |
| `composer.lock` | lock | dependency version (lock) | `league/flysystem` 3.36.0 (upstream 3.31.0): 1 low advisory (path normalizer). | same | same |
| `composer.lock` | lock | dependency version (lock) | `mtdowling/jmespath.php` 2.10.0 (upstream 2.8.0): 1 critical advisory (`CompilerRuntime` code injection). | same | same |
| `composer.lock` | lock | dependency version (lock) | `phpseclib/phpseclib` 3.0.59 (upstream 3.0.54): 2 advisories (PBKDF iteration count, X25519 timing). | same | same |
| `composer.lock` | lock | dependency version (lock) | `smarty/smarty` v4.6.0 (upstream v4.5.6): 2 medium advisories (`{fetch}` SSRF, symlink traversal). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/config` v6.4.50 (upstream v6.4.32): 1 advisory (XXE in `XmlUtils::parse()`). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/expression-language` v6.4.48 (upstream v6.4.32): 1 advisory (unbounded nesting, CVE-2026-109449). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/filesystem` v6.4.50 (upstream v6.4.30): 1 advisory (path prefix before `://`). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/http-client` v6.4.50 (upstream v6.4.33): 2 advisories (`ScopingHttpClient`, `NoPrivateNetworkHttpClient`). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/http-foundation` v6.4.50 (upstream v6.4.41): 4 advisories (1 high, ReDoS CVE-2026-108082; CVE-2026-118514). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/http-kernel` v6.4.50 (upstream v6.4.33): 1 advisory (inline fragments and forwarded headers). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/mime` v6.4.49 (upstream v6.4.40): 1 medium advisory (header injection, CVE-2026-45070). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/routing` v6.4.50 (upstream v6.4.41): 1 advisory (host placeholders, CVE-2026-118516). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/security-http` v6.4.50 (upstream v6.4.41): 2 advisories (CVE-2026-118513; `checkPostAuth()` skipped). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/serializer` v6.4.50 (upstream v6.4.33): 3 advisories (`DataUriNormalizer`, `ObjectNormalizer`, CVE-2026-118515). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/validator` v6.4.50 (upstream v6.4.33): 1 advisory (`Ip` constraint modes). | same | same |
| `composer.lock` | lock | dependency version (lock) | `symfony/yaml` v6.4.50 (upstream v6.4.40): 2 advisories (1 high, CVE-2026-109413; parse time). | same | same |
| `composer.lock` | lock | dependency version (lock) | `tinymce/tinymce` 8.10.2 (upstream 8.5.1): 1 high advisory (media plugin XSS). This copy serves the legacy UI. | same | same |
| `composer.lock` | lock (grouped) | dependency version (lock) | 24 transitive packages that `composer update -W` raised with the rows above. List: `guzzlehttp/promises` 2.6.2; `league/flysystem-local` 3.35.3; `league/mime-type-detection` 1.17.0; `symfony/cache` v6.4.50; `symfony/cache-contracts` v3.8.1; `symfony/deprecation-contracts` v3.8.1; `symfony/error-handler` v6.4.49; `symfony/event-dispatcher` v6.4.44; `symfony/event-dispatcher-contracts` v3.8.1; `symfony/http-client-contracts` v3.8.1; `symfony/polyfill-intl-grapheme` v1.43.3; `symfony/polyfill-intl-idn` v1.43.0; `symfony/polyfill-intl-normalizer` v1.43.0; `symfony/polyfill-mbstring` v1.43.3; `symfony/polyfill-php83` v1.43.0; `symfony/process` v6.4.49; `symfony/property-access` v6.4.50; `symfony/property-info` v6.4.50; `symfony/security-core` v6.4.50; `symfony/service-contracts` v3.8.1; `symfony/string` v6.4.49; `symfony/translation-contracts` v3.8.1; `symfony/var-dumper` v6.4.49; `symfony/var-exporter` v6.4.49. | same | Keep the TKC version or higher. Drop a package when upstream reaches it. |
| `yarn.lock` | lock | dependency version (lock) | npm `dompurify` 3.4.18 (upstream 3.4.11): advisories on the Angular runtime (see Accepted dependency advisories for the rest). Also: the `tinymce` 7.2.1 entry is merged into 7.9.3 (same command, `yarn up -R tinymce dompurify`). | `yarn.lock` of the upstream tag | Keep the TKC version or higher. Drop the row when upstream reaches it. |

Note for line (2): the Angular `RadioControlValueAccessor` takes `[name]` as a directive input, so Angular never sets the DOM `name` attribute. The inputs of one group have no shared `name`, and the arrow keys do not move between them. `[attr.name]` sets the DOM attribute. This differs from the architect prescription, which assumed that `[name]` was enough (QA found it).

The string `LBL_TKC_NOT_SET` ("Not set") is a new app string in `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_app_strings.php`. It is not an override. Do not reuse `LBL_NONE` or `LBL_EMPTY`.

Upstream v8.10.2 is the base of these versions. The TKC changes in `composer.lock` are 43 updates, all inside the upstream constraints. `composer.json`, `package.json`, `.yarnrc.yml` and `symfony.lock` did not change.

### Upstream merge procedure for lock files (SUITE-183, decision 2)
1. Never hand-merge `composer.lock` or `yarn.lock`.
2. On a conflict, take the upstream file: `git checkout --theirs composer.lock yarn.lock`.
3. For each row above that upstream does not yet meet, run the re-resolve command again. Keep `lcobucci/clock` 3.5.0.
4. Run `composer validate --no-check-publish`, `composer audit` and `yarn npm audit --all --recursive --environment production`. Compare the result with § Accepted dependency advisories.
5. Delete each row that upstream now meets. Update the accepted table.

### Dependency re-resolve commands (SUITE-183, decision 3)
- Composer runs in the app container as `www-data`, with `COMPOSER_HOME=/tmp/composer`:
  `composer update <packages> --with-all-dependencies --no-security-blocking`
- Reason for `--no-security-blocking`: the root `composer.json` pins some vulnerable packages (see the table below). Without the flag, Composer refuses the update.
- Reason for `www-data`: a run as root leaves root-owned files in the cache. The app then answers with HTTP 500.
- Yarn runs on the host: `yarn up -R tinymce dompurify`. Then run `scripts/build.sh`.
- Dependency work needs the architect when it changes a constraint in `composer.json` or a range in `package.json` (Level 5). Lock-only updates inside the constraints do not.

### Accepted dependency advisories (SUITE-183)
After SUITE-183, `composer audit` shows 4 advisories and 3 abandoned packages. `yarn npm audit` shows the rows below. The security-officer accepted each row at the SUITE-183 sign-off. "Server" means the code runs on the server. R means it ships to the browser. B means it runs only at build time.

Accepted by: security-officer, SUITE-183 sign-off, 2026-11-21, with the conditions in each row, unless the row says "architect". The security-officer co-signs the architect rows.

| Package | Version | Advisory | Severity | R/B | Reason | Mitigation / condition | Re-check trigger |
|---|---|---|---|---|---|---|---|
| `api-platform/core` | 3.4.17 | PKSA-8kfs-m8zw-ggzs, PKSA-3ncz-km6v-5vjr | medium | server | Only the `jsonld` and `multipart` formats are on (`config/packages/api_platform.yaml:4-6`). Record saves go through GraphQL with a custom processor. TKC has no `ApiResource`. The fix exists only in 4.x (a root pin change is Level 5). | TKC adds no `ApiResource` with a writable relation. No `jsonapi` or `jsonhal` format. Azure has `APP_ENV=prod` and `GRAPHQL_SHOW_DOCS` is not true. | Upstream moves `api-platform/core`. A TKC API resource (architect). |
| `zbateson/mail-mime-parser` | 2.4.1 | PKSA-f3bq-hwxm-x4yf, PKSA-r8x8-972m-nrc1 | high | server | The legacy InboundEmail parses every fetched message (`InboundEmail.php:325,4162-4183`). The fix is 3.0.6 only. The root requires `^2.4` and `javanile/php-imap2` requires `^2.2`. | No inbound mailbox of any type (group, personal, bounce handling, email-to-case) is active in an environment with real data until SUITE-192 closes. Users can create personal mailboxes themselves. Bounce-handling mailboxes are also `inbound_email` rows. Check at the OSC real-data import gate (SUITE-156) and at each Azure deploy (SUITE-164): `inbound_email` has no row with `deleted=0` and `status='Active'` (aggregate count only), and the scheduler jobs `function::pollMonitoredInboxes`, `function::pollMonitoredInboxesForBouncedCampaignEmails` and `function::pollMonitoredInboxesAOP` are Inactive. | SUITE-192. Upstream moves to 3.x. |
| `@angular/common`, `@angular/core`, `@angular/router` | 18.2.14 | 13 high and 5 moderate (XSS sanitizer, i18n, SSR, HttpTransferCache, XSRF, DoS) | high | R | No 18.x fix exists (first fix 19.2.26). The major upgrade is upstream work. TKC does not use SSR, hydration, transfer cache or i18n. | TKC code uses `HttpClient` with relative paths only. No `bypassSecurityTrust*`. No record data in SVG or MathML attribute bindings. No i18n attributes. No `trustHTML` field metadata. No `safeHtml` or `trustHtml` pipe in TKC templates. No field metadata `tinymce.edit.plugins` (it would merge `media` back in, `tinymce.component.ts:85-88`). Enforcement: the CI gate (SUITE-163) fails on these patterns. | Upstream Angular 19.2.26 or higher. A new advisory on a used feature. |
| `tinymce` (npm) | 7.9.3 | GHSA-mf2p-h6hf-fcwm | high | R | No 7.x fix exists. It is not reachable today: no Angular screen loads `html.edit` (the EmailMarketing body is `squire`; OutboundEmailAccounts is legacy). | The D5 delta `extensions/tkc/config/tkc_tinymce.php` removes the `media` plugin (defence in depth). | Upstream moves npm `tinymce` to a fixed release. The first html or wysiwyg field without `displayType` `squire` on a `record:true` module (then run the browser check). |
| `dompurify` (inside `monaco-editor`) | 3.2.7 | moderate and low | moderate | R | The version is exact-pinned inside the prebuilt monaco minified bundle. A resolution would not change the shipped code. | None. Accepted by the architect. | Upstream monaco upgrade. |
| Build-time group B (50 packages, including `piscina`, `shell-quote`, `proxy-addr`, `websocket-driver`, `@angular/compiler`) | various | various | critical, high, moderate, low | B | These packages run only on the build host or in CI, with trusted input. They are never deployed. The server has no Node runtime. | `ng serve` and Karma bind to localhost only. Never run them on Azure or on a public CI agent. | The CI audit gate (SUITE-163). |
| `behat/transliterator`, `doctrine/cache` | - | abandoned | - | server | No advisory. | None. Accepted by the architect. | Upstream merge. |
| `league/flysystem-azure-blob-storage` | 3.31.0 | abandoned | - | server | Active only with `AZURE_BLOB_INSTANCES`. | The Azure deploy does not set `AZURE_BLOB_INSTANCES`. | SUITE-193. |

Re-check this table at each upstream merge (see the procedure above). Delete a row when upstream no longer has the package at that version.

## Spike results S7–S11 (SUITE-50)
The spike code lives only on the `spike/SUITE-5x-*` branches of the `tkc-spike2` worktree. It is never merged. The stories below build the real code. Each story adds its own row to the Overrides register. The planned rows are in those stories.

**S10 — empty enum value and preset list filter (SUITE-54). Result: PARTIAL**
- Q1 "not set" value on `radioenum`: PARTIAL. The mouse works. The keyboard fails: core hides the radio inputs (`core/app/shell/src/themes/suite8/css/components/_radio.scss:27`, `display: none`), so they cannot get focus.
- Decision (S): keep the core `radioenum` (A3). A TKC theme override keeps the inputs focusable but not visible. The label gets a token `:focus-visible` state. The empty option shows the label "Not set". The M fallback (TKC Yes/No component) is not needed.
- Changes: a new theme story before E5-S2 (SUITE-103). Built in SUITE-186. The build also needed a thin Angular field override (see Overrides register).
- Q2 default filter on open: PARTIAL. A metadata preset and a SavedSearch `quick_filter` do not work. The current-filter user preference works, per user. Enum filter negation does not work (only `=`). A positive list with the empty value works.
- Decision (M, in E5-S5, SUITE-106): one idempotent seed creates a Contacts saved filter for each active user. The filter lists every key of `tkc_contact_type_list` except `spam`, plus the empty value. The seed reads the keys at run time. It sets the filter as current only when the user has none.
- Accepted gaps: a new user gets the filter only after a re-seed. A new list key needs a re-seed. The contract test checks that the filter covers every key except `spam`. A user's own last filter replaces the default later.

**S9 — record action and panel visibility (SUITE-53). Result: GO (Q1), NO-GO (Q2)**
- Q1 action `displayLogic` on a field value: GO. It shows and hides by value in detail mode and updates after a save without a reload. `ActionAvailabilityChecker` (M) is not needed. It is proven only for a field on the same record. It is UI only: handlers still check ACL and state.
- Q2 panel hides when all cells hide: NO-GO. Core uses only the panel-level `displayLogic` (`core/app/core/src/lib/views/record/components/record-container/record-container.component.ts:283-306`).
- Decision (0): accept the empty header. Layout rule for E6, E8 and E11: no panel has only conditional cells. Mix them with always-shown cells, or accept the empty header on purpose. Panel-level `displayLogic` stays forbidden (S3).
- Changes: SUITE-126 (the Cases spam actions depend on a related record; check this first), SUITE-108, SUITE-110.

**S8 — record thread in a TKC layout (SUITE-52). Result: GO**
- The thread item shows `tkc_entry_type_c` and `tkc_channel_c`. The create form edits both and the values persist. `dynamicClass` sets the class `tkc_entry_type_c-<key>`. `bottomWidgets` shows the thread at full width under the panels, and create works there.
- Decision (L2): E9 builds the item and create layouts in metadata. The thread uses `bottomWidgets`.
- Deviations: (1) The thread is a full-width block, not a "Messages" tab. A real tab needs custom Angular (M). UX confirms the block against the Cases mockup. (2) The create form cannot preset `staff`. A `before_save` rule on AOP_Case_Updates fills an empty entry type (`staff` for an agent, `customer` for an inbound email). It does nothing in import mode (E9-S1, SUITE-128). (3) Items are about 90% wide. E9-S3 (SUITE-130) fixes this in CSS from tokens.
- Changes: SUITE-128, SUITE-129 (wording), SUITE-130.

**S11 — probability follows the stage (SUITE-55). Result: GO (metadata logic)**
- A vardef extension adds one `logic` entry for each stage on the core field `probability`: `updateValue`, `activeOnFields: {sales_stage: [<stage>]}`, `targetValue`. It works in edit and create. A manual overwrite persists. The `$dictionary` key must be the object name (`$dictionary['Opportunity']`).
- Decision (L2, S): E3-S8 (SUITE-69) builds it. The hook fallback is dropped. The extension file adds only the `logic` key.
- Facts: the logic runs only when the stage changes (`update-value.action.ts:121-123`). Opening a record never overwrites a stored value. Core `Opportunity::save()` fills an empty probability from `sales_probability_dom` (`Opportunity.php:363`). An import or API call without a probability is covered.
- Accepted gap: a stage change outside the Angular edit or create view does not recompute an existing probability.
- Check: the percentages exist twice. The contract test must check that each `sales_stage_dom` key has one entry with `targetValue` = `sales_probability_dom[stage]`.
- Changes: SUITE-69, SUITE-137.

**S7 — AOS_Contracts in Angular (SUITE-51). Result: GO**
- A D5 delta sets `legacy.module_routing['contracts']['record'] = true`. Core has `record: false` (`config/services/module/module_routing.yaml:155-158`). The existing services glob loads the file. The Angular record works in detail, edit and create. Cell `displayLogic` on `contract_type` works. Save works. The Accounts Contracts subpanel opens the Angular record.
- Decision (L2 + D5 delta): E11-S2 (SUITE-144) builds it. The production file is `extensions/tkc/config/tkc_contracts_routing.php`. The legacy fallback is not needed.
- Not tested, assessed from core code: (1) Line items: Angular save sends no `group_*` keys (`AOS_Line_Item_Groups.php:54-59`). Existing items stay but are not shown. TKC contracts have none. (2) Renewal reminder: `createReminder()` and `createLink()` run only with `$_POST['renewal_reminder_date']` (`AOS_Contracts.php:65-91`). Angular does not send it. (3) The PDF and email-PDF actions are legacy and not shown. E11-S2 checks this in the browser. (4) List view: core already has `list: true`.
- Accepted gaps: see the renewal reminder in Known issues. PDF actions are not shown.

## Known issues
| Issue | Description | Effect |
|---|---|---|
| S7 (SUITE-51) | The renewal reminder does not run for an Angular save. Core creates the reminder only when the request has `renewal_reminder_date`. | Keep `renewal_reminder_date` off the TKC contract layout. The import leaves it empty. A reminder needs a new story. |
| S10 (SUITE-54) | Core `radioenum` hides its radio inputs (`_radio.scss:27`). A keyboard user cannot reach them. | Resolved by SUITE-186: a theme override and an Angular field override (see Overrides register). The legacy UI is not in scope. |
| SUITE-176 | The Symfony `test` env does not boot. The core file `config/packages/test/framework.yaml` uses the removed option `storage_id`. | The contract test boots the `dev` env. Kernel-based PHPUnit tests cannot use the `test` env. |
| SUITE-175 | Resolved with option B: `lcobucci/clock` is 3.5.0 (upstream has 3.0.0). `composer.json` keeps the PHP 8.3 platform. | Upstream-merge risk: the register row for `lcobucci/clock` lists the check (see Overrides register). Keep 3.5.0 while the platform is PHP 8.3. |
| SUITE-183 | Dependency advisories are fixed in the lock files. Some advisories stay accepted (see § Accepted dependency advisories). Follow-ups: SUITE-192 (`zbateson/mail-mime-parser` 3.x, blocks email-to-case), SUITE-193 (Azure upload storage). | Azure conditions are in `docs/environments.md`. |
| SUITE-190 | The font URLs in the legacy `@font-face` rules do not change with the font content. The `.htaccess` caches woff2 for 1 month. | A font file with new content must get a new file name until SUITE-190 is done. |
| Bundle budget | The Angular build warns "initial bundle exceeded budget" (10.79 MB against 9 MiB). | Cause is core (`main` 4.88 MB, `polyfills` 4.86 MB), not TKC. No action. |

## Environments
Local: see `LOCAL_SETUP.md`. Azure: phase later — `docs/environments.md`.
