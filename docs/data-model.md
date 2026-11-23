# Data model — custom fields, modules, relationships, dropdowns

> Living document: updated in the same story as the change. Owner: document-agent.

## How to read this document
- TKC fields are extension vardefs on the main table of the module (D2). They are not Studio fields. They have no `_cstm` table.
- Names follow the naming rule (A8) in `.claude/rules/suitecrm-customization.md` § Database. A field on a core module is `tkc_<english_name>_c`. A list is `tkc_<name>_list`. A label is `LBL_TKC_<NAME>`.
- Repair creates every column and index. Run `tkc:repair` (Quick Repair and Rebuild). Never write `ALTER TABLE` by hand.
- Every TKC field has `comment = 'TKC SUITE-<n>'` in its vardef. The number is the story that built it.
- Each section below has one table. Each story fills its own section only, so that parallel stories do not conflict.
- "OSC source" names the field in `docs/osc-mapping/<entity>.md`.
- Where a column says "Notes", it also lists the list name or the default value.

## Tables
### `tkc_sequences` (SUITE-62)
One row for each counter name and year. The sequence service uses it (see `docs/architecture.md` § Sequences). It is a plain table, not a module. It has no `id` and no `deleted` column. The engine is InnoDB. The character set is the core default (utf8).

| Column | Type / length | Null | Notes |
|---|---|---|---|
| `seq_name` | varchar, 40 | NOT NULL | Counter name, for example `quote`. Part of the primary key. |
| `seq_year` | int | NOT NULL | Year, 2000 to 2099. Part of the primary key. |
| `seq_value` | int | NOT NULL | Last value that `next()` gave or that was set. Default 0. |
| `date_modified` | datetime | NULL | UTC time of the last change. |

- Primary key: (`seq_name`, `seq_year`).
- Source: the TableDictionary extension `public/legacy/custom/Extension/application/Ext/TableDictionary/tkc_sequences.php`. Quick Repair and Rebuild (`tkc:repair`) creates the table.
- The three key and value columns must have `'isnull' => 'false'` together with `'required' => true`. Without it, Repair writes NULL on the primary key columns, and MySQL 8 refuses the `CREATE TABLE`.
- Do not name a column `last_value` or `year`. `LAST_VALUE` is a reserved word in MySQL 8.
- Repair SQL on a local database: one `CREATE TABLE tkc_sequences`. SUITE-72 does the authoritative run.
- OSC source: none. The table holds the numbers that replace OSC counters (see `docs/osc-mapping/opportunity.md` § 6.2).

### OSC import key `osc_id_c` (SUITE-71)
- `osc_id_c` is the numeric OSC record ID. It is the only upsert key of the OSC import. The import finds a record with `WHERE osc_id_c = ? AND deleted = 0`. The upserter of the core modules (SUITE-75) must also read `deleted = 1` rows and never re-create a deleted record, abort a row with more than one live match, reject an empty or non-numeric key, and check after each run that no key has two live rows (SUITE-71 security F1). SUITE-132 has the same rules for the thread table.
- `tkc_case_ref_c` is the human case reference. It is never the same value as `osc_id_c`. Never use one in place of the other.
- A record that a user creates in SuiteCRM has an empty `osc_id_c`.
- The field is in these modules: Accounts, Contacts, Cases, Opportunities (SUITE-71) and AOP_Case_Updates (SUITE-68). AOS_Contracts gets it in SUITE-144.
- The vardef is the same in every module. Type varchar, length 20, nullable. `audited` false. `importable` true. `inline_edit` false. `massupdate` false. `unified_search` false. `duplicate_merge` `disabled`. Label `LBL_TKC_OSC_ID` ("OSC ID"). No layout shows the field.
- The index is not unique, because of soft deletes. Names: `idx_accounts_osc_id_c`, `idx_contacts_osc_id_c`, `idx_cases_osc_id_c`, `idx_opportunities_osc_id_c`.
- Files for each module `<M>`: `public/legacy/custom/Extension/modules/<M>/Ext/Vardefs/tkc_osc_id.php` and `public/legacy/custom/Extension/modules/<M>/Ext/Language/en_us.tkc_osc_id.php`. The `$dictionary` keys are `Account`, `Contact`, `Case` and `Opportunity`. The Contacts `Ext/Language` folder is new.
- The Duplicate action does not copy `osc_id_c`. See `docs/architecture.md` § Overrides register (`tkc_duplicate_ignore.php`).
- Repair SQL (local): for each of the four modules, one `add COLUMN osc_id_c varchar(20) NULL` and one `ADD INDEX idx_<table>_osc_id_c`. SUITE-72 does the authoritative run.
- Contract test: the manifest is `Tests/Contract/Fields/suite71_osc_id.php`.

