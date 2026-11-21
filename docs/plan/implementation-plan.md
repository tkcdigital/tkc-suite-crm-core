# TKC SuiteCRM — build architecture and implementation plan

> Phase 5 (Plan) · Author: architect · Date: 2026-11-20 · Planning: SUITE-25 · Status: **Approved 2026-11-20** — the owner accepted the defaults of A1–A20 and the §11 addendum (roles, handler list, thread fields, priority vs. impact, hand-written Finance module).
> Inputs: `docs/assessment/customization-feasibility.md` (D1–D12, S1/S3/S5), `docs/architecture.md`, `docs/backlog.md`, `docs/osc-mapping/{organization,contact,incident,opportunity}.md`, `designs/system/components.md`, and the design notes in `designs/screens/*/mockup.html`.
> Rules: `.claude/rules/suitecrm-customization.md`, `workflow-order.md`, `data-privacy.md`. Every build story still gets its own architect prescription (workflow step 1). This plan sets the frame for those prescriptions.

---

## 1. Summary

**Scope.** Four screens get new fields and a redesign: Accounts, Contacts, Cases and Opportunities. Contracts (AOS_Contracts) are added. Quotes, Emails, Home and Admin get a restyle only. The OSC data is imported, with an anonymized sample first. SLA and Projects are out of scope. The UI is English only. Development runs in Docker only (PHP 8.3, MySQL 8.0).

**Approach.**
1. Metadata first. All fields are extension vardefs on the main table (D2). Layouts are one versioned `detailviewdefs.php` per module (D4). Lists and labels go in `custom/Extension/.../Language/` (D7).
2. Level 3 code only for a small, known set: the theme generator (D6), about 6 Angular field/display parts, about 12 process handlers, the import commands (D8), and one sequence service.
3. Level 4 code is limited to 4 logic hooks, each with an import-mode guard.
4. Level 5 (core patches): **none planned.**
5. All vardefs are built in one early sprint; layouts, actions and the import depend on them.
6. The anonymized sample is imported at full tst1 volume in Sprint 2, so the screens are built and tested with realistic data.

**Main risks** (details in §9):
- The import can email real customers, because AOP, AOW and assignment notices fire on save.
- SuiteCRM 8.10 has no field-level ACL (no `ACLFields` module), so the Finance fields need a different protection design.
- The Angular UI and the legacy iframe UI must look the same; every token goes to two themes.
- Full-copy viewdefs and core-list overrides can drift from upstream; re-check them at every upstream merge.
- Production volumes and the OSC extract method are not yet known.

---

## 2. Assumptions

**Owner decision 2026-11-20: all defaults accepted** (with the §11.7 corrections). The plan uses the default in each row. Effort: S < 1 day, M 1–3 days, L > 3 days.

| # | Topic | Recommended default | Reason | Cost impact | If the owner overrules |
|---|---|---|---|---|---|
| A1 | Costly extras in Sprint 1: pipeline board (L), list totals row (M), Home "My day" dashlet (M) | **Defer all three** to an unscheduled "Enhancements" epic. Keep the standard pipeline dashlet chart, restyled. | None has an OSC counterpart; OSC replacement does not need them. | Saves ~8–12 days | Add them in Sprint 5+. Board = new Angular view (L3); totals = backend aggregate + list footer (L3); "My day" = legacy dashlet in `custom/modules/Home/Dashlets/` (L4). |
| A2 | Case statuses | **All 12 OSC statuses**: 4 core keys + 8 new keys with the `Open_`/`Closed_` prefix (incident.md §10.1). **Priority P1–P4**: keep core P1–P3, add P4, relabel all four. | No data loss. AOP derives `state` from the key prefix (`CaseUpdatesHook.php:306-309`). | S | A shorter list needs a status mapping in the import plus owner sign-off on data loss. Same cost. |
| A3 | Nullable Yes/No flags (Contacts: 7; Accounts: 2) | **Three-state enum** `tkc_yes_no_list` (`yes`, `no`; empty = not set), shown with core `enum-radio` (`base-fields.manifest.ts:316-318`) plus TKC CSS for the segmented look. Same list for every OSC Yes/No field; `auto_upgrade` defaults to `no`. | No data loss (e.g. KMM: 7028 empty, 828 Yes, 125 No). No new field type. Enum filters work. Enum→bool later is easy; bool→three-state after import is not. | S–M (CSS + spike S10) | "Import empty as No": `bool` + CSS on the bool edit template; saves ~1 day; unknown vs. No is lost. |
| A4 | Case primary contact | **Yes: new required 1:N relate** `tkc_primary_contact_c` / `tkc_primary_contact_id_c`, relationship `tkc_contacts_cases_primary`. Keep core M:N `contacts_cases`; a hook keeps the primary contact in the M:N set so AOP emails reach it. | OSC `primaryContact` is not nullable (incident.md, CId). AOP mails M:N contacts. | M | Without it, the import puts the primary contact in M:N only; users cannot see the requester. |
| A5 | Case assigned group + queue | **Queue only** (`tkc_queue_c`, 12 values). No staff-group field; SecurityGroups for team membership only (admin config). "Route to Finance" sets queue Finance. Rule "queue by group" dropped. | Two overlapping routing fields confuse agents; 88 % of cases are in Finance; a record cannot be assigned to a group. | Saves S–M | Add `tkc_assigned_group_c` (enum, 14) + before-save "queue by group" hook (S–M). |
| A6 | Protection of the Finance fields | **Move the 6 Finance fields to a new module `tkc_AccountFinance`** (Module Builder, D11), 1:N to Accounts, one row per account (UI + import). Module ACL: role "TKC Sales" view/edit, other roles no access. Shown on the account as subpanel/tab "Finance". Security officer runs a leak test on Angular record, list, export, V8 API, AOR reports, global search. | No field-level ACL in 8.10: no `ACLFields` module; `acl_fields` in `SugarBean.php:523-526` is an unenforced flag. Module ACL is enforced in all read paths. `displayLogic` only hides UI. | M | "Hide only" (cell `displayLogic`) costs S but is **not a control**: list, export and API still show the data. The security officer must accept that in writing. |
| A7 | Quote number | **Keep `tkc_reference_c` (`OFTYY-NNNN`) on the Opportunity**, filled by record action "Generate quote number" (process handler + transactional table `tkc_sequences`). AOS_Quotes stay with their own numeric number, restyle only. No quotes imported (OSC has no line items). | The OSC workspace has no quotes/line-item grid (opportunity.md §2). | S–M | If AOS quotes with line items are needed: copy `tkc_reference_c` into the quote on "Create quote" (handler, M) + PDF template (L1). |
| A8 | Field naming | **TKC fields on core modules: `tkc_<english_name>_c`.** Exceptions: `osc_id_c` and the case reference (A12). Fields in a `tkc_` module: no prefix/suffix. Cases names in incident.md change to the prefixed form (e.g. `queue_c` → `tkc_queue_c`). | D3 allows it; separates TKC fields from Studio/add-on fields (`jjwg_maps_*_c`); 3 of 4 mappings already use it. | S (doc sync) | Mixed names make search and the security review harder. |
| A9 | Role-based layout differences (OSC Sales profile 73) | **Only the Finance fields depend on the role (A6).** RightNow block and contract dates visible to all staff; visibility depends on account type only. | Role conditions in `displayLogic` need custom logic; hiding is not a control. | Saves M | Role-aware `displayLogic` function (L3, M) + security review. |
| A10 | Incident Disposition (Q5a) | **Not built.** Disposition values only feed the Category mapping in the import (incident.md §10.3). | Owner has not decided; 72 of 4053 incidents have one. | S saved | Add `tkc_disposition_c` (enum) and re-run the idempotent case import. |
| A11 | Customer-specific / low-use Case fields | `tkc_customer_priority_c` = varchar 255 (Q7). **Drop** collect-before-share (Q10), self-service banner (Q20; 0 incidents), the assigned-menu filter rule, the 26 REST-only custom fields (Q19), `mailHeader`. | Mockup shows customer priority as text; the rest is customer-specific, unused, or data minimisation. | S | Each extra field: S + an import column. |
| A12 | Case reference | **One field `tkc_case_ref_c`** (varchar 20, indexed). Import: OSC `referenceNumber`. New cases: before-save hook generates `YYMMDD-NNNNNN` from `tkc_sequences`. Core `case_number` stays. Header eyebrow shows `tkc_case_ref_c`. Replaces `osc_ref_no_c` in the mapping. | One reference format for customers and agents; `osc_*` would be wrong for new cases. | S (reuses A7 sequence) | Keep `osc_ref_no_c` for imported cases only; new cases show the integer `case_number`. |
| A13 | Contracts module | **AOS_Contracts** (mapping §5a): add keys `tkc_support`, `tkc_license` to `contract_type_list` + 6 fields. Record view is legacy (`record: false`); spike S7 tests Angular via a D5 delta of `module_routing`. If S7 fails: legacy view with two labelled panels, no conditional logic. | Core relationships to Account/Opportunity/Contact and the Accounts subpanel exist. | M | A `tkc_Contracts` module (D11): Angular by default but rebuilds relationships/subpanels (M–L). |
| A14 | Opportunity details (Q5, Q6, Q13, Q14) | Keep both amounts and both close dates. `tkc_approach_c` new; `lead_source` keeps its core meaning. Drop `waarschijnlijkheid`, `type_medewerker`, `VERKOOP_ID`. The custom menu drives `sales_stage`. `name` vardef override to len 255. | Matches the mockup and mapping proposals. | S | Each change S. |
| A15 | Contacts details (Q3, Q4, Q12, Q14) | Import all 5 OSC phone types into the 5 core phone fields; alternative emails as extra `email_addresses` rows. Drop channel IDs, ERT, Test and the 16 REST-only fields. Keep Freshworks Usergroup (on the workspace). **Import the 365 SPAM contacts** with type SPAM; a default list filter hides them. | SPAM incidents point to these contacts as primary contacts (A4 makes it required). | S | Skipping SPAM contacts means skipping SPAM cases too. |
| A16 | Accounts details (Q5, Q16) | Keep `tkc_crm_modules_c` (multienum) on Accounts and Contacts. `org_telefoonnummer` → core `phone_office`. Drop REST-only rate-class fields and both credential fields. | Matches mockups; data minimisation; credentials never enter the CRM. | S | Each field S. |
| A17 | Thread and attachment history (Q13, Contact Q10) | Import **all thread entries** into AOP_Case_Updates. Do **not** import file attachments or audit history in the first full run; attachments may become a later import. | The thread is the case history. Attachments add volume, storage and PII risk; count unknown. | Saves M–L | Attachment import to Notes with files (L) + storage check. |
| A18 | OSC data extract | The **user** runs the bulk extract with the read-only API user (`pass-cli run`), JSONL per entity, REST field names, to `data/osc/export/` (gitignored). Agents do not run bulk extracts (`osc-access.md`). | Keeps agents inside the OSC access rule. | — | With an OSC CSV export only the reader class changes. |
| A19 | Outbound email in dev/import environments | Docker gets a mail sink (Mailpit). The import refuses to run unless the outbound account points to an allowlisted sink host. | Second protection after the in-process guards (§5.3). | S | — |
| A20 | Base currency | EUR; number format nl-NL; UI language en_us. | Backlog open question; matches the applied settings template. | — | Change before import: S; after import: data fix. |

