# Sprint 0 — Architectural prescriptions

> Architect, 2026-11-21 · Sprint 0 (2026-11-21 .. 2026-12-04), branch `sprint/0` · Workflow step 1 for SUITE-44..60.
> AC corrections from this review were applied in the planning tool (SUITE-44 AC1, 46 AC4, 48 AC1, 49 AC3, 56 AC3, 59 AC1).

## Cross-cutting decisions (apply to every Sprint 0 story)

| # | Decision | Reason / evidence |
|---|---|---|
| X1 | Only the **document-agent** writes `docs/architecture.md` (workflow step 5). Implementers put the text for it in their issue comment. | 9 of 12 stories touch this file; one writer prevents conflicts. |
| X2 | PHP tests go in directories named exactly **`Tests`** (`extensions/tkc/backend/Tests/...`). | `config/core_services.yaml:204-206` registers every class under `extensions/*` as a service and skips only `{config,language,Language,DependencyInjection,Entity,Migrations,Tests,Kernel.php}`. |
| X3 | The generator writes dark tokens **only** under `:root[data-theme="dark"]`; **no** `prefers-color-scheme` block until a dark-mode story exists. | Core components stay light; a dark OS would give a mixed UI and fail contrast. |
| X4 | Legacy output converts **rem → px (base 16)**. | Compiled Dawn CSS sets `html{font-size:10px}`; `0.875rem` would render as 8.75 px. |
| X5 | Fonts are **self-hosted** (no font CDN). | A font CDN sends each user's IP address to a third party (GDPR). |
| X6 | Legacy CSS must not use `calc()` with `+`. | Legacy minifier `include/SugarTheme/cssmin.php:65` removes the spaces around `+`. |
| X9 | Legacy hand-file selectors must not have whitespace before a `:` pseudo (`body :is(`); `build_theme.py --check` fails on it. | Legacy cssmin removes the space, so `body :is(` becomes `body:is(` and matches nothing. |
| X10 | A hand file must not set `z-index` with an overlay token (`--tkc-z-dropdown`, `-drawer`, `-modal`, `-toast`, `-tooltip`); `build_theme.py --check` fails on it. Local layers (`base`, `sticky`, `navbar`) stay allowed. | The token z-scale (0-700) is below the core overlay scale (modal backdrop 1050 `!important`, modal 1055, toast 1510). SUITE-58 put the modal window under its backdrop. |
| X7 | Spikes run on throwaway branches in a separate worktree with their own compose project. | They change metadata and the DB. |
| X8 | Do not add the `.tkc-*` classes from `tkc-ds.css` to the app; map tokens onto **core selectors**. | Core markup cannot change; `tkc-ds.css` is for mockups only. |

## Execution order and streams

| Stream | Agent | Sequence | Start |
|---|---|---|---|
| A build & tests | backend-engineer | SUITE-44 → 46 → 47 | Day 1 |
| B mail | azure-engineer | SUITE-45 | Day 1 |
| C Angular theme | frontend-engineer | SUITE-56 → 57 → 58 ∥ 59 | Day 1 |
| D legacy theme | frontend-engineer | SUITE-60 | after 56 |
| E docs & rules | document-agent | SUITE-48, 49 | Day 1 |
| F spikes | frontend (S10, S9, S8), backend (S11, S7) | 54 → 53 → 52 → 55 → 51 | after 45 (own instance), else one at a time on :8081 |

Merge order: 48, 49 → 44 → 45 → 56 → 46 → 57 → 60 → 47 → 58, 59 → 50.
Waits: 46 after 44; 47 after 46; 57 and 60 after 56; 58 and 59 after 57; 57 AC4 only after 60.

### Shared files and owners