## Accounts
Table `accounts`. Dictionary key `Account`. Story SUITE-63. Every field is importable. Vardef file: `public/legacy/custom/Extension/modules/Accounts/Ext/Vardefs/tkc_accounts.php`. Label file: `public/legacy/custom/Extension/modules/Accounts/Ext/Language/en_us.tkc_accounts.php`.

| Field | Type / length | Label | OSC source | Story | Notes |
|---|---|---|---|---|---|
| `tkc_has_rightnow_c` | enum, 10 | Has RightNow | `Org.C$HasRightnow` | SUITE-63 | List `tkc_yes_no_list`. Radio, row direction. |
| `tkc_rightnow_version_c` | enum, 10 | RightNow version | `Org.C$RightnowVersion` | SUITE-63 | List `tkc_rightnow_version_list`. |
| `tkc_rightnow_future_version_c` | enum, 10 | RightNow future version | `Org.C$RightnowFutureVersion` | SUITE-63 | List `tkc_rightnow_future_version_list`. |
| `tkc_pod_c` | enum, 10 | POD | `Org.C$Pod` | SUITE-63 | List `tkc_pod_list`. |
| `tkc_oracle_datacenter_c` | enum, 10 | Oracle datacenter | `Org.C$OracleDatacenter` | SUITE-63 | List `tkc_oracle_datacenter_list`. |
| `tkc_auto_upgrade_c` | enum, 10 | Auto upgrade program | `Org.C$AutoUpgrade` | SUITE-63 | List `tkc_yes_no_list`. Default `no`. Radio. |
| `tkc_projects_on_portal_c` | enum, 10 | Projects on portal | `Org.C$ProjectsOnPortal` | SUITE-63 | List `tkc_yes_no_list`. Radio. |
| `tkc_console_url_c` | url, 255 | Console URL | `Org.C$ConsoleUrl` | SUITE-63 | |
| `tkc_tooling_c` | text | Tooling | `Org.C$Tooling` | SUITE-63 | |
| `tkc_contract_begin_c` | date | Contract start date | `Org.C$BegindatumContract` | SUITE-63 | Audited. Range search. |
| `tkc_contract_end_c` | date | Contract end date | `Org.C$EinddatumContract` | SUITE-63 | Audited. Range search. |
| `tkc_replicon_name_c` | varchar, 100 | Replicon name | `Org.C$Repliconname` | SUITE-63 | |
| `tkc_sharepoint_name_c` | varchar, 40 | SharePoint name | `Org.C$Sharepointname` | SUITE-63 | |
| `tkc_debtor_number_c` | varchar, 40 | Debtor number | `Org.C$Debiteurnummer` | SUITE-63 | Audited. Index `idx_accounts_tkc_debtor_number_c`. The planning name was `tkc_debiteurnummer_c`. It was renamed to an English name (A8). |
| `osc_id_c` | varchar, 20 | OSC ID | `Org.OrgId` | SUITE-71 | Import key. Index `idx_accounts_osc_id_c` (not unique). `inline_edit` false. `massupdate` false. See § OSC import key. |

The Yes/No fields have the type `enum` with `displayType` `radio`, not `bool`. The reason: a radio shows three states (Yes, No, Not set). A `bool` has no "not set" state.

Not built on Accounts:
- `tkc_crm_modules_c` (OSC `Org.State`). The owner has not decided to keep it.
- The Finance fields (hourly rates, expected revenue, other financial terms). SUITE-64 builds them in `tkc_AccountFinance`.
- The seven fields that only REST shows. Open question 16 in `docs/osc-mapping/organization.md`.
- `Login`, `PasswordEncrypt`, `CompanyImage` and the SLA list. They are dropped.