---

## 3. Workstreams

Size: **S** ≤ 1 sprint-week, **M** 1–2 sprint-weeks, **L** > 2 sprint-weeks.

| WS | Goal | Main deliverables | Depends on | Level | Size |
|---|---|---|---|---|---|
| **W1 Foundation** | Build and deploy from a clean checkout in one chain. | `scripts/build.sh` (`yarn merge-angular-json`, `yarn build:shell`, `yarn build:extension tkc`, `tkc:repair --dry-run` → review → `--execute`, `tkc:config:apply`, `cache:clear`). Mailpit (A19). PHPUnit for `extensions/tkc/backend`, Karma for `extensions/tkc/app`, metadata contract test per module. Register of overridden viewdefs/lists in `docs/architecture.md`. Rule text for A8. | D1, D9, D10, D12 done | 3 | M |
| **W2 Theme (D6)** | One token source, two UIs. | Generator `scripts/theme/build_theme.py` (`python3 -I`, reads `designs/system/tokens.json`) → `extensions/tkc/app/src/theme/_tkc-tokens.scss` and the token block of `public/legacy/custom/themes/suite8/css/Dawn/style.css`. Hand rules: `_tkc-overrides.scss` (Angular), legacy partials `extensions/tkc/legacy-theme/*.css` joined into `style.css`. CI: regenerate gives no diff. Base restyle: navbar, buttons, fields, tabs, panels, list, subpanels, alerts, modals, login (logo `company_logo`). | W1 | 3 + 4 | M |
| **W3 Data model** | All fields, lists, relationships and tables exist after Repair. | Vardef + language extensions for Accounts, Contacts, Cases, AOP_Case_Updates, Opportunities, AOS_Contracts (§4). Module `tkc_AccountFinance` (D11). Table `tkc_sequences`. 3 new relationships. Core list overrides. `docs/data-model.md`. | W1 | 2 | M |
| **W4 Shared UI components** | Reusable parts for the screens. | (a) `TkcEnumPillComponent` (enum display, key→variant map); (b) segmented Yes/No = CSS on `enum-radio` (A3); (c) `TkcUrlTemplateField` (Replicon, SharePoint, TFS); (d) record-action base + status-bar look; (e) "Save and close" (low priority); (f) default list filter (SPAM contacts). | W2, W3, S9/S10 | 3 | M |
| **W5 Screens** | Four redesigned screens match the mockups. | Per module: `custom/modules/<M>/metadata/{detailviewdefs,listviewdefs,searchdefs}.php`, subpanel layoutdefs, `displayLogic`, `recordActions`, process handlers (§4). | W3, W4 | 2 + 3 | L |
| **W6 Case thread** | OSC incident thread in the AOP record thread. | Entry type + channel on AOP_Case_Updates; `record-thread` item/create layouts in metadata (S8); `dynamicClass` per entry type; `description` → `longtext`; standard-text insert (after Q16). CC/BCC deferred. | W3, W5-Cases | 2 (+3) | M |
| **W7 Contracts** | Support and licence contracts on the account. | `contract_type_list` keys, 6 fields, layout (Angular if S7, else legacy), Accounts subpanel columns, import. | W3, S7 | 2 (+3) | S–M |
| **W8 Import (D8)** | Idempotent, guarded OSC import; anonymized sample first. | `tkc:osc:anonymize`, `tkc:osc:import`, staff map, guards, `scripts/seed/import.sh`, synthetic fixtures `scripts/seed/fixtures/`, `scripts/seed/README.md` (§5). | W3, A18 | 3 | L |
| **W9 Legacy restyle** | Quotes detail, Emails, Home dashlets, Admin, global search match the tokens. | Dawn override partials; dashlets stack on phones; standard dashlets restyled; no markup change. | W2 | 4 | M |
| **W10 Security & access** | Roles, Finance protection, handler ACL, PII. | Roles "TKC Sales" and "TKC Service" + admin flag (§11.2; idempotent extension migration). Leak test for `tkc_AccountFinance`. Review every `getRequiredACLs()`. Import gate. jjwg geocode scheduler off. | W3, W5, W8 | 1 + 3 | M |
| **W11 QA** | Evidence per story; regression set. | Unit tests (handlers, sequence, mappers, anonymizer); metadata contract tests; browser checks vs. mockups; idempotency test (import twice, same counts); PII-leak test on anonymizer output. | all | — | cross-cutting |
| **W12 Docs** | Living documents stay current. | `docs/data-model.md`, mapping status, `docs/admin-guide.md`, `docs/user-guide/<screen>.md`, `docs/architecture.md` registers, `scripts/seed/README.md`. | all | — | cross-cutting |
| **W13 Azure (later)** | Shared environment with Docker parity. | Image (PHP 8.3), Azure Database for MySQL 8.0, CI running the W1 chain, deploy with reviewed `tkc:repair`. | W1 | — | M–L |