| File | Owner / rule |
|---|---|
| `docs/architecture.md` | document-agent only (X1) |
| `LOCAL_SETUP.md` | SUITE-44 creates section headings "Build chain", "Mail sink", "Tests", "Theme"; each story edits only its own section; document-agent final pass |
| `scripts/build.sh` | SUITE-44; SUITE-56 and SUITE-57 each add one step after 44 merges |
| `scripts/test.sh`, `extensions/tkc/phpunit.xml.dist` | SUITE-46; SUITE-47 adds test files only; SUITE-56 adds the THEME line |
| `compose.yaml`, `docker/.env.example` | SUITE-45 only |
| `docker/dc.sh` | SUITE-44 only |
| `extensions/tkc/app/angular.json`, `karma.conf.js` | SUITE-46 only |
| `_tkc-overrides.scss` (index) | SUITE-57 only; 58/59 edit only their partials |
| `_tkc-tokens.scss`, `public/legacy/custom/themes/suite8/css/Dawn/style.css` | generated — never edited by hand |
| `.claude/rules/suitecrm-customization.md`, `docs/osc-mapping/incident.md`, `.claude/skills/suitecrm-field/SKILL.md` | SUITE-48 |
| `.claude/rules/documentation-maintenance.md`, `.claude/rules/git-workflow.md` | SUITE-49 |
| `docs/admin-guide.md` | document-agent (input from 45, 59) |

---

## SUITE-44 — Build: one build chain from a clean checkout

LEVEL 3 (tooling) · DATA MODEL none · VERDICT **APPROVED TO BUILD**

- **New `scripts/build.sh`:** `set -euo pipefail`; trap on ERR prints `FAILED at step n: <name>` and exits non-zero; each step prints `==> [n/N] <name>`. Steps:
  0. Preflight: Docker, `app` container running, `test -f public/legacy/config.php` (never read it), node/yarn, python3.
  1. `yarn install --immutable` · 2. `yarn merge-angular-json` · 3. `yarn build:core` (skip with `--skip-core`) · 4. `yarn build:shell` · 5. `yarn build:extension tkc`
  6. Compile the core Dawn CSS with the `pscss` command from `LOCAL_SETUP.md` (output gitignored)
  7. `scrm:copy-legacy-assets` · 8. `tkc:repair --dry-run` (output shown)
  9. Always ask "Run tkc:repair --execute? [y/N]" (also when no SQL — `--execute` creates audit tables the dry run does not list); `--yes` skips; no TTY and no `--yes` → exit 2.
  10. `tkc:repair --execute` · 11. `tkc:config:apply` · 12. `cache:clear` · 13. smoke: `curl` `http://localhost:8081/` → 200.
  - Flags: `--yes`, `--skip-core`, `--skip-frontend`, `--dry-run-only` (stop after 8).
  - Placeholders for later: step 0b `python3 -I scripts/theme/build_theme.py --check` (SUITE-56), step 4b `python3 -I scripts/theme/check_bundle.py` (SUITE-57).
- **Edit `docker/dc.sh`:** `console` adds `-T` when stdin is not a TTY (`[ -t 0 ] || tflag=-T`).
- **Edit `LOCAL_SETUP.md`:** section "Build chain" + empty headings "Mail sink", "Tests", "Theme".
- Patterns: `docker/init.sh` (cd to repo root via `dirname`), `docker/dc.sh`, exit codes of `RepairCommand`.
- Risks: `--yes` runs SQL unread — only for reviewed deploys (E14-S4). Never print `docker/.env`, never `set -x`.
- Upgrade risk low: root `package.json` script names, `scrm:copy-legacy-assets`, Dawn SCSS path.
- **Verify:** (1) fresh clone: `docker/init.sh`, `up -d --build`, `composer install`, installer per `LOCAL_SETUP.md`, `scripts/build.sh` → exit 0, login page at :8081; (2) a failing step → non-zero + step name; (3) `scripts/build.sh </dev/null` → exit 2 at the confirmation; (4) `shellcheck` clean.

## SUITE-45 — Mail: Mailpit sink in Docker, outbound mail pointed at it

LEVEL infra + 3 · DATA no schema; dev-only values of the system `outbound_email` row · VERDICT **APPROVED TO BUILD**

