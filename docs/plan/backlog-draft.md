# TKC SuiteCRM: draft backlog and sprint plan

> Status: **DRAFT. The owner must approve it. No issue exists in the planning tool for these items yet.**
> Author: product-owner. Date: 2026-11-20. Planning epic: SUITE-25.
> Source: `docs/plan/implementation-plan.md` (called "the plan" below). Narrative: `docs/backlog.md`.
> Open issues that this draft does not duplicate: SUITE-7 (OSC REST verification) and SUITE-9 (extension check). Stories that need them list them as dependencies.

---

## 1. How to read this

- **Item types.** An epic holds stories. A story is one user-visible or verifiable outcome. A task is a slice of a story that is too large to verify as one unit. A spike is a task that answers one question.
- **Draft IDs.** Epic `E1`. Story `E1-S1`. Task `E1-S1-T1`. The planning tool gives the real issue IDs later. Create each child with `--epic` or `--parent`. Write each AC as a structured AC item.
- **Sizes.** S = up to 1 day. M = 1 to 3 days. L = more than 3 days. Every story fits in one sprint (10 working days, one agent team).
- **Workflow.** Every story follows `.claude/rules/workflow-order.md`: architect, implement, QA, product-owner AC check, document-agent sync, security-officer sign-off. These steps are not repeated in each story. Each story updates the documents in `.claude/rules/documentation-maintenance.md`.
- **Writing.** Titles, descriptions and AC use ASD-STE100. UI terms are English. All data in examples is synthetic.

---

## 2. Epic list

| Epic | Title | Goal | Size | Sprint | Depends on |
|---|---|---|---|---|---|
| E1 | Platform foundation | Build and deploy from a clean checkout with one chain. Run the tests. Answer the five spikes. | M | 0 | SUITE-9 |
| E2 | Design tokens and base theme | Make one token file drive the Angular UI and the legacy UI. | M | 0–1 | E1 |
| E3 | Data model | Create all TKC fields, lists, relationships and tables with Repair. Change no UI. | M | 1 | E1 |
| E4 | OSC import framework and anonymized sample | Import OSC data with guards. Run the anonymized full-volume import. | L | 1–2 | E3 |
| E5 | Shared UI components | Build the parts that more than one screen uses. | M | 2 (one optional story in 5) | E2, E3, spikes S9 and S10 |
| E6 | Accounts screen | Show the Accounts screen as in the mockup. | M | 2 | E3, E5, E13-S1 |
| E7 | Contacts screen | Show the Contacts screen as in the mockup. | S–M | 2 | E3, E5 |
| E8 | Cases screen and actions | Show the Cases screen as in the mockup. Add the status bar and the record actions. | L | 3 | E3, E4, E5, spikes S8 and S9 |
| E9 | Case thread | Show the OSC thread in the AOP record thread. Import the thread. | M | 3–4 | E3, E8 |
| E10 | Opportunities and quote number | Show the Opportunities screen. Generate the quote number. | M | 4 | E3, E5 |
| E11 | Contracts | Show support and licence contracts on the account. | S–M | 4 | E3, E6, spike S7 |
| E12 | Legacy restyle | Make Quotes, Emails, Home, Admin and search match the tokens. | M | 5 | E2 |
| E13 | Security, roles and real-data import | Protect the data. Pass gate B. Run the real import. | M | 2, 5, 6, 7+ | E3, E4, E6, E8 |
| E14 | Azure environment (later) | Run the same image in a shared Azure environment. | M–L | 7+ | E1 |
| EN | Enhancements (not scheduled) | Hold the items that the owner deferred in A1. | — | none | owner |

---

## 3. Epics and stories

### E1. Platform foundation

Goal: A new engineer builds and deploys from a clean checkout. The test tools run. The five spikes have a written answer.
Plan: §3 W1, §9, Appendix. Size M. Sprint 0.

#### E1-S1 — Build: Run one build chain from a clean checkout
**Description:** The engineer runs `scripts/build.sh` in a clean Docker install. The script runs the whole chain from plan §3 W1. The chain stops on the first error.
**Acceptance criteria:**
1. `scripts/build.sh` runs `yarn merge-angular-json`, `yarn build:shell`, `yarn build:extension tkc`, `tkc:repair --dry-run`, `tkc:repair --execute`, `tkc:config:apply` and `cache:clear` in this order.
2. The script shows the SQL from `tkc:repair --dry-run` and waits for a confirmation before it runs `--execute`.
3. The script stops with a non-zero exit code when one step fails.
4. A clean Docker install that runs the script ends with a working login page.
5. `LOCAL_SETUP.md` describes the script.
**Size:** M · **Sprint:** 0 · **Depends on:** SUITE-9 · **Assumptions:** none

#### E1-S2 — Mail: Catch all outgoing email in a Mailpit sink
**Description:** The Docker environment has a Mailpit container. Every outgoing email goes to it. The import guard in E4-S2 uses the same host name.
**Acceptance criteria:**
1. `docker compose up` starts a Mailpit container.
2. The SuiteCRM outbound account points to the Mailpit host.
3. A test email from SuiteCRM shows in the Mailpit web page.
4. The sink host name is stored in one config value that E4-S2 reads.
**Size:** S · **Sprint:** 0 · **Depends on:** none · **Assumptions:** A19

#### E1-S3 — Tests: Run PHPUnit and Karma for the extension
**Description:** The extension has a PHP test harness and an Angular test harness. Both run from one command.
**Acceptance criteria:**
1. `extensions/tkc/backend` has a PHPUnit configuration and one passing example test.
2. `extensions/tkc/app` has a Karma configuration and one passing example test.
3. One documented command runs both test sets.
4. The commands run inside Docker.
**Size:** M · **Sprint:** 0 · **Depends on:** E1-S1 · **Assumptions:** none

#### E1-S4 — Tests: Check the metadata of each module with a contract test
**Description:** A test reads the layout files of a module and checks them against the vardefs. The test finds a field in a layout that has no vardef.
**Acceptance criteria:**
1. The contract test fails when a layout names a field that does not exist in the vardefs.
2. The contract test fails when a layout names a dropdown key that is not in its list.
3. The test runs for one example module in this story. The test runs for each TKC module when the module story ends.
4. The test runs in the one test command from E1-S3.
**Size:** M · **Sprint:** 0 · **Depends on:** E1-S3 · **Assumptions:** none

#### E1-S5 — Rules: Write the TKC field naming rule
**Description:** The rule text in `.claude/rules/suitecrm-customization.md` must match A8. Fields on core modules use `tkc_<english_name>_c`. Fields in a `tkc_` module have no prefix.
**Acceptance criteria:**
1. `.claude/rules/suitecrm-customization.md` states the A8 naming rule and the two exceptions (`osc_id_c`, `tkc_case_ref_c`).
2. `docs/osc-mapping/incident.md` uses the prefixed field names (for example `tkc_queue_c`).
3. `docs/osc-mapping/incident.md` no longer names `osc_ref_no_c`.
**Size:** S · **Sprint:** 0 · **Depends on:** owner decision A8, A12 · **Assumptions:** A8, A12

#### E1-S6 — Docs: Add the register of overridden viewdefs and lists
**Description:** `docs/architecture.md` has a register of every full-copy viewdef and core list override. Each upstream merge re-checks the register.
**Acceptance criteria:**
1. `docs/architecture.md` has a section "Overrides register" with columns for the file, the module, the reason and the check at merge.
2. The register is empty and has a header row.
3. The story template for build stories names the register in its documentation step.
**Size:** S · **Sprint:** 0 · **Depends on:** none · **Assumptions:** none

#### E1-S7 — Spikes: Answer the five open feasibility questions
**Description:** Five spikes check the plan against SuiteCRM 8.10. Each spike writes its result in a comment on its task and in `docs/architecture.md`. The architect reads the result. Plan: Appendix. The spikes are Level 3 work.
**Acceptance criteria:**
1. Each of the five tasks has a written go or no-go with evidence.
2. Each no-go names the fallback from the plan and its size.
3. `docs/architecture.md` records the five results.
**Size:** L · **Sprint:** 0 · **Depends on:** E1-S1 · **Assumptions:** A3, A13

##### E1-S7-T1 — Spike S7: Show AOS_Contracts in Angular
**Description:** Test whether a D5 delta of `module_routing` (`contracts: record: true`) renders AOS_Contracts in Angular with a TKC `detailviewdefs.php` and no line items.
**Acceptance criteria:**
1. The task states whether the record view renders in Angular.
2. The task states whether cell `displayLogic` on `contract_type` works in that view.
3. A no-go result names the fallback (legacy record with two panels that always show).
**Size:** M · **Sprint:** 0 · **Depends on:** E1-S1 · **Assumptions:** A13

##### E1-S7-T2 — Spike S8: Put the record thread in a TKC layout
**Description:** Test whether `record-thread` accepts `tkc_entry_type_c` and `tkc_channel_c` in item and create layouts. Test `dynamicClass` on the entry type. Test the widget in `bottomWidgets`.
**Acceptance criteria:**
1. The task states whether the two fields show in the item layout and in the create layout.
2. The task states whether `dynamicClass` sets a CSS class from the entry type.
3. The task states whether the thread renders in `bottomWidgets` as the Messages tab.
**Size:** M · **Sprint:** 0 · **Depends on:** E1-S1 · **Assumptions:** none

##### E1-S7-T3 — Spike S9: Check record-action and panel visibility
**Description:** Test whether `recordActions` support `displayLogic` on field values. Test whether a panel hides when all its cells hide.
**Acceptance criteria:**
1. The task states whether an action hides by the value of a field.
2. The task states whether a panel hides when all its cells hide.
3. A no-go result names the fallback (backend `ActionAvailabilityChecker`; an empty panel header is accepted).
**Size:** M · **Sprint:** 0 · **Depends on:** E1-S1 · **Assumptions:** none

##### E1-S7-T4 — Spike S10: Check the empty state of enum-radio and preset list filters
**Description:** Test whether `enum-radio` shows an empty "not set" state and a clear action. Test whether the Angular list supports a preset default filter.
**Acceptance criteria:**
1. The task states whether a user can clear an `enum-radio` value back to empty.
2. The task states whether a default filter applies when the list opens.
3. A no-go result names the fallback and its size (Yes/No edit component M; saved default filter L1).
**Size:** M · **Sprint:** 0 · **Depends on:** E1-S1 · **Assumptions:** A3

##### E1-S7-T5 — Spike S11: Check the probability update on stage change
**Description:** Test whether the 8.10 Angular edit view updates `probability` when `sales_stage` changes.
**Acceptance criteria:**
1. The task states whether `probability` changes in the edit view when the stage changes.
2. A no-go result states that the `tkc_opportunity_won` hook sets the probability on save.
**Size:** S · **Sprint:** 0 · **Depends on:** E1-S1 · **Assumptions:** A14

---

### E2. Design tokens and base theme

Goal: One token file drives both UIs. The base components look the same in Angular and in the legacy iframe.
Plan: §3 W2. Source: `designs/system/`. Size M. Sprints 0–1.