---

## 4. Per-module build spec

Shared conventions:
- Files: `public/legacy/custom/Extension/modules/<M>/Ext/{Vardefs,Language}/tkc_<topic>.php` and `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_<list>.php`.
- Every imported module gets `osc_id_c` (varchar 20) with non-unique index `idx_<table>_osc_id_c` in `Vardefs/tkc_osc_id.php`.
- Fields that need a change log get `audited => true`.
- Process handlers: `extensions/tkc/backend/Process/Service/RecordActions/<Module>/`, namespace `App\Extension\tkc\backend\Process\Service\RecordActions\<Module>`, process type `record-tkc-<action>`, each implements `getRequiredACLs()`.
- Shared legacy logic: `public/legacy/custom/include/TKC/` (`Sequence.php`, `ImportContext.php`, `CasePrimaryContact.php`), used by hooks and handlers.

### 4.1 Accounts (OSC Organization)

| Group | Fields (`type`, length, list) |
|---|---|
| Core reuse | `account_type` (override `account_type_dom`: `Lead` new, `Prospect` core, `Customer` core (label "Customer" = Klant), `Supplier` new, `Relation` new, `FormerCustomer` new; hide unused core keys; default `Relation`), `name`, `assigned_user_id`, `billing_address_*` (type 1 only), `phone_office` |
| RightNow / technical | `tkc_has_rightnow_c` (enum `tkc_yes_no_list`), `tkc_rightnow_version_c` (enum, 21), `tkc_rightnow_future_version_c` (enum, 36), `tkc_pod_c` (enum, 9), `tkc_oracle_datacenter_c` (enum, 3), `tkc_auto_upgrade_c` (enum `tkc_yes_no_list`, default `no`), `tkc_projects_on_portal_c` (enum `tkc_yes_no_list`), `tkc_console_url_c` (url 255), `tkc_tooling_c` (text) |
| Contract dates | `tkc_contract_begin_c`, `tkc_contract_end_c` (date) |
| Links | `tkc_replicon_name_c` (varchar 100), `tkc_sharepoint_name_c` (varchar 40), shown via `TkcUrlTemplateField` |
| Finance ref | `tkc_debiteurnummer_c` (varchar 40, indexed, audited) |
| CRM modules | *not built in Sprint 1* — `tkc_crm_modules_c` waits for org Q5 / contact Q2 (§11.7) |
| Import key | `osc_id_c` |
| **`tkc_AccountFinance` module** (A6) | `rate_angels`, `rate_snor`, `rate_body`, `expected_revenue_current_fy`, `expected_revenue_next_fy`, `other_financial_terms` (text, as in OSC, Q6) + `account_id` (relationship `tkc_accounts_tkc_accountfinance`); all audited |

- **Relationships:** core (`accounts_contacts`, `member_of`, `accounts_opportunities`, `account_cases`, `account_aos_contracts`) + `tkc_accounts_tkc_accountfinance`.
- **Layout** (`custom/modules/Accounts/metadata/detailviewdefs.php`, keep `topWidget`, `sidebarWidgets`, `recordActions`): tab Overview, panels General · RightNow · Contract · Links. Subpanels: Contacts, Member organizations, Opportunities, Contracts, Cases, Finance (ACL-limited), Activities, History, Documents.
- **List:** name, type pill, city, assigned user, debtor number, date modified. Filters: type, assigned user, has RightNow.
- **displayLogic** (cell level only, S3; type-based only, A9): POD, datacenter, auto upgrade, projects on portal and the Replicon link show when `account_type` = `Customer`; all other cells always. Spike S9 checks whether a panel hides when all its cells hide; panel-level logic is not used.
- **Actions:** core only. **Angular parts:** enum pill (type), segmented Yes/No, URL-template field.

### 4.2 Contacts (OSC Contact)

| Group | Fields |
|---|---|
| Core reuse | `first_name`, `last_name`, `title`, `account_id`, `assigned_user_id`, email widget (primary + alternatives), `phone_mobile`, `phone_work`, `phone_fax`, `phone_other`, `phone_home`, `primary_address_*`, `email_opt_out` (= NOT `marketingOptIn`; `lawful_basis` waits for Q8) |
| Identity | `tkc_contact_type_c` (enum `tkc_contact_type_list`, indexed; key `spam` = OSC 14; values 9–11 are customer names → neutral labels in git, owner sets final labels), `tkc_function_level_c` (enum: C, M, O; labels wait for Q13), `tkc_language_c` (enum: nl, en) |
| TKC programs and groups (enum `tkc_yes_no_list`) | `tkc_osc_usergroup_c`, `tkc_kmm_c`, `tkc_cafe_c`, `tkc_service_tomorrow_c`, `tkc_newsletter_c`, `tkc_freshworks_usergroup_c` |
| CRM modules | *not built in Sprint 1* (§11.7) |
| Import key | `osc_id_c` |

- **Layout:** panels Identity · Contact details · TKC programs and groups · Consent. Subpanels: Cases, Opportunities, Activities and history, Documents.
- **List:** name, account, email, phone, contact type pill, assigned user. Default filter excludes `tkc_contact_type_c = spam` (W4f; S10 checks preset filters; fallback: saved default filter, L1).
- **displayLogic:** none. **Actions:** core. **Angular parts:** pill, segmented Yes/No, multienum chips (core `multienum` + CSS).

### 4.3 Cases (OSC Incident)

| Group | Fields |
|---|---|
| Core reuse | `name` (subject), `account_id`, `assigned_user_id`, `state`, `status` (override `case_status_dom`: 12 keys, A2), `priority` (override `case_priority_dom`: P1–P4), `type` (override `case_type_dom`: `support_request`, `general_communication`, `info_message`, `project_communication`), `update_text`, `date_entered`, `created_by` |
| General | `tkc_case_ref_c` (varchar 20, indexed, read-only, A12), `tkc_primary_contact_c` / `tkc_primary_contact_id_c` (relate, required, indexed, A4) |
| Categorisation | `tkc_priority_c` (enum `tkc_impact_list`, 4), `tkc_customer_priority_c` (varchar 255), `tkc_tact_id_c` (varchar 10), `tkc_product_c` (enum `tkc_product_list`: oracle_service_cloud, egain), `tkc_category_c` (enum `tkc_case_category_list`: service_request, complaint, question, change_request). Product/category **not** required (3982 imported cases have no product). |
| Routing | `tkc_queue_c` (enum `tkc_queue_list`, 12, indexed) |
| Status | `tkc_expected_resolution_date_c` (date), `tkc_date_closed_c` (datetime, set by hook) |
| Details | `tkc_case_origin_c` (enum: email, selfservice, staff, other), `tkc_event_remark_c` (varchar 255), `tkc_event_remarks_c` (text), `tkc_tfs_project_id_c` (varchar 10, URL-template link) |
| Import key | `osc_id_c` |