Repair SQL (local dry run, SUITE-63): one `ALTER TABLE accounts` with 14 `add COLUMN tkc_*_c` and one `ADD INDEX idx_accounts_tkc_debtor_number_c`. A second dry run says "Database is in sync: no SQL." SUITE-72 does the authoritative run.

## tkc_AccountFinance
Module `tkc_AccountFinance`. Label "Finance". Story SUITE-64. Table `tkc_accountfinance` (utf8). One row holds the Finance data of one account.
- The module is hand-written in `public/legacy/modules/tkc_AccountFinance/` (D11). It uses the `basic` template only, so it has no assigned user.
- The fields have plain snake case names, without prefix or suffix (A8). Every field has `comment = 'TKC SUITE-64'`.
- `save()` fills `name` from the account name. Without an account, `name` is "Finance".
- `importable` is false and `unified_search` is false. The OSC data is not imported into this module yet.
- One Finance row for each account is not a database constraint. The Accounts subpanel has no select button, so a user can create more than one row.

| Field | Type / length | Label | OSC source | Story | Notes |
|---|---|---|---|---|---|
| `rate_angels` | text | Hourly rate Angels | `Org.C$Uurtariefangels` | SUITE-64 | Audited. `massupdate` false. Label `LBL_RATE_ANGELS`. |
| `rate_snor` | text | Hourly rate SNOR | `Org.C$Uursnor` | SUITE-64 | Audited. `massupdate` false. |
| `rate_body` | text | Hourly rate Body | `Org.C$Uurtariefbody` | SUITE-64 | Audited. `massupdate` false. |
| `expected_revenue_current_fy` | text | Expected revenue current fiscal year | `Org.C$Tekstgebieduurtarief` | SUITE-64 | Audited. `massupdate` false. |
| `expected_revenue_next_fy` | text | Expected revenue next fiscal year | `Org.C$Tekstgebiedreiskosten` | SUITE-64 | Audited. `massupdate` false. |
| `other_financial_terms` | text | Other financial terms | `Org.C$Tekstgebiedoverig` | SUITE-64 | Audited. `massupdate` false. |
| `account_id` | id | Account ID | n/a | SUITE-64 | Required. Index `idx_tkc_accountfinance_account_id`. |
| `account_name` | relate (non-db) | Account | n/a | SUITE-64 | Required. Link `tkc_accounts_tkc_accountfinance`. |

- Audit table: `tkc_accountfinance_audit`. `tkc:repair --execute` creates it (see `docs/architecture.md` § Console commands).
- Repair SQL (local): one `CREATE TABLE tkc_accountfinance` with the index. The audit table comes at execute.
- Contract test: the module is in `Tests/Contract/modules.php`. The manifest is `Tests/Contract/Fields/suite64_accountfinance.php`.

### Default ACL of the module
- Eight module ACL rows exist (category `tkc_AccountFinance`, acltype `module`):
  - `access` = 89 (Enabled).
  - `view`, `list`, `edit`, `delete`, `import`, `export` and `massupdate` = -99 (None).
- The migration `extensions/tkc/Migrations/Version20261124120000SeedFinanceAcl.php` seeds the rows. It uses DBAL. It updates existing rows and inserts missing rows. A second run changes nothing. `down()` throws.
- Why `access` is 89 and not -98: `ModuleRegistryHandler::applyUserActionFilter()` removes each module with `access` below 89 for every user, also for admins. With -98 the admin gets no labels and no metadata. The data stays denied: `ACLAction::userHasAccess` gives the same result for -98/-99 and for 89/-99.
- Why an upsert: `ACLAction::addActions`, `create_tables` and "Repair Roles" insert allow-all rows (89 and 90). They never update a row. An insert-only seed would leave Finance open.
- Role values (E13, SUITE-151):

| Role | access | view | list | edit | delete | import | export | massupdate |
|---|---|---|---|---|---|---|---|---|
| TKC Sales | 89 | 90 | 90 | 90 | -99 | -99 | -99 | -99 |
| TKC Service | -98 | -99 | -99 | -99 | -99 | -99 | -99 | -99 |

