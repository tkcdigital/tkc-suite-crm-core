# TKC SuiteCRM: draft backlog and sprint plan

> Status: **Approved and loaded into SUITE on 2026-11-20 (see the last section). The planning tool is the truth for status.**
> Author: product-owner. Date: 2026-11-20. Planning epic: SUITE-25.
> Updated 2026-11-20: the owner accepted A1 to A20, and the architect added plan §11 (addendum). This draft applies both.
> Updated 2026-11-20 (owner inputs): production volumes, a read-only OSC database as the extract source, inactive users for former staff, and the role decisions. See §0.
> Source: `docs/plan/implementation-plan.md` (called "the plan" below). Narrative: `docs/backlog.md`.
> Open issues that this draft does not duplicate: SUITE-7 (OSC REST verification) and SUITE-9 (extension check). Stories that need them list them as dependencies.

---

## 0. Owner inputs of 2026-11-20 (applied in this draft)

1. **Production volumes (OSC production, not tst1).** Incidents 74,142. Contacts 9,232. Organizations 796. Opportunities 2,159. The thread volume is unknown and will be large. Stories that import or list data use these figures. The import must run in batches. The run time is measured at production scale. The case import and the thread import must be resumable.
2. **Extract source.** The OSC extract is a database. The owner gives the agents read-only access. The credentials come through Proton Pass and `pass-cli`. Agents never see a value. The owner JSONL extract (old A18) is gone. The owner story E4-S8 now provides the database access. The new story E4-S18 builds a reader for that database. **Gate A (E4-S7) must pass before any agent reads the database.** The anonymizer reads from the database and writes anonymized output to `data/osc/anon/`.
3. **Former OSC staff.** The admin disables the accounts of past staff. The staff map creates or links **inactive SuiteCRM users** for former staff. The single `tkc_migration` user is gone.
4. **Role decisions.** Admins can see the Finance data. The case priority starts empty. The MVP has no Finance role, Export is None and Cases delete is None. Later iterations are logged as SUITE-26 (configurable roles), SUITE-27 (export per role) and SUITE-28 (delete per role). They have the label "Backlog" and no sprint. This draft does not duplicate them.

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
| E4 | OSC import framework and anonymized sample | Import OSC data with guards. Run the anonymized full-volume import. | L | 1–2 (contract import in 4) | E3 |
| E5 | Shared UI components | Build the parts that more than one screen uses. | M | 2 (one optional story in 5) | E2, E3, spikes S9 and S10 |
| E6 | Accounts screen | Show the Accounts screen as in the mockup. | M | 2 | E3, E5, E13-S1 |
| E7 | Contacts screen | Show the Contacts screen as in the mockup. | S–M | 2–3 | E3, E5 |
| E8 | Cases screen and actions | Show the Cases screen as in the mockup. Add the status bar and the record actions. | L | 3 | E3, E4, E5, spikes S8 and S9 |
| E9 | Case thread | Show the OSC thread in the AOP record thread. Import the thread. | M | 3–4 | E3, E8 |
| E10 | Opportunities and quote number | Show the Opportunities screen. Generate the quote number. | M | 4 | E3, E5 |
| E11 | Contracts | Show support and licence contracts on the account. | S–M | 4 | E6, E4, spike S7 (builds its own contract vardefs) |
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
**Size:** S · **Sprint:** 0 · **Depends on:** none (A8 and A12 accepted) · **Assumptions:** A8, A12

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
**Size:** M · **Sprint:** 0 · **Depends on:** E2-S2 · **Assumptions:** none

#### E2-S4 — Login: Show the TKC login page with the company logo
**Persona/goal/benefit:** As any user, I want a branded login page, so that I know I am in the TKC system.
**Acceptance criteria:**
1. The login page shows the `company_logo` setting.
2. The login page uses the token colours and fonts.
3. The user signs in with the keyboard only.
**Size:** S · **Sprint:** 0 · **Depends on:** E2-S2 · **Assumptions:** none

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
Note: The owner accepted A1 to A20 on 2026-11-20 (plan §11.1). Fields that fix a type, a key set, a relationship or a name are built in this sprint. "Keep or drop" fields are not built; they come later with `--only-fields` (E4-S1-T5). The contract fields are built in E11-S2 (Sprint 4), not here.

**Rule for all TKC relationships (plan §11.6):** Each TKC relationship is a 1:N foreign-key relationship declared in a vardef. No join table. Reason: `custom/metadata/` is gitignored, so Studio and Module Builder relationship files would be lost.

#### E3-S1 — Sequences: Add the `tkc_sequences` table and the `TKC\Sequence` service
**Description:** A transactional table holds counters per name and year. The vardef is in `custom/Extension/application/Ext/TableDictionary/tkc_sequences.php`. `public/legacy/custom/include/TKC/Sequence.php` gives the next value with a row lock. Plan: §4, §6.
**Acceptance criteria:**
1. `tkc:repair --dry-run` proposes the table `tkc_sequences` from the vardef in `TableDictionary`, and `--execute` creates it.
2. `TKC\Sequence::next('quote', 2026)` gives a value that is one higher than the last value.
3. Two parallel calls give two different values (PHPUnit test).
4. A new year starts a new counter.
5. The service can set a counter to a given start value.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S3 · **Assumptions:** A7, A12

#### E3-S2 — Accounts: Add the TKC fields and lists
**Description:** Add the Accounts fields from plan §4.1. Override `account_type_dom`. Do not build `tkc_crm_modules_c` (plan §11.7). Mapping: `docs/osc-mapping/organization.md`.
**Acceptance criteria:**
1. After Repair, Accounts have the fields in plan §4.1 with the given types and lengths. The Finance fields and `tkc_crm_modules_c` are not on Accounts.
2. `account_type_dom` has the keys `Lead`, `Prospect`, `Customer`, `Supplier`, `Relation` and `FormerCustomer`. The default is `Relation`.
3. The label of `Customer` is "Customer".
4. `tkc_yes_no_list` has the keys `yes` and `no`. The empty value means not set.
5. `tkc_auto_upgrade_c` has the default `no`.
6. `tkc_debiteurnummer_c` has an index and is audited.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A3, A8, A16

#### E3-S3 — Accounts: Add the `tkc_AccountFinance` module with hand-written files
**Description:** Write the module files by hand. Do not use Module Builder, because its files are not in git (plan §11.6). The module holds the six Finance fields. One row exists per account. The module ACL protects the data. Plan: A6, §4.1, §11.6.
**Acceptance criteria:**
1. The files listed in plan §11.6 exist in git under `public/legacy/modules/tkc_AccountFinance/`, `public/legacy/custom/Extension/` and `extensions/tkc/Migrations/`.
2. After Repair, the table `tkc_accountfinance` has the six audited fields and an indexed `account_id`.
3. The 1:N relationship `tkc_accounts_tkc_accountfinance` is declared in the module vardef. No file exists in `custom/metadata/`.
4. `Menu.php` is empty and the module label is "Finance".
5. The idempotent migration `SeedFinanceAcl` registers the module ACL actions. A second run changes nothing.
6. A record that opens from the Accounts subpanel gets an Angular route while the navbar does not show the module.
7. `docs/admin-guide.md` records the Display Modules setting.
8. The metadata contract test passes for the module.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A6

#### E3-S4 — Contacts: Add the TKC fields and lists
**Description:** Add the Contacts fields from plan §4.2. Do not build `tkc_crm_modules_c` (plan §11.7). Mapping: `docs/osc-mapping/contact.md`.
**Acceptance criteria:**
1. After Repair, Contacts have the fields in plan §4.2, except `tkc_crm_modules_c`.
2. `tkc_contact_type_c` has an index. Its list has the key `spam`.
3. The dropdown labels contain no customer names.
4. The six program fields use `tkc_yes_no_list`.
5. `tkc_function_level_c` has the keys C, M and O.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4, E3-S2 · **Assumptions:** A3, A8, A15, A16

#### E3-S5 — Cases: Add the TKC fields and lists
**Description:** Add the Cases fields from plan §4.3 and override the status, priority and type lists. Priority and Impact are two different fields (plan §11.5). Mapping: `docs/osc-mapping/incident.md`.
**Acceptance criteria:**
1. After Repair, Cases have the fields in plan §4.3.
2. `case_status_dom` has 12 keys. The 8 new keys use the prefix `Open_` or `Closed_`.
3. `case_priority_dom` has the keys P1, P2, P3 and P4 with the labels "Priority 1" to "Priority 4". The label of the field is "Priority".
4. The default of `priority` is empty. The story checks the core code that reads `case_priority_default_key` and records the result.
5. `tkc_priority_c` has the label "Impact". Its list `tkc_impact_list` has the keys `no_impact`, `some_impact`, `production_disrupted` and `immediate_action`. The default is empty.
6. `tkc_case_ref_c` is a varchar 20 field with an index.
7. `tkc_queue_c` has 12 values and an index.
8. `tkc_product_c` and `tkc_category_c` are not required.
9. No field exists for Disposition, an assigned group or `mailHeader`.
**Size:** M · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** A2, A5, A8, A10, A11, A12

#### E3-S6 — Cases: Add the primary contact relationship
**Description:** Add the required relate field `tkc_primary_contact_c` and the relationship `tkc_contacts_cases_primary`. The core M:N relationship stays.
**Acceptance criteria:**
1. After Repair, the 1:N relationship `tkc_contacts_cases_primary` links one contact to many cases. It is declared in `custom/Extension/modules/Cases/Ext/Vardefs/`.
2. The field `tkc_primary_contact_id_c` has an index.
3. The field is required in the edit view metadata.
4. The relationship `contacts_cases` still exists.
**Size:** S · **Sprint:** 1 · **Depends on:** E3-S5 · **Assumptions:** A4