- **Relationships:** `tkc_contacts_cases_primary` (1:N Contacts→Cases, rhs key `tkc_primary_contact_id_c`); `tkc_cases_opportunities` (1:N Cases→Opportunities, §4.4); core `contacts_cases`, `account_cases`, `cases_aop_case_updates`, `case_notes`, `case_tasks`.
- **Layout:** header eyebrow `tkc_case_ref_c`, pills for status, priority, queue. Panels General · Categorisation · Routing · Status · Details. Thread: `record-thread` widget (W6); S8 tests `bottomWidgets` so it reads as the Messages tab, else it stays in the sidebar. Subpanels: Contacts, Opportunities, Tasks, History/Notes, Documents.
- **List:** reference (mono), subject, account, primary contact, status/priority/queue pills, assigned user, date modified. Filters: queue, status, state, assigned user, product, category. Bulk: core mass update (queue, status, assigned user).

**Record actions and process handlers** (`recordActions` in metadata):

| Action key | Handler class | Effect | ACL | Visibility |
|---|---|---|---|---|
| `tkc-case-waiting`, `-updated`, `-unresolved`, `-solved` (status bar) | `CaseSetStatusAction` (abstract) + 4 subclasses | Set `status` + matching `state`; Solved = `Closed_Closed` | Cases edit | Always; current status pressed (W4d) |
| `tkc-case-assign-to-me` | `CaseAssignToMeAction` | `assigned_user_id` = current user; no taxonomy preset (open point) | Cases edit | Always |
| `tkc-case-solve-cx`, `tkc-case-solve-egain` | `CaseSolveAsAction` + 2 subclasses | Set product, category = Service Request, assign to current user, `Closed_Closed` | Cases edit | More menu |
| `tkc-case-route-finance` | `CaseRouteFinanceAction` | Queue Finance, assign to user in parameter `tkc.cases.finance_user_name` (D5 config), `Closed_Closed`. Forward stays manual (Q14). | Cases edit | More menu |
| `tkc-case-mark-spam`, `tkc-case-not-spam` | `CaseSpamAction` + 2 subclasses | Spam: contact type `spam`, queue SPAM, `Closed_Closed`. Not spam: clear contact type, queue "Support 1e lijn", `Open_New`. | Cases edit **and** Contacts edit | One of two, by the primary contact's type (S9: action `displayLogic` or backend `ActionAvailabilityChecker`) |

**Logic hooks (L4)** in `custom/Extension/modules/Cases/Ext/LogicHooks/tkc_cases.php`:
- `before_save` `tkc_case_reference`: generates `tkc_case_ref_c` when empty.
- `before_save` `tkc_case_date_closed`: sets `tkc_date_closed_c` when `state` changes to Closed.
- `after_save` `tkc_case_primary_contact`: links the primary contact into `contacts_cases`; fills an empty primary contact from an existing M:N contact (AOP inbound-email case).
- All skip when `ImportContext::isActive()` and the importer sets the value itself.

**Angular parts:** pill (status, priority, queue), status-bar look, thread styling (W6).

### 4.4 Opportunities (OSC Opportunity)

| Group | Fields |
|---|---|
| Core reuse | `name` (vardef override len 255), `assigned_user_id`, `amount` + `currency_id`, `account_id`, contacts (M:N), `description`, `date_closed` (expected close), `sales_stage` (override `sales_stage_dom` and `sales_probability_dom`, 8 keys, opportunity.md §6.3), `probability` |
| Overview | `tkc_closed_amount_c` (currency), `tkc_date_closed_actual_c` (date), `tkc_sales_type_c` (enum: cross_sell, up_sell), `tkc_country_c` (enum, 8) |
| Quote | `tkc_reference_c` (varchar 12, indexed, read-only in UI), `tkc_quote_date_c` (date) |
| Source | `tkc_source_case_c` / `tkc_source_case_id_c` (relate via `tkc_cases_opportunities`), `tkc_approach_c` (enum: website, phone, social, network), `tkc_initial_contact_c` (date) |
| Import key | `osc_id_c` |

- **Layout:** panels Overview · Quote · Source; sidebar summary. Subpanels: Contacts, Quotes, Activities, History/Notes, Documents.
- **List:** name, account, stage pill with percent, amount, expected close, assigned user, quote number.
- **Record action:** `tkc-opportunity-quote-number` → `OpportunityQuoteNumberAction`: `TKC\Sequence::next('quote', year)` in one transaction with a row lock; writes `tkc_reference_c` only when empty. ACL: Opportunities edit.
- **Validator:** `extensions/tkc/backend/Validation/QuoteNumberUniqueValidator` (`RecordValidatorInterface`) warns on duplicates (index non-unique; OSC uniqueness unverified).
- **Logic hook:** `before_save` `tkc_opportunity_won`: `tkc_date_closed_actual_c` = today + 1 month when `sales_stage` changes to `Closed Won` and the field is empty (OSC rule, Q15); skips in import mode.
- **Probability follows stage:** spike S11; fallback in the same hook.
- **Angular parts:** stage pill with percent.

### 4.5 Contracts (AOS_Contracts, A13)

- **Core reuse:** `name`, `reference_code`, `start_date`, `end_date`, `description`, `contract_account_id`, `opportunity_id`, `contact_id`, `contract_type` (+ keys `tkc_support`, `tkc_license`), `status` (import default `Signed`, Q17d).
- **New fields:** `tkc_support_level_c`, `tkc_support_channel_c` (enum `tkc_support_level_list`: bronze, silver, gold; Q17a), `tkc_hours_in_contract_c` (int), `tkc_change_threshold_c` (int), `tkc_replicon_project_c` (varchar 60), `tkc_duration_c` (int, unit open), `osc_id_c`.
- **Layout:** panel Contract · "Support contract details" · "Licence contract details". S7 go: cell `displayLogic` on `contract_type`; no-go: legacy panels always shown.
- **Accounts subpanel columns:** name, type, start, end, support level.
- `renewal_reminder_date` is **not** set on import (no reminders fire).

### 4.6 Restyle-only screens

| Screen | UI | Scope | Level |
|---|---|---|---|
| Quotes | Angular list, legacy record | List columns (number, title, account, opportunity, stage pill, total, valid until) in `custom/modules/AOS_Quotes/metadata/listviewdefs.php`; detail + line items: Dawn CSS only | 2 + 4 |
| Emails | Legacy | Dawn CSS: list, folder tree, preview, compose; no markup change | 4 |
| Home | Legacy | Dawn CSS on standard dashlets; stack on phones; dashlet pills deferred (A1 group) | 4 |
| Admin, Studio, global search | Legacy (78 % of Admin) | Tokens only via the Dawn override: same font, base size, link colour, focus ring as Angular | 4 |

---

