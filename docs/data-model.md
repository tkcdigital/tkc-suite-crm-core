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
Filled by SUITE-62.

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
Filled by SUITE-64.

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
Filled by SUITE-67 and SUITE-70.

## Core overrides
Each row is also in `docs/architecture.md` § Overrides register.

| Core item | Change | File | Story |
|---|---|---|---|
| List `account_type_dom` | Replaced by the TKC list (C7). Keys in order: `tkc_lead` (Lead), `Prospect` (Prospect), `Customer` (Customer), `tkc_supplier` (Supplier), `tkc_relation` (Relation), `tkc_former_customer` (Former customer). No empty key. The core keys Analyst, Competitor, Integrator, Investor, Partner, Press, Reseller and Other are hidden. No core key is renamed (D7). | `public/legacy/custom/Extension/application/Ext/Language/en_us.tkc_account_type_dom.php` | SUITE-63 |
| Vardef `Account.account_type` | The default is `tkc_relation` (C8). It matches the OSC default type "Relatie". Repair gives no SQL, because the column has no database default. | `public/legacy/custom/Extension/modules/Accounts/Ext/Vardefs/tkc_account_type_default.php` | SUITE-63 |