#### E3-S7 — Case thread: Add the thread fields to AOP_Case_Updates
**Description:** Add the entry type, the channel and the import key to AOP_Case_Updates. Change `description` to `longtext`. Plan §11.4 gives the fields and the lists. The files are `Vardefs/tkc_thread.php` and `Language/en_us.tkc_thread.php` in `public/legacy/custom/Extension/modules/AOP_Case_Updates/Ext/`.
**Acceptance criteria:**
1. After Repair, AOP_Case_Updates has `tkc_entry_type_c` (enum, 20, default `staff`), `tkc_channel_c` (enum, 20, default empty) and `osc_id_c` (varchar 20).
2. `tkc_entry_type_list` has the keys `note`, `staff`, `customer` and `customer_proxy`.
3. `tkc_channel_list` has the keys `email`, `phone`, `web`, `chat`, `fax`, `social` and `other`.
4. `osc_id_c` has the non-unique index `idx_aop_case_updates_osc_id_c`.
5. The column `description` is `longtext`.
6. A test entry of 1 MB saves and loads without loss.
7. The Repair SQL for the `description` change is on the issue.
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
1. After Repair, the 1:N relationship `tkc_cases_opportunities` links one case to many opportunities. It is declared in `custom/Extension/modules/Opportunities/Ext/Vardefs/`. No join table exists.
2. The field `tkc_source_case_id_c` has an index.
3. The Cases record shows the opportunity in a subpanel definition.
**Size:** S · **Sprint:** 1 · **Depends on:** E3-S5, E3-S8 · **Assumptions:** A14

#### E3-S10 — Import key: Add `osc_id_c` with an index to each imported module
**Description:** Add `osc_id_c` (varchar 20) and the index `idx_<table>_osc_id_c` to Accounts, Contacts, Cases and Opportunities. AOP_Case_Updates is in E3-S7. AOS_Contracts is in E11-S2.
**Acceptance criteria:**
1. Each of the four modules has `osc_id_c` after Repair.
2. Each module has a non-unique index on the field.
3. The vardef file is `Vardefs/tkc_osc_id.php` in each module.
**Size:** S · **Sprint:** 1 · **Depends on:** E1-S4 · **Assumptions:** none

#### E3-S11 — Repair: Review and run the Repair for all vardefs
**Description:** The backend engineer reviews the SQL from `tkc:repair --dry-run`. The engineer runs Repair on a clean database. The security-officer reads the SQL for `ALTER` on core columns.
**Acceptance criteria:**
1. The Repair SQL is on the issue.
2. The SQL changes only the expected columns, including `opportunities.name` and `aop_case_updates.description`.
3. A second Repair run proposes no change.
4. The metadata contract test (E1-S4) passes for each module.
5. No TKC relationship file exists in `custom/metadata/`.
6. `docs/data-model.md` lists every field, list and relationship.
**Size:** S · **Sprint:** 1 · **Depends on:** E3-S1 to E3-S10 · **Assumptions:** none

---

### E4. OSC import framework and anonymized sample

Goal: An idempotent, guarded, resumable import. The anonymized sample runs at production volume. Real data does not enter before gate B.
Plan: §3 W8, §5. Rules: `.claude/rules/data-privacy.md`, `.claude/rules/osc-access.md`. Size L. Sprints 1–2.
Volumes (OSC production): Incidents 74,142. Contacts 9,232. Organizations 796. Opportunities 2,159. Thread entries: unknown, large. Source: a read-only OSC database (E4-S8, E4-S18). Gate A (E4-S7) comes before any agent reads it.

#### E4-S1 — Import: Build the import command, the upserter and the checkpoint
**Description:** `tkc:osc:import <entity|all> --source=<dir|db> [--dry-run|--execute] [--limit=n] [--since=<osc-id>]` reads JSONL (fixtures and anonymized output) or the OSC database reader (E4-S18) and upserts by `osc_id_c`. Plan: §5.1. This story is too large to verify as one unit.
**Acceptance criteria:**
1. All five tasks have passed.
2. A run without `--execute` writes no record.
3. A run with a bad option stops with a clear message.
**Size:** L · **Sprint:** 1 · **Depends on:** E3-S10 · **Assumptions:** none

##### E4-S1-T1 — Add the command and the JSONL reader
**Acceptance criteria:**
1. The command `tkc:osc:import` exists in `extensions/tkc/backend/Import/Console/OscImportCommand.php`.
2. The JSONL reader reads one JSONL file per entity. The reader streams the file and does not load it into memory.
3. The command rejects a source directory that is outside `data/` or `scripts/seed/fixtures/`.
4. The command accepts `--source=db` and uses the reader from E4-S18 for it.
5. A dry run prints the counts of records that it would create and update.
**Size:** M · **Sprint:** 1 · **Depends on:** E3-S10 · **Assumptions:** A18

##### E4-S1-T2 — Add the upserter
**Acceptance criteria:**
1. The upserter finds a record with `osc_id_c = ? AND deleted = 0`.
2. The upserter creates the record when none exists and updates it when one exists.
3. The upserter saves in batches. The batch size is a config value with the default 200.
4. The upserter keeps memory use flat over the whole run. A test with 50,000 synthetic records shows it.
5. A PHPUnit test shows that two runs give the same record count.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1-T1 · **Assumptions:** none

##### E4-S1-T3 — Add the checkpoint and the log
**Acceptance criteria:**
1. The command writes a checkpoint in `data/osc/import-state/` after each batch.
2. A new run with the same source continues from the checkpoint. A test stops a run in the middle and resumes it. The final counts are the same as for an uninterrupted run.
3. The log in `data/osc/import-logs/<run>.log` has counts and IDs only.
4. A test shows that the log has no field content.
5. The log gives the run time and the record count of each batch.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1-T2 · **Assumptions:** none

##### E4-S1-T4 — Add the staff map and the inactive users for former staff
**Description:** The admin disables the accounts of past OSC staff. The importer creates or links an inactive SuiteCRM user for each former staff member. Records keep their original owner and author.
**Acceptance criteria:**
1. The command reads `data/osc/staff-map.csv` (OSC account id to `user_name`).
2. The importer links an OSC account id in the map to the SuiteCRM user in the map. The user can be active or inactive.
3. The importer creates an inactive SuiteCRM user for an OSC account id that is not in the map and has no user. The `user_name` is derived from the OSC account id.
4. An inactive user created by the importer cannot sign in, has no role and gets no email.
5. A second run creates no duplicate user.
6. No user `tkc_migration` exists.
7. The run report gives the count of linked users and of created inactive users, not their names.
**Size:** S · **Sprint:** 1 · **Depends on:** E4-S1-T2 · **Assumptions:** none

##### E4-S1-T5 — Add the `--only-fields` option
**Description:** `tkc:osc:import <entity> --only-fields=<f1,f2>` updates only the named columns on existing rows. A field that is added late then never overwrites user edits after go-live (plan §11.1).
**Acceptance criteria:**
1. The option updates only the named columns of rows that already exist.
2. The option does not change any other column or the modified date of an unchanged row.
3. The option does not create a new record. The report counts the OSC ids that have no row.
4. The command rejects a field name that is not in the mapper.
5. A PHPUnit test changes a user-edited column and shows that a run with `--only-fields` keeps the edit.
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
**Description:** `tkc:osc:anonymize --source=db --out=data/osc/anon` reads from the read-only OSC database through the reader in E4-S18. It keeps ids, menu ids, dates, relations and volumes. It replaces names, emails, phones, streets, free text and subjects. The seed is deterministic. It also covers thread text. It processes the data in batches and can resume (production volume: 74,142 incidents and a large thread set). It runs only after gate A.
**Acceptance criteria:**
1. The command reads from the OSC database reader (E4-S18) and writes JSONL files to `data/osc/anon/`.
2. Ids, menu ids, dates and relations stay the same.
3. Names, emails, phones, streets, free text, subjects and thread bodies are synthetic.
4. A second run with the same seed gives the same output.
5. The command refuses an output path outside `data/`.
6. The command writes no real value to a log, a file or a screen. The output has no real data.
7. The command streams the data in batches and resumes after an interruption.
8. The command refuses to read the database before the issue E4-S7 (gate A) is Done.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1-T1, E4-S18 · **Assumptions:** A17

#### E4-S6 — Anonymizer: Test the output for personal data
**Description:** A test reads a synthetic input that has known "personal" tokens. It checks that no token is in the output.
**Acceptance criteria:**
1. The test fails when one known token stays in the output.
2. The test covers names, emails, phones, addresses, subjects, free text and thread bodies.
3. The test runs in CI with synthetic data only.
**Size:** S · **Sprint:** 1 · **Depends on:** E4-S5 · **Assumptions:** none

#### E4-S7 — Security gate A: Review the anonymizer and the guards
**Description:** The security-officer reviews the anonymizer, the PII-leak test, the import guards and the database reader. Gate A opens the way for agent access to the read-only OSC database. No agent reads the database before gate A. Plan §5.4 step 2.
**Acceptance criteria:**
1. The security-officer states in writing that the guards in E4-S2 are complete.
2. The security-officer states in writing that the anonymizer passes the test in E4-S6.
3. The security-officer states in writing that the database reader (E4-S18) is read-only and holds no credential in code or logs.
4. The comment lists each finding with its fix.
5. The gate is open only when no finding is open.
**Size:** S · **Sprint:** 1 · **Depends on:** E4-S2, E4-S5, E4-S6, E4-S18 · **Assumptions:** A18, A19