## 5. Import plan (OSC → SuiteCRM)

### 5.1 Components (D8)

| Part | Path |
|---|---|
| Command | `extensions/tkc/backend/Import/Console/OscImportCommand.php`: `tkc:osc:import <entity\|all> --source=<dir> [--dry-run\|--execute] [--limit=n] [--since=<osc-id>]` |
| Anonymizer | `extensions/tkc/backend/Import/Console/OscAnonymizeCommand.php`: `tkc:osc:anonymize --in=data/osc/export --out=data/osc/anon`. Keeps ids, menu ids, dates, relations, volumes; replaces names, emails, phones, streets, free text, subjects with synthetic values; deterministic seed. |
| Mappers | `extensions/tkc/backend/Import/Mapper/{Account,Contact,Opportunity,Contract,Case,CaseThread}Mapper.php`; menu id → key tables in `extensions/tkc/config/tkc_import_maps.php` |
| Upsert | `Import/Upserter.php`: `SELECT id FROM <table> WHERE osc_id_c = ? AND deleted = 0`, then create/update via the bean; batches of 200; checkpoint in `data/osc/import-state/` |
| Guards | `Import/Guard/ImportGuard.php` (§5.3) |
| Staff map | `data/osc/staff-map.csv` (gitignored): OSC account id → SuiteCRM `user_name`; unmapped → inactive user `tkc_migration` |
| Wrapper & docs | `scripts/seed/import.sh`, `scripts/seed/README.md`, synthetic fixtures `scripts/seed/fixtures/*.jsonl` (synthetic only) |
| Logs | `data/osc/import-logs/<run>.log`: counts and ids only, no field contents |

### 5.2 Order and volumes (tst1 counts; production unknown)

| # | Entity | Source | Key | Depends on | tst1 volume |
|---|---|---|---|---|---|
| 0 | Users | user-supplied list (admin), not from OSC | `user_name` | — | not read |
| 1 | Accounts (pass 1 fields, pass 2 `parent_id`) | organizations | `osc_id_c` | 0 | 738 |
| 1b | `tkc_AccountFinance` | organizations (6 finance fields) | `account_id` | 1 | ≤ 738 |
| 2 | Contacts + emails + phones | contacts | `osc_id_c` | 1 | 7981 (365 SPAM) |
| 3 | Opportunities + 1 contact link | opportunities | `osc_id_c` | 1, 2 | 1964 |
| 4 | Contracts (both types) | Support.SupportContract, Support.LicentieContract | `osc_id_c` | 1, 2, 3 | not counted |
| 5 | Cases + primary contact, other contacts, account | incidents | `osc_id_c` | 1, 2, A4 | 4053 |
| 6 | Opportunity → source case | opportunities `Support.Incident` | — | 3, 5 | ≤ 1964 |
| 7 | Case thread → AOP_Case_Updates | incident threads | `osc_id_c` on AOP_Case_Updates (new field) | 5 | **not counted** (Q13) |
| — | Attachments, audit history | — | — | — | not in the first run (A17) |

### 5.3 Guards (all required; security-officer gate items)

| Risk (assessment §8.2) | Guard |
|---|---|
| `creationNotify` emails the contact | Set `$_REQUEST['module'] = 'Import'` (checked at `CaseUpdatesHook.php:436`) |
| AOP update and closure emails | In-process `$sugar_config['aop']['enable_aop'] = false` (`isAOPEnabled()`, `util.php:124-132`); `$case->send_closure_email = false` |
| AOW workflows | `$_REQUEST['module'] = 'Import'` skips flows without `run_on_import` (`AOW_WorkFlow.php:236`); before a real run, list flows with `run_on_import = 1` and refuse if any exist |
| Assignment notices | `$bean->save(false)` |
| Geocoding (PII) | Refuse to run unless the jjwg geocode scheduler is inactive and no geocoding key is set |
| Outbound email | Refuse unless the outbound host is on the sink allowlist (A19) |
| TKC hooks | `TKC\ImportContext::activate()` |
| Audit/modified fields | `update_date_modified = false`, `update_modified_by = false`, `set_created_by = false`; set `date_entered`, `date_modified`, `created_by`, `modified_user_id` from OSC |

### 5.4 Sequence of runs

1. **Synthetic fixtures** (CI, every sprint): import twice, identical counts.
2. **Security-officer gate A**: review anonymizer and guards. Then the user runs the extract (A18) and `tkc:osc:anonymize` locally; output stays in `data/osc/anon/`.
3. **Anonymized full-volume import** into local Docker (Sprint 2): measure run time, row counts, unmapped values (product, category, status).
4. **Security-officer gate B** before real data: guards in the target environment, data minimisation. User approves.
5. Real-data dry run, then the real run. Document-agent updates `scripts/seed/README.md` and mapping status.

**Not migrated:** SLA data, Projects, surveys, outreach, OSC audit log, rule state, `Login`, `PasswordEncrypt`, `unrncrm`, `pwrncrm`, company image, REST-only custom fields (A11, A15, A16), Disposition as a field (A10), OSC category tree values, `mailHeader`, attachments (A17), standard-text bodies, the staff group on a case (A5).

---

## 6. Dependency graph and critical path

```
W1 build chain ──┬─> W2 token generator (D6) ──> base theme ──┬─> W5 screen restyles
                 │                                            └─> W9 legacy restyle
                 └─> W3 vardefs + lists + relationships ──┬─> W5 layouts / list / filters
                        │  (incl. A4 primary-contact rel, ├─> W4 components (pill, Yes/No)
                        │   tkc_sequences, Finance module)├─> W6 thread fields ──> thread import
                        │                                 └─> W8 import mappers
Spikes S7–S11 ──────────┘ (layout, thread placement, action visibility)
A18 extract + gate A ──> anonymizer ──> anonymized full import (Sprint 2) ──> screen QA at volume
Staff map (Q8/Q9) ──────> every import with an owner field
W5 Cases actions + A2 status lists ──> case import status mapping
W10 roles + Finance module ──> leak test ──> gate B ──> real import
```

**Critical path:** W1 → W3 → W8 import framework → anonymized import → W5 Cases → W6 thread → W10 gate B → real import.

| Before | After | Why |
|---|---|---|
| D6 generator | any screen restyle | No raw values; one token source |
| Vardefs + Repair | layouts, list defs, import | They reference the fields |
| `tkc_contacts_cases_primary` + hook | case import | Required field; AOP routing needs the M:N link |
| Status, priority, queue lists (A2, A5) | status-bar actions, case import | Both use the keys |
| `tkc_sequences` + `TKC\Sequence` | case-reference hook, quote-number action, sequence start values | Import sets each sequence above the highest imported number |
| Staff map | any import | Owner fields |
| Gate A | anonymizer on the real extract | It handles real data |
| Gate B | real-data import | `data-privacy.md` |

---

## 7. Proposed epics (delivery order)