- Never give an admin user the TKC Service role. The -98 override removes Finance from the admin registry.
- The test `FinanceAclDenyTest` reads the real rows. SUITE-164 checks the rows at the Azure deploy.
- Known core gap (SUITE-207): a GraphQL subpanel preset and the subpanel statistic do not check the list access of the child module. A user who can view the Account can get Finance data this way. SUITE-207 must close the gap before the real Finance import.

## Contacts
Filled by SUITE-65. The rest of this section is a placeholder.

Table `contacts`. Dictionary key `Contact`. SUITE-71 built this row only:

| Field | Type / length | Label | OSC source | Story | Notes |
|---|---|---|---|---|---|
| `osc_id_c` | varchar, 20 | OSC ID | `Contact.CId` | SUITE-71 | Import key. Index `idx_contacts_osc_id_c` (not unique). `inline_edit` false. `massupdate` false. See § OSC import key. |

## Cases
Table `cases`. Dictionary key `Case` (the object name, not the module name). Story SUITE-66. SUITE-67 adds the relationship later. Every field is importable and has `comment = 'TKC SUITE-66'`. Vardef file: `public/legacy/custom/Extension/modules/Cases/Ext/Vardefs/tkc_cases.php`. Label file: `public/legacy/custom/Extension/modules/Cases/Ext/Language/en_us.tkc_cases.php`.

| Field | Type / length | Label | OSC source | Story | Notes |
|---|---|---|---|---|---|
| `tkc_case_ref_c` | varchar, 20 | Case reference | `Incident.RefNo` | SUITE-66 | `inline_edit` false. `massupdate` false. Index `idx_cases_tkc_case_ref_c`. The sequence service fills it later in E8 (A12). |
| `tkc_priority_c` | enum, 30 | Impact | `Incident.C$TkcPrioriteit` | SUITE-66 | List `tkc_impact_list`. Default empty. Audited. It is an impact scale. The core field `priority` stays the priority. |
| `tkc_customer_priority_c` | varchar, 255 | Customer priority | `Incident.Support$SupportPriority` | SUITE-66 | Plain text for now. |
| `tkc_tact_id_c` | varchar, 10 | Tact ID | `Incident.C$Tactid` | SUITE-66 | |
| `tkc_product_c` | enum, 30 | Product | `Incident.ProdId` | SUITE-66 | List `tkc_product_list`. Not required. |
| `tkc_category_c` | enum, 30 | Category | `Incident.CatId` / `DispId` | SUITE-66 | List `tkc_case_category_list`. Not required. |
| `tkc_queue_c` | enum, 30 | Queue | `Incident.QueueId` | SUITE-66 | List `tkc_queue_list`. Audited. Index `idx_cases_tkc_queue_c`. |
| `tkc_expected_resolution_date_c` | date | Expected resolution date | `Incident.C$IndicatedSolveDate` | SUITE-66 | Range search. |
| `tkc_date_closed_c` | datetime | Date closed | `Incident.Closed` | SUITE-66 | `massupdate` false. Range search. |
| `tkc_case_origin_c` | enum, 20 | Origin | `Incident.Source` | SUITE-66 | List `tkc_case_origin_list`. |
| `tkc_event_remark_c` | varchar, 255 | Event remark | `Incident.C$EventOpmerking` | SUITE-66 | |
| `tkc_event_remarks_c` | text | Event remarks | `Incident.C$EventOpmerkingen` | SUITE-66 | |
| `tkc_tfs_project_id_c` | varchar, 10 | TFS project ID | `Incident.C$TfsProjectId` | SUITE-66 | |
| `osc_id_c` | varchar, 20 | OSC ID | `Incident.IId` | SUITE-71 | Import key. Index `idx_cases_osc_id_c` (not unique). `inline_edit` false. `massupdate` false. It is not the case reference `tkc_case_ref_c`. See § OSC import key. |

Not built on Cases:
- `tkc_disposition_c`. The owner has not decided about Disposition.
- `tkc_assigned_group_c`.
- The mail header field.
- The two "collect before share" fields.
- The primary contact fields. The relationship story builds them. (`osc_id_c` is built in SUITE-71.)

Repair SQL (local dry run, SUITE-66): one `ALTER TABLE cases` with 13 `ADD COLUMN` and 2 `ADD INDEX`. SUITE-72 does the authoritative run.