- `compose.yaml`: service `mailpit` (`axllent/mailpit:<pinned v1 tag>`), ports `127.0.0.1:${MAILPIT_UI_PORT:-8025}:8025` only, SMTP 1025 **not published**, env `MP_MAX_MESSAGES=500`, **no volume**. `app`: `depends_on` mailpit, env `TKC_MAIL_SINK_HOSTS=mailpit`. App/db ports become `${APP_PORT:-8081}` / `${DB_PORT:-3307}` (needed for X7).
- `docker/.env.example`: commented `APP_PORT`, `DB_PORT`, `MAILPIT_UI_PORT` (not secrets).
- `extensions/tkc/config/tkc_mail.yaml`: `env(TKC_MAIL_SINK_HOSTS): 'mailpit'`, `tkc.mail.sink_hosts: '%env(csv:TKC_MAIL_SINK_HOSTS)%'` (read later by E4-S2).
- `extensions/tkc/backend/Mail/MailSinkPolicy.php`: `isSinkHost(string): bool`; `findNonSinkOutboundAccounts(): list<{id,type,host}>` over all non-deleted rows (system, system-override, user).
- `extensions/tkc/backend/Mail/Console/MailUseSinkCommand.php`: `tkc:mail:use-sink [--dry-run]` — sets the system account to SMTP, host = first sink host, port 1025, no auth/SSL, empty user/password, from `crm@example.com` / "TKC CRM (dev)"; boots legacy like `RepairCommand::runRepair`, uses the `OutboundEmail` bean; idempotent; prints a diff like `ConfigApplyCommand` ("No change" on rerun).
- Docs: `docker/README.md`, `LOCAL_SETUP.md` § Mail sink; `docs/admin-guide.md` via document-agent.
- Risks: Mailpit UI has no auth → 127.0.0.1 only; held mail can contain PII → no volume; users can add personal SMTP accounts → policy checks all rows; this compose file never goes to Azure. Admin > Email Settings stays the manual fallback.
- **Verify:** (1) `dc.sh up -d` starts mailpit; (2) `tkc:mail:use-sink --dry-run`, then real; rerun → "No change"; (3) Admin > Email Settings > send test to `test@example.com` → visible in Mailpit; (4) `docker compose port mailpit 1025` is empty; (5) unit test for `MailSinkPolicy` once SUITE-46 merges.

## SUITE-46 — Tests: PHPUnit and Karma for the extension

LEVEL 3 · VERDICT **APPROVED TO BUILD** (AC4 corrected: PHPUnit in the app container, Karma on the host with ChromeHeadless)

- `extensions/tkc/phpunit.xml.dist`: bootstrap `backend/Tests/bootstrap.php`; suites `unit` (`backend/Tests/Unit`) and `contract` (`backend/Tests/Contract`); env `KERNEL_CLASS=App\Kernel`, `APP_ENV=test`; cacheDirectory `var/cache/phpunit-tkc`.
- `extensions/tkc/backend/Tests/bootstrap.php`: requires `vendor/autoload.php` only (do NOT reuse `tests/bootstrap.php` — it loads AspectMock).
- `backend/Tests/Unit/Config/ConfigOverrideMergerTest.php` (move/convert the existing `tests/unit/tkc` test): merge idempotent, diff empty after merge, template wins its keys, other keys stay; synthetic fixtures in `backend/Tests/Fixtures/`.
- `extensions/tkc/app/karma.conf.js`: plugin list from `core/app/core/karma.conf.js` (use `karma-coverage-istanbul-reporter`, not the missing `karma-coverage`); coverage dir `coverage/tkc`.
- `extensions/tkc/app/webpack.config.spec.js` (new): copy of `core/app/core/webpack.config.spec.js` with `uniqueName 'tkc'`; `angular.json` `test.options.extraWebpackConfig` → it (module federation breaks Karma).
- `extensions/tkc/app/src/extension.module.spec.ts`: ExtensionModule compiles; `init()` exists.
- `scripts/test.sh [--php] [--ng] [--theme]` (default all): PHP `docker/dc.sh exec -T -u www-data app vendor/bin/phpunit -c extensions/tkc/phpunit.xml.dist`; NG `yarn ng test tkc --watch=false --browsers=ChromeHeadless` (host); THEME `python3 -I -m unittest discover scripts/theme/tests` (once SUITE-56 exists); non-zero if any part fails.
- `LOCAL_SETUP.md` § Tests.
- Risks: the test-env kernel must boot (`dc.sh console --env=test about` first); the container needs composer dev deps.
- **Verify:** `scripts/test.sh` green; broken example → non-zero; `dc.sh console debug:container` lists no `…\Tests\…` class; run `yarn merge-angular-json` after the `angular.json` change.