#### E2-S1 — Theme: Generate both theme files from `tokens.json`
**Description:** The script `scripts/theme/build_theme.py` reads `designs/system/tokens.json`. It writes `extensions/tkc/app/src/theme/_tkc-tokens.scss` and the token block of `public/legacy/custom/themes/suite8/css/Dawn/style.css`. Light and dark tokens exist.
**Acceptance criteria:**
1. `python3 -I scripts/theme/build_theme.py` writes both files.
2. A second run gives no diff in git.
3. A CI check fails when a generated file differs from the generator output.
4. The script writes the light tokens and the dark tokens.
5. No hand-written file contains a raw hex value.
**Size:** M · **Sprint:** 0 · **Depends on:** E1-S1 · **Assumptions:** none

#### E2-S2 — Theme: Apply the tokens in the Angular shell
**Persona/goal/benefit:** As a sales rep, I want the navbar, buttons and fields to use the TKC look, so that the app feels like one product.
**Description:** `_tkc-overrides.scss` restyles the navbar, buttons and field inputs. The build test checks that a TKC rule is in the bundle. Mockup: `designs/system/design-system.html`.
**Acceptance criteria:**
1. The navbar, primary button, secondary button and text input show the token colours and fonts.
2. A build test fails when no TKC rule is in the CSS bundle.
3. Each field has a visible label and a visible focus ring.
4. The Angular page and the legacy Admin page use the same font and base size.
**Size:** M · **Sprint:** 0 · **Depends on:** E2-S1 · **Assumptions:** none

#### E2-S3 — Theme: Restyle tabs, panels, lists, subpanels, alerts and modals in Angular
**Persona/goal/benefit:** As a service agent, I want panels, lists and dialogs to follow the design system, so that every screen looks the same.
**Description:** Extend `_tkc-overrides.scss` for the parts that the screen stories use. Use components from `designs/system/components.md`.
**Acceptance criteria:**
1. Tabs, panels, list rows, subpanel headers, alerts and modals use tokens only.
2. A list row has a visible hover state and a visible focus state.
3. A modal closes with the Escape key.
4. The result matches `designs/system/design-system.html` in a browser check.
**Size:** M · **Sprint:** 1 · **Depends on:** E2-S2 · **Assumptions:** none

#### E2-S4 — Login: Show the TKC login page with the company logo
**Persona/goal/benefit:** As any user, I want a branded login page, so that I know I am in the TKC system.
**Acceptance criteria:**
1. The login page shows the `company_logo` setting.
2. The login page uses the token colours and fonts.
3. The user signs in with the keyboard only.
**Size:** S · **Sprint:** 1 · **Depends on:** E2-S2 · **Assumptions:** none

#### E2-S5 — Theme: Apply the tokens in the legacy Dawn theme
**Persona/goal/benefit:** As an admin, I want the legacy pages to look like the Angular pages, so that I do not see a seam.
**Description:** The generator writes the token block. Hand rules live in `extensions/tkc/legacy-theme/*.css` and join into `style.css`. Plan: §3 W2. Rule: `.claude/rules/design-system.md`.
**Acceptance criteria:**
1. The Admin page uses the token font, base size, link colour and focus ring.
2. The partials in `extensions/tkc/legacy-theme/` join into `style.css` with one documented command.
3. No file under `public/legacy/` outside `public/legacy/custom/` changes.
4. The recompile step is in `LOCAL_SETUP.md`.
**Size:** M · **Sprint:** 0 · **Depends on:** E2-S1 · **Assumptions:** none

#### E2-S6 — Theme: Check contrast and focus in both UIs
**Description:** The qa-engineer checks WCAG 2.2 AA contrast and focus states in Angular and in the legacy UI. The result goes on the issue.
**Acceptance criteria:**
1. Text and background pairs in both UIs meet a 4.5:1 contrast ratio.
2. Each interactive element in both UIs shows a visible focus state.
3. The check lists each failure with a screenshot of synthetic data.
**Size:** S · **Sprint:** 1 · **Depends on:** E2-S3, E2-S5 · **Assumptions:** none

---

### E3. Data model

Goal: All TKC fields, lists, relationships and tables exist after `tkc:repair`. No UI changes.
Plan: §3 W3, §4. Size M. Sprint 1. Each story updates `docs/data-model.md` and the status in `docs/osc-mapping/<entity>.md`.
Note: All owner decisions A2 to A16 must be known before Sprint 1. This epic builds all vardefs.

#### E3-S1 — Sequences: Add the `tkc_sequences` table and the `TKC\Sequence` service
**Description:** A transactional table holds counters per name and year. `public/legacy/custom/include/TKC/Sequence.php` gives the next value with a row lock. Plan: §4, §6.
**Acceptance criteria:**
1. `tkc:repair` creates the table `tkc_sequences` from a vardef.
2. `TKC\Sequence::next('quote', 2026)` gives a value that is one higher than the last value.
3. Two parallel calls give two different values (PHPUnit test).
4. A new year starts a new counter.
5. The service can set a counter to a given start value.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S3 · **Assumptions:** A7, A12

#### E3-S2 — Accounts: Add the TKC fields and lists
**Description:** Add the Accounts fields from plan §4.1. Override `account_type_dom`. Mapping: `docs/osc-mapping/organization.md`.
**Acceptance criteria:**
1. After Repair, Accounts have the fields in plan §4.1 with the given types and lengths (except the Finance fields).
2. `account_type_dom` has the keys `Lead`, `Prospect`, `Customer`, `Supplier`, `Relation` and `FormerCustomer`. The default is `Relation`.
3. The label of `Customer` is "Customer".
4. `tkc_yes_no_list` has the keys `yes` and `no`. The empty value means not set.
5. `tkc_auto_upgrade_c` has the default `no`.
6. `tkc_debiteurnummer_c` has an index and is audited.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A3, A8, A16

#### E3-S3 — Accounts: Add the `tkc_AccountFinance` module
**Description:** Create the module with Module Builder. It holds the six Finance fields. One row exists per account. The module ACL protects the data. Plan: A6, §4.1.
**Acceptance criteria:**
1. After Repair, the module `tkc_AccountFinance` exists with the six fields and `account_id`.
2. The relationship `tkc_accounts_tkc_accountfinance` links one account to many rows.
3. All six fields are audited.
4. The module files are in git under `public/legacy/custom/`.
5. A user without a role for the module cannot open it (checked in E13-S1).
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A6

#### E3-S4 — Contacts: Add the TKC fields and lists
**Description:** Add the Contacts fields from plan §4.2. Mapping: `docs/osc-mapping/contact.md`.
**Acceptance criteria:**
1. After Repair, Contacts have the fields in plan §4.2.
2. `tkc_contact_type_c` has an index. Its list has the key `spam`.
3. The dropdown labels contain no customer names.
4. The six program fields use `tkc_yes_no_list`.
5. `tkc_function_level_c` has the keys C, M and O.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4, E3-S2 · **Assumptions:** A3, A8, A15

#### E3-S5 — Cases: Add the TKC fields and lists
**Description:** Add the Cases fields from plan §4.3 and override the status, priority and type lists. Mapping: `docs/osc-mapping/incident.md`.
**Acceptance criteria:**
1. After Repair, Cases have the fields in plan §4.3.
2. `case_status_dom` has 12 keys. The 8 new keys use the prefix `Open_` or `Closed_`.
3. `case_priority_dom` has P1, P2, P3 and P4 with the OSC labels.
4. `tkc_case_ref_c` is a varchar 20 field with an index.
5. `tkc_queue_c` has 12 values and an index.
6. `tkc_product_c` and `tkc_category_c` are not required.
7. No field exists for Disposition, an assigned group or `mailHeader`.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A2, A5, A8, A10, A11, A12

#### E3-S6 — Cases: Add the primary contact relationship
**Description:** Add the required relate field `tkc_primary_contact_c` and the relationship `tkc_contacts_cases_primary`. The core M:N relationship stays.
**Acceptance criteria:**
1. After Repair, the relationship `tkc_contacts_cases_primary` links one contact to many cases.
2. The field `tkc_primary_contact_id_c` has an index.
3. The field is required in the edit view metadata.
4. The relationship `contacts_cases` still exists.
**Size:** S · **Sprint:** 1 · **Depends on:** E3-S5 · **Assumptions:** A4

#### E3-S7 — Case thread: Add the thread fields to AOP_Case_Updates
**Description:** Add the entry type, the channel and the import key to AOP_Case_Updates. Change `description` to `longtext`. The plan names these fields in §3 W6 and §5.2 but has no field list. See section 6.2.
**Acceptance criteria:**
1. After Repair, AOP_Case_Updates has `tkc_entry_type_c`, `tkc_channel_c` and `osc_id_c`.
2. `osc_id_c` has an index.
3. The column `description` is `longtext`.
4. A test entry of 1 MB saves and loads without loss.
5. The Repair SQL for the `description` change is on the issue.
**Size:** S · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A17, A8

#### E3-S8 — Opportunities: Add the TKC fields and the stage list
**Description:** Add the Opportunities fields from plan §4.4 and override `sales_stage_dom` and `sales_probability_dom`. Mapping: `docs/osc-mapping/opportunity.md`.
**Acceptance criteria:**
1. After Repair, Opportunities have the fields in plan §4.4.
2. `sales_stage_dom` has 8 keys. `Closed Won` and `Closed Lost` stay.
3. `sales_probability_dom` has one value per stage.
4. The vardef sets `name` to length 255.
5. `tkc_reference_c` has an index.
6. No field exists for `waarschijnlijkheid`, `type_medewerker` or `VERKOOP_ID`.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A7, A8, A14

#### E3-S9 — Opportunities: Add the source case relationship
**Description:** Add the relationship `tkc_cases_opportunities` and the fields `tkc_source_case_c` and `tkc_source_case_id_c`.
**Acceptance criteria:**
1. After Repair, the relationship links one case to many opportunities.
2. The field `tkc_source_case_id_c` has an index.
3. The Cases record shows the opportunity in a subpanel definition.
**Size:** S · **Sprint:** 1 · **Depends on:** E3-S5, E3-S8 · **Assumptions:** A14

#### E3-S10 — Contracts: Add the contract types and fields
**Description:** Add the keys `tkc_support` and `tkc_license` to `contract_type_list`. Add the fields from plan §4.5. Mapping: `docs/osc-mapping/organization.md` §5a.
**Acceptance criteria:**
1. After Repair, AOS_Contracts has the six fields from plan §4.5.
2. `contract_type_list` has the keys `tkc_support` and `tkc_license`.
3. `tkc_support_level_list` has the keys bronze, silver and gold.
4. The Accounts record keeps its Contracts relationship.
**Size:** S · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A13

#### E3-S11 — Import key: Add `osc_id_c` with an index to each imported module
**Description:** Add `osc_id_c` (varchar 20) and the index `idx_<table>_osc_id_c` to Accounts, Contacts, Cases, Opportunities and AOS_Contracts. AOP_Case_Updates is in E3-S7.
**Acceptance criteria:**
1. Each of the five modules has `osc_id_c` after Repair.
2. Each module has a non-unique index on the field.
3. The vardef file is `Vardefs/tkc_osc_id.php` in each module.
**Size:** S · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** none

#### E3-S12 — Repair: Review and run the Repair for all vardefs
**Description:** The backend engineer reviews the SQL from `tkc:repair --dry-run`. The engineer runs Repair on a clean database. The security-officer reads the SQL for `ALTER` on core columns.
**Acceptance criteria:**
1. The Repair SQL is on the issue.
2. The SQL changes only the expected columns, including `opportunities.name` and `aop_case_updates.description`.
3. A second Repair run proposes no change.
4. The metadata contract test (E1-S4) passes for each module.
5. `docs/data-model.md` lists every field, list and relationship.
**Size:** S · **Sprint:** 1 · **Depends on:** E3-S1 to E3-S11 · **Assumptions:** none