Contract test: `Cases` is in `Tests/Contract/modules.php`. The manifest is `Tests/Contract/Fields/suite66_cases.php`.

## AOP_Case_Updates
Table `aop_case_updates`. Dictionary key `AOP_Case_Updates`. Story SUITE-68. A row is one entry of the case thread. Every new field has `comment = 'TKC SUITE-68'`. Vardef files in `public/legacy/custom/Extension/modules/AOP_Case_Updates/Ext/Vardefs/`: `tkc_thread.php`, `tkc_osc_id.php` and `tkc_description_longtext.php`. Label file: `public/legacy/custom/Extension/modules/AOP_Case_Updates/Ext/Language/en_us.tkc_thread.php`.

| Field | Type / length | Label | OSC source | Story | Notes |
|---|---|---|---|---|---|
| `tkc_entry_type_c` | enum, 20 | Entry type | Thread entry type (see `docs/osc-mapping/incident.md`) | SUITE-68 | List `tkc_entry_type_list`. Default `staff`. `massupdate` false. |
| `tkc_channel_c` | enum, 20 | Channel | Thread channel (see `docs/osc-mapping/incident.md`) | SUITE-68 | List `tkc_channel_list`. Default empty. `massupdate` false. |
| `osc_id_c` | varchar, 20 | OSC ID (`LBL_OSC_ID`) | Thread entry ID | SUITE-68 | Import key of the thread rows. `inline_edit` false. `massupdate` false. Index `idx_aop_case_updates_osc_id_c` (not unique). |
| `description` | text (column `longtext`) | core label | Thread text | SUITE-68 | Core vardef override (C8). `dbType` is `longtext` (was `text`). The field type stays `text`. See § Core overrides. |

Not built on AOP_Case_Updates:
- The `before_save` rule that presets the entry type in the create form. Core does not preset defaults in the create form (spike S8). E9-S1 builds the rule. The column default `staff` covers rows that are saved without a value.
- Layouts, CC and BCC fields, attachments and the mail header field.

Repair SQL (local): one `ALTER TABLE aop_case_updates`. It has one `modify COLUMN description longtext NULL`, three `add COLUMN` and one `ADD INDEX idx_aop_case_updates_osc_id_c`. SUITE-72 does the authoritative run.

Contract test: `AOP_Case_Updates` is in `Tests/Contract/modules.php`. The manifest is `Tests/Contract/Fields/suite68_aop_case_updates.php`. The long text test is `Tests/Integration/Thread/LongDescriptionTest.php` (see `docs/architecture.md` § Tests).

## Opportunities
Table `opportunities`. Dictionary key `Opportunity` (the object name, not the module name). Story SUITE-69. SUITE-70 adds the source-case relationship later. Every new field is importable and has `comment = 'TKC SUITE-69'`. Vardef file: `public/legacy/custom/Extension/modules/Opportunities/Ext/Vardefs/tkc_opportunities.php`. Label file: `public/legacy/custom/Extension/modules/Opportunities/Ext/Language/en_us.tkc_opportunities.php`.

| Field | Type / length | Label | OSC source | Story | Notes |
|---|---|---|---|---|---|
| `tkc_closed_amount_c` | currency, decimal(26,6) | Closed amount | `Opportunity.ClosedValue` | SUITE-69 | `massupdate` false. |
| `tkc_date_closed_actual_c` | date | Actual close date | `Opportunity.Closed` | SUITE-69 | Range search. |
| `tkc_sales_type_c` | enum, 20 | Sales type | `Opportunity.C$Typeverkoop` | SUITE-69 | List `tkc_sales_type_list`. |
| `tkc_country_c` | enum, 10 | Country | `Opportunity.Terr` | SUITE-69 | List `tkc_country_list`. |
| `tkc_reference_c` | varchar, 12 | Reference | `Opportunity.C$Reference` | SUITE-69 | Quote number (E10). Non-unique index `idx_opportunities_tkc_reference_c`. `inline_edit` false. `massupdate` false. |
| `tkc_quote_date_c` | date | Quote date | `Opportunity.C$Offertedatum` | SUITE-69 | Range search. |
| `tkc_approach_c` | enum, 20 | Approach | `Opportunity.C$Benadering` | SUITE-69 | List `tkc_approach_list`. |
| `tkc_initial_contact_c` | date | Initial contact | `Opportunity.InitialContact` | SUITE-69 | Range search. |
| `osc_id_c` | varchar, 20 | OSC ID | `Opportunity.OpId` | SUITE-71 | Import key. Index `idx_opportunities_osc_id_c` (not unique). `inline_edit` false. `massupdate` false. See § OSC import key. |