#### E4-S8 — Owner task: Provide read-only access to the OSC database
**Description:** **Assigned to the owner.** The owner gives the agents read-only access to the OSC database. The credentials go into Proton Pass (vault `TKC-SUITE`, `pass://` references in `.claude/scripts/secrets.env`). Agents never see a value. No agent reads the database before gate A (E4-S7). Rule: `.claude/rules/osc-access.md`, `.claude/rules/secrets.md`. Plan: A18.
**Acceptance criteria:**
1. The owner creates a database user that has SELECT rights only. The user has no write right and no DDL right.
2. The owner stores the credentials in Proton Pass in the vault `TKC-SUITE` and adds the `pass://` references to `.claude/scripts/secrets.env`.
3. The owner states in a comment the database engine, the schema names and the tables of the seven entities (organizations, contacts, opportunities, both contract types, incidents and incident threads). The comment has no record data.
4. The owner states in a comment the record counts per entity, including the thread count (counts only).
5. The owner states in a comment which network path the agents use and who can revoke the access.
6. The owner states in a comment that the access starts only after gate A (E4-S7) is Done.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S7 · **Assumptions:** A18

#### E4-S9 — Owner task: Provide the staff map
**Description:** **Assigned to the owner.** The owner gives the list that maps OSC staff accounts to SuiteCRM users. The users are in the SuiteCRM admin list. Past staff: the admin disables their accounts. The importer creates or links inactive users for them (E4-S1-T4).
**Acceptance criteria:**
1. The owner places `data/osc/staff-map.csv` in the repository folder (gitignored).
2. The owner states in a comment whether the map uses login or email.
3. The map marks each OSC account of past staff, or the owner states in a comment how to find them.
4. The admin disables the SuiteCRM accounts of past staff that already have a user.
**Size:** S · **Sprint:** 1 · **Depends on:** none · **Assumptions:** none

#### E4-S10 — Import: Map Organizations to Accounts and Finance rows
**Description:** The mapper creates Accounts in pass 1 and sets `parent_id` in pass 2. It creates one `tkc_AccountFinance` row per account. Mapping: `docs/osc-mapping/organization.md`.
**Acceptance criteria:**
1. The import creates one account per OSC organization with `osc_id_c`.
2. Pass 2 sets `parent_id` for each organization that has a parent.
3. The mapper does not read the two credential fields, the REST-only rate-class fields or the CRM modules ("State") value. The field comes later with `--only-fields`.
4. The mapper creates the Finance row with the six fields.
5. The mapper maps `org_telefoonnummer` to `phone_office`.
6. The mapper maps each menu id to a key with `extensions/tkc/config/tkc_import_maps.php`. The run report counts unmapped menu ids.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1, E3-S2, E3-S3, E3-S10 · **Assumptions:** A6, A16

#### E4-S11 — Import: Map OSC Contacts to Contacts
**Description:** The mapper creates Contacts with email and phone rows. It imports the SPAM contacts with the type `spam`. Mapping: `docs/osc-mapping/contact.md`.
**Acceptance criteria:**
1. The import creates one contact per OSC contact and links it to its account.
2. The mapper writes the five OSC phone types into the five core phone fields.
3. The mapper writes alternative emails as extra `email_addresses` rows.
4. The mapper sets `tkc_contact_type_c = spam` for the SPAM contacts.
5. The mapper sets a Yes/No field to empty when OSC has no value.
6. The mapper does not read the channel ids, ERT, Test, the CRM modules value or the REST-only fields.
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
**Description:** The mapper handles `Support.SupportContract` and `Support.LicentieContract`. The contract fields come from E11-S1, so this story runs in Sprint 4. Mapping: `docs/osc-mapping/organization.md` §5a.
**Acceptance criteria:**
1. The import creates one contract per OSC contract with the right `contract_type` key.
2. The mapper sets the status to `Signed` as the default.
3. The mapper does not set `renewal_reminder_date`.
4. No reminder email goes out during the import (Mailpit is empty).
5. The synthetic fixtures include both contract types.
6. The anonymized import of contracts gives the OSC database count.
**Size:** S · **Sprint:** 4 · **Depends on:** E4-S12, E11-S1, E4-S17 · **Assumptions:** A13

#### E4-S14 — Import: Map OSC Incidents to Cases
**Description:** The mapper creates Cases with the primary contact, the other contacts and the account. The volume is 74,142 incidents. The import runs in batches and is resumable. This story is too large to verify as one unit. Mapping: `docs/osc-mapping/incident.md`.
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
6. The case import runs in batches and resumes from the checkpoint after a stop. A test with 100,000 synthetic cases shows the same final count after a stop and a resume.
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
**Description:** The team anonymizes the data from the read-only OSC database locally (E4-S5, after gate A). The anonymized output goes to `data/osc/anon/`. The team imports it into local Docker at production volume. This story is too large to verify as one unit. Plan §5.4 step 3. Output stays in `data/`.
**Acceptance criteria:**
1. All three tasks have passed.
2. The report on the issue has counts, run time and unmapped values only.
3. Mailpit is empty after the run.
4. The total run time at production scale is on the issue, with the batch size used.
**Size:** M · **Sprint:** 2 · **Depends on:** E4-S7, E4-S8, E4-S9, E4-S5, E4-S18 · **Assumptions:** A17, A18

##### E4-S17-T1 — Import Accounts and Contacts at full volume
**Acceptance criteria:**
1. The local database has 796 accounts and 9,232 contacts (the OSC production volume).
2. A second run gives the same counts.
3. The run report counts the unmapped ids.
4. The run time for each entity is on the issue.
**Size:** S · **Sprint:** 2 · **Depends on:** E4-S10, E4-S11 · **Assumptions:** A18

##### E4-S17-T2 — Import Opportunities and Cases at full volume
**Acceptance criteria:**
1. The local database has 2,159 opportunities and 74,142 cases (the OSC production volume).
2. Contracts are not in this run. E4-S13 imports them in Sprint 4.
3. A second run gives the same counts.
4. The case import runs in batches. A stop and a resume gives the same final counts.
5. The run time for the case import at production scale is on the issue. The result shows whether the run fits in one working day.
**Size:** M · **Sprint:** 2 · **Depends on:** E4-S12, E4-S14, E4-S15, E4-S17-T1 · **Assumptions:** A18

##### E4-S17-T3 — Write the import report
**Acceptance criteria:**
1. The report gives the run time per entity.
2. The report gives the unmapped values for product, category and status.
3. The report gives the thread count from the OSC database.
4. The report has no field content.
5. The report states the expected run time of the thread import (E9-S5), based on the thread count and the measured speed.
**Size:** S · **Sprint:** 2 · **Depends on:** E4-S17-T2 · **Assumptions:** none

#### E4-S18 — Import: Add a read-only reader for the OSC database
**Description:** The import framework gets a reader for the read-only OSC database. The command uses it with `--source=db`. The anonymizer uses it too. The reader runs SELECT statements only, in batches by key, and keeps a checkpoint. Credentials come only through `pass-cli run --env-file .claude/scripts/secrets.env`. The reader runs against the real database only after gate A (E4-S7). Before gate A, tests use a local synthetic database. Rules: `.claude/rules/secrets.md`, `.claude/rules/data-privacy.md`, `.claude/rules/osc-access.md`.
**Acceptance criteria:**
1. The reader opens a read-only connection. The reader runs SELECT statements only. A test shows that an INSERT, UPDATE or DDL statement is refused by the reader.
2. The reader reads the credentials from environment variables that `pass-cli run` sets. No credential is in code, config, git or a log.
3. The reader reads each entity in batches ordered by the OSC id (keyset paging). Memory use stays flat for 74,142 incidents.
4. The reader keeps a checkpoint per entity in `data/osc/import-state/` and resumes after a stop.
5. The reader reads only the columns in the approved mapping (data minimization).
6. The reader logs counts, ids and run time only, never field content.
7. The reader refuses to connect to the real database before the issue E4-S7 (gate A) is Done.
8. A test with a local synthetic database shows the batches, the checkpoint and the resume.
9. `scripts/seed/README.md` describes the reader and the `pass-cli run` call.
**Size:** M · **Sprint:** 1 · **Depends on:** E4-S1-T1, E3-S10 · **Assumptions:** A18

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
1. A shared base class gives each Cases and Opportunities handler its ACL check and its save step.
2. The handler checks `getRequiredACLs()` before it saves.
3. The status bar shows the current status as pressed.
4. A PHPUnit test covers the shared base with one test subclass.
**Size:** M · **Sprint:** 2 · **Depends on:** E1-S3, E2-S3, E1-S7-T3 · **Assumptions:** none

#### E5-S5 — List: Apply a default filter when a list opens
**Persona/goal/benefit:** As a sales rep, I want the Contacts list to hide SPAM contacts by default, so that I see real contacts first.
**Description:** Use the preset-filter feature if spike S10 shows it. Otherwise use a saved default filter (L1). Plan: §3 W4f.
**Acceptance criteria:**
1. The Contacts list opens with `tkc_contact_type_c` not equal to `spam`.
2. The user removes the filter and sees the SPAM contacts.
3. The filter shows as a visible chip.
**Size:** M · **Sprint:** 2 · **Depends on:** E1-S7-T4, E3-S4 · **Assumptions:** A15