| # | Epic | Goal | Size |
|---|---|---|---|
| E1 | Platform foundation | One build/deploy chain from a clean checkout; test harnesses (PHPUnit, Karma, metadata contract tests); Mailpit; A8 naming rule; registers of overridden viewdefs/lists; spikes S7–S11. | M |
| E2 | Design tokens and base theme | D6 generator → Angular token file + Dawn token block; base components in both UIs; WCAG 2.2 AA focus and contrast checked in both UIs. | M |
| E3 | Data model | All TKC fields, lists, relationships, `tkc_sequences`, `tkc_AccountFinance` after `tkc:repair`; `docs/data-model.md` complete; no UI change. | M |
| E4 | OSC import framework and anonymized sample | Import/anonymize commands, guards, staff map, fixtures, mappers (except thread), anonymized full-volume run with gate A. | L |
| E5 | Shared UI components | Enum pill, segmented Yes/No, URL-template field, record-action base + status-bar look, default list filter, "Save and close" (optional). | M |
| E6 | Accounts screen | Layout, list, filters, subpanels, type-based displayLogic, Finance subpanel; matches the mockup. | M |
| E7 | Contacts screen | Layout, list with SPAM default filter, panels, Yes/No flags, email/phone rows (CSS). | S–M |
| E8 | Cases screen and actions | Layout, list, pills, primary contact, queue, status bar, 11 process handlers, reference and closed-date hooks, SPAM write to Contacts. | L |
| E9 | Case thread | Entry type + channel, thread layout in metadata, type styling, long text, thread import, standard texts (after Q16). | M |
| E10 | Opportunities and quote number | Stage list, layout, pills with percent, quote-number action + sequence, source-case relationship, won-date rule. | M |
| E11 | Contracts | AOS_Contracts types, fields, layout (Angular or legacy per S7), Accounts subpanel, contract import. | S–M |
| E12 | Legacy restyle | Quotes detail/list, Emails, Home dashlets, Admin, global search on tokens; dashlets stack on phones. | M |
| E13 | Security, roles and real-data import | Roles via migration, Finance leak test, handler ACL review, gate B, real-data dry run and run, cut-over checklist. | M |
| E14 | Azure environment (later) | Image, managed MySQL 8.0, CI/CD with the build chain and reviewed `tkc:repair`. | M–L |
| — | *Enhancements (not scheduled)* | Pipeline board, list totals, "My day" dashlet, dashlet pills, thread CC/BCC (A1). | — |

---

## 8. Sprint outline (2-week sprints, one agent team)

| Sprint | Epics | Demo at sprint end |
|---|---|---|
| **0** | E1, E2 (generator + base theme), spikes S7–S11 | Clean Docker install runs `scripts/build.sh`; Repair SQL reviewed; TKC theme in Angular and a legacy page (Admin) with the same tokens; spike results recorded; test suites green. |
| **1** | E3, E4 part 1 (commands, guards, upsert, fixtures, anonymizer), gate A | All fields exist after `tkc:repair`; synthetic import of all entities runs twice with identical counts; anonymizer passes the PII-leak test; gate A signed; the user runs the extract. |
| **2** | E4 part 2 (anonymized full import), E5, E6, E7 | Accounts and Contacts redesigned on ~8 700 anonymized records; pills and Yes/No work; SPAM filter works; Finance visible to Sales only. |
| **3** | E8, E9 part 1 (fields, metadata layout, thread import) | Cases redesigned on 4 053 anonymized cases with threads; status bar, Solve as CX/eGain, Route to Finance, Mark as spam work; Mailpit stays empty during import. |
| **4** | E9 part 2, E10, E11 | Opportunities with quote number and source case; contracts on the account; thread entry types styled; standard texts if Q16 is answered. |
| **5** | E12, E13 part 1 (roles, leak test, ACL review), performance at volume | Quotes, Emails, Home, Admin restyled; iframe-seam check passes; security officer signs the role model and Finance protection. |
| **6** | E13 part 2 (gate B, real-data dry run), full regression, UAT, docs, release notes | UAT-ready build; real-data dry-run report (counts and unmapped values only). |
| **7+** | E14, Enhancements, cut-over rehearsal | Shared Azure environment; scope per owner priorities. |

---

## 9. Risks and mitigations

| Risk | Likelihood / impact | Mitigation |
|---|---|---|
| Import emails customers or users | M / **H** | Guards (§5.3), Mailpit + allowlist, gates A and B, dry run first |
| No field-level ACL → Finance data leaks | H / H | A6 separate module with module ACL + leak test on all read paths |
| Full-copy viewdefs hide upstream changes | M / M | Register in `docs/architecture.md`; diff at every merge; metadata contract test per module |
| Core list overrides drift or code reads a hidden key | M / M | Add keys only, never rename (D7); `Closed Won`/`Closed Lost` stay; search core for key use at merges |
| Vardef overrides of core columns (`opportunities.name` 255, `aop_case_updates.description` longtext) | L / M | Review Repair SQL; list in the register |
| Angular extension breaks on upstream upgrade | M / M | Small, registry-based extension; rebuild + smoke test at every merge |
| Theme entry depends on `styles[1]` in `base-angular.json` (S1) | L / M | Re-check at merges; build test asserts a TKC rule is in the bundle |
| Visible iframe seam | H / M | One generator; Dawn override sets font, base size, links, focus ring; browser check of both UIs per restyle story |
| PHP/MySQL parity | M / M | Docker only (D9); Azure uses the same image and MySQL 8.0; CI on PHP 8.3 |
| Repair not run after deploy (`Ext/` not in git) | M / H | `scripts/build.sh` and the pipeline always run `tkc:repair` |
| Import performance at production volume | M / M | Batches, `osc_id_c` indices, AOW/AOP off, timing measured in Sprint 2 |
| Thread volume and text size unknown (≤ 1 MB entries) | M / M | `longtext` override; measure in anonymized run; extract counts entries |
| Status-bar actions and visibility rules depend on unverified metadata (S9) | M / L | Fallback: backend `ActionAvailabilityChecker` (`core_services.yaml:123`) |
| Case reference/sequence collides with imported numbers | L / M | Import sets each sequence above the highest imported value per year; non-unique index + validator |
| Studio used on a TKC viewdef | M / M | S3 rules; diff review; no panel-level `displayLogic` |
| Customer names in dropdown labels (contact types 9–11) enter git | M / L | Neutral labels in git; owner sets final labels; security officer checks |

---

## 10. Open questions that block specific epics

| Epic | Blocking question | Mapping ref | Needed by |
|---|---|---|---|
| E4, E13 | Production volumes, extract method and who runs it (A18), thread counts | incident.md §9.2, Q13 | Sprint 1 |
| E4 | Staff → user map: by login or email; owner of inactive staff records | org Q8, contact Q6, opp Q9 | Sprint 1 |
| E6 | Finance field meaning/type; Replicon and SharePoint URLs; Site Werkzaamheden report | org Q6, Q12, Q4 | Sprint 2 |
| E6, E7 | CRM modules ("State"): keep or drop | org Q5, contact Q2 | Sprint 2 (A16 default) |
| E7 | Consent model (`MaOptIn`, newsletter, lawful basis) — Legal; function-level labels; SPAM import | contact Q8, Q13, Q14 | Sprint 2 |
| E8 | A2 statuses/priorities; A4 primary contact; A5 group vs. queue; A10 Disposition; A11 customer priority; "Assign to me" presets | incident Q3–Q7 | Sprint 3 |
| E8 | Production ids of product 551, category 552, status 103 (needs read-only production credentials) | incident Q21 | Sprint 3 |
| E8, E9 | AOP email on staff reply; how the inbound mailbox creates cases; short source/channel lists | incident Q14, Q20 | Sprint 3 |
| E9 | Bodies of the standard texts 10, 20, 33, 40, 41, 341, 371 | incident Q16 | Sprint 4 (E9 part 2 ships without them) |
| E10 | Quote number yearly restart; AOS quotes needed (A7); two amounts/dates; approach; won-date rule | opp Q4, Q5, Q6, Q15 | Sprint 4 |
| E11 | `Kanalen` vs. support level; unit of `Duur` and `ChangeDrempel`; `DatumRenewal`; contract status; Incident link | org Q17 | Sprint 4 |
| E12 | Email view in use; dashboard tabs per role; Activity Stream in use | emails, home mockup notes | Sprint 5 |
| E13 | Security officer: accept A6, credential drops (org Q7, Q16), import guards | assessment §8.2 | Sprint 5 |
| all | Base currency EUR (A20) | backlog | Sprint 1 |