Not built on Opportunities:
- The menu `waarschijnlijkheid`. The core `probability` field replaces it.
- `type_medewerker` and `VERKOOP_ID`.
- The hook that sets the actual close date on a won deal.
- The quote number action and the reference validator (E10, SUITE-138 and SUITE-139). The index of `tkc_reference_c` is not unique. SUITE-139 plans the duplicate warning.
- The source-case relationship. SUITE-70 builds it. (`osc_id_c` is built in SUITE-71.)

SUITE-71 adds `tkc_reference_c` to the Duplicate exclusion list. The Duplicate action does not copy the quote number.

The `name` length and the `probability` logic are core overrides. See § Core overrides.

Repair SQL (local): one `ALTER TABLE opportunities` with one `modify COLUMN name varchar(255) NULL`, 8 `add COLUMN tkc_*_c` and one `ADD INDEX idx_opportunities_tkc_reference_c`. SUITE-72 does the authoritative run.

Contract test: `Opportunities` is in `Tests/Contract/modules.php`. The manifest is `Tests/Contract/Fields/suite69_opportunities.php`. The probability test is `Tests/Contract/OpportunityProbabilityLogicTest.php` (see `docs/architecture.md` § Tests).

## Lists
The first key of every TKC list is the empty key `''` with an empty label. Exception: `tkc_entry_type_list` has no empty key, because every thread entry has a type. A radio field shows it as "Not set" (SUITE-186). Application lists are in `public/legacy/custom/Extension/application/Ext/Language/`. Never change a TKC list in Studio or in the Dropdown Editor (see `docs/admin-guide.md`).

| List | Keys (in order) | Used by | File | Story |
|---|---|---|---|---|
| `tkc_yes_no_list` | `''`, `yes` (Yes), `no` (No) | Accounts: `tkc_has_rightnow_c`, `tkc_auto_upgrade_c`, `tkc_projects_on_portal_c`. Later stories reuse it. | `en_us.tkc_shared_lists.php` | SUITE-63 |
| `tkc_rightnow_version_list` | `''`, then 21 keys `2012_02` to `2017_02` (quarterly: 02, 05, 08, 11). Labels such as "2012.02 February". | Accounts: `tkc_rightnow_version_c` | `en_us.tkc_accounts_lists.php` | SUITE-63 |
| `tkc_rightnow_future_version_list` | `''`, then 36 keys `2012_02` to `2020_11`. Same label format. | Accounts: `tkc_rightnow_future_version_c` | `en_us.tkc_accounts_lists.php` | SUITE-63 |
| `tkc_pod_list` | `''`, `ca_ap`, `wc`, `mw`, `pc`, `ps`, `gb`, `am`, `sy`, `jp` | Accounts: `tkc_pod_c` | `en_us.tkc_accounts_lists.php` | SUITE-63 |
| `tkc_oracle_datacenter_list` | `''`, `gb`, `am`, `mw` | Accounts: `tkc_oracle_datacenter_c` | `en_us.tkc_accounts_lists.php` | SUITE-63 |
| `tkc_impact_list` | `''`, `no_impact`, `some_impact`, `production_disrupted`, `immediate_action` | Cases: `tkc_priority_c` | `en_us.tkc_cases_lists.php` | SUITE-66 |
| `tkc_product_list` | `''`, `oracle_service_cloud`, `egain`. Flat list (owner decision). | Cases: `tkc_product_c` | `en_us.tkc_cases_lists.php` | SUITE-66 |
| `tkc_case_category_list` | `''`, `service_request`, `complaint`, `question`, `change_request`. Flat list (owner decision). | Cases: `tkc_category_c` | `en_us.tkc_cases_lists.php` | SUITE-66 |
| `tkc_queue_list` | `''`, `support_1st_line`, `support_2nd_line`, `change_request`, `info_tkc_digital`, `answer_feedback`, `info`, `rightnow_automail`, `finance`, `archive`, `quotes`, `spam`, `consular_affairs`. The labels are English, from the OSC queue names, in OSC order. | Cases: `tkc_queue_c` | `en_us.tkc_cases_lists.php` | SUITE-66 |
| `tkc_case_origin_list` | `''`, `email`, `selfservice`, `staff`, `other` | Cases: `tkc_case_origin_c` | `en_us.tkc_cases_lists.php` | SUITE-66 |
| `tkc_entry_type_list` | `note` (Internal note), `staff` (Agent reply), `customer` (Customer), `customer_proxy` (Customer (logged by agent)). No empty key. | AOP_Case_Updates: `tkc_entry_type_c` | `en_us.tkc_thread_lists.php` | SUITE-68 |
| `tkc_channel_list` | `''`, `email`, `phone`, `web`, `chat`, `fax`, `social`, `other` | AOP_Case_Updates: `tkc_channel_c` | `en_us.tkc_thread_lists.php` | SUITE-68 |
| `tkc_sales_type_list` | `''`, `cross_sell` (Cross-sell), `up_sell` (Up-sell) | Opportunities: `tkc_sales_type_c` | `en_us.tkc_opportunities_lists.php` | SUITE-69 |
| `tkc_country_list` | `''`, `nl` (Netherlands), `de` (Germany), `be` (Belgium), `other` (Other), `dk` (Denmark), `se` (Sweden), `no` (Norway), `lu` (Luxembourg). OSC order. | Opportunities: `tkc_country_c` | `en_us.tkc_opportunities_lists.php` | SUITE-69 |
| `tkc_approach_list` | `''`, `website` (Website), `phone` (Phone), `social` (Social media), `network` (Network) | Opportunities: `tkc_approach_c` | `en_us.tkc_opportunities_lists.php` | SUITE-69 |