---

### E4. OSC import framework and anonymized sample

Goal: An idempotent, guarded import. The anonymized sample runs at full volume. Real data does not enter before gate B.
Plan: §3 W8, §5. Rules: `.claude/rules/data-privacy.md`, `.claude/rules/osc-access.md`. Size L. Sprints 1–2.

#### E4-S1 — Import: Build the import command, the upserter and the checkpoint
**Description:** `tkc:osc:import <entity|all> --source=<dir> [--dry-run|--execute] [--limit=n] [--since=<osc-id>]` reads JSONL and upserts by `osc_id_c`. Plan: §5.1. This story is too large to verify as one unit.
**Acceptance criteria:**
1. All four tasks have passed.
2. A run without `--execute` writes no record.
3. A run with a bad option stops with a clear message.
**Size:** L · **Sprint:** 1 · **Depends on:** E3-S11 · **Assumptions:** none

##### E4-S1-T1 — Add the command and the JSONL reader
**Acceptance criteria:**
1. The command `tkc:osc:import` exists in `extensions/tkc/backend/Import/Console/OscImportCommand.php`.
2. The reader reads one JSONL file per entity.
3. The command rejects a source directory that is outside `data/` or `scripts/seed/fixtures/`.
4. A dry run prints the counts of records that it would create and update.
**Size:** M · **Sprint:** 1 · **Depends on:** E3-S11 · **Assumptions:** A18

##### E4-S1-T2 — Add the upserter
**Acceptance criteria:**
1. The upserter finds a record with `osc_id_c = ? AND deleted = 0`.
2. The upserter creates the record when none exists and updates it when one exists.
3. The upserter saves in batches of 200.
4. A PHPUnit test shows that two runs give the same record count.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1-T1 · **Assumptions:** none

##### E4-S1-T3 — Add the checkpoint and the log
**Acceptance criteria:**
1. The command writes a checkpoint in `data/osc/import-state/` after each batch.
2. A new run with the same source continues from the checkpoint.
3. The log in `data/osc/import-logs/<run>.log` has counts and IDs only.
4. A test shows that the log has no field content.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1-T2 · **Assumptions:** none

##### E4-S1-T4 — Add the staff map
**Acceptance criteria:**
1. The command reads `data/osc/staff-map.csv` (OSC account id to `user_name`).
2. An unmapped OSC account id becomes the inactive user `tkc_migration`.
3. The run report gives the count of unmapped ids, not their names.
**Size:** S · **Sprint:** 1 · **Depends on:** E4-S1-T2 · **Assumptions:** none

#### E4-S2 — Import guards: Stop all outbound email, workflows and geocoding
**Description:** The import must not send email or call an outside service. Plan §5.3 lists each guard. This story is a security gate item.
**Acceptance criteria:**
1. The import sets `$_REQUEST['module'] = 'Import'` and the case creation notice does not send.
2. The import sets `enable_aop` to false in the process and sets `send_closure_email` to false.
3. The import refuses to run when an AOW flow has `run_on_import = 1`.
4. The import saves with `$bean->save(false)` and no assignment notice goes out.
5. The import refuses to run when the jjwg geocode scheduler is active or a geocoding key is set.
6. The import refuses to run when the outbound host is not on the sink allowlist.
7. `TKC\ImportContext::activate()` runs before the first save. A hook test shows that the TKC hooks do nothing in import mode.
8. A test run with synthetic data leaves Mailpit empty.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S2, E4-S1-T2 · **Assumptions:** A19

#### E4-S3 — Import: Keep dates, owners and the created-by value from OSC
**Description:** The import must keep `date_entered`, `date_modified`, `created_by` and `modified_user_id` from OSC. Plan §5.3, last row.
**Acceptance criteria:**
1. An imported record has the OSC created date and modified date.
2. An imported record has the mapped created-by user and modified-by user.
3. The import does not change the modified date on an update without a change.
**Size:** S · **Sprint:** 1 · **Depends on:** E4-S1-T2 · **Assumptions:** none

#### E4-S4 — Import: Add synthetic fixtures and the idempotency test
**Description:** `scripts/seed/fixtures/*.jsonl` hold synthetic records for each entity. The test imports twice. The mapper stories for Opportunities, Contracts and Cases add their fixtures.
**Acceptance criteria:**
1. The fixture files hold Accounts and Contacts with OSC field names and no real data.
2. An import of the fixtures twice gives the same record count in each table.
3. The test runs in CI with the one test command.
4. `scripts/seed/README.md` explains the fixtures.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1, E4-S10, E4-S11 · **Assumptions:** none

#### E4-S5 — Anonymizer: Replace personal data with synthetic values
**Description:** `tkc:osc:anonymize --in=data/osc/export --out=data/osc/anon` keeps ids, menu ids, dates, relations and volumes. It replaces names, emails, phones, streets, free text and subjects. The seed is deterministic. It also covers thread text.
**Acceptance criteria:**
1. The command reads from `data/osc/export` and writes to `data/osc/anon`.
2. Ids, menu ids, dates and relations stay the same.
3. Names, emails, phones, streets, free text, subjects and thread bodies are synthetic.
4. A second run with the same seed gives the same output.
5. The command refuses an output path outside `data/`.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1-T1 · **Assumptions:** A17

#### E4-S6 — Anonymizer: Test the output for personal data
**Description:** A test reads a synthetic input that has known "personal" tokens. It checks that no token is in the output.
**Acceptance criteria:**
1. The test fails when one known token stays in the output.
2. The test covers names, emails, phones, addresses, subjects, free text and thread bodies.
3. The test runs in CI with synthetic data only.
**Size:** S · **Sprint:** 1 · **Depends on:** E4-S5 · **Assumptions:** none

#### E4-S7 — Security gate A: Review the anonymizer and the guards
**Description:** The security-officer reviews the anonymizer, the PII-leak test and the import guards. Gate A opens the way for the owner-run extract. Plan §5.4 step 2.
**Acceptance criteria:**
1. The security-officer states in writing that the guards in E4-S2 are complete.
2. The security-officer states in writing that the anonymizer passes the test in E4-S6.
3. The comment lists each finding with its fix.
4. The gate is open only when no finding is open.
**Size:** S · **Sprint:** 1 · **Depends on:** E4-S2, E4-S5, E4-S6 · **Assumptions:** A18, A19

#### E4-S8 — Owner task: Run the OSC bulk extract
**Description:** **Assigned to the owner.** The owner runs the bulk extract with the read-only API user and `pass-cli run`. Agents do not run bulk extracts. Rule: `.claude/rules/osc-access.md`. Plan: A18.
**Acceptance criteria:**
1. The owner writes one JSONL file per entity to `data/osc/export/` (gitignored). The files use REST field names.
2. The entities are organizations, contacts, opportunities, both contract types, incidents and incident threads.
3. The owner gives the record counts per entity, including the thread count, in a comment (counts only).
4. No file from the extract is in git.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S7, SUITE-7 · **Assumptions:** A18

#### E4-S9 — Owner task: Provide the staff map
**Description:** **Assigned to the owner.** The owner gives the list that maps OSC staff accounts to SuiteCRM users. The users are in the SuiteCRM admin list.
**Acceptance criteria:**
1. The owner places `data/osc/staff-map.csv` in the repository folder (gitignored).
2. The owner states in a comment whether the map uses login or email.
3. The owner states who owns the records of inactive staff.
**Size:** S · **Sprint:** 1 · **Depends on:** none · **Assumptions:** none

#### E4-S10 — Import: Map Organizations to Accounts and Finance rows
**Description:** The mapper creates Accounts in pass 1 and sets `parent_id` in pass 2. It creates one `tkc_AccountFinance` row per account. Mapping: `docs/osc-mapping/organization.md`.
**Acceptance criteria:**
1. The import creates one account per OSC organization with `osc_id_c`.
2. Pass 2 sets `parent_id` for each organization that has a parent.
3. The mapper does not read the two credential fields or the REST-only rate-class fields.
4. The mapper creates the Finance row with the six fields.
5. The mapper maps `org_telefoonnummer` to `phone_office`.
6. The mapper maps each menu id to a key with `extensions/tkc/config/tkc_import_maps.php`. The run report counts unmapped menu ids.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1, E3-S2, E3-S3, E3-S11 · **Assumptions:** A6, A16

#### E4-S11 — Import: Map OSC Contacts to Contacts
**Description:** The mapper creates Contacts with email and phone rows. It imports the SPAM contacts with the type `spam`. Mapping: `docs/osc-mapping/contact.md`.
**Acceptance criteria:**
1. The import creates one contact per OSC contact and links it to its account.
2. The mapper writes the five OSC phone types into the five core phone fields.
3. The mapper writes alternative emails as extra `email_addresses` rows.
4. The mapper sets `tkc_contact_type_c = spam` for the SPAM contacts.
5. The mapper sets a Yes/No field to empty when OSC has no value.
6. The mapper does not read the channel ids, ERT, Test or the REST-only fields.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S10, E3-S4 · **Assumptions:** A3, A15

#### E4-S12 — Import: Map OSC Opportunities to Opportunities
**Description:** The mapper creates Opportunities with one contact link. Mapping: `docs/osc-mapping/opportunity.md`.
**Acceptance criteria:**
1. The import creates one opportunity per OSC opportunity with `osc_id_c`.
2. The mapper keeps both amounts and both close dates.
3. The mapper maps the OSC stage to a `sales_stage` key.
4. The mapper copies the OSC quote number to `tkc_reference_c`.
5. The mapper does not read `waarschijnlijkheid`, `type_medewerker` or `VERKOOP_ID`.
6. The synthetic fixtures include opportunities.
**Size:** M · **Sprint:** 2 · **Depends on:** E4-S11, E3-S8 · **Assumptions:** A7, A14

#### E4-S13 — Import: Map OSC contracts to Contracts
**Description:** The mapper handles `Support.SupportContract` and `Support.LicentieContract`. Mapping: `docs/osc-mapping/organization.md` §5a.
**Acceptance criteria:**
1. The import creates one contract per OSC contract with the right `contract_type` key.
2. The mapper sets the status to `Signed` as the default.
3. The mapper does not set `renewal_reminder_date`.
4. No reminder email goes out during the import (Mailpit is empty).
5. The synthetic fixtures include both contract types.
**Size:** S · **Sprint:** 2 · **Depends on:** E4-S12, E3-S10 · **Assumptions:** A13

#### E4-S14 — Import: Map OSC Incidents to Cases
**Description:** The mapper creates Cases with the primary contact, the other contacts and the account. This story is too large to verify as one unit. Mapping: `docs/osc-mapping/incident.md`.
**Acceptance criteria:**
1. All three tasks have passed.
2. The synthetic fixtures include cases.
3. The run report counts unmapped status, product and category ids.
**Size:** L · **Sprint:** 2 · **Depends on:** E4-S11, E3-S5, E3-S6 · **Assumptions:** A2, A4, A5, A10, A11, A12