---

## Appendix: new spikes (planning tasks, Level 3, read-out to the architect)

| ID | Question | Fallback if no-go |
|---|---|---|
| S7 | Does a D5 delta of `module_routing` (`contracts: record: true`) render AOS_Contracts in Angular with a TKC `detailviewdefs.php` without line items? | Legacy record with two always-shown panels |
| S8 | Can `record-thread` take `tkc_entry_type_c` and `tkc_channel_c` in item and create layouts, use `dynamicClass` on the entry type, and render in `bottomWidgets`? | Thread stays in the sidebar; composer extras become an L3 component (M) |
| S9 | Do `recordActions` support `displayLogic` on field values? Does a panel hide when all its cells hide? | Backend `ActionAvailabilityChecker`; accept an empty panel header |
| S10 | Does `enum-radio` show an empty "not set" state and a clear action? Does the Angular list support a preset default filter? | Small TKC Yes/No edit component (M); saved default filter (L1) |
| S11 | Does the 8.10 Angular edit view update `probability` when `sales_stage` changes? | Set it in the `tkc_opportunity_won` hook (on save) |

S2 (dependent dropdown) is no longer needed (flat Product/Category lists). S4 (API Platform path) is needed only if a TKC API resource is added; none is planned.

---

## 11. Addendum — answers to the backlog-draft gaps (architect, 2026-11-20)

Answers gaps 2, 4, 5, 6, 7 and 14 of `docs/plan/backlog-draft.md` §6.2 and corrects §1–§10 (see 11.7).

### 11.1 Which data-model assumptions must be decided before Sprint 1

Schema changes happen only through vardefs + `tkc:repair` (D2). **Adding** a field later is cheap (one small story + `--only-fields` re-import). **Changing** a type, list keys or a relationship after the import is expensive (data conversion). **Removing** leaves a dead column. So decide before Sprint 1 only the items that fix a type, key set, relationship or name; "keep or drop" items default to **do not build** and can be added later.

| Assumption | Fixes type / keys / structure? | Decide before | If late |
|---|---|---|---|
| A8 naming | yes (all column names) | **Sprint 1** | Rename = data migration |
| A2 status / priority keys | yes | **Sprint 1** | Key remap of all cases |
| A3 three-state enum vs. bool | yes (9 fields) | **Sprint 1** | Type conversion; "not set" lost |
| A4 primary contact relate | yes | **Sprint 1** | Back-fill from M:N; some cases ambiguous |
| A5 queue only | queue keys yes; group field additive | Queue: **Sprint 1**; group: later | Group field = S |
| A6 Finance module vs. fields | yes | **Sprint 1** | Move data between tables |
| A12 case reference field | yes | **Sprint 1** | Rename + re-index |
| A14 `name` len 255; `tkc_approach_c` vs. `lead_source` | yes | **Sprint 1** | Names > 50 chars cut on import |
| A7 quote-number field + `tkc_sequences` | field/table yes; AOS copy additive | Field: **Sprint 1**; AOS: Sprint 4 | AOS link = M |
| A13 AOS_Contracts vs. `tkc_` module | yes | **before Sprint 4** (E11 builds contract vardefs then) | — |
| A10 Disposition | additive | later | S + re-import of the column |
| A11 customer priority, dropped fields | additive | later | S each |
| A15 phones / SPAM import | import rules only | before Sprint 2 | Re-run the import |
| A16 CRM modules, rate-class fields | additive | later | **Not built in Sprint 1** (changes A16, see 11.7) |

**New E4 requirement:** `tkc:osc:import <entity> --only-fields=<f1,f2>` updates only the named columns on existing rows, so a late field never overwrites user edits after go-live.

### 11.2 Roles and permission sets

**Two roles plus the admin flag.** Finance staff work the Finance queue and use **TKC Service**. Admin = SuiteCRM `is_admin` (bypasses ACL; the security officer must accept that admins see Finance data). A "TKC Finance" role only if Finance staff must see `tkc_AccountFinance` (ASSUMPTION: no). ACL levels All / Owner / None; Import = None for both (data enters only via `tkc:osc:import`).

| Module | TKC Sales: access · view · list · edit · delete · export · mass update | TKC Service: same columns |
|---|---|---|
| Accounts | ✓ · All · All · All · Owner · None · All | ✓ · All · All · All · None · None · None |
| Contacts | ✓ · All · All · All · Owner · None · All | ✓ · All · All · All · None · None · None |
| Opportunities | ✓ · All · All · All · Owner · None · All | ✓ · All · All · None · None · None · None |
| Cases | ✓ · All · All · All · None · None · None | ✓ · All · All · All · None · None · All |
| Case Updates (AOP) | ✓ · All · All · Owner · None · None · None | ✓ · All · All · Owner · None · None · None |
| AOS_Contracts | ✓ · All · All · All · Owner · None · None | ✓ · All · All · None · None · None · None |
| `tkc_AccountFinance` | ✓ · All · All · All · None · None · None | **Disabled** |
| Quotes, Products | ✓ · All · All · All · Owner · None · None | ✓ · All · All · None · None · None · None |
| Calls, Meetings, Tasks, Notes, Documents, Emails | ✓ · All · All · Owner · Owner · None · None | same as Sales |

- **Cases delete = None** for all roles (OSC archives incidents). **Export = None** for all roles (PII minimisation; owner may relax with the security officer). "Mark as spam" checks Cases edit + Contacts edit.
- Seeding: idempotent extension migration `extensions/tkc/Migrations/Version…SeedRoles.php` (data only); roles recorded in `docs/admin-guide.md`. No security groups in phase 1.

### 11.3 Process handlers in E8 and E10

**11 process types** (10 in E8, 1 in E10) and **3 abstract bases**, under `extensions/tkc/backend/Process/Service/RecordActions/`:

| # | Epic | Process type | Class | Extends | Required ACLs |
|---|---|---|---|---|---|
| 1 | E8 | `record-tkc-case-waiting` | `Cases/CaseSetWaitingAction` | `Cases/AbstractCaseSetStatusAction` | Cases edit |
| 2 | E8 | `record-tkc-case-updated` | `Cases/CaseSetUpdatedAction` | same | Cases edit |
| 3 | E8 | `record-tkc-case-unresolved` | `Cases/CaseSetUnresolvedAction` | same | Cases edit |
| 4 | E8 | `record-tkc-case-solved` | `Cases/CaseSetSolvedAction` | same | Cases edit |
| 5 | E8 | `record-tkc-case-assign-to-me` | `Cases/CaseAssignToMeAction` | — | Cases edit |
| 6 | E8 | `record-tkc-case-solve-cx` | `Cases/CaseSolveCxAction` | `Cases/AbstractCaseSolveAsAction` | Cases edit |
| 7 | E8 | `record-tkc-case-solve-egain` | `Cases/CaseSolveEgainAction` | same | Cases edit |
| 8 | E8 | `record-tkc-case-route-finance` | `Cases/CaseRouteFinanceAction` | — | Cases edit |
| 9 | E8 | `record-tkc-case-mark-spam` | `Cases/CaseMarkSpamAction` | `Cases/AbstractCaseSpamAction` | Cases edit + Contacts edit |
| 10 | E8 | `record-tkc-case-not-spam` | `Cases/CaseNotSpamAction` | same | Cases edit + Contacts edit |
| 11 | E10 | `record-tkc-opportunity-quote-number` | `Opportunities/OpportunityQuoteNumberAction` | — | Opportunities edit |