#### E5-S6 — Edit view: Add "Save and close" (optional)
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
2. Each panel shows the fields in plan §4.1. The CRM modules field is not shown (pending the owner, plan §11.7).
3. The type shows as a pill.
4. The Yes/No fields show as segmented controls in edit mode.
5. The Replicon and SharePoint values show as links.
6. The contract begin and end dates show in the Contract panel.
7. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 2 · **Depends on:** E3-S2, E5-S1, E5-S2, E5-S3 · **Assumptions:** A3, A9

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
2. The Contracts subpanel columns are added in E11-S3.
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
4. The CRM modules field is not shown (pending the owner, plan §11.7).
5. The Consent panel shows `email_opt_out`.
6. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 2 · **Depends on:** E3-S4, E5-S1, E5-S2 · **Assumptions:** A3, A15

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
**Size:** S · **Sprint:** 3 · **Depends on:** E7-S1 · **Assumptions:** none

#### E7-S4 — Contacts: Show email and phone rows as in the mockup
**Description:** Use CSS on the core email widget and the five phone fields.
**Acceptance criteria:**
1. The record shows the primary email and the alternative emails in separate rows.
2. The record shows the five phone types with labels.
3. Rows use tokens only.
**Size:** S · **Sprint:** 3 · **Depends on:** E7-S1 · **Assumptions:** A15

---

### E8. Cases screen and actions

Goal: The Cases list and record views match the mockup. The status bar and the record actions work.
Plan: §4.3. Mockup: `designs/screens/cases/mockup.html`. Mapping: `docs/osc-mapping/incident.md`. Size L. Sprint 3.

#### E8-S1 — Cases: Show the record with header, pills and panels
**Persona/goal/benefit:** As a service agent, I want the case record with a clear header, so that I see reference, status, priority and queue at once.
**Acceptance criteria:**
1. The header eyebrow shows `tkc_case_ref_c`.
2. The header shows status, priority and queue as pills. The priority pill uses the label "Priority" and the variants P1 danger, P2 warning, P3 neutral and P4 neutral plain.
3. The record has the panels General, Categorisation, Routing, Status and Details. The Categorisation panel shows the field "Impact" (`tkc_priority_c`) as a pill: `immediate_action` solid danger, `production_disrupted` danger, `some_impact` warning, `no_impact` neutral.
4. The primary contact shows in the General panel and is required in edit mode.
5. `tkc_case_ref_c` is read-only.
6. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 3 · **Depends on:** E3-S5, E3-S6, E5-S1, E4-S17-T2 · **Assumptions:** A2, A4, A5, A12

#### E8-S2 — Cases: Show the list with filters and bulk update
**Persona/goal/benefit:** As a service agent, I want the case list with the key columns and filters, so that I work my queue.
**Acceptance criteria:**
1. The list shows reference (mono font), subject, account, primary contact, status, priority and queue pills, assigned user and date modified.
2. The filters are queue, status, state, priority, impact, assigned user, product and category. The list shows priority as a column and does not show impact as a default column.
3. The core mass update changes queue, status and assigned user.
4. The list loads the 74,142 imported cases with no error. The first page shows in the time the owner sets in E13-S9.
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
**Description:** Ten record actions from plan §11.3 (process types 1 to 10). The three abstract bases are `AbstractCaseSetStatusAction`, `AbstractCaseSolveAsAction` and `AbstractCaseSpamAction`. This story is too large to verify as one unit. Each task has its own handler classes, a PHPUnit test and `getRequiredACLs()`. All classes are in `extensions/tkc/backend/Process/Service/RecordActions/Cases/`.
**Acceptance criteria:**
1. All five tasks have passed.
2. The status bar shows Waiting, Updated, Unresolved and Solved.
**Size:** L · **Sprint:** 3 · **Depends on:** E5-S4, E8-S1 · **Assumptions:** A2, A5

##### E8-S5-T1 — Add the four status actions
**Acceptance criteria:**
1. `AbstractCaseSetStatusAction` and the four classes `CaseSetWaitingAction`, `CaseSetUpdatedAction`, `CaseSetUnresolvedAction` and `CaseSetSolvedAction` exist. The process types are `record-tkc-case-waiting`, `-updated`, `-unresolved` and `-solved`.
2. Each action sets `status` and the matching `state`.
3. Solved sets `Closed_Closed`.
4. The current status shows as pressed.
5. A user without Cases edit access cannot run the action.
**Size:** M · **Sprint:** 3 · **Depends on:** E5-S4 · **Assumptions:** A2

##### E8-S5-T2 — Add "Assign to me"
**Acceptance criteria:**
1. The class `CaseAssignToMeAction` has the process type `record-tkc-case-assign-to-me`.
2. The action sets `assigned_user_id` to the current user.
3. The action is always visible.
4. The action sets no queue or other preset.
**Size:** S · **Sprint:** 3 · **Depends on:** E5-S4 · **Assumptions:** none

##### E8-S5-T3 — Add "Solve as CX" and "Solve as eGain"
**Acceptance criteria:**
1. `AbstractCaseSolveAsAction`, `CaseSolveCxAction` and `CaseSolveEgainAction` exist. The process types are `record-tkc-case-solve-cx` and `record-tkc-case-solve-egain`.
2. Each action sets the product (Oracle Service Cloud or eGain) and the category Service Request.
3. Each action assigns the case to the current user and sets `Closed_Closed`.
4. The actions are in the More menu.
**Size:** M · **Sprint:** 3 · **Depends on:** E5-S4 · **Assumptions:** A2

##### E8-S5-T4 — Add "Route to Finance"
**Acceptance criteria:**
1. The class `CaseRouteFinanceAction` has the process type `record-tkc-case-route-finance`.
2. The action sets the queue Finance and `Closed_Closed`.
3. The action assigns the case to the user in the parameter `tkc.cases.finance_user_name`.
4. The parameter is in the D5 config and in `docs/admin-guide.md`.
5. The action does not forward the case by email.
**Size:** M · **Sprint:** 3 · **Depends on:** E5-S4 · **Assumptions:** A5

##### E8-S5-T5 — Add "Mark as spam" and "Not spam"
**Acceptance criteria:**
1. `AbstractCaseSpamAction`, `CaseMarkSpamAction` and `CaseNotSpamAction` exist. The process types are `record-tkc-case-mark-spam` and `record-tkc-case-not-spam`.
2. "Mark as spam" sets the contact type `spam`, the queue SPAM and `Closed_Closed`.
3. "Not spam" clears the contact type, sets the queue "Support 1e lijn" and sets `Open_New`.
4. Only one of the two actions shows, based on the type of the primary contact.
5. The action needs edit access to Cases and to Contacts.
6. A user without Contacts edit access cannot run either action.
7. When spike S9 is a no-go, the class `Process/Availability/CaseSpamActionAvailability` hides the wrong action.
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
**Description:** The mapper imports all thread entries. The thread volume is unknown and large (74,142 incidents). The import runs in batches and is resumable. The report gives the run time. It sets the entry type and the channel. It does not import attachments. Plan §5.2, row 7.
**Acceptance criteria:**
1. The import creates one AOP_Case_Updates row per OSC thread entry with `osc_id_c`.
2. The row links to its case.
3. The mapper sets `tkc_entry_type_c` and `tkc_channel_c` with the maps in plan §11.4. An unknown channel id becomes `other` and the report counts it.
4. The mapper sets `assigned_user_id` from the staff map, `contact_id` from the contact `osc_id_c` and `date_entered` from the OSC created time.
5. The mapper escapes plain text and changes newlines to `<br>`. The mapper cleans HTML text with `SugarCleaner::cleanHtml` (see E9-S7).
6. The mapper does not read `mailHeader`.
7. The import creates no file attachment and no audit history.
8. No update email goes out (Mailpit is empty).
9. A second run gives the same row count.
10. The report gives the entry count and the run time only.
11. The import runs in batches and resumes from the checkpoint after a stop. A test shows the same final count after a stop and a resume.
12. A run at production scale gives a measured run time on the issue.
**Size:** M · **Sprint:** 3 · **Depends on:** E4-S14, E3-S7, E4-S2, E9-S7 · **Assumptions:** A17, A18

#### E9-S6 — Case thread: Insert a standard text into a reply (conditional)
**Persona/goal/benefit:** As a service agent, I want to insert a standard text, so that I reply faster.
**Description:** The owner must give the bodies of the seven standard texts (incident Q16). If the owner does not give them, this story moves to the "Enhancements" epic.
**Acceptance criteria:**
1. The reply editor offers the seven standard texts.
2. The editor inserts the chosen text at the cursor.
3. The texts come from a config file, not from code.
4. The texts contain no customer data.
**Size:** M · **Sprint:** 4 · **Depends on:** E9-S1, owner answer to Q16 · **Assumptions:** none

#### E9-S7 — Case thread: Clean the HTML of each thread entry (security)
**Description:** The thread shows `description` as HTML. OSC entries of content type 2 are HTML from outside senders. The importer must clean them before it saves them. This is a security item for the security-officer (XSS). Plan: §11.4.
**Acceptance criteria:**
1. The importer passes each HTML entry through `SugarCleaner::cleanHtml` before it saves.
2. A test entry with a `<script>` tag, an `onerror` attribute and a `javascript:` link has none of the three after import.
3. The thread does not run a script from an imported entry in the browser (QA check with a synthetic entry).
4. The same cleaning applies to an entry that an agent saves in the composer, or a test shows that core code already cleans it.
5. The security-officer signs the XSS test result.
**Size:** S · **Sprint:** 3 · **Depends on:** E3-S7 · **Assumptions:** A17

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
2. The list loads the 2,159 imported opportunities with no error.
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
**Description:** The handler `Opportunities/OpportunityQuoteNumberAction` (process type `record-tkc-opportunity-quote-number`) is the only handler in this epic. It calls `TKC\Sequence::next('quote', year)` in one transaction with a row lock. It writes `tkc_reference_c` only when empty. The number has the form `OFTYY-NNNN`.
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