## SUITE-47 — Tests: metadata contract test per module

LEVEL 3 · reads metadata only (no records, no PII) · VERDICT **APPROVED TO BUILD** (after SUITE-46)

- `extensions/tkc/backend/Metadata/Contract/MetadataContractChecker.php` (pure PHP): input (module, layouts[], fieldDefs, appListStrings) → `list<Violation>`.
  - R1 (required): every field named in detailview panel cells (string or `['name'=>…]`; skip `''`/null), listviewdefs (keys lowercased), searchdefs basic+advanced, subpanel list fields exists in fieldDefs.
  - R2 (required): for `displayLogic` / `logic` `params.activeOnFields` (cells and `recordActions`): the field exists and each value is a key of that field's option list (enum/multienum/radioenum/dynamicenum).
  - R3 (optional now): each `tkc_*_c` / `osc_id_c` vardef with `options` points to an existing list. R4 (optional): enum default is a key of its list.
- `.../Contract/Violation.php` (module, file, path, message); `.../Contract/LegacyMetadataLoader.php` (boot legacy like `RepairCommand::runRepair`: init → runLegacyEntryPoint → system user → close in finally; field defs from `BeanFactory::newBean($m)->field_defs`; lists from `return_app_list_strings_language('en_us')`; layouts resolved custom-first like SuiteCRM, included in closure scope).
- Tests: `backend/Tests/Unit/Metadata/MetadataContractCheckerTest.php` (synthetic: unknown field → violation; unknown dropdown key → violation; clean → none); `backend/Tests/Contract/ModuleMetadataContractTest.php` (KernelTestCase, `#[RunTestsInSeparateProcesses]`, provider `backend/Tests/Contract/modules.php = ['Accounts']`); `known-core-violations.php` allowlist (reasons; keep empty if possible).
- DoD rule for later module stories: add the module to `modules.php` (document-agent records it with SUITE-49).
- Precondition: `tkc:repair` has run (Ext compiled). Upgrade risk medium by design (fails loudly at merges).
- **Verify:** `scripts/test.sh` green; a fake field in a fixture turns it red.

## SUITE-48 — Rules: TKC field naming rule (A8) — docs only

Owner document-agent (architect reviews the text) · VERDICT **APPROVED TO BUILD**

- `.claude/rules/suitecrm-customization.md` § Database, replace the naming line with the A8 block:
  - TKC field on a core module: `tkc_<english_name>_c`, snake case, English, ≤ 40 characters (so `idx_<table>_<field>` ≤ 64).
  - Relate pair: `tkc_<name>_c` (relate, non-db) + `tkc_<name>_id_c` (id). Link field = relationship name `tkc_<lhs>_<rhs>`, no `_c`.
  - Exception: `osc_id_c` (OSC import key on every imported module). `tkc_case_ref_c` follows the rule (A12); `osc_ref_no_c` is retired.
  - A core field override keeps the core name. A field in a `tkc_` module: plain snake case, no prefix/suffix.
  - Labels `LBL_TKC_<NAME>` (name without `tkc_` and `_c`); lists `tkc_<name>_list`.
  - A new key in a core list is `tkc_<key>` (A13), except where core reads the key format (`case_status_dom` `Open_*`/`Closed_*`, A2). Never rename a core key (D7).