##### E4-S14-T1 — Add the menu maps for cases
**Acceptance criteria:**
1. `tkc_import_maps.php` maps each OSC status id to one of the 12 status keys.
2. The file maps priority, queue, product, category, type and origin ids.
3. Disposition ids feed the Category mapping only.
4. A unit test fails when a mapped key is not in its dropdown list.
**Size:** M · **Sprint:** 2 · **Depends on:** E3-S5 · **Assumptions:** A2, A10

##### E4-S14-T2 — Add the case mapper
**Acceptance criteria:**
1. The mapper writes OSC `referenceNumber` to `tkc_case_ref_c`.
2. The mapper sets `state` from the prefix of the status key.
3. The mapper sets `tkc_date_closed_c` from OSC for closed cases.
4. The mapper sets the primary contact and the account.
5. The mapper does not read the 26 REST-only fields.
**Size:** M · **Sprint:** 2 · **Depends on:** E4-S14-T1 · **Assumptions:** A4, A11, A12

##### E4-S14-T3 — Link the other contacts and the primary contact in the M:N set
**Acceptance criteria:**
1. The importer links the primary contact into `contacts_cases`.
2. The importer links each other OSC contact into `contacts_cases`.
3. A rerun does not create a double link.
**Size:** S · **Sprint:** 2 · **Depends on:** E4-S14-T2 · **Assumptions:** A4

#### E4-S15 — Import: Link opportunities to their source cases
**Description:** Pass 2 sets `tkc_source_case_id_c` from the OSC `Support.Incident` link of an opportunity. Plan §5.2, step 6.
**Acceptance criteria:**
1. Each opportunity with a source incident gets the matching case.
2. The importer leaves the field empty when the case does not exist and counts it in the report.
3. A rerun gives the same links.
**Size:** S · **Sprint:** 2 · **Depends on:** E4-S12, E4-S14 · **Assumptions:** A14

#### E4-S16 — Import: Set the sequence start values after an import
**Description:** After an import, each sequence must start above the highest imported number. Plan §6, last rows.
**Acceptance criteria:**
1. After the import, the case reference sequence is above the highest imported `YYMMDD-NNNNNN` value for each day.
2. After the import, the quote sequence is above the highest imported number per year.
3. A new case gets a reference that no imported case has.
**Size:** S · **Sprint:** 2 · **Depends on:** E3-S1, E4-S12, E4-S14 · **Assumptions:** A7, A12

#### E4-S17 — Import: Run the anonymized full-volume import and report the result
**Description:** The team anonymizes the owner extract locally. The team imports it into local Docker. This story is too large to verify as one unit. Plan §5.4 step 3. Output stays in `data/`.
**Acceptance criteria:**
1. All three tasks have passed.
2. The report on the issue has counts, run time and unmapped values only.
3. Mailpit is empty after the run.
**Size:** M · **Sprint:** 2 · **Depends on:** E4-S7, E4-S8, E4-S9 · **Assumptions:** A17, A18

##### E4-S17-T1 — Import Accounts and Contacts at full volume
**Acceptance criteria:**
1. The local database has about 738 accounts and 7981 contacts (the tst1 volume).
2. A second run gives the same counts.
3. The run report counts the unmapped ids.
**Size:** S · **Sprint:** 2 · **Depends on:** E4-S10, E4-S11 · **Assumptions:** A18

##### E4-S17-T2 — Import Opportunities, Contracts and Cases at full volume
**Acceptance criteria:**
1. The local database has about 1964 opportunities and 4053 cases.
2. The contract count matches the extract count.
3. A second run gives the same counts.
**Size:** M · **Sprint:** 2 · **Depends on:** E4-S12 to E4-S15, E4-S17-T1 · **Assumptions:** A18

##### E4-S17-T3 — Write the import report
**Acceptance criteria:**
1. The report gives the run time per entity.
2. The report gives the unmapped values for product, category and status.
3. The report gives the thread count from the extract.
4. The report has no field content.
**Size:** S · **Sprint:** 2 · **Depends on:** E4-S17-T2 · **Assumptions:** none

---

### E5. Shared UI components

Goal: The parts that more than one screen uses exist once, in `extensions/tkc/app/`.
Plan: §3 W4. Design system: `designs/system/components.md`. Size M. Sprint 2.

#### E5-S1 — Components: Show an enum as a coloured pill
**Persona/goal/benefit:** As a service agent, I want a status, priority or type as a coloured pill, so that I see the state at a glance.
**Description:** `TkcEnumPillComponent` shows an enum value as a pill. A key-to-variant map sets the colour. Plan: §3 W4a.
**Acceptance criteria:**
1. The pill shows the label of the enum value.
2. A map from key to variant sets the colour from tokens.
3. The pill shows in the list and in the record view.
4. The pill text meets a 4.5:1 contrast ratio.
5. A Karma test covers one value per variant.
**Size:** M · **Sprint:** 2 · **Depends on:** E2-S3, E1-S3 · **Assumptions:** none

#### E5-S2 — Components: Show Yes/No fields as a segmented control
**Persona/goal/benefit:** As a sales rep, I want a Yes/No field with three states, so that I see "not set" and "No" as different values.
**Description:** Style the core `enum-radio` with TKC CSS. The empty value is "not set". Spike S10 sets the fallback. Plan: A3.
**Acceptance criteria:**
1. The edit view shows Yes and No as a segmented control.
2. The user clears the value back to empty.
3. The detail view shows "Yes", "No" or an empty value.
4. The control works with the keyboard.
**Size:** M · **Sprint:** 2 · **Depends on:** E2-S3, E1-S7-T4 · **Assumptions:** A3

#### E5-S3 — Components: Show a field value as a link from a URL template
**Persona/goal/benefit:** As a service agent, I want the Replicon, SharePoint and TFS values as links, so that I open the target in one click.
**Description:** `TkcUrlTemplateField` builds a link from a field value and a template. Plan: §3 W4c. The templates are open (org Q12).
**Acceptance criteria:**
1. The field shows a link when the value is not empty.
2. The template is a config value, not code.
3. The link opens in a new tab with `rel="noopener"`.
4. The field shows plain text when no template exists.
**Size:** M · **Sprint:** 2 · **Depends on:** E2-S3 · **Assumptions:** A16

#### E5-S4 — Components: Add the record-action base and the status-bar look
**Description:** Add a shared abstract handler for record actions in `extensions/tkc/backend/Process/Service/RecordActions/`. Add the status-bar look. The Cases actions in E8-S5 use both. Plan: §3 W4d.
**Acceptance criteria:**
1. An abstract handler sets a status and the matching state from a subclass value.
2. The handler checks `getRequiredACLs()` before it saves.
3. The status bar shows the current status as pressed.
4. A PHPUnit test covers one subclass.
**Size:** M · **Sprint:** 2 · **Depends on:** E1-S3, E2-S3, E1-S7-T3 · **Assumptions:** none

#### E5-S5 — List: Apply a default filter when a list opens
**Persona/goal/benefit:** As a sales rep, I want the Contacts list to hide SPAM contacts by default, so that I see real contacts first.
**Description:** Use the preset-filter feature if spike S10 shows it. Otherwise use a saved default filter (L1). Plan: §3 W4f.
**Acceptance criteria:**
1. The Contacts list opens with `tkc_contact_type_c` not equal to `spam`.
2. The user removes the filter and sees the SPAM contacts.
3. The filter shows as a visible chip.
**Size:** M · **Sprint:** 2 · **Depends on:** E1-S7-T4, E3-S4 · **Assumptions:** A15

#### E5-S6 — Components: Show multi-select values as chips
**Persona/goal/benefit:** As a sales rep, I want the CRM modules field to show chips, so that I read the values quickly.
**Description:** Use the core `multienum` with TKC CSS. Plan: §4.2.
**Acceptance criteria:**
1. The detail view shows each selected value as a chip.
2. The edit view lets the user add and remove values.
3. Chips use tokens only.
**Size:** S · **Sprint:** 2 · **Depends on:** E2-S3 · **Assumptions:** A16

#### E5-S7 — Edit view: Add "Save and close" (optional)
**Persona/goal/benefit:** As a service agent, I want to save and close an edit view in one action, so that I work faster.
**Description:** Low priority. The owner decides. Plan: §3 W4e.
**Acceptance criteria:**
1. The edit view has a "Save and close" action.
2. The action saves the record and shows the detail view.
3. The action does not save when validation fails.
**Size:** S · **Sprint:** 5 · **Depends on:** E5-S4 · **Assumptions:** none

---

### E6. Accounts screen

Goal: The Accounts list and record views match the mockup.
Plan: §4.1. Mockup: `designs/screens/accounts/mockup.html`. Mapping: `docs/osc-mapping/organization.md`. Size M. Sprint 2.

#### E6-S1 — Accounts: Show the record with the TKC panels
**Persona/goal/benefit:** As a sales rep, I want the account record in clear panels, so that I find the RightNow, contract and link data fast.
**Description:** One `detailviewdefs.php` in `public/legacy/custom/modules/Accounts/metadata/`. It keeps `topWidget`, `sidebarWidgets` and `recordActions`. The file goes into the overrides register.
**Acceptance criteria:**
1. The record has the tab Overview with the panels General, RightNow, Contract and Links.
2. Each panel shows the fields in plan §4.1.
3. The type shows as a pill.
4. The Yes/No fields show as segmented controls in edit mode.
5. The Replicon and SharePoint values show as links.
6. The contract begin and end dates show in the Contract panel.
7. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 2 · **Depends on:** E3-S2, E5-S1, E5-S2, E5-S3, E5-S6 · **Assumptions:** A3, A9

#### E6-S2 — Accounts: Show the list with TKC columns and filters
**Persona/goal/benefit:** As a sales rep, I want the account list to show the key columns, so that I find an account without opening it.
**Acceptance criteria:**
1. The list shows name, type pill, city, assigned user, debtor number and date modified.
2. The filters are type, assigned user and has RightNow.
3. The list loads the imported accounts with no error.
4. The result matches the list in the mockup.
**Size:** S · **Sprint:** 2 · **Depends on:** E3-S2, E5-S1, E4-S17-T1 · **Assumptions:** A9

#### E6-S3 — Accounts: Show type-based fields only for customers
**Description:** Cell `displayLogic` shows POD, data centre, auto upgrade, projects on portal and the Replicon link when `account_type` is `Customer`. No panel-level logic.
**Acceptance criteria:**
1. The five fields show for an account of type Customer.
2. The five fields do not show for an account of another type.
3. All other fields show for every type.
4. No panel has a `displayLogic` rule.
**Size:** S · **Sprint:** 2 · **Depends on:** E6-S1, E1-S7-T3 · **Assumptions:** A9

#### E6-S4 — Accounts: Show the subpanels
**Persona/goal/benefit:** As a service agent, I want the related records on the account, so that I see contacts, cases and contracts in one place.
**Acceptance criteria:**
1. The record shows the subpanels Contacts, Member organizations, Opportunities, Contracts, Cases, Activities, History and Documents.
2. The Contracts subpanel columns are added in E11-S2.
3. No Projects subpanel exists.
4. Each subpanel shows imported synthetic data.
**Size:** M · **Sprint:** 2 · **Depends on:** E6-S1 · **Assumptions:** A13