#### E11-S1 — Contracts: Add the contract types and fields
**Description:** Add the keys `tkc_support` and `tkc_license` to `contract_type_list`. Add the fields from plan §4.5. Add the import key. This story moved from E3 to Sprint 4, because the owner decides A13 before Sprint 4 (plan §11.1). Mapping: `docs/osc-mapping/organization.md` §5a.
**Acceptance criteria:**
1. After Repair, AOS_Contracts has the six fields from plan §4.5 and `osc_id_c` with the index `idx_aos_contracts_osc_id_c`.
2. `contract_type_list` has the keys `tkc_support` and `tkc_license`.
3. `tkc_support_level_list` has the keys bronze, silver and gold.
4. The Accounts record keeps its Contracts relationship.
5. The metadata contract test passes for AOS_Contracts.
**Size:** S · **Sprint:** 4 · **Depends on:** E1-S4, owner decision A13 · **Assumptions:** A13

#### E11-S2 — Contracts: Show the contract record with support and licence panels
**Persona/goal/benefit:** As a sales rep, I want a contract record with the right panels for each type, so that I see support and licence data.
**Description:** If spike S7 is a go, the record renders in Angular with a D5 delta of `module_routing` and cell `displayLogic` on `contract_type`. If not, the record is legacy with two panels that always show.
**Acceptance criteria:**
1. The record has the panels Contract, "Support contract details" and "Licence contract details".
2. With spike S7 a go, only the panel of the chosen type shows.
3. With spike S7 a no-go, both panels always show.
4. The record shows the support level, hours in contract, change threshold, Replicon project and duration.
5. The file is in the register in `docs/architecture.md`.
**Size:** M · **Sprint:** 4 · **Depends on:** E11-S1, E1-S7-T1, E4-S13 · **Assumptions:** A13

#### E11-S3 — Contracts: Show the contract columns in the Accounts subpanel
**Acceptance criteria:**
1. The Accounts Contracts subpanel shows name, type, start, end and support level.
2. A click on the name opens the contract.
**Size:** S · **Sprint:** 4 · **Depends on:** E11-S2, E6-S4 · **Assumptions:** A13

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

#### E13-S1 — Roles: Create the two TKC roles and the admin rule with a migration
**Persona/goal/benefit:** As an admin, I want the roles to exist after every deploy, so that access is the same in each environment.
**Description:** An idempotent extension migration (`extensions/tkc/Migrations/Version…SeedRoles.php`, data only) creates the roles "TKC Sales" and "TKC Service". A person with the SuiteCRM `is_admin` flag is the admin; this flag bypasses ACL. Finance staff use "TKC Service". A role "TKC Finance" is not built (owner decision 2026-11-20: the MVP has no Finance role). Admins see the Finance data (owner decision). Export is None and Cases delete is None in the MVP (owner decision). Later iterations: SUITE-26 (configurable roles), SUITE-27 (export per role) and SUITE-28 (delete per role). They have the label "Backlog" and no sprint. No security groups exist in phase 1. The plan puts this work in Sprint 5. This draft puts it in Sprint 2 because E6-S5 needs it. Plan: §11.2.
**Acceptance criteria:**
1. After `tkc:config:apply`, the roles "TKC Sales" and "TKC Service" exist. No other TKC role exists (later: SUITE-26).
2. A second run creates no duplicate role and changes no grant.
3. For "TKC Sales" the grants are as follows (access, view, list, edit, delete, export, mass update). Accounts and Contacts: yes, All, All, All, Owner, None, All. Opportunities: yes, All, All, All, Owner, None, All. Cases: yes, All, All, All, None, None, None. Case Updates: yes, All, All, Owner, None, None, None. AOS_Contracts: yes, All, All, All, Owner, None, None. `tkc_AccountFinance`: yes, All, All, All, None, None, None. Quotes and Products: yes, All, All, All, Owner, None, None. Calls, Meetings, Tasks, Notes, Documents, Emails: yes, All, All, Owner, Owner, None, None.
4. For "TKC Service" the grants are as follows. Accounts and Contacts: yes, All, All, All, None, None, None. Opportunities: yes, All, All, None, None, None, None. Cases: yes, All, All, All, None, None, All. Case Updates: yes, All, All, Owner, None, None, None. AOS_Contracts: yes, All, All, None, None, None, None. Quotes and Products: yes, All, All, None, None, None, None. Calls, Meetings, Tasks, Notes, Documents, Emails: same as "TKC Sales". `tkc_AccountFinance`: disabled.
5. Delete on Cases is None for both roles (later: SUITE-28). Export is None for both roles on every module (later: SUITE-27).
6. Import is None for both roles on every module. Data enters only with `tkc:osc:import`.
7. A user with "TKC Service" and no admin flag cannot open `tkc_AccountFinance`.
8. The security-officer accepts in writing that an admin sees the Finance data (owner decision of 2026-11-20).
9. The permission table and the admin rule are in `docs/admin-guide.md`.
**Size:** M · **Sprint:** 2 · **Depends on:** E3-S3 · **Assumptions:** A6, A9

#### E13-S2 — Security: Test that Finance data does not leak
**Description:** The security-officer runs a leak test with a user that has the role "TKC Service". The test covers the Angular record, the list, the export, the V8 API, AOR reports and global search.
**Acceptance criteria:**
1. The user with the role "TKC Service" and no admin flag gets no Finance value in each of the six read paths.
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
**Description:** **Assigned to the owner.** The mapping needs the production ids of product 551, category 552 and status 103. This needs read-only production access and an approved call (`--prod-approved`). The read-only database access (E4-S8) can replace the REST call when the owner agrees. Mapping: `docs/osc-mapping/incident.md` Q21. SUITE-7 is related.
**Acceptance criteria:**
1. The owner approves each production call in a comment.
2. The ids of the menu values are in `extensions/tkc/config/tkc_import_maps.php` (ids only, no record data).
3. The mapping status in `docs/osc-mapping/incident.md` shows the ids as verified.
**Size:** S · **Sprint:** 3 · **Depends on:** SUITE-7 · **Assumptions:** A18

#### E13-S6 — Security gate B: Approve the guards and the data minimisation for real data
**Description:** The security-officer checks the guards in the target environment, the database reader and the data minimisation. The owner approves the run. Plan §5.4 step 4.
**Acceptance criteria:**
1. The security-officer confirms each guard in E4-S2 in the target environment.
2. The security-officer confirms that the import reads only the fields in the approved mapping.
3. The security-officer confirms that the role model and the Finance test (E13-S2) passed.
4. The owner approves the real-data run in a comment.
**Size:** S · **Sprint:** 6 · **Depends on:** E13-S2, E13-S3, E13-S5, E4-S17 · **Assumptions:** A6, A18, A19

#### E13-S7 — Import: Run the real-data dry run
**Description:** The team runs `tkc:osc:import --source=db --dry-run` on the real OSC database. The report has counts and unmapped values only.
**Acceptance criteria:**
1. The dry run writes no record.
2. The report gives counts per entity and the unmapped values.
3. The report has no field content.
4. The report gives the run time per entity at production scale and the expected run time of the real run.
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
1. The measure gives the load time of each of the four lists with the full anonymized data (74,142 cases, 9,232 contacts, 796 accounts, 2,159 opportunities and the thread set).
2. The measure gives the load time of the Case record with its thread.
3. A value above the limit that the owner sets has a fix story.
**Size:** M · **Sprint:** 5 · **Depends on:** E8-S2, E9-S5, E10-S2 · **Assumptions:** A18

#### E13-S10 — Import: Run the real import and the cut-over checklist
**Description:** Run only after gate B, the dry run, the UAT and the owner's approval. The run reads the OSC database with `--source=db`. The cut-over checklist covers sequences, users (including the inactive users of former staff) and the OSC read-only date.
**Acceptance criteria:**
1. The real run creates the counts that the dry run reported. A stop and a resume gives the same counts.
2. Mailpit or the sink holds no customer email after the run.
3. The sequences are above the highest imported value (E4-S16).
4. The owner signs the cut-over checklist.
5. The document-agent updates `scripts/seed/README.md` and the mapping status.
6. The run time at production scale is on the issue.
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

These items are deferred by A1 and the plan. Each has no OSC counterpart. The owner schedules them. EN-9 waits for the owner's answer. Each becomes a story with its own AC when scheduled. They are listed as placeholders and do not count in the totals.

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
| EN-9 | Accounts and Contacts: Add the CRM modules field with chips, after the owner answers org Q5 and contact Q2 (add with `--only-fields`) | A16, plan §11.7 | M |

---

## 4. Sprint plan

Sprints last two weeks. One agent team works in parallel: backend work (E3, E4) and frontend work (E2, E5) run side by side in Sprints 0 to 2. The owner accepted the assumptions A1 to A20 on 2026-11-20. The sprint plan uses them.

### Sprint 0: Foundation and tokens

**Goal:** The team builds from a clean checkout, the tokens drive both UIs, and the five spikes have answers.
**Stories:** E1-S1 to E1-S6, E1-S7 (T1 to T5), E2-S1 to E2-S5.
**Demo:** A clean Docker install runs `scripts/build.sh`. The Repair SQL is on screen. An Angular page, the login page and the legacy Admin page show the same tokens. Each spike result is on the issue. The test command is green. Mailpit shows a test email.
**Owner action before the sprint:** Finish SUITE-9. No other decision is open.
**Dependency check:** E1-S3 and E1-S4 depend on E1-S1, in the same sprint (order inside the sprint). E2-S2 to E2-S5 depend on E2-S1 or E2-S2, in the same sprint. No story needs a later sprint.