- `docs/osc-mapping/incident.md`: `queue_c`→`tkc_queue_c`; `primary_contact_c`/`primary_contact_id_c`→`tkc_primary_contact_c`/`tkc_primary_contact_id_c`; `entry_type_c`→`tkc_entry_type_c`; `channel_c`→`tkc_channel_c`; `case_origin_c`→`tkc_case_origin_c`; `osc_ref_no_c`→`tkc_case_ref_c`.
- Same story: `.claude/skills/suitecrm-field/SKILL.md` template `<field>_c`→`tkc_<field>_c`, `LBL_<FIELD>`→`LBL_TKC_<FIELD>`; `designs/system/components.md` §11 (`entry_type_c`, `channel_c`); `docs/architecture.md` D3 line "refined by A8" (document-agent).
- **Verify:** grep `[a-z0-9_]+_c\b` over `docs/osc-mapping`, `designs/system/components.md`, `.claude/` — every hit starts with `tkc_`, is `osc_id_c`, or is a core/`jjwg_` name.

## SUITE-49 — Docs: register of overridden viewdefs and lists — docs only

Owner document-agent · VERDICT **APPROVED TO BUILD**

- `docs/architecture.md`: new § "Overrides register" after § Core patches; columns File | Module | Type | Reason | Upstream source to diff | Check at merge; Type values: viewdef full copy · core list override · core vardef override · D5 parameter delta; empty with header row.
- `.claude/rules/documentation-maintenance.md`: new map row "Full-copy viewdef, core list or vardef override, D5 parameter delta → `docs/architecture.md` § Overrides register" + the SUITE-47 rule ("a module story adds its module to the contract test").
- `.claude/rules/git-workflow.md` Upstream updates: "re-check § Core patches and § Overrides register".

## SUITE-50 — Spikes S7–S11 (tasks SUITE-51..55)

Rules for every spike:
- **Where:** separate worktree `git worktree add ../tkc-spike -b spike/SUITE-5x-<slug> sprint/0`; separate instance `APP_PORT=8082 DB_PORT=3308 MAILPIT_UI_PORT=8026 docker compose -p tkc-spike --env-file docker/.env up -d` (needs SUITE-45 port parameters). Before SUITE-45 merges: one spike at a time on the main instance, only when no other stream uses it.
- **Changes:** only in `public/legacy/custom/` or `extensions/tkc/`; spike code is never merged; synthetic records only.
- **Database:** only S8 creates columns (exact §11.4 definitions). For every other spike `tkc:repair --dry-run` must show no SQL — otherwise stop, do not execute.
- **Post on the task:** files changed, steps, a screenshot (synthetic data), go/no-go per question, fallback + size. The architect reads the result.
- **Time-box:** past the time-box = no-go with reason. **Close:** document-agent writes § "Spike results S7–S11" in `docs/architecture.md`.
- **Order:** S10, S9 → S8 → S11 → S7.

**SUITE-51 S7 — AOS_Contracts in Angular (1.5 d).** (1) `extensions/tkc/config/tkc_spike_routing.php` D5 delta: `$r = $container->getParameter('legacy.module_routing'); $r['contracts']['record'] = true; setParameter` (mirror `extensions/tkc/config/extension.php`; core `record: false` at `config/services/module/module_routing.yaml:155-158`). (2) Copy `modules/AOS_Contracts/metadata/detailviewdefs.php` to `custom/…`, remove the line-items panel. (3) Keys `tkc_support`, `tkc_license` in `contract_type_list` via `custom/Extension/application/Ext/Language/en_us.tkc_spike_s7.php` (list only). (4) Cell `displayLogic` on two fields, `activeOnFields: {contract_type: [tkc_support]}`. (5) `cache:clear`; create/open/edit/save a synthetic contract; open from the Accounts Contracts subpanel. **Go:** Angular record (no legacy iframe) in detail/edit/create, save keeps core fields with no line-item error, cells toggle on `contract_type`. **Partial:** Angular but no displayLogic → two always-shown panels. **No-go:** legacy record, two always-shown panels (S). If go, the delta becomes an Overrides-register row in E11.

