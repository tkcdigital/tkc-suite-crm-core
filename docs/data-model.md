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
Filled by SUITE-65.

## Cases
Filled by SUITE-66 and SUITE-67.

## AOP_Case_Updates
Filled by SUITE-68.

## Opportunities
Filled by SUITE-69 and SUITE-70.

## Lists
The first key of every TKC list is the empty key `''` with an empty label. A radio field shows it as "Not set" (SUITE-186). Application lists are in `public/legacy/custom/Extension/application/Ext/Language/`. Never change a TKC list in Studio or in the Dropdown Editor (see `docs/admin-guide.md`).

| List | Keys (in order) | Used by | File | Story |
|---|---|---|---|---|
| `tkc_yes_no_list` | `''`, `yes` (Yes), `no` (No) | Accounts: `tkc_has_rightnow_c`, `tkc_auto_upgrade_c`, `tkc_projects_on_portal_c`. Later stories reuse it. | `en_us.tkc_shared_lists.php` | SUITE-63 |
| `tkc_rightnow_version_list` | `''`, then 21 keys `2012_02` to `2017_02` (quarterly: 02, 05, 08, 11). Labels such as "2012.02 February". | Accounts: `tkc_rightnow_version_c` | `en_us.tkc_accounts_lists.php` | SUITE-63 |
| `tkc_rightnow_future_version_list` | `''`, then 36 keys `2012_02` to `2020_11`. Same label format. | Accounts: `tkc_rightnow_future_version_c` | `en_us.tkc_accounts_lists.php` | SUITE-63 |
| `tkc_pod_list` | `''`, `ca_ap`, `wc`, `mw`, `pc`, `ps`, `gb`, `am`, `sy`, `jp` | Accounts: `tkc_pod_c` | `en_us.tkc_accounts_lists.php` | SUITE-63 |
| `tkc_oracle_datacenter_list` | `''`, `gb`, `am`, `mw` | Accounts: `tkc_oracle_datacenter_c` | `en_us.tkc_accounts_lists.php` | SUITE-63 |

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