### Sprint 1: Data model, import framework and gate A

**Goal:** All TKC fields exist after Repair, the guarded import runs twice on synthetic data with the same counts, and gate A is open.
**Stories:** E2-S6, E3-S1 to E3-S11, E4-S1 (T1 to T5), E4-S2 to E4-S7, E4-S9, E4-S10, E4-S11, E4-S18, E4-S8.
**Demo:** Repair shows no change on the second run. The synthetic import of Accounts, Contacts and Finance rows runs twice with the same counts. A run with `--only-fields` keeps a user edit. Mailpit stays empty. The anonymizer passes the leak test. The security-officer opens gate A. The agents get read-only access to the OSC database. The anonymizer reads from it.
**Owner action before the sprint:** The staff-map format and the list of past staff (E4-S9). The read-only database user and the Proton Pass entries (E4-S8, only after gate A is open). The production volumes are known (section 0).
**Dependency check:** E3 depends on E1 (Sprint 0). E4-S4 needs E4-S10 and E4-S11 (same sprint, order inside). E4-S8 needs E4-S7 (same sprint, last). E4-S18 needs E4-S1-T1 (same sprint). E4-S5 needs E4-S18 (same sprint, order inside). E2-S6 needs E2-S3 and E2-S5 (Sprint 0).
**Risk:** The load is high (about 45 days). The anonymizer and the reader must handle 74,142 incidents and a large thread set in batches. The measured run time can force a smaller batch or a longer run window. The thread count is unknown until the owner reports it (E4-S8). The mappers for Opportunities and Cases are in Sprint 2. The contract vardefs and the contract mapper are in Sprint 4.

### Sprint 2: Accounts, Contacts and the anonymized data

**Goal:** Accounts and Contacts show as in the mockups on anonymized full-volume data, and the Finance data is visible to the Sales role only.
**Stories:** E4-S12, E4-S14, E4-S15, E4-S16, E4-S17 (T1 to T3), E5-S1 to E5-S5, E6-S1 to E6-S5, E7-S1, E7-S2, E13-S1, E13-S4.
**Demo:** 796 anonymized accounts and 9,232 anonymized contacts in the redesigned lists and records. The pills and the Yes/No controls work. The SPAM filter works. A Sales user sees the Finance tab and a Service user does not. Opportunities and cases are in the database. Contracts come in Sprint 4.
**Owner action before the sprint:** The Finance field meaning and the Replicon and SharePoint URL templates (org Q6, Q12, Q4). The consent model and function-level labels (contact Q8, Q13, Q14). The staff-map owner for inactive staff.
**Risk:** The anonymized import of 74,142 cases can run for hours. The story E4-S17-T2 measures the run time. The import must resume after a stop.
**Dependency check:** E5 needs E2-S3 (Sprint 0) and spikes S9 and S10 (Sprint 0). E6-S5 needs E13-S1 (same sprint, order inside). E6 and E7 list stories need E4-S17-T1 (same sprint, first). E4-S17 needs E4-S8, E4-S9 and E4-S18 (Sprint 1).

### Sprint 3: Cases and the thread import

**Goal:** Cases show as in the mockup with the status bar and the record actions, the thread is imported with cleaned HTML, and the import sends no email.
**Stories:** E7-S3, E7-S4, E8-S1 to E8-S6 (E8-S5 with T1 to T5), E9-S1, E9-S2, E9-S5, E9-S7, E13-S5.
**Demo:** 74,142 anonymized cases with their threads. The status bar, "Solve as CX", "Solve as eGain", "Route to Finance" and "Mark as spam" work. A new case gets a reference. The Priority pill and the Impact pill show. A thread entry with a script tag shows no script. Mailpit is empty after the thread import.
**Owner action before the sprint:** The "Assign to me" presets (incident Q3 to Q7 remainder). The production menu ids (E13-S5). The AOP email rule on a staff reply and the inbound mailbox rule (incident Q14, Q20).
**Dependency check:** E8 needs E3 (Sprint 1), E4-S14 (Sprint 2), E5-S4 (Sprint 2) and spikes S8 and S9 (Sprint 0). E9-S5 needs E4-S14 (Sprint 2) and E9-S7 (same sprint, first). E7-S3 and E7-S4 need E7-S1 (Sprint 2). The database must hold the threads (E4-S8). The thread import must be resumable (E9-S5).

### Sprint 4: Opportunities, contracts and the styled thread

**Goal:** Opportunities show with the quote-number action and the source case, contracts have fields and show on the account, and thread entries show by type.
**Stories:** E4-S13, E9-S3, E9-S4, E9-S6 (conditional), E10-S1 to E10-S8, E11-S1 to E11-S3.
**Demo:** An opportunity gets a quote number with one action. The stage pill shows the percent. Contracts are imported and show on the account with the right panels. A thread shows styled entries. Standard texts show if the owner gave the bodies.
**Owner action before the sprint:** The standard text bodies (incident Q16). The quote-number yearly restart, the need for AOS quotes, the two amounts and dates, the approach field and the won-date rule (opp Q4, Q5, Q6, Q15). The contract questions (org Q17).
**Dependency check:** E10 needs E3-S8, E3-S9 (Sprint 1), E5-S4 (Sprint 2), E4-S16 (Sprint 2) and spike S11 (Sprint 0). E11-S1 needs E1-S4 (Sprint 0). E11-S2 needs spike S7 (Sprint 0), E11-S1 and E4-S13 (same sprint, order inside). E11-S3 needs E6-S4 (Sprint 2). E9-S4 needs E9-S5 (Sprint 3).

### Sprint 5: Legacy restyle, security checks and performance

**Goal:** All screens use the tokens, the security-officer passes the Finance leak test and the handler review, and the lists load fast at full volume.
**Stories:** E12-S1 to E12-S5, E13-S2, E13-S3, E13-S9, E5-S6 (optional).
**Demo:** Quotes, Emails, Home and Admin in the new look. The seam check passes. The leak test result for six read paths. The load-time table.
**Owner action before the sprint:** The email view in use, the dashboard tabs per role and the use of the Activity Stream (emails and home mockup notes). The security acceptance of A6, the credential drops, the import guards, the admin access to Finance data and the export rule.
**Dependency check:** E12 needs E2-S5 (Sprint 0) and E5-S1 (Sprint 2). E13-S2 needs E6-S5 and E13-S1 (Sprint 2). E13-S3 needs E8-S5 (Sprint 3) and E10-S4 (Sprint 4). E13-S9 needs E8-S2, E9-S5 and E10-S2 (Sprints 3 and 4).

### Sprint 6: Gate B, dry run and UAT

**Goal:** The build is UAT-ready, gate B is passed, and the real-data dry run has a report.
**Stories:** E13-S6, E13-S7, E13-S8.
**Demo:** The UAT build. The dry-run report with counts and unmapped values only. The release notes.
**Owner action before the sprint:** Approval of the real-data dry run. UAT dates.
**Dependency check:** E13-S6 needs E13-S2, E13-S3 (Sprint 5), E13-S5 (Sprint 3) and E4-S17 (Sprint 2). E13-S8 needs E12-S5 (Sprint 5).

### Sprint 7+: Real import, Azure and enhancements

**Goal:** The real data is in the system, and a shared Azure environment runs the same chain.
**Stories:** E13-S10, E14-S1 to E14-S5. Enhancements EN-1 to EN-9 as the owner schedules them. A cut-over rehearsal.
**Demo:** The cut-over checklist is signed. The Azure environment shows the login page and the migrated data.
**Owner action before the sprint:** The scope of the enhancements. The cut-over date.
**Dependency check:** E13-S10 needs E13-S6, E13-S7 and E13-S8 (Sprint 6). E14 needs E1-S1 (Sprint 0).

---

## 5. Decisions needed from the owner

### 5.1 Assumptions A1 to A20: decided

The owner accepted all defaults on 2026-11-20. No decision is open. The architect's addendum (`docs/plan/implementation-plan.md` §11) changes four details.

| # | Decision (default accepted) |
|---|---|
| A1 | Defer the pipeline board, the totals row and the "My day" dashlet. They are in EN. |
| A2 | Keep all 12 OSC case statuses. Priority P1 to P4. |
| A3 | Nullable Yes/No fields use a three-state enum with `enum-radio`. |
| A4 | A required primary contact on cases (new 1:N relate). The M:N link stays. |
| A5 | Queue only. No assigned-group field (EN-8 if needed later). |
| A6 | Finance fields go to the module `tkc_AccountFinance` with module ACL. The files are hand-written (plan §11.6). |
| A7 | The quote number stays on the opportunity. A record action generates it. No quotes are imported. |
| A8 | TKC fields on core modules use `tkc_<english_name>_c`. |
| A9 | Only the Finance fields depend on the role. Roles are "TKC Sales" and "TKC Service" plus the admin flag (plan §11.2). |
| A10 | Do not build the Disposition field (EN-7 if needed later). |
| A11 | Customer priority is text. Drop the low-use case fields. |
| A12 | One case reference field `tkc_case_ref_c`. |
| A13 | Contracts use AOS_Contracts. The contract vardefs are built in E11 (Sprint 4). Angular if spike S7 is a go. |
| A14 | Keep both amounts and both close dates. Drop three OSC fields. |
| A15 | Import all five phone types and all SPAM contacts. |
| A16 | Drop the credential fields. The CRM modules field is **not built in Sprint 1**. It waits for the owner's answer (EN-9, plan §11.7). |
| A17 | Import all thread entries. Do not import attachments or audit history. |
| A18 | The OSC extract is a read-only database. The owner gives the access (E4-S8). Agents read it only after gate A (E4-S7), through a reader (E4-S18). Credentials via Proton Pass. |
| A19 | Mailpit in Docker. The import refuses to run without a sink host. |
| A20 | Base currency EUR. Number format nl-NL. UI language en_us. |

