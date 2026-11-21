# Release notes

> Status truth: the `SUITE` planning project. Narrative and decisions: `docs/backlog.md`.
> One section per sprint. Owner: document-agent.

## Since Sprint 0 (branches `feature/SUITE-180-brand-colour`, `feature/SUITE-189-roboto`, `feature/SUITE-183-dependency-advisories`)

### Delivered

| Area | Result | Issue |
|---|---|---|
| Brand colour | The owner chose option A on 2026-11-21. The UI keeps the teal primary (hue 195). The new token `--tkc-color-brand-pink` (#e95295) and the semantic token `--tkc-logo` colour the logo on the login page and in the navbar. Options B and C are rejected. The design system is v0.2 and `tokens.json` has status APPROVED. | SUITE-180 |
| Design-system generator | `designs/system/generator/assemble.py` writes the committed `tkc-ds.css` header. Generated files come only from the committed generator. | SUITE-188 |
| Font | The app uses Roboto (variable, weights 400 to 700), the brand guide font. IBM Plex Sans is removed. IBM Plex Mono 400 stays. Both fonts are self-hosted. The app loads no external font. The Roboto files are 72528 bytes. The old Plex Sans files were 87844 bytes. The design system and the 7 mockups use Roboto. | SUITE-189 |
| Legacy asset version | The legacy CSS URL now changes with the theme and font content. The new command `tkc:legacy:asset-version` sets `js_custom_version`. The build runs it in step 11b. A browser hard reload is not needed after a theme change. The value is global, so it also versions the legacy JS, sprites and `yui.css`. | SUITE-181 |
| Dependency fix | The lock files now hold fixed versions for the HIGH and critical advisories. Composer has 43 updates (19 packages with advisories, 24 transitive), all inside the upstream constraints. These include Symfony 6.4.50, Guzzle, Smarty, phpseclib, Flysystem, `jmespath.php` (critical) and TinyMCE 8.10.2 for the legacy UI. In `yarn.lock`, `dompurify` is 3.4.18 and the `tinymce` 7.2.1 entry is merged into 7.9.3. The Angular edit field no longer loads the TinyMCE `media` plugin. After a vendor update or an upstream release, the legacy asset URL changes, so browsers load new legacy JS. Some advisories stay accepted with conditions (see below). | SUITE-183 |

### What changes for admins and developers
- Dependency updates (SUITE-183):
  - Update locked packages as `docs/architecture.md` § Dependency re-resolve commands and `LOCAL_SETUP.md` § Dependency updates describe. Run Composer as `www-data` with `--no-security-blocking`.
  - Each security-driven version has a row in `docs/architecture.md` § Overrides register. Never hand-merge a lock file at an upstream merge.
  - `docs/architecture.md` § Accepted dependency advisories lists what stays open and why.
  - The new D5 delta `extensions/tkc/config/tkc_tinymce.php` removes the TinyMCE `media` plugin from the Angular edit field.
  - `tkc:legacy:asset-version` now also hashes `composer.lock` and `public/legacy/suitecrm_version.php`. It stops with exit code 1 when one is missing.
  - Azure conditions are in `docs/environments.md`.
- The build has two new steps. Step 7b clears the cache so the console knows new `tkc:*` commands. Step 11b runs `tkc:legacy:asset-version`. Step 14 also checks that the legacy login page links `Dawn/style.css?v=`.
- Never set `js_custom_version` by hand or in the config template. The build sets it.
- On Azure, run `tkc:legacy:asset-version` after `tkc:config:apply`. Reload php-fpm when `opcache.validate_timestamps=0`. A CDN must use the query string in its cache key.
- A font file with new content must get a new file name until SUITE-190 is done.
- The logo colour comes from `--tkc-logo`, not from the primary colour. Use the token only for `color` or `fill` of `.logo`. Never use it for text, borders, focus rings, links or buttons.
- The legacy UI gets the token but does not use it.
- The brand guide `designs/TKC 2024 Brandguide 20240926.pptx` is a local reference. Git ignores it. It names the font Roboto. The font change is story SUITE-189. It is done (see "Delivered").
- Follow-up SUITE-190: the font URLs do not change with the font content. It is not done.

## Sprint 0 — closed 2026-11-21

Branch: `sprint/0`. Sprint 0 builds the base: build chain, dev tools, tests, theme and spikes.
It adds no data model and no import. No user-facing data changes.

### Delivered

| Area | Result | Issue |
|---|---|---|
| Build chain | `scripts/build.sh` is the single build entry point. It runs Angular on the host and PHP in the app container. | SUITE-44 |
| Mail sink | The dev Docker environment has a `mailpit` service. It catches all outgoing email. | SUITE-45 |
| Tests | `scripts/test.sh` runs PHP unit tests, Angular tests and theme tests. | SUITE-46 |
| Metadata contract test | A PHP test checks the layouts of each listed module against its vardefs and dropdown lists. Accounts is the first module. | SUITE-47 |
| Naming rule | The TKC naming rule for modules, fields, relationships, labels and lists is in the project rules. | SUITE-48 |
| Overrides register | `docs/architecture.md` has a register of overridden viewdefs, lists and parameters. Each upstream merge checks it. | SUITE-49 |
| Theme generator | `scripts/theme/build_theme.py` reads `designs/system/tokens.json`. It writes the Angular and legacy theme files. It also checks them (`--check`). | SUITE-56 |
| Angular theme | The Angular UI uses the TKC tokens and IBM Plex fonts (self-hosted). Partials restyle the navbar, buttons, fields, tabs, panels, lists, subpanels, alerts and modals. | SUITE-57, SUITE-58 |
| Login page | The login page shows the TKC Digital logo. A user can sign in with the keyboard only. | SUITE-59 |
| Legacy theme | The legacy (Dawn) UI uses the same tokens. | SUITE-60 |
| Spikes | Five spikes (S7 to S11) tested risky layout and logic. Results and decisions are in `docs/architecture.md`. | SUITE-50 to SUITE-55 |
| Dependency fix | `lcobucci/clock` is locked at 3.5.0. The owner chose option B: keep the PHP 8.3 platform. | SUITE-175 |
| Security fixes | `tkc:mail:use-sink` runs only where `TKC_DEV_MAIL_SINK=1` and does not show SMTP credentials. Git tracks no Python cache or coverage files. | SUITE-177 |
| Separator repair | The command `tkc:users:repair-number-separators` repairs user number separators. | SUITE-179 |

### Spike results and owner decisions

| Spike | Result | Effect |
|---|---|---|
| S7 (SUITE-51) | Go. AOS_Contracts renders in Angular with a D5 delta. The renewal reminder is not supported. | The layout leaves that field off. |
| S8 (SUITE-52) | Go. Thread fields, `dynamicClass` and `bottomWidgets` work. | Owner decision: SUITE-129 uses a custom "Messages" tab (size M). The architect prescribes the extension point before the build. |
| S9 (SUITE-53) | Action `displayLogic` on a field value works. A panel stays visible when all its cells hide. | Accept the empty panel header. |
| S10 (SUITE-54) | Partial. Keyboard access to radio fields needs a theme fix. | New story SUITE-186. The default list filter is a saved filter seeded per user (SUITE-106). |
| S11 (SUITE-55) | Go. Metadata logic on `probability` works. No hook is needed. | Used in SUITE-69. |

Owner decision: SUITE-144 uses option A. One panel "Contract details" shows the support or the licence cells by `contract_type`.

The spike code stays on `spike/*` branches. It is never merged. The obsolete dev instance `tkc-spike` (port 8082) is removed. The instance `tkc-spike2` (port 8083) stays for spikes.

### What changes for admins and developers

- Build with `scripts/build.sh`. It asks for confirmation before it runs the Repair SQL.
  - `--yes` runs the Repair SQL without review. Use it only for reviewed deploys.
  - `--skip-core`, `--skip-frontend` and `--dry-run-only` skip steps.
  - Exit code 1 means a step failed. The output names the step. Exit code 2 means no confirmation was possible.
- Run `docker/dc.sh console <command>` for Symfony commands. It adds `-T` when stdin is not a terminal.
- Test with `scripts/test.sh [--php] [--ng] [--theme]`. The Angular tests need Google Chrome on the host.
- Never edit generated theme files. Edit `designs/system/tokens.json` or the hand files in `extensions/tkc/`, then run `python3 scripts/theme/build_theme.py`.
- Change the logo by replacing `public/legacy/custom/themes/suite8/images/company_logo.svg`. Then run `docker/dc.sh console cache:clear`. Do not use the Admin logo upload.
- Each new dev database needs `docker/dc.sh console tkc:mail:use-sink` once. It sets the outbound account to Mailpit. Use it in DEV only.
- The Mailpit web page listens on 127.0.0.1 only (`MAILPIT_UI_PORT`, default 8025). Held mail is lost at restart.
- A second dev instance needs its own worktree, its own `docker/.env` and its own ports. See `LOCAL_SETUP.md`.
- A new module story adds its module to `extensions/tkc/backend/Tests/Contract/modules.php`.
- Details: `docs/architecture.md`, `docs/admin-guide.md`, `docs/environments.md`, `LOCAL_SETUP.md`.

### Known issues and limits

| Issue | Limit |
|---|---|
| SUITE-176 | The Symfony `test` environment does not boot. A core config file uses a removed option. The contract test boots the `dev` environment instead. |
| SUITE-181 | Resolved after Sprint 0. See "Since Sprint 0". |
| SUITE-182 | Login fields have no visible labels. The "Forgot password" link has no `href`, so the keyboard cannot reach it. These are core limits. |
| SUITE-186 | Radio fields (`radioenum`) do not take keyboard focus. |
| SUITE-180 | Resolved after Sprint 0. See "Since Sprint 0". |
| Build size | The Angular initial bundle is over budget (10.78 MB against 9.44 MB). The cause is not checked. |
| SUITE-183 | Resolved after Sprint 0 (after the security sign-off). See "Since Sprint 0". Accepted advisories stay open on conditions: `api-platform/core` (medium), `zbateson/mail-mime-parser` (high, no inbound mailbox with real data until SUITE-192), Angular 18 (`@angular/common`, `core`, `router`), npm `tinymce` 7.9.3, `dompurify` in `monaco-editor`, the 50 build-time packages and 3 abandoned packages. |
| SUITE-192 | `zbateson/mail-mime-parser` 3.x. It blocks email-to-case. |
| SUITE-193 | Azure upload storage (`AZURE_BLOB_INSTANCES`). |

### Follow-ups (To Do)

| Issue | Subject |
|---|---|
| SUITE-176 | Symfony `test` env does not boot (bug). |
| SUITE-178 | Mailpit version. |
| SUITE-180 | Done after Sprint 0. The owner approved the brand colour (option A). See "Since Sprint 0". |
| SUITE-181 | Done after Sprint 0. The legacy CSS URL version follows the content. See "Since Sprint 0". |
| SUITE-182 | Label association and keyboard access to "Forgot password". |
| SUITE-183 | Done after Sprint 0. Accepted advisories are in `docs/architecture.md`. The Azure conditions in `docs/environments.md` apply to the first deploy. |
| SUITE-192 | `zbateson/mail-mime-parser` 3.x. It blocks email-to-case. |
| SUITE-193 | Azure upload storage. |
| SUITE-184 | Build hardening. |
| SUITE-185 | Layout differences from the design system. |
| SUITE-186 | `radioenum` keyboard focus. It runs in Sprint 1, before SUITE-103. |
| SUITE-187 | Open several records in tabs. Not scheduled. It needs an architect check and a mockup. |