#### E6-S5 — Accounts: Show the Finance data only to the Sales role
**Persona/goal/benefit:** As a sales rep, I want the Finance data on the account, so that I see the rates and the expected revenue.
**Description:** The Finance data lives in `tkc_AccountFinance`. The account shows it in a subpanel or tab named "Finance". The module ACL protects it.
**Acceptance criteria:**
1. A user with the role "TKC Sales" sees and edits the Finance tab.
2. A user with another role does not see the tab or the data.
3. The six fields are not in the account list columns or in the account detail view.
4. The Finance data of an account shows in the tab after the import.
**Size:** M · **Sprint:** 2 · **Depends on:** E3-S3, E4-S17-T1, E13-S1 · **Assumptions:** A6, A9

---

### E7. Contacts screen

Goal: The Contacts list and record views match the mockup.
Plan: §4.2. Mockup: `designs/screens/contacts/mockup.html`. Mapping: `docs/osc-mapping/contact.md`. Size S–M. Sprint 2.

#### E7-S1 — Contacts: Show the record with the TKC panels
**Persona/goal/benefit:** As a service agent, I want the contact record in clear panels, so that I find the programs and consent data fast.
**Acceptance criteria:**
1. The record has the panels Identity, Contact details, TKC programs and groups, and Consent.
2. The contact type shows as a pill.
3. The six program fields show as segmented controls in edit mode.
4. The CRM modules show as chips.
5. The Consent panel shows `email_opt_out`.
6. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 2 · **Depends on:** E3-S4, E5-S1, E5-S2, E5-S6 · **Assumptions:** A3, A15

#### E7-S2 — Contacts: Show the list and hide SPAM contacts by default
**Persona/goal/benefit:** As a service agent, I want a Contacts list without SPAM, so that I do not see junk contacts.
**Acceptance criteria:**
1. The list shows name, account, email, phone, contact type pill and assigned user.
2. The list hides contacts of type `spam` when it opens.
3. The user can show the SPAM contacts by removing the filter.
4. The list loads the imported contacts with no error.
**Size:** M · **Sprint:** 2 · **Depends on:** E5-S5, E4-S17-T1 · **Assumptions:** A15

#### E7-S3 — Contacts: Show the subpanels
**Acceptance criteria:**
1. The record shows the subpanels Cases, Opportunities, Activities and history, and Documents.
2. Each subpanel shows imported synthetic data.
**Size:** S · **Sprint:** 2 · **Depends on:** E7-S1 · **Assumptions:** none

#### E7-S4 — Contacts: Show email and phone rows as in the mockup
**Description:** Use CSS on the core email widget and the five phone fields.
**Acceptance criteria:**
1. The record shows the primary email and the alternative emails in separate rows.
2. The record shows the five phone types with labels.
3. Rows use tokens only.
**Size:** S · **Sprint:** 2 · **Depends on:** E7-S1 · **Assumptions:** A15

---

### E8. Cases screen and actions

Goal: The Cases list and record views match the mockup. The status bar and the record actions work.
Plan: §4.3. Mockup: `designs/screens/cases/mockup.html`. Mapping: `docs/osc-mapping/incident.md`. Size L. Sprint 3.

#### E8-S1 — Cases: Show the record with header, pills and panels
**Persona/goal/benefit:** As a service agent, I want the case record with a clear header, so that I see reference, status, priority and queue at once.
**Acceptance criteria:**
1. The header eyebrow shows `tkc_case_ref_c`.
2. The header shows status, priority and queue as pills.
3. The record has the panels General, Categorisation, Routing, Status and Details.
4. The primary contact shows in the General panel and is required in edit mode.
5. `tkc_case_ref_c` is read-only.
6. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 3 · **Depends on:** E3-S5, E3-S6, E5-S1, E4-S17-T2 · **Assumptions:** A2, A4, A5, A12

#### E8-S2 — Cases: Show the list with filters and bulk update
**Persona/goal/benefit:** As a service agent, I want the case list with the key columns and filters, so that I work my queue.
**Acceptance criteria:**
1. The list shows reference (mono font), subject, account, primary contact, status, priority and queue pills, assigned user and date modified.
2. The filters are queue, status, state, assigned user, product and category.
3. The core mass update changes queue, status and assigned user.
4. The list loads the 4053 imported cases with no error.
**Size:** M · **Sprint:** 3 · **Depends on:** E3-S5, E5-S1, E4-S17-T2 · **Assumptions:** A2, A5

#### E8-S3 — Cases: Generate the case reference and set the closed date
**Description:** Two `before_save` hooks in `custom/Extension/modules/Cases/Ext/LogicHooks/tkc_cases.php`. Both skip in import mode. Plan §4.3.
**Acceptance criteria:**
1. A new case without `tkc_case_ref_c` gets a reference in the form `YYMMDD-NNNNNN` from `tkc_sequences`.
2. Two new cases get two different references.
3. A case that changes to a Closed state gets `tkc_date_closed_c`.
4. Both hooks do nothing when `ImportContext::isActive()` is true.
5. PHPUnit tests cover both hooks and the import skip.
**Size:** M · **Sprint:** 3 · **Depends on:** E3-S1, E3-S5, E4-S2 · **Assumptions:** A12

#### E8-S4 — Cases: Keep the primary contact in the contact list of the case
**Description:** An `after_save` hook links the primary contact into `contacts_cases` so that AOP emails reach it. The hook fills an empty primary contact from an existing M:N contact. The shared logic is `CasePrimaryContact.php`.
**Acceptance criteria:**
1. A saved case has its primary contact in `contacts_cases`.
2. A case with an empty primary contact and one M:N contact gets that contact as primary contact.
3. The hook does nothing in import mode.
4. A PHPUnit test covers both cases and the import skip.
**Size:** M · **Sprint:** 3 · **Depends on:** E3-S6, E4-S2 · **Assumptions:** A4

#### E8-S5 — Cases: Add the status bar and the record actions
**Persona/goal/benefit:** As a service agent, I want one-click actions on the case, so that I close, route or assign a case fast.
**Description:** Ten record actions from plan §4.3. This story is too large to verify as one unit. Each task has its own handler class, a PHPUnit test and `getRequiredACLs()`.
**Acceptance criteria:**
1. All five tasks have passed.
2. The status bar shows Waiting, Updated, Unresolved and Solved.
**Size:** L · **Sprint:** 3 · **Depends on:** E5-S4, E8-S1 · **Assumptions:** A2, A5

##### E8-S5-T1 — Add the four status actions
**Acceptance criteria:**
1. `CaseSetStatusAction` and four subclasses exist for Waiting, Updated, Unresolved and Solved.
2. Each action sets `status` and the matching `state`.
3. Solved sets `Closed_Closed`.
4. The current status shows as pressed.
5. A user without Cases edit access cannot run the action.
**Size:** M · **Sprint:** 3 · **Depends on:** E5-S4 · **Assumptions:** A2

##### E8-S5-T2 — Add "Assign to me"
**Acceptance criteria:**
1. The action sets `assigned_user_id` to the current user.
2. The action is always visible.
3. The action sets no queue or other preset.
**Size:** S · **Sprint:** 3 · **Depends on:** E5-S4 · **Assumptions:** none

##### E8-S5-T3 — Add "Solve as CX" and "Solve as eGain"
**Acceptance criteria:**
1. Each action sets the product (Oracle Service Cloud or eGain) and the category Service Request.
2. Each action assigns the case to the current user and sets `Closed_Closed`.
3. The actions are in the More menu.
**Size:** M · **Sprint:** 3 · **Depends on:** E5-S4 · **Assumptions:** A2

##### E8-S5-T4 — Add "Route to Finance"
**Acceptance criteria:**
1. The action sets the queue Finance and `Closed_Closed`.
2. The action assigns the case to the user in the parameter `tkc.cases.finance_user_name`.
3. The parameter is in the D5 config and in `docs/admin-guide.md`.
4. The action does not forward the case by email.
**Size:** M · **Sprint:** 3 · **Depends on:** E5-S4 · **Assumptions:** A5

##### E8-S5-T5 — Add "Mark as spam" and "Not spam"
**Acceptance criteria:**
1. "Mark as spam" sets the contact type `spam`, the queue SPAM and `Closed_Closed`.
2. "Not spam" clears the contact type, sets the queue "Support 1e lijn" and sets `Open_New`.
3. Only one of the two actions shows, based on the type of the primary contact.
4. The action needs edit access to Cases and to Contacts.
5. A user without Contacts edit access cannot run either action.
**Size:** M · **Sprint:** 3 · **Depends on:** E5-S4, E1-S7-T3 · **Assumptions:** A5

#### E8-S6 — Cases: Show the subpanels
**Acceptance criteria:**
1. The record shows the subpanels Contacts, Opportunities, Tasks, History/Notes and Documents.
2. Each subpanel shows imported synthetic data.
**Size:** S · **Sprint:** 3 · **Depends on:** E8-S1 · **Assumptions:** none

---

### E9. Case thread

Goal: The OSC thread shows in the AOP record thread with an entry type and a channel. The team imports the thread.
Plan: §3 W6. Size M. Sprints 3–4.

#### E9-S1 — Case thread: Show entry type and channel on each entry
**Persona/goal/benefit:** As a service agent, I want each thread entry to show its type and channel, so that I see staff notes and customer mails apart.
**Description:** Add the two fields to the `record-thread` item and create layouts in metadata. Spike S8 sets the fallback.
**Acceptance criteria:**
1. Each thread entry shows its entry type and its channel.
2. The create layout has the entry type and the channel.
3. The fields come from the metadata, not from code, when spike S8 is a go.
**Size:** M · **Sprint:** 3 · **Depends on:** E3-S7, E1-S7-T2, E8-S1 · **Assumptions:** A17

#### E9-S2 — Case thread: Show the thread as the Messages tab
**Persona/goal/benefit:** As a service agent, I want the thread under a Messages tab, so that I read the history next to the case data.
**Acceptance criteria:**
1. The thread shows in `bottomWidgets` as the Messages tab when spike S8 is a go.
2. The thread stays in the sidebar when spike S8 is a no-go.
3. The thread loads 50 entries of a case with no error.
**Size:** S · **Sprint:** 3 · **Depends on:** E9-S1 · **Assumptions:** none

#### E9-S3 — Case thread: Style each entry by its type
**Persona/goal/benefit:** As a service agent, I want a different look for each entry type, so that I see the entry type at once.
**Acceptance criteria:**
1. `dynamicClass` adds a CSS class from the entry type.
2. Each entry type has a style from the tokens.
3. The text of each style meets a 4.5:1 contrast ratio.
**Size:** S · **Sprint:** 4 · **Depends on:** E9-S1 · **Assumptions:** none

#### E9-S4 — Case thread: Show long entries in full
**Persona/goal/benefit:** As a service agent, I want to read a long customer email in full, so that I miss no detail.
**Acceptance criteria:**
1. An entry of 1 MB shows in full.
2. The thread collapses long entries and lets the user open them.
3. The page does not freeze on a case with 200 entries.
**Size:** S · **Sprint:** 4 · **Depends on:** E3-S7, E9-S5 · **Assumptions:** A17