### 5.2 Decisions inside the addendum

The owner decided these on 2026-11-20. The security-officer still signs them in E13-S1.

| Item | Decision |
|---|---|
| Role "TKC Finance" | Not built in the MVP. Finance staff use "TKC Service" and do not see `tkc_AccountFinance`. Later: SUITE-26 (configurable roles). |
| Export | None for all roles on all modules. Later: SUITE-27 (export per role). |
| Cases delete | None for all roles. Later: SUITE-28 (delete per role). |
| Admin access | An admin sees the Finance data (decided). The security-officer accepts this in E13-S1. |
| Default of Priority | Empty (decided). The story E3-S5 checks the core code that reads the default. |
| Former OSC staff | The admin disables their accounts. The import creates or links inactive users (E4-S1-T4). No `tkc_migration` user. |
| Thread entry types | Default in the plan: OSC types 6 and 7 map to `note`, type 5 (chat) to `customer`, type 8 (voice) to `customer_proxy`. The owner still confirms. |
| Priority or Impact in the header | Default in the plan: Priority in the header and list. The team swaps them with a small metadata change if agents use Impact more. The owner still confirms. |

### 5.3 Open business questions per sprint

| Before sprint | Question | Epic |
|---|---|---|
| 0 | Finish SUITE-9. | E1 |
| 1 | Thread count and the database details (E4-S8). Production volumes are known (section 0). | E4 |
| 1 | Staff map: login or email. List of past staff (E4-S9). | E4 |
| 2 | Finance field meaning. Replicon and SharePoint URLs. Site Werkzaamheden report. | E6 |
| 2 | Consent model, function-level labels, SPAM import. | E7 |
| 3 | "Assign to me" presets. | E8 |
| 3 | Production ids of product 551, category 552 and status 103 (needs read-only production access). | E8 |
| 3 | AOP email on a staff reply. Inbound mailbox case rule. | E8, E9 |
| 4 | Bodies of the standard texts 10, 20, 33, 40, 41, 341 and 371. | E9 |
| 4 | Quote number yearly restart. AOS quotes. Two amounts and dates. Approach. Won-date rule. | E10 |
| 4 | `Kanalen` against support level. Units of `Duur` and `ChangeDrempel`. `DatumRenewal`. Contract status. Incident link. | E11 |
| 5 | Email view in use. Dashboard tabs per role. Activity Stream in use. | E12 |
| 5 | Security acceptance of the credential drops, the guards, admin access to Finance data and the export rule (decided; the security-officer signs). | E13 |
| 6 | Approval of the real-data dry run. | E13 |
| open | CRM modules ("State") field: keep or drop (org Q5, contact Q2). The field is not built until the owner answers (EN-9). | EN |

---

## 6. Totals and gaps in the plan

### 6.1 Totals

| Item | Count |
|---|---|
| Epics (E1 to E14) | 14, plus the unscheduled "Enhancements" bucket (9 placeholders, not counted below) |
| Stories | 101 (E1: 7, E2: 6, E3: 11, E4: 18, E5: 6, E6: 5, E7: 4, E8: 6, E9: 7, E10: 8, E11: 3, E12: 5, E13: 10, E14: 5) |
| Stories that have tasks | 5 (E1-S7, E4-S1, E4-S14, E4-S17, E8-S5) |
| Tasks | 21 (5 spikes, 5 import-command tasks, 3 case-mapper tasks, 3 import-run tasks, 5 case-action tasks) |
| Owner-assigned stories | 3 (E4-S8 database access, E4-S9 staff map, E13-S5 production ids) |
| Security-officer gates | 2 (E4-S7 gate A, E13-S6 gate B) |
| Process handlers | 10 in E8, 1 in E10 |

Effort per sprint. The count uses leaf items only (a story with tasks counts through its tasks). Weights: S = 1 day, M = 2 days, L = 5 days. These weights are a rough guide, not an estimate.

| Sprint | Leaf items | Rough days | Note |
|---|---|---|---|
| 0 | 5 S, 11 M | 27 | Five spikes; tokens and base theme |
| 1 | 12 S, 16 M | 44 | Highest load. Backend (E3, E4) and frontend (E2) run in parallel. |
| 2 | 8 S, 15 M | 38 | High load. Import, shared components, two screens. |
| 3 | 7 S, 10 M | 27 | One screen, ten actions, thread import, HTML cleaning |
| 4 | 11 S, 4 M | 19 | Contract fields, contract import, quote number |
| 5 | 4 S, 5 M | 14 | One optional story (E5-S6) |
| 6 | 1 S, 2 M | 5 | Gate B, dry run, UAT |
| 7+ | 1 S, 5 M | 11 | Real import and Azure |

Before the owner inputs, Sprint 1 had about 42 days (44 days with the database reader) and Sprint 2 about 38 days. Sprints 1 and 2 are still the heaviest. Sprints 5 to 7+ are light. The team can pull stories forward when the dependencies allow.

### 6.2 Gaps and inconsistencies