Other code: E10 validator `QuoteNumberUniqueValidator`; E8 3 Cases hooks; E10 1 Opportunities hook; E8 (only if S9 no-go) `Process/Availability/CaseSpamActionAvailability`.

### 11.4 Fields on AOP_Case_Updates

File `public/legacy/custom/Extension/modules/AOP_Case_Updates/Ext/Vardefs/tkc_thread.php` (+ `Language/en_us.tkc_thread.php`).

| Field | Type / len | List | Required | Default | Index | UI |
|---|---|---|---|---|---|---|
| `tkc_entry_type_c` | enum / 20 | `tkc_entry_type_list` | no (hook sets it) | `staff` | no | thread item (`dynamicClass`), composer |
| `tkc_channel_c` | enum / 20 | `tkc_channel_list` | no | empty (composer sets `phone` for a customer entry) | no | thread item pill, composer |
| `osc_id_c` | varchar / 20 | — | no | — | `idx_aop_case_updates_osc_id_c` (non-unique) | hidden |
| `description` (core override) | `longtext` | — | — | — | — | OSC text up to 1 MB |

`tkc_entry_type_list`: `note` Internal note (OSC 1 Opmerking; 6/7 rule note — ASSUMPTION; internal = 1) · `staff` Agent reply (2) · `customer` Customer (3; 5 Chat — ASSUMPTION) · `customer_proxy` Customer (logged by agent) (4; 8 voice — ASSUMPTION). The importer sets `internal` from the key.

`tkc_channel_list` (Q20 short list): `email` (OSC 1, 2, 9) · `phone` (3) · `web` (6, 7) · `chat` (8) · `fax` (4) · `social` (10–12, 14, 16–43) · `other` (5, 1001–1008, unknown ids — logged).

Import rules per entry: staff `account` → `assigned_user_id` via the staff map; `contact` → `contact_id` via contact `osc_id_c`; `createdTime` → `date_entered`. Plain text (contentType 1): escape + newlines to `<br>`. HTML (contentType 2): clean with `SugarCleaner::cleanHtml` before save (the thread renders `description` as HTML) — **security-officer item (XSS)**. `mailHeader` dropped.

### 11.5 `priority` vs. `tkc_priority_c`

| | Core `priority` | `tkc_priority_c` |
|---|---|---|
| OSC source | `Incident.SeverityId`, "Prioriteit" (4 items) | `Incident.C$TkcPrioriteit`, "TKC Prioriteit" (ids 209–212) |
| Meaning | Priority number (severity) | Impact on the customer's operation |
| UI label | **Priority** | **Impact** (not "TKC priority", to avoid confusion) |
| List | `case_priority_dom` override: `P1`–`P4` "Priority 1–4" (P4 new) | `tkc_impact_list`: `no_impact`, `some_impact`, `production_disrupted`, `immediate_action` |
| Default | Empty — ASSUMPTION: override `case_priority_default_key` (core `P2`) to empty; E3 checks core code that reads it | Empty |
| Pill | P1 danger · P2 warning · P3 neutral · P4 neutral plain | immediate_action solid-danger · production_disrupted danger · some_impact warning · no_impact neutral |
| Shown in | Header pill, list column, list filter, Categorisation | Categorisation, list filter (not a default column) |
| tst1 usage | 17 of 4053 cases | not counted |

If the import shows agents use Impact more, swap them in header and list (metadata, S).

### 11.6 `tkc_AccountFinance` — files, location, upgrade safety

**Hand-written files, not Module Builder.** Module Builder keeps its working copy in `custom/modulebuilder/` (gitignored), writes a package zip to `upload/` (gitignored) and a row in `upgrade_history` — none reproducible from git.

| Path (under `public/legacy/`) | Content |
|---|---|
| `modules/tkc_AccountFinance/tkc_AccountFinance.php` | Bean class, extends `Basic` |
| `modules/tkc_AccountFinance/vardefs.php` | Table `tkc_accountfinance`, 6 audited text fields, indexed `account_id`, 1:N relationship `tkc_accounts_tkc_accountfinance` declared inline, ACL implements |
| `modules/tkc_AccountFinance/language/en_us.lang.php` | Labels |
| `modules/tkc_AccountFinance/metadata/{detailviewdefs,editviewdefs,listviewdefs,searchdefs,SearchFields}.php`, `metadata/subpanels/default.php` | Layouts, subpanel columns |
| `modules/tkc_AccountFinance/Menu.php` | Empty (no create from navbar) |
| `custom/Extension/application/Ext/Include/tkc_AccountFinance.php` | `$beanList`, `$beanFiles`, `$moduleList` |
| `custom/Extension/application/Ext/Language/en_us.tkc_AccountFinance.php` | Module label "Finance" |
| `custom/Extension/modules/Accounts/Ext/Vardefs/tkc_finance_link.php` | `link` on Accounts |
| `custom/Extension/modules/Accounts/Ext/Layoutdefs/tkc_finance_subpanel.php` | Finance subpanel |
| `extensions/tkc/Migrations/Version…SeedFinanceAcl.php` | Idempotent `ACLAction::addActions('tkc_AccountFinance', 'module')` + role grants (11.2) |

Upgrade safety (D11): new `tkc_` directory (no upstream conflict); all else in `custom/Extension/` or `extensions/tkc/`; remaining dependency is the legacy `Basic` template + bean API, re-checked by the metadata contract test. In `$moduleList` → Angular renders it and role ACL lists it; Display Modules hides it from the navbar (record in `docs/admin-guide.md`). **To verify in E3:** a record opened from the Accounts subpanel gets an Angular route while hidden from the navbar.

**Rule for all TKC relationships:** `custom/metadata/` is **gitignored** (`custom/*` with only `Extension/`, `modules/`, `themes/`, `include/`, `language/` re-included), so Studio/Module-Builder `custom/metadata/<rel>MetaData.php` files would be lost. Every TKC relationship is therefore a **1:N foreign-key relationship declared in a vardef** (no join table): `tkc_accounts_tkc_accountfinance` (module vardefs), `tkc_contacts_cases_primary` and `tkc_cases_opportunities` (`custom/Extension/modules/{Cases,Opportunities}/Ext/Vardefs/`). `tkc_sequences` is defined in `custom/Extension/application/Ext/TableDictionary/tkc_sequences.php`; E3 confirms with `tkc:repair --dry-run` that Repair creates it. If an M:N relationship is ever needed, re-include `custom/metadata/` in `public/legacy/.gitignore` in the same story.

### 11.7 Corrections to §1–§10

1. §7 E8: "11 process handlers" → **10** (E8 + E10 = 11, see 11.3).
2. §2 A16: `tkc_crm_modules_c` is **not built in Sprint 1**; added when the owner answers org Q5 / contact Q2. The mockups show it as pending.
3. §3 W10 and §7 E13: roles = **TKC Sales, TKC Service + admin flag** (not four roles).
4. §5.1: add `--only-fields` to `tkc:osc:import`.