The release lists use the key format `YYYY_MM`, in date order. The OSC menu order is not in date order, so the keys do not copy it. The release names are product data, not personal data.

## Relationships
Other relationships are filled by SUITE-67 and SUITE-70.

| Relationship | Type | Sides | Declared in | Story |
|---|---|---|---|---|
| `tkc_accounts_tkc_accountfinance` | 1:N | `Accounts.id` to `tkc_accountfinance.account_id` | The module vardef `public/legacy/modules/tkc_AccountFinance/vardefs.php` only. There is no `custom/metadata/` file. | SUITE-64 |

- The Accounts link is in `public/legacy/custom/Extension/modules/Accounts/Ext/Vardefs/tkc_finance_link.php`.
- The Accounts subpanel "Finance" is in `public/legacy/custom/Extension/modules/Accounts/Ext/Layoutdefs/tkc_finance_subpanel.php`. It has a quick create button and no select button.

## Core overrides
Each row is also in `docs/architecture.md` § Overrides register.

| Core item | Change | File | Story |
|---|---|---|---|
| List `account_type_dom` | Replaced by the TKC list (C7). Keys in order: `tkc_lead` (Lead), `Prospect` (Prospect), `Customer` (Customer), `tkc_supplier` (Supplier), `tkc_relation` (Relation), `tkc_former_customer` (Former customer). No empty key. The core keys Analyst, Competitor, Integrator, Investor, Partner, Press, Reseller and Other are hidden. No core key is renamed (D7). | `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_account_type_dom.php` | SUITE-63 |
| Vardef `Account.account_type` | The default is `tkc_relation` (C8). It matches the OSC default type "Relatie". Repair gives no SQL, because the column has no database default. | `public/legacy/custom/Extension/modules/Accounts/Ext/Vardefs/tkc_account_type_default.php` | SUITE-63 |
| List `case_status_dom` | Replaced by 12 keys in OSC order (C7). Relabelled core keys: `Open_New` (Unresolved), `Open_Assigned` (In progress), `Open_Pending Input` (Waiting for customer (info needed)), `Closed_Closed` (Solved). New keys: `Open_Updated` (Updated), `Open_OnHold` (On hold), `Open_WaitOracle` (Waiting for Oracle), `Open_AwaitApproval` (Ready, awaiting approval of the requester), `Open_WaitFreshworks` (Waiting for Freshworks), `Open_FeatureRequest` (Feature request), `Open_CAJira` (CA Jira), `Closed_Archived` (Archived). Every key keeps the `Open_` or `Closed_` prefix, because core reads it (A2). The core keys `Closed_Rejected` and `Closed_Duplicate` are hidden. In the Angular UI the Status options follow the State field (core behaviour). | `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_case_core_lists.php` | SUITE-66 |
| List `case_priority_dom` | Replaced by `''`, `P1`, `P2`, `P3`, `tkc_P4` with the labels "Priority 1" to "Priority 4" (A13). The empty first key keeps the priority empty. | same file | SUITE-66 |
| String `case_priority_default_key` | Set to `''`. Core sets `P2`. Core reads it only in `modules/Cases/CasesQuickCreate.php:61` (legacy quick create). The core vardef of `priority` has no default. | same file | SUITE-66 |
| Vardef `AOP_Case_Updates.description` | `dbType` is `longtext` (C8). Reason: OSC thread texts can be longer than `text` (64 KB). The field type stays `text`. Check at merge: the core `description` definition of AOP_Case_Updates and the Basic template. Upgrade risk: medium. | `public/legacy/custom/Extension/modules/AOP_Case_Updates/Ext/Vardefs/tkc_description_longtext.php` | SUITE-68 |
| List `case_type_dom` | Replaced by `''`, `tkc_support_request` (Support request), `tkc_general_communication` (General communication), `tkc_info_message` (Info message), `tkc_project_communication` (Project communication). The core keys Administration, Product and User are hidden. | same file | SUITE-66 |
| Vardef `Opportunity.name` | `len` is 255 (C8). Core has 50. OSC names are longer. Repair gives one `MODIFY` of the length only. | `public/legacy/custom/Extension/modules/Opportunities/Ext/Vardefs/tkc_name_len.php` | SUITE-69 |
| Vardef `Opportunity.probability` | Adds only the key `logic`: 8 `updateValue` entries (ids `tkc_prob_<stage>`, modes edit and create, `fieldDependencies` `sales_stage`, `activeOnFields` one stage each, `targetValue` is the `sales_probability_dom` value). In the Angular edit and create view, the probability follows the stage (spike S11). Repair gives no SQL. Accepted gap: a stage change outside the Angular edit and create view does not recompute the probability. Core `Opportunity::save()` still fills an empty probability. | `public/legacy/custom/Extension/modules/Opportunities/Ext/Vardefs/tkc_probability_logic.php` | SUITE-69 |
| List `sales_stage_dom` | Replaced by 8 keys (C7), in this order: `Prospecting` (0% - Lead), `Qualification` (5% - Qualify), `Needs Analysis` (10% - Analysis), `tkc_quote_multi` (25% - Quote, several vendors), `tkc_quote_single` (50% - Quote, single vendor), `Negotiation/Review` (80% - Verbal commitment), `Closed Won` (100% - Won), `Closed Lost` (Lost). The core keys `Value Proposition`, `Id. Decision Makers`, `Perception Analysis` and `Proposal/Price Quote` are hidden. No core key is renamed (D7). | `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_sales_stage_dom.php` | SUITE-69 |
| List `sales_probability_dom` | Replaced. Same 8 keys. Values: 0, 5, 10, 25, 50, 80, 100 and 0 (`Closed Lost`). Core has other values (for example `Prospecting` 10). Keep it in line with the `logic` file. | same file | SUITE-69 |

Known gaps of the Opportunities core overrides (check at each upstream merge):
- `modules/AOS_Quotes/createOpportunity.php:49-50` writes the hidden key `Proposal/Price Quote` and reads its probability. Follow-up: SUITE-202.
- The My Opportunities dashlet filters on core stage keys. Follow-up: SUITE-201.

Known gaps of the Cases core overrides (check at each upstream merge):
- AOP derives `state` from the key prefix (`modules/AOP_Case_Updates/CaseUpdatesHook.php:308`). Keep the prefixes.
- The core "open only" search filter and the My Cases dashlet know only the core status keys. Follow-up stories: SUITE-200 and SUITE-201.