**SUITE-52 S8 — record thread in a TKC layout (1.5 d).** (1) Spike vardefs on AOP_Case_Updates with the final §11.4 definitions (`tkc_entry_type_c`, `tkc_channel_c` + lists); dry run must show exactly 2 ADD COLUMN; review, then execute (E3-S7 then shows no SQL). (2) Copy `modules/Cases/metadata/detailviewdefs.php` to `custom/`; move `record-thread` from `sidebarWidgets` to `bottomWidgets` (`bottom-widget.adapter.ts`, `RecordThreadDefinitionMapper.php`). (3) Add both fields to the thread item and create layouts; `dynamicClass` on `tkc_entry_type_c` (mirror core use of `internal` in `record-thread-item.component`). Checks: (a) item shows both fields; (b) create form edits both and values persist; (c) item element has a class from the entry type; (d) thread renders full width under the panels and create works there. **Go:** a–d. Fallbacks: only (d) fails → thread stays in sidebar (0); (b) fails → composer extras as L3 component (M). The local DB keeps the 2 columns.

**SUITE-53 S9 — record-action and panel visibility (1 d).** (1) Copy Accounts `detailviewdefs.php` to `custom/`. (2) One test entry in `recordActions.actions` (reuse a core action key) with `displayLogic` `params.activeOnFields: {account_type: ['Customer']}` (format of `views/record/action-logic/display-type/display-type.logic.ts`). (3) A panel whose cells all hide on `account_type`. **Q1 go:** action hides/shows by field value in detail mode and updates after save without reload; no-go → backend `ActionAvailabilityChecker` (`core_services.yaml:123`, M). **Q2 go:** panel header hides when all its cells hide; no-go → accept an empty header (0). Do not use panel-level `displayLogic` (S3).

**SUITE-54 S10 — enum-radio empty state and preset list filter (1.5 d).** **Q1:** Accounts `industry` (list starts with `''`); first a cell-level `type => 'radioenum'` in a `custom/` detailviewdefs; else a spike vardef override (`type => 'radioenum'`, keep `dbType => 'varchar'`; dry run must show no SQL for `accounts.industry`). Edit template renders one radio per option (`radioenum.component.html:29-44`), so `''` gives "not set". Test: set value, save, set "not set" by mouse and keyboard (arrows), save. Go: empty value persists and detail shows empty. No-go: TKC Yes/No edit component (M). **Q2:** default filter on open — test (i) metadata preset (trace `list-view.store.ts:624`, `list-view-url-query.service.ts:81-87` choosing `activeFilters.default`), (ii) SavedSearch with `quick_filter = 1` (per user), (iii) user preference for current filter. Also test negation with Contacts `lead_source`: exclude one key and keep empty values. Go: applies on open for all users without per-user setup and keeps empty values. Partial (per user only): saved default filter (L1, seeded per user). No-go: small Angular list extension (M).

**SUITE-55 S11 — probability follows the stage (0.5 d).** (1) Synthetic Opportunity; in edit and create, change `sales_stage`, watch `probability` (core has no Angular probability logic). (2) If nothing happens, spike vardef extension overriding `probability` with one `logic` entry per stage: `key: updateValue`, `params: {activeOnFields: {sales_stage: [<stage>]}, targetValue: '<pct>'}` (`fields/field-logic/update-value/update-value.action.ts`); dry run no SQL; check manual overwrite still works. **Go-A:** works unchanged. **Go-B:** metadata logic works (L2, S; better than the hook; E3-S8 builds it; contract test R2 checks the keys). **No-go:** `tkc_opportunity_won` hook on save.

## SUITE-56 — Theme: generate both theme files from `tokens.json` (D6)

LEVEL 3 (tooling) · VERDICT **APPROVED TO BUILD** (AC3 corrected)