#### E9-S5 — Import: Map incident threads to AOP_Case_Updates
**Description:** The mapper imports all thread entries. It sets the entry type and the channel. It does not import attachments. Plan §5.2, row 7.
**Acceptance criteria:**
1. The import creates one AOP_Case_Updates row per OSC thread entry with `osc_id_c`.
2. The row links to its case.
3. The mapper sets `tkc_entry_type_c` and `tkc_channel_c` from the OSC values.
4. The import creates no file attachment and no audit history.
5. No update email goes out (Mailpit is empty).
6. A second run gives the same row count.
7. The report gives the entry count only.
**Size:** M · **Sprint:** 3 · **Depends on:** E4-S14, E3-S7, E4-S2 · **Assumptions:** A17, A18

#### E9-S6 — Case thread: Insert a standard text into a reply (conditional)
**Persona/goal/benefit:** As a service agent, I want to insert a standard text, so that I reply faster.
**Description:** The owner must give the bodies of the seven standard texts (incident Q16). If the owner does not give them, this story moves to the "Enhancements" epic.
**Acceptance criteria:**
1. The reply editor offers the seven standard texts.
2. The editor inserts the chosen text at the cursor.
3. The texts come from a config file, not from code.
4. The texts contain no customer data.
**Size:** M · **Sprint:** 4 · **Depends on:** E9-S1, owner answer to Q16 · **Assumptions:** none

---

### E10. Opportunities and quote number

Goal: The Opportunities list and record views match the mockup. The user generates the quote number with one action.
Plan: §4.4. Mockup: `designs/screens/opportunities/mockup.html`. Mapping: `docs/osc-mapping/opportunity.md`. Size M. Sprint 4.

#### E10-S1 — Opportunities: Show the record with the TKC panels
**Persona/goal/benefit:** As a sales rep, I want the opportunity in clear panels, so that I see amounts, quote and source in one view.
**Acceptance criteria:**
1. The record has the panels Overview, Quote and Source and a sidebar summary.
2. The Overview shows both amounts and both close dates.
3. `tkc_reference_c` is read-only.
4. The stage shows as a pill with the percent.
5. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 4 · **Depends on:** E3-S8, E3-S9, E5-S1, E4-S17-T2 · **Assumptions:** A7, A14

#### E10-S2 — Opportunities: Show the list with stage and quote number
**Persona/goal/benefit:** As a sales rep, I want the list to show stage and quote number, so that I track my deals.
**Acceptance criteria:**
1. The list shows name, account, stage pill with percent, amount, expected close, assigned user and quote number.
2. The list loads the 1964 imported opportunities with no error.
3. The result matches the mockup.
**Size:** S · **Sprint:** 4 · **Depends on:** E3-S8, E5-S1, E4-S17-T2 · **Assumptions:** A14

#### E10-S3 — Opportunities: Update the probability when the stage changes
**Description:** Spike S11 decides whether the edit view does this. If not, the `tkc_opportunity_won` hook sets the probability on save.
**Acceptance criteria:**
1. A change of stage sets the matching probability from `sales_probability_dom`.
2. The result is correct after save in both cases of spike S11.
3. A PHPUnit test covers the hook case.
**Size:** S · **Sprint:** 4 · **Depends on:** E3-S8, E1-S7-T5 · **Assumptions:** A14

#### E10-S4 — Opportunities: Generate the quote number with one action
**Persona/goal/benefit:** As a sales rep, I want a "Generate quote number" action, so that I get the next number without a spreadsheet.
**Description:** The handler `OpportunityQuoteNumberAction` calls `TKC\Sequence::next('quote', year)` in one transaction with a row lock. It writes `tkc_reference_c` only when empty. The number has the form `OFTYY-NNNN`.
**Acceptance criteria:**
1. The action fills an empty `tkc_reference_c` with `OFTYY-NNNN`.
2. The action does not change a filled `tkc_reference_c`.
3. Two parallel actions give two different numbers.
4. A user without Opportunities edit access cannot run the action.
5. The number restarts each year when the owner confirms that rule.
**Size:** M · **Sprint:** 4 · **Depends on:** E3-S1, E3-S8, E5-S4, E4-S16 · **Assumptions:** A7

#### E10-S5 — Opportunities: Warn about a duplicate quote number
**Acceptance criteria:**
1. `QuoteNumberUniqueValidator` shows a warning when another opportunity has the same `tkc_reference_c`.
2. The warning does not block the save.
3. A PHPUnit test covers the duplicate case.
**Size:** S · **Sprint:** 4 · **Depends on:** E10-S4 · **Assumptions:** A7

#### E10-S6 — Opportunities: Set the actual close date when the deal is won
**Description:** The `before_save` hook `tkc_opportunity_won` sets `tkc_date_closed_actual_c` to today plus one month when the stage changes to `Closed Won` and the field is empty. It does nothing in import mode.
**Acceptance criteria:**
1. A change to `Closed Won` sets the date when the field is empty.
2. A change to `Closed Won` keeps a filled date.
3. The hook does nothing in import mode.
4. A PHPUnit test covers the three cases.
**Size:** S · **Sprint:** 4 · **Depends on:** E3-S8, E4-S2 · **Assumptions:** A14

#### E10-S7 — Opportunities: Show the source case
**Acceptance criteria:**
1. The Source panel shows the source case as a link.
2. The case record shows the opportunities in the subpanel.
3. The user picks a case in edit mode.
**Size:** S · **Sprint:** 4 · **Depends on:** E10-S1, E3-S9, E8-S6 · **Assumptions:** A14

#### E10-S8 — Opportunities: Show the subpanels
**Acceptance criteria:**
1. The record shows the subpanels Contacts, Quotes, Activities, History/Notes and Documents.
2. The Quotes subpanel shows the standard AOS_Quotes columns.
**Size:** S · **Sprint:** 4 · **Depends on:** E10-S1 · **Assumptions:** A7

---

### E11. Contracts

Goal: Support and licence contracts show on the account.
Plan: §4.5. Mapping: `docs/osc-mapping/organization.md` §5a. Size S–M. Sprint 4.
Note: No mockup exists for contracts (see section 6.2).

#### E11-S1 — Contracts: Show the contract record with support and licence panels
**Persona/goal/benefit:** As a sales rep, I want a contract record with the right panels for each type, so that I see support and licence data.
**Description:** If spike S7 is a go, the record renders in Angular with a D5 delta of `module_routing` and cell `displayLogic` on `contract_type`. If not, the record is legacy with two panels that always show.
**Acceptance criteria:**
1. The record has the panels Contract, "Support contract details" and "Licence contract details".
2. With spike S7 a go, only the panel of the chosen type shows.
3. With spike S7 a no-go, both panels always show.
4. The record shows the support level, hours in contract, change threshold, Replicon project and duration.
5. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 4 · **Depends on:** E3-S10, E1-S7-T1, E4-S17-T2 · **Assumptions:** A13

#### E11-S2 — Contracts: Show the contract columns in the Accounts subpanel
**Acceptance criteria:**
1. The Accounts Contracts subpanel shows name, type, start, end and support level.
2. A click on the name opens the contract.
**Size:** S · **Sprint:** 4 · **Depends on:** E11-S1, E6-S4 · **Assumptions:** A13

---

### E12. Legacy restyle

Goal: The legacy pages use the same tokens as the Angular pages. No markup change.
Plan: §3 W9, §4.6. Mockups: `designs/screens/quotes/mockup.html`, `designs/screens/emails/mockup.html`, `designs/screens/home-dashboard/mockup.html`. These screens have no OSC counterpart. Scope is cosmetic only. Size M. Sprint 5.

#### E12-S1 — Quotes: Restyle the list and the legacy detail view
**Persona/goal/benefit:** As a sales rep, I want Quotes to look like the other screens, so that the app feels the same everywhere.
**Acceptance criteria:**
1. The list shows number, title, account, opportunity, stage pill, total and valid-until date.
2. The legacy detail view and the line items use the tokens through Dawn CSS only.
3. No legacy PHP or template file changes.
**Size:** M · **Sprint:** 5 · **Depends on:** E2-S5, E5-S1 · **Assumptions:** A7

#### E12-S2 — Emails: Restyle the legacy email pages
**Acceptance criteria:**
1. The list, folder tree, preview and compose view use the tokens through Dawn CSS only.
2. No markup changes.
3. The compose view has visible focus states.
**Size:** M · **Sprint:** 5 · **Depends on:** E2-S5 · **Assumptions:** none

#### E12-S3 — Home: Restyle the dashlets and stack them on phones
**Acceptance criteria:**
1. The standard dashlets use the tokens through Dawn CSS only.
2. The dashlets stack in one column on a phone screen (width 390 px).
3. The standard pipeline chart dashlet shows in the token colours.
**Size:** M · **Sprint:** 5 · **Depends on:** E2-S5 · **Assumptions:** A1

#### E12-S4 — Admin, Studio and global search: Apply the tokens
**Acceptance criteria:**
1. Admin, Studio and the global search page use the token font, base size, link colour and focus ring.
2. The Admin page and an Angular page show no visible seam in font or size.
**Size:** S · **Sprint:** 5 · **Depends on:** E2-S5 · **Assumptions:** none

#### E12-S5 — Theme: Check the seam between the Angular UI and the legacy UI
**Description:** The qa-engineer compares the two UIs page by page against the mockups.
**Acceptance criteria:**
1. The check covers one page per restyle story.
2. Font, base size, link colour and focus ring match on each pair of pages.
3. Each mismatch has a screenshot with synthetic data and a fix.
**Size:** S · **Sprint:** 5 · **Depends on:** E12-S1 to E12-S4 · **Assumptions:** none

---

### E13. Security, roles and real-data import

Goal: Roles, Finance protection and import guards pass the security-officer. The real data goes in after gate B and the owner's approval.
Plan: §3 W10, §5.4. Rule: `.claude/rules/data-privacy.md`. Size M. Sprints 2, 5, 6 and 7+.

#### E13-S1 — Roles: Create the TKC roles with a migration
**Persona/goal/benefit:** As an admin, I want the roles to exist after every deploy, so that access is the same in each environment.
**Description:** An idempotent extension migration creates the roles "TKC Sales", "TKC Service", "TKC Finance" and "TKC Admin". See section 6.2 for the open point on "TKC Finance". The plan puts this work in Sprint 5. This draft moves the first part to Sprint 2 because E6-S5 needs it.
**Acceptance criteria:**
1. After `tkc:config:apply`, the four roles exist.
2. A second run creates no duplicate role.
3. "TKC Sales" has view and edit access to `tkc_AccountFinance`.
4. All other roles have no access to `tkc_AccountFinance`.
5. The role table is in `docs/admin-guide.md`.
**Size:** M · **Sprint:** 2 · **Depends on:** E3-S3 · **Assumptions:** A6, A9

#### E13-S2 — Security: Test that Finance data does not leak
**Description:** The security-officer runs a leak test with a user without the Sales role. The test covers the Angular record, the list, the export, the V8 API, AOR reports and global search.
**Acceptance criteria:**
1. The user without the role gets no Finance value in each of the six read paths.
2. The test result lists each path with its outcome.
3. A failed path has a fix story before sign-off.
**Size:** M · **Sprint:** 5 · **Depends on:** E6-S5, E13-S1 · **Assumptions:** A6

#### E13-S3 — Security: Review the ACL of each process handler
**Acceptance criteria:**
1. The security-officer reads `getRequiredACLs()` of each handler class.
2. Each handler needs the same access as the manual change.
3. The review lists each handler with its result.
**Size:** S · **Sprint:** 5 · **Depends on:** E8-S5, E10-S4 · **Assumptions:** none