**Resolved** (by the owner's acceptance of A1 to A20, the architect addendum §11, or this update):

1. Sprint 1 demo and mapper scope. This draft imports Accounts, Contacts and Finance rows in Sprint 1 and the other mappers later (the contract mapper in Sprint 4).
2. Decision dates. The plan §11.1 sets what must be known before Sprint 1. The owner accepted all defaults, so nothing blocks Sprint 1.
3. Roles in Sprint 2. E13-S1 is in Sprint 2. The leak test stays in Sprint 5.
4. Roles. Two roles plus the admin flag, with a permission table (plan §11.2, E13-S1).
5. Handler count. 10 in E8 and 1 in E10 (plan §11.3).
6. Thread fields. Fields, lists and import rules are in plan §11.4 and E3-S7.
7. Two priority fields. Labels "Priority" and "Impact" (plan §11.5, E3-S5, E8-S1).
8. Module Builder files. The module files are hand-written in git (plan §11.6, E3-S3).
9. Stale phases in `docs/backlog.md`: the document-agent updates them when the owner approves this draft (not a plan gap).
10. Load balance. E2-S3 and E2-S4 moved to Sprint 0. E7-S3 and E7-S4 moved to Sprint 3. The CRM modules chips story (old E5-S6) left the plan with the field (EN-9).

**Still open:**

1. Case menu ids. The Sprint 2 anonymized case import uses the ids in the database that the owner opens (E4-S8). Check that they are production ids. The production ids (incident Q21) come from the owner in Sprint 3 (E13-S5) and are needed before gate B.
2. Database content. The thread import (Sprint 3) needs the threads in the database. E4-S8 lists the thread table as required. The thread volume is still unknown and large.
3. Gate A order. The database access (E4-S8) is the last item of Sprint 1 (plan §5.4). A delay moves the Sprint 2 import.
4. Contracts mockup. No mockup exists for contracts. E11-S2 has no mockup link. The ux-designer may need a small mockup before Sprint 4.
5. Start state. SUITE-9 and the `extensions/tkc/` scaffold are open. E1 depends on SUITE-9.
6. Contract decision A13. The owner accepted the default, but the contract vardefs wait until Sprint 4. A change to a `tkc_` module after Sprint 4 starts costs M to L.
7. Plan §3 and §4.1 still list `tkc_crm_modules_c` and four roles. Plan §11.7 corrects them. The document-agent must apply the corrections to the plan text.
8. Load. Sprints 1 and 2 stay above the later sprints (see 6.1). More moves need the owner's view on scope.

---

## Loaded into SUITE (2026-11-20)

The product-owner loaded the approved backlog into the planning project SUITE on 2026-11-20 with the owner's go. No sprint is started. No item is started. Every item has the status To Do.

**Totals:** 14 epics (E1 to E14) plus 1 epic "Enhancements (not scheduled)" (EN), 101 stories, 21 tasks, 9 placeholders EN-1 to EN-9. That makes 146 new issues, SUITE-29 to SUITE-174. SUITE-1 to SUITE-28 are unchanged. SUITE-26, SUITE-27 and SUITE-28 keep the label "Backlog" and have no sprint.

**Decisions at load time:**

1. The story count is 101, not 100. The new story E4-S18 (OSC database reader) is part of the owner input in section 0.
2. The placeholders EN-1 to EN-9 are stories under one small epic EN (SUITE-43). They have the label "Backlog" and no sprint. Each has one AC: the owner approves scope and AC before work starts.
3. Size is in the description and in the story points field (S = 1, M = 3, L = 8). Priority follows the sprint: Sprints 0 and 1 high, Sprints 2 to 6 medium, Sprint 7+ and enhancements low.
4. **Sprints (update 2026-11-21):** the owner granted the right; the coordinator created "Sprint 0" to "Sprint 6" and "Sprint 7+" (state planned, goals from the sprint plan) and added all 122 stories and tasks to their sprint (0: 17, 1: 29, 2: 25, 3: 18, 4: 15, 5: 9, 6: 3, 7+: 6). No sprint is started. *Earlier note:* sprints were not in the planning tool at load time. The agent account has no right to create sprints (HTTP 403 on `POST /sprints`) and no right to create labels (HTTP 403 on `POST /labels`). The sprint of each item is in its description ("**Sprint:** n") and in the table below. The wrapper has the verbs `sprints`, `sprint-create` and `sprint-add`, and the option `create --sprint`. The owner must create the sprints "Sprint 0" to "Sprint 6" and "Sprint 7+" in the planning tool (state planned), or give the agent account the right. Then the agent runs `sprint-add` with the issues of each sprint from this table.
5. During the load, two loader processes ran at the same time by mistake. They made two partial copies of E4-S11 and E4-S12. The product-owner reused those four issues (SUITE-88 to SUITE-91) for E4-S11 to E4-S14. The titles, descriptions, points and AC were rewritten and checked. No duplicate remains. The numbers match the dependency references in the descriptions.
6. Dependency references in descriptions and AC use the form `SUITE-n (E4-S7)`.

| Draft ID | SUITE ID | Type | Sprint |
|---|---|---|---|
| E1 | SUITE-29 | epic | - |
| E2 | SUITE-30 | epic | - |
| E3 | SUITE-31 | epic | - |
| E4 | SUITE-32 | epic | - |
| E5 | SUITE-33 | epic | - |
| E6 | SUITE-34 | epic | - |
| E7 | SUITE-35 | epic | - |
| E8 | SUITE-36 | epic | - |
| E9 | SUITE-37 | epic | - |
| E10 | SUITE-38 | epic | - |
| E11 | SUITE-39 | epic | - |
| E12 | SUITE-40 | epic | - |
| E13 | SUITE-41 | epic | - |
| E14 | SUITE-42 | epic | - |
| EN | SUITE-43 | epic (Enhancements) | none (Backlog) |
| E1-S1 | SUITE-44 | story | 0 |
| E1-S2 | SUITE-45 | story | 0 |
| E1-S3 | SUITE-46 | story | 0 |
| E1-S4 | SUITE-47 | story | 0 |
| E1-S5 | SUITE-48 | story | 0 |
| E1-S6 | SUITE-49 | story | 0 |
| E1-S7 | SUITE-50 | story | 0 |
| E1-S7-T1 | SUITE-51 | task | 0 |
| E1-S7-T2 | SUITE-52 | task | 0 |
| E1-S7-T3 | SUITE-53 | task | 0 |
| E1-S7-T4 | SUITE-54 | task | 0 |
| E1-S7-T5 | SUITE-55 | task | 0 |
| E2-S1 | SUITE-56 | story | 0 |
| E2-S2 | SUITE-57 | story | 0 |
| E2-S3 | SUITE-58 | story | 0 |
| E2-S4 | SUITE-59 | story | 0 |
| E2-S5 | SUITE-60 | story | 0 |
| E2-S6 | SUITE-61 | story | 1 |
| E3-S1 | SUITE-62 | story | 1 |
| E3-S2 | SUITE-63 | story | 1 |
| E3-S3 | SUITE-64 | story | 1 |
| E3-S4 | SUITE-65 | story | 1 |
| E3-S5 | SUITE-66 | story | 1 |
| E3-S6 | SUITE-67 | story | 1 |
| E3-S7 | SUITE-68 | story | 1 |
| E3-S8 | SUITE-69 | story | 1 |
| E3-S9 | SUITE-70 | story | 1 |
| E3-S10 | SUITE-71 | story | 1 |
| E3-S11 | SUITE-72 | story | 1 |
| E4-S1 | SUITE-73 | story | 1 |
| E4-S1-T1 | SUITE-74 | task | 1 |
| E4-S1-T2 | SUITE-75 | task | 1 |
| E4-S1-T3 | SUITE-76 | task | 1 |
| E4-S1-T4 | SUITE-77 | task | 1 |
| E4-S1-T5 | SUITE-78 | task | 1 |
| E4-S2 | SUITE-79 | story | 1 |
| E4-S3 | SUITE-80 | story | 1 |
| E4-S4 | SUITE-81 | story | 1 |
| E4-S5 | SUITE-82 | story | 1 |
| E4-S6 | SUITE-83 | story | 1 |
| E4-S7 | SUITE-84 | story | 1 |
| E4-S8 | SUITE-85 | story | 1 |
| E4-S9 | SUITE-86 | story | 1 |
| E4-S10 | SUITE-87 | story | 1 |
| E4-S11 | SUITE-88 | story | 1 |
| E4-S12 | SUITE-89 | story | 2 |
| E4-S13 | SUITE-90 | story | 4 |
| E4-S14 | SUITE-91 | story | 2 |
| E4-S14-T1 | SUITE-92 | task | 2 |
| E4-S14-T2 | SUITE-93 | task | 2 |
| E4-S14-T3 | SUITE-94 | task | 2 |
| E4-S15 | SUITE-95 | story | 2 |
| E4-S16 | SUITE-96 | story | 2 |
| E4-S17 | SUITE-97 | story | 2 |
| E4-S17-T1 | SUITE-98 | task | 2 |
| E4-S17-T2 | SUITE-99 | task | 2 |
| E4-S17-T3 | SUITE-100 | task | 2 |
| E4-S18 | SUITE-101 | story | 1 |
| E5-S1 | SUITE-102 | story | 2 |
| E5-S2 | SUITE-103 | story | 2 |
| E5-S3 | SUITE-104 | story | 2 |
| E5-S4 | SUITE-105 | story | 2 |
| E5-S5 | SUITE-106 | story | 2 |
| E5-S6 | SUITE-107 | story | 5 |
| E6-S1 | SUITE-108 | story | 2 |
| E6-S2 | SUITE-109 | story | 2 |
| E6-S3 | SUITE-110 | story | 2 |
| E6-S4 | SUITE-111 | story | 2 |
| E6-S5 | SUITE-112 | story | 2 |
| E7-S1 | SUITE-113 | story | 2 |
| E7-S2 | SUITE-114 | story | 2 |
| E7-S3 | SUITE-115 | story | 3 |
| E7-S4 | SUITE-116 | story | 3 |
| E8-S1 | SUITE-117 | story | 3 |
| E8-S2 | SUITE-118 | story | 3 |
| E8-S3 | SUITE-119 | story | 3 |
| E8-S4 | SUITE-120 | story | 3 |
| E8-S5 | SUITE-121 | story | 3 |
| E8-S5-T1 | SUITE-122 | task | 3 |
| E8-S5-T2 | SUITE-123 | task | 3 |
| E8-S5-T3 | SUITE-124 | task | 3 |
| E8-S5-T4 | SUITE-125 | task | 3 |
| E8-S5-T5 | SUITE-126 | task | 3 |
| E8-S6 | SUITE-127 | story | 3 |
| E9-S1 | SUITE-128 | story | 3 |
| E9-S2 | SUITE-129 | story | 3 |
| E9-S3 | SUITE-130 | story | 4 |
| E9-S4 | SUITE-131 | story | 4 |
| E9-S5 | SUITE-132 | story | 3 |
| E9-S6 | SUITE-133 | story | 4 |
| E9-S7 | SUITE-134 | story | 3 |
| E10-S1 | SUITE-135 | story | 4 |
| E10-S2 | SUITE-136 | story | 4 |
| E10-S3 | SUITE-137 | story | 4 |
| E10-S4 | SUITE-138 | story | 4 |
| E10-S5 | SUITE-139 | story | 4 |
| E10-S6 | SUITE-140 | story | 4 |
| E10-S7 | SUITE-141 | story | 4 |
| E10-S8 | SUITE-142 | story | 4 |
| E11-S1 | SUITE-143 | story | 4 |
| E11-S2 | SUITE-144 | story | 4 |
| E11-S3 | SUITE-145 | story | 4 |
| E12-S1 | SUITE-146 | story | 5 |
| E12-S2 | SUITE-147 | story | 5 |
| E12-S3 | SUITE-148 | story | 5 |
| E12-S4 | SUITE-149 | story | 5 |
| E12-S5 | SUITE-150 | story | 5 |
| E13-S1 | SUITE-151 | story | 2 |
| E13-S2 | SUITE-152 | story | 5 |
| E13-S3 | SUITE-153 | story | 5 |
| E13-S4 | SUITE-154 | story | 2 |
| E13-S5 | SUITE-155 | story | 3 |
| E13-S6 | SUITE-156 | story | 6 |
| E13-S7 | SUITE-157 | story | 6 |
| E13-S8 | SUITE-158 | story | 6 |
| E13-S9 | SUITE-159 | story | 5 |
| E13-S10 | SUITE-160 | story | 7+ |
| E14-S1 | SUITE-161 | story | 7+ |
| E14-S2 | SUITE-162 | story | 7+ |
| E14-S3 | SUITE-163 | story | 7+ |
| E14-S4 | SUITE-164 | story | 7+ |
| E14-S5 | SUITE-165 | story | 7+ |
| EN-1 | SUITE-166 | story (placeholder) | none (Backlog) |
| EN-2 | SUITE-167 | story (placeholder) | none (Backlog) |
| EN-3 | SUITE-168 | story (placeholder) | none (Backlog) |
| EN-4 | SUITE-169 | story (placeholder) | none (Backlog) |
| EN-5 | SUITE-170 | story (placeholder) | none (Backlog) |
| EN-6 | SUITE-171 | story (placeholder) | none (Backlog) |
| EN-7 | SUITE-172 | story (placeholder) | none (Backlog) |
| EN-8 | SUITE-173 | story (placeholder) | none (Backlog) |
| EN-9 | SUITE-174 | story (placeholder) | none (Backlog) |