- `scripts/theme/build_theme.py` (stdlib only, `python3 -I`, no import from `designs/system/generator` — `tokens.json` is the contract):
  - Validate: each `{primitive…}` reference resolves, each leaf has `css`; else exit 1.
  - Blocks (components.md §16 order, changed by X3): primitives `:root`; semantic light `:root`; semantic dark `:root[data-theme="dark"]` only (flag `--dark-auto` reserved, default off); density `:root` / `[data-density="comfortable"]` / `@media (pointer: coarse)`; reduced motion; `@font-face`; sentinel `--tkc-theme-build: "<first 12 hex of sha256(tokens.json)>"`.
  - Output A: `extensions/tkc/app/src/theme/_tkc-tokens.scss` (replaces the hand file; header "GENERATED … do not edit").
  - Output L: `extensions/tkc/legacy-theme/00-tokens.generated.css` (rem→px ×16, X4), then join `extensions/tkc/legacy-theme/*.css` in name order into `public/legacy/custom/themes/suite8/css/Dawn/style.css`.
  - Deterministic: LF, final newline, no timestamps, JSON order.
  - `--check`: render in memory, compare the 3 outputs, exit 1 with the file list on a difference; lint hand-written files (`extensions/tkc/app/src/theme/**/*.scss`, `extensions/tkc/legacy-theme/*.css`, excluding generated): fail on hex colours, `rgb(`/`hsl(` literals; legacy also fails on `calc(` with ` + ` (X6).
- `scripts/theme/tests/test_build_theme.py` (unittest, synthetic mini-tokens: references, rem→px, dark selector, determinism, lint finds hex); `scripts/theme/README.md`.
- `public/legacy/custom/themes/suite8/fonts/`: IBM Plex Sans 400/500/600/700 + Plex Mono 400, Latin-subset woff2 + `OFL.txt` (official IBM Plex release). `@font-face` url `/legacy/custom/themes/suite8/fonts/<file>` in both outputs.
- After SUITE-44/46 merge: build.sh step 0b; THEME part of test.sh.
- Risks: dart-sass must pass `oklch(... var())` through inside custom properties (check with `yarn build:shell`); fonts ~150 KB.
- **Verify:** run twice → `git status` clean; change brand hue → `--check` fails; `#fff` in a hand partial → `--check` fails; `yarn build:shell` passes; the legacy file has no `rem`.

## SUITE-57 — Theme: apply the tokens in the Angular shell

LEVEL 3 (SCSS via the S1 entry) · VERDICT **APPROVED TO BUILD** (after 56)

- `extensions/tkc/app/src/theme/_tkc-overrides.scss` → index of `@import`s only. Create ALL partials now in `extensions/tkc/app/src/theme/overrides/`: `_base`, `_navbar`, `_buttons`, `_fields` (this story); stubs `_tabs`, `_panels`, `_list`, `_subpanels`, `_alerts`, `_modals` (SUITE-58), `_login` (SUITE-59) with header "owned by SUITE-58/59". 58/59 never edit the index.
- `_base.scss`: on `body` (not html) token font family, `--tkc-font-size-md`, text colour, canvas; links; global `:focus-visible` (`--tkc-focus-ring-width` solid `--tkc-focus-ring`, offset `--tkc-focus-ring-offset`); reduced motion.
- `_navbar`/`_buttons`/`_fields`: components.md §1, §2, §4 (A selectors); keep core selectors; `!important` only where the core rule has it (comment).
- `scripts/theme/check_bundle.py`: newest `public/dist/styles*.css` contains the sentinel and the TKC `.navbar` rule comes after the core `.navbar` rule; else exit 1. Add to build.sh (step 4b) and test.sh `--theme`.
- Change colour, type, spacing, borders only — not core layout. Upgrade risk medium: S1 `styles[1]` index (check_bundle catches it), core selectors, core 'Lato' body rule (`_typography.scss:30`).
- **Verify:** `yarn merge-angular-json && yarn build:shell && python3 -I scripts/theme/check_bundle.py`, `cache:clear`; compare navbar, primary/secondary buttons, text input with `design-system.html`; tab through a record edit view (ring + visible label); AC4 (same font/size as legacy Admin) after SUITE-60 via devtools computed `font-family`/`font-size` of `body` in Angular and iframe.