#### E13-S4 — Admin: Switch off geocoding and review the PII settings
**Description:** The jjwg geocode scheduler stays off. No geocoding key is set. The settings are in `docs/admin-guide.md`.
**Acceptance criteria:**
1. The jjwg geocode scheduler is inactive after `tkc:config:apply`.
2. No geocoding key is set.
3. `docs/admin-guide.md` lists both settings.
**Size:** S · **Sprint:** 2 · **Depends on:** E1-S1 · **Assumptions:** none

#### E13-S5 — Owner task: Provide the production menu ids
**Description:** **Assigned to the owner.** The mapping needs the production ids of product 551, category 552 and status 103. This needs read-only production credentials and an approved call (`--prod-approved`). Mapping: `docs/osc-mapping/incident.md` Q21. SUITE-7 is related.
**Acceptance criteria:**
1. The owner approves each production call in a comment.
2. The ids of the menu values are in `extensions/tkc/config/tkc_import_maps.php` (ids only, no record data).
3. The mapping status in `docs/osc-mapping/incident.md` shows the ids as verified.
**Size:** S · **Sprint:** 3 · **Depends on:** SUITE-7 · **Assumptions:** A18

#### E13-S6 — Security gate B: Approve the guards and the data minimisation for real data
**Description:** The security-officer checks the guards in the target environment and the data minimisation. The owner approves the run. Plan §5.4 step 4.
**Acceptance criteria:**
1. The security-officer confirms each guard in E4-S2 in the target environment.
2. The security-officer confirms that the import reads only the fields in the approved mapping.
3. The security-officer confirms that the role model and the Finance test (E13-S2) passed.
4. The owner approves the real-data run in a comment.
**Size:** S · **Sprint:** 6 · **Depends on:** E13-S2, E13-S3, E13-S5, E4-S17 · **Assumptions:** A6, A18, A19

#### E13-S7 — Import: Run the real-data dry run
**Description:** The team runs `tkc:osc:import --dry-run` on the real extract. The report has counts and unmapped values only.
**Acceptance criteria:**
1. The dry run writes no record.
2. The report gives counts per entity and the unmapped values.
3. The report has no field content.
**Size:** M · **Sprint:** 6 · **Depends on:** E13-S6 · **Assumptions:** A18

#### E13-S8 — Release: Run the full regression, the UAT and the release notes
**Description:** The qa-engineer runs all tests. The owner runs UAT. The document-agent writes `docs/release-notes.md`.
**Acceptance criteria:**
1. All unit tests, contract tests and the idempotency test pass.
2. Each screen passes the browser check against its mockup.
3. The owner signs the UAT result in a comment.
4. `docs/release-notes.md` lists the change per sprint.
**Size:** M · **Sprint:** 6 · **Depends on:** E12-S5, E13-S2 · **Assumptions:** none

#### E13-S9 — Performance: Measure list and record load times at full volume
**Acceptance criteria:**
1. The measure gives the load time of each of the four lists with the full anonymized data.
2. The measure gives the load time of the Case record with its thread.
3. A value above the limit that the owner sets has a fix story.
**Size:** M · **Sprint:** 5 · **Depends on:** E8-S2, E9-S5, E10-S2 · **Assumptions:** A18

#### E13-S10 — Import: Run the real import and the cut-over checklist
**Description:** Run only after gate B, the dry run, the UAT and the owner's approval. The cut-over checklist covers sequences, users and the OSC read-only date.
**Acceptance criteria:**
1. The real run creates the counts that the dry run reported.
2. Mailpit or the sink holds no customer email after the run.
3. The sequences are above the highest imported value (E4-S16).
4. The owner signs the cut-over checklist.
5. The document-agent updates `scripts/seed/README.md` and the mapping status.
**Size:** M · **Sprint:** 7+ · **Depends on:** E13-S6, E13-S7, E13-S8 · **Assumptions:** A18, A19

---

### E14. Azure environment (later)

Goal: A shared environment runs the same image and the same build chain as Docker.
Plan: §3 W13. Resource group: `TKC-SUITE-CRM-RG`. Size M–L. Sprint 7+.

#### E14-S1 — Azure: Build the application image
**Acceptance criteria:**
1. The image uses PHP 8.3 and the same extensions as the Docker dev setup.
2. The image holds no secret.
3. The image starts and shows the login page.
**Size:** M · **Sprint:** 7+ · **Depends on:** E1-S1 · **Assumptions:** none

#### E14-S2 — Azure: Create the managed MySQL 8.0 database
**Acceptance criteria:**
1. Azure Database for MySQL 8.0 runs in the resource group.
2. The database accepts a connection from the application only.
3. The credentials are in the vault, not in git.
**Size:** M · **Sprint:** 7+ · **Depends on:** E14-S1 · **Assumptions:** none

#### E14-S3 — Azure: Run the build chain in CI
**Acceptance criteria:**
1. CI runs the build chain from E1-S1 on PHP 8.3.
2. CI runs the PHPUnit, Karma and contract tests.
3. CI fails on a theme generator diff (E2-S1).
**Size:** M · **Sprint:** 7+ · **Depends on:** E14-S1 · **Assumptions:** none

#### E14-S4 — Azure: Deploy with a reviewed Repair
**Acceptance criteria:**
1. The pipeline runs `tkc:repair --dry-run` and shows the SQL.
2. A person approves the SQL before `--execute` runs.
3. The pipeline runs `tkc:config:apply` and `cache:clear` after Repair.
**Size:** M · **Sprint:** 7+ · **Depends on:** E14-S2, E14-S3 · **Assumptions:** none

#### E14-S5 — Azure: Review the environment for security
**Acceptance criteria:**
1. The security-officer checks access, secrets, network and outbound email in the environment.
2. The outbound email host is on the sink allowlist until the owner approves real mail.
3. Each finding has a fix.
**Size:** S · **Sprint:** 7+ · **Depends on:** E14-S4 · **Assumptions:** A19

---

### EN. Enhancements (not scheduled)

These items are deferred by A1 and the plan. Each has no OSC counterpart. The owner schedules them. Each becomes a story with its own AC when scheduled. They are listed as placeholders and do not count in the totals.

| ID | Title | Plan reference | Size |
|---|---|---|---|
| EN-1 | Opportunities: Show a pipeline board (new Angular view) | A1 | L |
| EN-2 | Lists: Show a totals row (backend aggregate and list footer) | A1 | M |
| EN-3 | Home: Add the "My day" dashlet | A1 | M |
| EN-4 | Home: Show pills in the dashlets | §4.6 | S |
| EN-5 | Case thread: Add CC and BCC fields | §3 W6 | M |
| EN-6 | Case thread: Import file attachments to Notes | A17 | L |
| EN-7 | Cases: Add the Disposition field | A10 | S |
| EN-8 | Cases: Add the assigned-group field and the "queue by group" hook | A5 | M |

---

## 4. Sprint plan

Sprints last two weeks. One agent team works in parallel: backend work (E3, E4) and frontend work (E2, E5) run side by side in Sprints 0 to 2.

### Sprint 0: Foundation and tokens

**Goal:** The team builds from a clean checkout, the tokens drive both UIs, and the five spikes have answers.
**Stories:** E1-S1 to E1-S6, E1-S7 (T1 to T5), E2-S1, E2-S2, E2-S5.
**Demo:** A clean Docker install runs `scripts/build.sh`. The Repair SQL is on screen. An Angular page and the legacy Admin page show the same tokens. Each spike result is on the issue. The test command is green. Mailpit shows a test email.
**Owner decisions before the sprint:** A8, A12 (naming rule text), A19, A20. SUITE-9 must be done.
**Dependency check:** E1-S3 and E1-S4 depend on E1-S1, in the same sprint (order inside the sprint). E2-S2 and E2-S5 depend on E2-S1, in the same sprint. No story needs a later sprint.

### Sprint 1: Data model, import framework and gate A

**Goal:** All TKC fields exist after Repair, the guarded import runs twice on synthetic data with the same counts, and gate A is open.
**Stories:** E2-S3, E2-S4, E2-S6, E3-S1 to E3-S12, E4-S1 (T1 to T4), E4-S2 to E4-S7, E4-S9, E4-S10, E4-S11, E4-S8.
**Demo:** Quick Repair shows no change on the second run. The synthetic import of Accounts, Contacts and Finance rows runs twice with the same counts. Mailpit stays empty. The anonymizer passes the leak test. The security-officer opens gate A. The owner starts the extract.
**Owner decisions before the sprint:** All of A1 to A18, because E3 builds all vardefs. Plus the staff-map format (E4-S9), the production volumes and the extract method (A18).
**Dependency check:** E3 depends on E1 (Sprint 0). E4-S4 needs E4-S10 and E4-S11 (same sprint, order inside). E4-S8 needs E4-S7 (same sprint, last). E2-S3 needs E2-S2 (Sprint 0).
**Risk:** This sprint is the largest. This draft moves the mappers for Opportunities, Contracts and Cases to Sprint 2. See section 6.2.

### Sprint 2: Accounts, Contacts and the anonymized data

**Goal:** Accounts and Contacts show as in the mockups on anonymized full-volume data, and the Finance data is visible to the Sales role only.
**Stories:** E4-S12 to E4-S16, E4-S17 (T1 to T3), E5-S1 to E5-S6, E6-S1 to E6-S5, E7-S1 to E7-S4, E13-S1, E13-S4.
**Demo:** About 8 700 anonymized accounts and contacts in the redesigned lists and records. The pills and the Yes/No controls work. The SPAM filter works. A Sales user sees the Finance tab and another user does not. All entities, including cases, are in the database.
**Owner decisions before the sprint:** Finance field meaning and Replicon/SharePoint URL templates (org Q6, Q12, Q4), CRM modules (org Q5, contact Q2), the consent model and function-level labels (contact Q8, Q13, Q14), the staff-map owner for inactive staff.
**Dependency check:** E5 needs E2-S3 (Sprint 1) and spikes S9 and S10 (Sprint 0). E6-S5 needs E13-S1 (same sprint, order inside). E6 and E7 list stories need E4-S17-T1 (same sprint, first). E4-S8 (Sprint 1) must be done before E4-S17.

### Sprint 3: Cases and the thread import

**Goal:** Cases show as in the mockup with the status bar and the record actions, the thread is imported, and the import sends no email.
**Stories:** E8-S1 to E8-S6 (E8-S5 with T1 to T5), E9-S1, E9-S2, E9-S5, E13-S5.
**Demo:** 4 053 anonymized cases with their threads. The status bar, "Solve as CX", "Solve as eGain", "Route to Finance" and "Mark as spam" work. A new case gets a reference. Mailpit is empty after the thread import.
**Owner decisions before the sprint:** The case decisions A2, A4, A5, A10, A11 and the "Assign to me" presets (incident Q3 to Q7). The production menu ids (E13-S5, owner task). The AOP email rule on a staff reply and the inbound mailbox rule (incident Q14, Q20).
**Dependency check:** E8 needs E3 (Sprint 1), E4-S14 (Sprint 2), E5-S4 (Sprint 2) and spikes S8 and S9 (Sprint 0). E9-S5 needs E4-S14 (Sprint 2). The extract must hold the threads (E4-S8).

### Sprint 4: Opportunities, contracts and the styled thread

**Goal:** Opportunities show with the quote number action and the source case, contracts show on the account, and thread entries show by type.
**Stories:** E9-S3, E9-S4, E9-S6 (conditional), E10-S1 to E10-S8, E11-S1, E11-S2.
**Demo:** An opportunity gets a quote number with one action. The stage pill shows the percent. A contract shows on the account with the right panels. A thread shows styled entries. Standard texts show if the owner gave the bodies.
**Owner decisions before the sprint:** The standard text bodies (incident Q16), the yearly restart of the quote number, the need for AOS quotes, the two amounts and dates, the approach field, the won-date rule (opp Q4, Q5, Q6, Q15), the contract questions (org Q17).
**Dependency check:** E10 needs E3-S8, E3-S9 (Sprint 1), E5-S4 (Sprint 2), E4-S16 (Sprint 2) and spike S11 (Sprint 0). E11-S1 needs spike S7 (Sprint 0). E11-S2 needs E6-S4 (Sprint 2). E9-S4 needs E9-S5 (Sprint 3).

### Sprint 5: Legacy restyle, security checks and performance

**Goal:** All screens use the tokens, the security-officer passes the Finance leak test and the handler review, and the lists load fast at full volume.
**Stories:** E12-S1 to E12-S5, E13-S2, E13-S3, E13-S9, E5-S7 (optional).
**Demo:** Quotes, Emails, Home and Admin in the new look. The seam check passes. The leak test result for six read paths. The load-time table.
**Owner decisions before the sprint:** The email view in use, the dashboard tabs per role, the use of the Activity Stream (emails and home mockup notes), the security acceptance of A6, the credential drops and the import guards.
**Dependency check:** E12 needs E2-S5 (Sprint 0) and E5-S1 (Sprint 2). E13-S2 needs E6-S5 and E13-S1 (Sprint 2). E13-S3 needs E8-S5 (Sprint 3) and E10-S4 (Sprint 4). E13-S9 needs E8-S2, E9-S5 and E10-S2 (Sprints 3 and 4).

### Sprint 6: Gate B, dry run and UAT

**Goal:** The build is UAT-ready, gate B is passed, and the real-data dry run has a report.
**Stories:** E13-S6, E13-S7, E13-S8.
**Demo:** The UAT build. The dry-run report with counts and unmapped values only. The release notes.
**Owner decisions before the sprint:** Approval of the real-data dry run. UAT dates.
**Dependency check:** E13-S6 needs E13-S2, E13-S3 (Sprint 5), E13-S5 (Sprint 3) and E4-S17 (Sprint 2). E13-S8 needs E12-S5 (Sprint 5).

### Sprint 7+: Real import, Azure and enhancements

**Goal:** The real data is in the system, and a shared Azure environment runs the same chain.
**Stories:** E13-S10, E14-S1 to E14-S5. Enhancements EN-1 to EN-8 as the owner schedules them. A cut-over rehearsal.
**Demo:** The cut-over checklist is signed. The Azure environment shows the login page and the migrated data.
**Owner decisions before the sprint:** The scope of the enhancements. The cut-over date.
**Dependency check:** E13-S10 needs E13-S6, E13-S7 (Sprint 6) and E13-S8 (Sprint 6). E14 needs E1-S1 (Sprint 0).

---

## 5. Decisions needed from the owner

### 5.1 Assumptions (the draft uses the default)

| # | Default in this draft |
|---|---|
| A1 | Defer the pipeline board, the totals row and the "My day" dashlet. They are in EN. |
| A2 | Keep all 12 OSC case statuses. Priority P1 to P4. |
| A3 | Nullable Yes/No fields use a three-state enum with `enum-radio`. |
| A4 | A required primary contact on cases (new 1:N relate). The M:N link stays. |
| A5 | Queue only. No assigned-group field. |
| A6 | Finance fields go to the module `tkc_AccountFinance` with module ACL. |
| A7 | The quote number stays on the opportunity. A record action generates it. No quotes are imported. |
| A8 | TKC fields on core modules use `tkc_<english_name>_c`. |
| A9 | Only the Finance fields depend on the role. |
| A10 | Do not build the Disposition field. |
| A11 | Customer priority is text. Drop the low-use case fields. |
| A12 | One case reference field `tkc_case_ref_c`. |
| A13 | Contracts use AOS_Contracts. Angular if spike S7 is a go. |
| A14 | Keep both amounts and both close dates. Drop three OSC fields. |
| A15 | Import all five phone types and all SPAM contacts. |
| A16 | Keep the CRM modules field. Drop the credential fields. |
| A17 | Import all thread entries. Do not import attachments or audit history. |
| A18 | The owner runs the OSC bulk extract. |
| A19 | Mailpit in Docker. The import refuses to run without a sink host. |
| A20 | Base currency EUR. Number format nl-NL. UI language en_us. |

### 5.2 Blocking questions per sprint

| Before sprint | Question | Epic |
|---|---|---|
| 0 | Confirm A8, A12, A19, A20. Finish SUITE-9. | E1 |
| 1 | Confirm A1 to A18 (all vardefs are built in this sprint). | E3 |
| 1 | Production volumes, extract method, thread counts (A18). | E4 |
| 1 | Staff map: login or email. Owner of the inactive staff records. | E4 |
| 2 | Finance field meaning. Replicon and SharePoint URLs. Site Werkzaamheden report. | E6 |
| 2 | CRM modules: keep or drop. | E6, E7 |
| 2 | Consent model, function-level labels, SPAM import. | E7 |
| 3 | Case decisions A2, A4, A5, A10, A11. "Assign to me" presets. | E8 |
| 3 | Production ids of product 551, category 552 and status 103. | E8 |
| 3 | AOP email on a staff reply. Inbound mailbox case rule. Short source and channel lists. | E8, E9 |
| 4 | Bodies of the standard texts 10, 20, 33, 40, 41, 341 and 371. | E9 |
| 4 | Quote number yearly restart. AOS quotes. Two amounts and dates. Approach. Won-date rule. | E10 |
| 4 | `Kanalen` against support level. Units of `Duur` and `ChangeDrempel`. `DatumRenewal`. Contract status. Incident link. | E11 |
| 5 | Email view in use. Dashboard tabs per role. Activity Stream in use. | E12 |
| 5 | Security acceptance of A6, the credential drops and the guards. | E13 |
| 6 | Approval of the real-data dry run. | E13 |

---

## 6. Totals and gaps in the plan

### 6.1 Totals

| Item | Count |
|---|---|
| Epics (E1 to E14) | 14, plus the unscheduled "Enhancements" bucket (8 placeholders, not counted below) |
| Stories | 100 (E1: 7, E2: 6, E3: 12, E4: 17, E5: 7, E6: 5, E7: 4, E8: 6, E9: 6, E10: 8, E11: 2, E12: 5, E13: 10, E14: 5) |
| Stories that have tasks | 5 (E1-S7, E4-S1, E4-S14, E4-S17, E8-S5) |
| Tasks | 20 (5 spikes, 4 import-command tasks, 3 case-mapper tasks, 3 import-run tasks, 5 case-action tasks) |
| Owner-assigned stories | 3 (E4-S8 extract, E4-S9 staff map, E13-S5 production ids) |
| Security-officer gates | 2 (E4-S7 gate A, E13-S6 gate B) |

Effort per sprint. The count uses leaf items only (a story with tasks counts through its tasks). Weights: S = 1 day, M = 2 days, L = 5 days. These weights are a rough guide, not an estimate.

| Sprint | Leaf items (S / M) | Rough days | Note |
|---|---|---|---|
| 0 | 4 S, 10 M | 24 | Five spikes |
| 1 | 13 S, 16 M | 45 | Highest load. Backend (E3, E4) and frontend (E2) run in parallel. |
| 2 | 12 S, 15 M | 42 | High load. Import, shared components, two screens. |
| 3 | 4 S, 10 M | 24 | One screen, ten actions, thread import |
| 4 | 9 S, 4 M | 17 | |
| 5 | 4 S, 5 M | 14 | One optional story (E5-S7) |
| 6 | 1 S, 2 M | 5 | Gate B, dry run, UAT |
| 7+ | 1 S, 5 M | 11 | Real import and Azure |

Sprints 1 and 2 carry about twice the load of Sprint 3. Options: move E2-S3 and E2-S4 into Sprint 0 (about 3 days), and move E5-S6 and E7-S4 into Sprint 3. The owner and the team decide.

### 6.2 Gaps and inconsistencies found in the plan

1. **Sprint 1 demo and mapper scope.** Plan §8 puts the mappers outside "E4 part 1" but the Sprint 1 demo says "synthetic import of all entities". This draft imports Accounts, Contacts and Finance rows in Sprint 1. It moves the Opportunity, Contract and Case mappers to Sprint 2.
2. **Decision dates.** Plan §10 needs the case, opportunity and contract answers in Sprints 3 and 4. E3 builds all vardefs in Sprint 1. The answers (A2 to A16) must be known before Sprint 1.
3. **Roles in Sprint 2.** The Sprint 2 demo says "Finance visible to Sales only". The plan schedules roles in Sprint 5. This draft moves the roles story (E13-S1) to Sprint 2. The leak test stays in Sprint 5.
4. **Role "TKC Finance".** Plan §3 W10 lists four roles but A6 and A9 give no permission set for "TKC Finance" and "TKC Service". E13-S1 must have the permission set from the owner.
5. **Handler count.** Plan §3 says "about 12" handlers and plan §7 says 11 for E8. Plan §4.3 lists 10 case handlers (4 + 1 + 2 + 1 + 2, plus 3 abstract bases) and 1 opportunity handler.
6. **Thread fields.** Plan §3 W6 and §5.2 name `tkc_entry_type_c`, `tkc_channel_c` and `osc_id_c` on AOP_Case_Updates. Plan §4 has no field list and no value lists for entry type and channel. E3-S7 needs them from the architect and the osc-analyst.
7. **Two priority fields on cases.** Plan §4.3 has core `priority` (P1 to P4) and `tkc_priority_c` (impact list, 4 values). The difference is not stated in the plan. Ask the owner.
8. **Case menu ids.** Plan §10 needs the production ids (incident Q21) in Sprint 3. The Sprint 2 anonymized case import also uses these maps. This draft uses the tst1 ids for the sample and the production ids before gate B (E13-S5).
9. **Extract content.** The thread import (Sprint 3) needs the threads in the Sprint 1 extract. Plan §10 asks for the thread count only. E4-S8 lists the threads as required content.
10. **Gate A order.** Plan §5.4 puts the extract after gate A. E4-S8 follows that order, so the extract is the last item of Sprint 1. A delay moves the Sprint 2 import.
11. **Contracts mockup.** No mockup exists for contracts. E11-S1 has no mockup link. The ux-designer may need a small mockup before Sprint 4.
12. **Start state.** Plan §3 W1 says D1, D9, D10 and D12 are done. `docs/backlog.md` still shows the `extensions/tkc/` scaffold as open, and SUITE-9 is open. E1 depends on SUITE-9.
13. **Stale backlog phases.** `docs/backlog.md` still shows phases 3 to 5 as "to do". The document-agent updates the narrative when the owner approves this draft.
14. **Module Builder files.** `tkc_AccountFinance` comes from Module Builder (D11). The plan does not say where the generated files live in git. E3-S3 requires the files in `public/legacy/custom/`. The architect must confirm that this is upgrade-safe.