## SUITE-58 — Theme: tabs, panels, lists, subpanels, alerts, modals in Angular

LEVEL 3 · VERDICT **APPROVED TO BUILD** (after 57)

- Only `overrides/_tabs`, `_panels`, `_list`, `_subpanels`, `_alerts`, `_modals` (A selectors, components.md §5–§10). List row: hover `--tkc-table-row-hover`; focus `tr:focus-within`. Scope list and subpanel selectors by container.
- Modals: ng-bootstrap closes on Esc by default — check only; if a modal does not close, record a finding (no core patch, no Angular override here). X8: no `tkc-ds.css`, no `.tkc-*` classes.
- **Verify:** against `design-system.html`: Accounts list, record with tabs/panels, subpanels, toast after save, delete confirmation (Esc closes, focus returns); `check_bundle.py` and `--check` green.

## SUITE-59 — Login: TKC login page with the company logo

LEVEL 2 + 3 · VERDICT **APPROVED TO BUILD** (after 57; AC1 corrected)

- `public/legacy/custom/themes/suite8/images/company_logo.svg`: placeholder TKC tile; svg-icon inlines SVG (`core/app/core/src/lib/components/image/image.component.html:30`) → `fill="currentColor"`, colour from a token; SVG wins type priority (`config/services/themes/image_supported_types.yaml`); custom suite8 path overrides default (`config/services/themes/image_paths.yaml`). Owner supplies the real logo (post an `ask`).
- `overrides/_login.scss`: `.login-view` (canvas + two brand-tint gradients), `.login-form` card (surface-raised, radius-xl, shadow-3), inputs as fields, `.login-button` primary full width 44 px, focus rings.
- `docs/admin-guide.md` (document-agent): logo comes from git; do not use the Admin logo upload. Keep "Supercharged by SuiteCRM" visible (AGPL §7(b)).
- Known core limits (record on the issue, accept; E2-S6 lists them): inputs have aria-label/placeholder but no visible label; "Forgot password" is an `<a>` without `href` (`login.component.html:104`), not keyboard-reachable. AC3 still met (Tab user → password → Sign in; Enter submits).
- **Verify:** `cache:clear`; logged out: logo and colours, keyboard-only sign-in; 2FA and logged-out views still work; navbar logo also changes (same key).

## SUITE-60 — Theme: apply the tokens in the legacy Dawn theme

LEVEL 4 · VERDICT **APPROVED TO BUILD** (after 56; parallel with 57)

- `extensions/tkc/legacy-theme/10-base.css`: on `body` (NOT html, X4) font, base size (px), text colour, canvas, links, `:focus-visible` ring; `20-buttons.css`, `30-fields.css` (Admin forms); `40-tables.css` optional. Sprint 0 scope = Admin page base (Emails, Home, Quotes in E12).
- `public/legacy/custom/themes/suite8/css/Dawn/style.css` is written ONLY by `build_theme.py`.
- `LOCAL_SETUP.md` § Theme: `python3 -I scripts/theme/build_theme.py`, then `docker/dc.sh console tkc:repair --dry-run` (clears the theme cache, no DB change).
- Mechanism: `SugarTheme::getCSSURL` appends `custom/themes/suite8/css/Dawn/style.css` after core (`SugarTheme.php:1036-1041`), minifies (cssmin), caches in `public/legacy/cache/themes/` (named volume).
- Risks: no `calc(+)` (X6); no hex (lint); `!important` only against a core `!important`.
- **Verify:** Admin page in the iframe and at `/legacy/index.php?module=Administration&action=index`: computed `body` font IBM Plex Sans 14 px, link colour = token, Tab shows the ring; `git status public/legacy` shows changes only in `custom/`; the minified cached CSS keeps custom properties and `@media` blocks.
