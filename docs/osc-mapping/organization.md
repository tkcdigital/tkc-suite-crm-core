# OSC Organization → SuiteCRM Accounts

> **Owner decisions 2026-11-20:** SLA **dropped** (SLA container → `drop`). Contracts (`Support$SupportContract`, `Support$LicentieContract`) **in scope**. Projects (`Support$Project`) **out of scope**. See `docs/backlog.md` § Product decisions.

Status: **VERIFIED with REST 2026-11-20 (tst1)**. All REST-answerable items are done (5 batch runs). The other open points are business decisions (section 6). Customer names are replaced by placeholders; the real values are in OSC and in data/osc/rest-output/ (gitignored).

**REST verification result (2026-11-20, tst1):**
- Verified: type, length and nullability of every standard field used and all 29 custom fields (`metadata-catalog/organizations` and `.../customFields/c`, header `Accept: application/schema+json`). Verified: all menu value lists (`C$OrgRelType`, `auto_upgrade`, `rightnow_version`, `rightnow_future_version`, `pod`, `oracle_datacenter`, `rightnow_uurtarief`). Verified: field lists of both contract objects (section 5a).
- Date fields: REST marks them with `minimumDate` (date only) or `minimumDateTime`. All three org date fields are date only.
- Usage counts are aggregate ROQL only (`GROUP BY`, counts). No organisation record was read in this run. tst1 holds 738 organisations.
- Raw output: `data/osc/rest-output/s/` (schemas) and `data/osc/rest-output/v/` (values) (gitignored). Batch files: `data/osc/batches/`.
- Environment note: tst1 is a test copy. Ids and counts can differ in production.

Earlier status: DRAFT v1 (SUITE-3). Source: workflow export `designs/screens/accounts/tkc-organization-workflow-with-workspaces.xml`,
the flow diagram, one screenshot and the SuiteCRM 8.10 vardefs. **No OSC REST access yet.**
The REST run of 2026-11-20 verified the types, lengths and menu values. A `TBC` that remains needs an answer from the user.

Raw parse output: `data/osc/workspace/accounts.json` and `data/osc/workspace/accounts-flow.json` (gitignored).

Legend for `Exists?`: `core` = standard SuiteCRM field. `new` = we add it. `drop` = not migrated.
OSC field names in the XML use the form `Org.C$<Name>` (custom field) or `Org.<Name>` (standard field).
The REST name of a custom field is `CustomFields.c.<snake_case>`. The verified REST names are in column 1 of section 2.

## 1. Workflow summary

The export is one workflow, **"TKC - Organisatie flow"**. It chooses one of six workspaces when an agent opens an organization.

```
Entry -> Decision "Type" on Org.C$OrgRelType
   Lead (273) / Prospect (180)   -> WS Prospect
   Klant (181)                   -> Decision "Profile of the logged-in account = Sales" (profile id 73)
                                       Ja  -> WS Klant - SalesTeam
                                       Nee -> WS Klant - Algemeen
   Leverancier (182)             -> WS Leverancier
   Oud Klant (187)               -> WS Oud Klant
   Relatie (183) / no value / other -> WS Relatie
```

The numbers are the menu item IDs of `C$OrgRelType`. The labels come from the path names in the XML.
The diagram image is cropped. The XML is the source for the routing.
In the diagram, "Oud Klant" seems to join the Relatie workspace. In the XML it goes to its own workspace "Oud Klant".

### Workspaces

All six use the object `Organization` (UI type `Organization`). All six have the same ribbon and the same left-hand field panel (see section 3).

| Workspace (OSC name) | Screen title in OSC | Tabs (right-hand tab set) | Extra fields (only here) |
|---|---|---|---|
| TKC - Organisatie - Klant - SalesTeam | Klanten Scherm - Sales Team | Contactpersonen, Verkoopkansen en Contracten, Projecten, Incidenten, Replicon Overzicht, Opmerkingen en Taken, SharePoint Documenten, Details | 3 hourly rates, 3 finance text areas, Tooling, Console URL, contract dates, Debiteurnummer, Replicon name, company image |
| TKC - Organisatie - Klant - Algemeen | Klanten Scherm - Algemeen | Contactpersonen, Verkoopkansen en Contracten, Projecten, Incidenten, Replicon Overzicht, Opmerkingen en Taken, SharePoint Documenten, Site Werkzaamheden | RightNow block (Heeft RightNow, version, future version, POD, datacenter, auto upgrade, projects on portal), Replicon name, company image |
| TKC - Organisatie - Prospect | Prospect Scherm | Contactpersonen, Contracten, Contact Historie, Opmerkingen en Taken, SharePoint Documenten | RightNow block (without POD, datacenter, auto upgrade, projects on portal) |
| TKC - Organisatie - Leverancier | Leveranciers Scherm | Contactpersonen, Projecten, Contact Historie, Opmerkingen en Taken, SharePoint Documenten | same as Prospect |
| TKC - Organisatie - Relatie | Relatie Scherm | Contactpersonen, Contact Historie, Opmerkingen en Taken, SharePoint Documenten | same as Prospect |
| TKC - Organisatie - Oud Klant | Relatie Scherm (title text copied from Relatie) | same as Relatie | same as Prospect |

Observations:
- Tab names are only partly equal to the content. "Contracten" (Prospect) and "Verkoopkansen en Contracten" (Klant) both show the Opportunities grid. Only Klant adds the two contract objects. The Prospect tab list does not show which grids are inside the "Contracten" tab beyond Opportunities. TBC with the user.
- "Contact Historie" (Prospect, Leverancier, Relatie, Oud Klant) shows the Incident grid (`IncidentView`). In Klant the same grid is on tab "Incidenten".
- Tab "Details" (SalesTeam) holds: `State`, the SLA container, `Login`, `PasswordEncrypt`, `C$CompanyImage`.
- A screenshot of the Klant - Algemeen layout shows type = Prospect. So the screen was opened without the workflow, or the workflow is not the only entry. See open question 9.

### Layout (left column, all workspaces)

Left panel, one column of fields, labels above (Dutch labels as shown in the screenshot):
Organisatienaam, Type, Facturering (address), [Klant only: SLA's], Staat, Verkoper, Inloggen, Wachtwoord, then the type-specific block, then Debiteur Nummer.
Tab panel on the right. The tab panel holds relationship grids, two embedded browser controls and one report.

### Relationship grids and other tab content

| Tab (OSC) | Control (`ItemType`) | Object | Where used | SuiteCRM target |
|---|---|---|---|---|
| Contactpersonen | `Contacts` (columns in screenshot: Volledige naam, E-mailadres, Organisatienaam, Telefoon werk, Titel) | Contact | all 6 | Contacts subpanel |
| Contactpersonen | `OrgHierarchy` | Organization (parent/child) | all 6 | Member Organizations subpanel |
| Verkoopkansen en Contracten / Contracten | `OpportunitiesView` | Opportunity | Klant x2, Prospect | Opportunities subpanel |
| Verkoopkansen en Contracten | `Support$LicentieContractRelatedRecordsView` (FK `Organization`) | custom object `Support$LicentieContract` | Klant x2 | **Open question 3** |
| Verkoopkansen en Contracten | `Support$SupportContractRelatedRecordsView` (FK `Organization`) | custom object `Support$SupportContract` | Klant x2 | **Open question 3** |
| Projecten | `Support$ProjectRelatedRecordsView` (FK `Org_id`) | custom object `Support$Project` | Klant x2, Leverancier | **Open question 3** |
| Incidenten / Contact Historie | `IncidentView` | Incident | all 6 | Cases subpanel |
| Opmerkingen en Taken | `OrgNotes` | Organization notes | all 6 | History subpanel (Notes) |
| Opmerkingen en Taken | `Tasks` | Task | all 6 | Activities subpanel (Tasks) |
| Opmerkingen en Taken | `FileAttachments` | file attachments | all 6 | Documents subpanel / Note attachments |
| Opmerkingen en Taken | `AuditLog` ("Controle Logboek") | audit | all 6 | Change log (audited fields) |
| Replicon Overzicht | field `C$Repliconname` + `Browser` control (URL template with `$p_orgcf_109`) | external web page | Klant x2 | `[custom]` link or embedded frame. See section 5 |
| SharePoint Documenten | field `C$Sharepointname` + `Browser` control (URL template with the org name or `$p_orgcf_112`) | external web page | all 6 | `[custom]` link or embedded frame. See section 5 |
| Site Werkzaamheden | `Report` (report id 101032) | report on custom data | Klant - Algemeen | **Open question 4** |
| (left panel) | `SlaContainer` | SLA | all 6 | **Open question 2** (no SLA module in SuiteCRM) |

The record types in the workspace also list `Asset`, `Task`. No grid shows Asset. No mapping is needed for Asset.

### Ribbon and quick access toolbar (identical in all six)

Home tab. Group Save: `Save`, `SaveAndClose`. Group Actions: `New`, `Refresh`, `Appointment`, `Print`, `Copy`, `Delete`. Group Proofing: `SpellCheck`. Group Links and Info: `EditorLinks` (one link, the OSC vendor site), `Info`.
Quick access toolbar: `New`, `Save`, `SaveAndClose`, `Print`.
Info pane items: `OrgId`, `Created`, `Updated`, `RuleState`.
The screenshot also shows a Bookmark ("Bladwijzer") button. It is a console function and is not in the XML.

### Workspace rules

One rule in the Klant - SalesTeam workspace only: **"Wel of niet tonen RN Versie"**.
Trigger: `EditorLoaded`. Condition: `Org.C$HasRightnow = 1`. Then/Else: **empty**. Status: **inactive** (`Active="False"`).
The rule does nothing. The other five workspaces have no rules. Result: nothing to migrate.
A label in the SalesTeam workspace shows the text "Heeft RightNow: $orgs.c$has_rightnow" (a label with a variable, not a field).

## 2. Field mapping table

SuiteCRM names follow `.claude/rules/suitecrm-customization.md`: custom fields end in `_c`. TKC-specific fields start with `tkc_`.
`Type` of an OSC field is verified by REST schema (2026-11-20) unless the cell says otherwise.
`Req/RO` is not set in the XML for any field (`RequiredOption` and `ReadOnlyOption` are empty). The screenshot shows a required marker on Organisatienaam and Staat. Those come from the object definition.

| OSC field (API name) | OSC type | Workspace location | SuiteCRM module.field | Exists? | New field type/len | Dropdown values | Notes |
|---|---|---|---|---|---|---|---|
| `Org.Name` | text(80), required (REST: string, maxLength 80, not nullable) | Left panel row 0, all 6 | Accounts.`name` | core |  |  | Label "Organisatienaam". SuiteCRM `name` is varchar(150): fits. |
| `Org.SalesAcctId` (REST `salesSettings.salesAccount`) | FK to staff account (verified: object under `salesSettings`) | Left panel row 1, all 6 | Accounts.`assigned_user_id` | core | | | Shown as "Verkoper". Staff-to-user mapping needed for import (open question 8). |
| `Org.C$OrgRelType` (REST `CustomFields.c.org_rel_type`) | menu (verified: object id + lookupName), nullable; 6 items | Left panel row 2, all 6; drives the workflow | Accounts.`account_type` | core | enum (replace the `account_type_dom` list) | 273 Lead, 180 Prospect, 181 Klant, 182 Leverancier, 183 Relatie, 187 Oud Klant (REST list, complete: 6 items, 2026-11-20). Usage in tst1 (738 orgs): Lead 154, Prospect 196, Klant 95, Leverancier 27, Relatie 250, Oud Klant 7, empty 9. | The REST list equals the six values from the workflow XML: no other item exists. Core `account_type` has an English list. Architect: replace the list by a custom override, or add `tkc_relatie_type_c`. 9 organisations have no type (workflow falls back to Relatie). **Built (SUITE-63):** override of `account_type_dom` (keys `tkc_lead`, `Prospect`, `Customer`, `tkc_supplier`, `tkc_relation`, `tkc_former_customer`) and default `tkc_relation`. |
| `Org.Oaddr` (REST `addresses`) | address collection `addresses` (verified): each item has street (240), city (80), postalCode (10), stateOrProvince (menu), country (object, `countries` collection), addressType (menu, not null) | Left panel row 3, all 6 | Accounts.`billing_address_street`, `_city`, `_state`, `_postalcode`, `_country` | core |  |  | Label means billing address. SuiteCRM street varchar(150), city 100, postalcode 20, state 100, country 255: all fit. Address types (verified 2026-11-20): 1 Facturering (billing), 2 Verzenden, 3 Postbus, 4 Afleveradres. The import takes type 1 for `billing_address_*`. The `stateOrProvince` menu has 74 items (codes such as AK, AL, AR, AS: a US state list, not Dutch provinces). |
| `Org.C$Uurtariefangels` (REST `CustomFields.c.uurtariefangels`) | text, multi-line (verified: string, maxLength 1333). Label "Uurtarief angels" | Klant - SalesTeam, left panel | Accounts.`tkc_uurtarief_angels_c` | new | `text` (not currency: OSC holds free text) |  | Hourly rate stored as free text (max 1333 in OSC). If the owner wants a number, the import must parse it. Open question 6. |
| `Org.C$Uursnor` (REST `CustomFields.c.uursnor`) | text, multi-line (verified: string, maxLength 1333). Label "Uurtarief snor" | Klant - SalesTeam | Accounts.`tkc_uurtarief_snor_c` | new | `text` (not currency: OSC holds free text) |  | Hourly rate stored as free text (max 1333 in OSC). If the owner wants a number, the import must parse it. Open question 6. |
| `Org.C$Uurtariefbody` (REST `CustomFields.c.uurtariefbody`) | text, multi-line (verified: string, maxLength 1333). Label "Uurtarief body" | Klant - SalesTeam | Accounts.`tkc_uurtarief_body_c` | new | `text` (not currency: OSC holds free text) |  | Hourly rate stored as free text (max 1333 in OSC). If the owner wants a number, the import must parse it. Open question 6. |
| `Org.C$Tekstgebieduurtarief` (REST `CustomFields.c.tekstgebieduurtarief`) | text area (verified: string, maxLength 1333), label "Verw omzet lopend boekjaar" | Klant - SalesTeam | Accounts.`tkc_verw_omzet_lopend_c` | new | `text` |  | Field name says "hourly rate". Label says "expected revenue, current fiscal year". Name and label do not match. Open question 6. |
| `Org.C$Tekstgebiedreiskosten` (REST `CustomFields.c.tekstgebiedreiskosten`) | text area (verified: string, maxLength 1333), label "Verw omzet komend boekjaar" | Klant - SalesTeam | Accounts.`tkc_verw_omzet_komend_c` | new | `text` |  | Field name says "travel costs". Label says "expected revenue, next fiscal year". Open question 6. |
| `Org.C$Tekstgebiedoverig` (REST `CustomFields.c.tekstgebiedoverig`) | text area (verified: string, maxLength 1333), label "Overige financiële afspraken" | Klant - SalesTeam | Accounts.`tkc_overige_fin_afspraken_c` | new | `text` |  | Free text. |
| `Org.C$Tooling` (REST `CustomFields.c.tooling`) | text area (verified: string, maxLength 1333), label "Overige tooling" | Klant - SalesTeam | Accounts.`tkc_tooling_c` | new | `text` |  |  |
| `Org.C$HasRightnow` (REST `CustomFields.c.has_rightnow`) | **boolean** (verified), nullable; Ja/Nee dropdown, label "Heeft RightNow ?" | Klant - Algemeen, Prospect, Leverancier, Relatie, Oud Klant | Accounts.`tkc_has_rightnow_c` | new | `enum` `tkc_yes_no_list` (radio), was `bool` | Ja / Nee (boolean) | Used in the inactive rule. **Built (SUITE-63).** The type changed from `bool` to `enum`: a radio has the state "Not set". |
| `Org.C$RightnowVersion` (REST `CustomFields.c.rightnow_version`; empty in sample) | menu (verified), 21 items, label "RightNow Version" (XML label "Versie" in SalesTeam) | all 6 | Accounts.`tkc_rightnow_version_c` | new | `enum` | Release names, format `YYYY.MM Month`: 272 2012.02 February ... 267 2017.02 February (21 items, 2012.02 to 2017.02, quarterly; list is not in date order: ids 267-272 were added later). Full list in `data/osc/rest-output/v/cm-organizations-rightnow_version-A.json`. | Old release list (ends 2017). Candidate to migrate as free text or as the list. Owner decides if the list is still needed. |
| `Org.C$RightnowFutureVersion` (REST `CustomFields.c.rightnow_future_version`; empty in sample) | menu (verified), 36 items, label "RightNow Future Version" | Klant - Algemeen, Prospect, Leverancier, Relatie, Oud Klant | Accounts.`tkc_rightnow_future_version_c` | new | `enum` | Same format: 163 2012.02 February ... 266 2020.11 November (36 items, quarterly, 2012.02 to 2020.11). | Obsolete list (last item 2020.11). Candidate to migrate as text. |
| `Org.C$Pod` (REST `CustomFields.c.pod`; empty in sample) | menu (verified), 9 items, label "POD" | Klant - Algemeen | Accounts.`tkc_pod_c` | new | `enum` | 196 CA/AP (California / Asia Pacific), 197 WC (West Coast), 198 MW (Mid West), 199 PC (PCI Compliant), 200 PS (Public Sector), 201 GB (Great Britain), 202 AM (Amsterdam location), 203 SY (Sydney location), 204 JP (Japan location) |  |
| `Org.C$OracleDatacenter` (REST `CustomFields.c.oracle_datacenter`; empty in sample) | menu (verified), 3 items, label "Oracle datacenter" | Klant - Algemeen | Accounts.`tkc_oracle_datacenter_c` | new | `enum` | 234 GB, 235 AM, 236 MW |  |
| `Org.C$AutoUpgrade` (REST `CustomFields.c.auto_upgrade`) | **menu** (verified: id + lookupName), 2 items, label "Auto Upgrade Program" | Klant - Algemeen | Accounts.`tkc_auto_upgrade_c` | new | `enum` `tkc_yes_no_list` (radio), was `bool` | 237 Ja, 238 Nee. Usage in tst1: Nee 737, Ja 1, empty 0 (every org has a value) | Menu but only Ja/Nee, never empty: default `no`. **Built (SUITE-63).** The type changed from `bool` to `enum`. |
| `Org.C$ProjectsOnPortal` (REST `CustomFields.c.projects_on_portal`) | **boolean** (verified), nullable; Ja/Nee. Label "Projects On Portal" | Klant - Algemeen | Accounts.`tkc_projects_on_portal_c` | new | `enum` `tkc_yes_no_list` (radio), was `bool` | Ja / Nee (boolean) | **Built (SUITE-63).** The type changed from `bool` to `enum`. |
| `Org.C$ConsoleUrl` (REST `CustomFields.c.console_url`; empty in sample) | text (verified: string, maxLength 255), label "Console URL" | all 6 | Accounts.`tkc_console_url_c` | new | `url`, varchar(255) |  |  |
| `Org.C$BegindatumContract` (REST `CustomFields.c.begindatum_contract`; empty in sample) | date (verified: string with date limits `minimumDate`, no time) | Klant - SalesTeam, Prospect, Leverancier, Relatie, Oud Klant | Accounts.`tkc_contract_begin_c` | new | `date` |  | Contract start. Not on Klant - Algemeen. |
| `Org.C$EinddatumContract` (REST `CustomFields.c.einddatum_contract`; value is a string in REST: date or datetime not verified) | date (verified: string with date limits, no time) | same as above | Accounts.`tkc_contract_end_c` | new | `date` |  | Contract end. |
| `Org.C$Debiteurnummer` (REST `CustomFields.c.debiteurnummer`; empty in sample) | text (verified: string, maxLength 40), label "Debiteur Nummer" | all 6 | Accounts.`tkc_debtor_number_c` | new | `varchar(40)`, indexed, audited |  | Debtor number. Key for the finance system. **Built (SUITE-63).** Renamed from `tkc_debiteurnummer_c` to an English name (A8). Index `idx_accounts_tkc_debtor_number_c`. |
| `Org.C$Repliconname` (REST `CustomFields.c.repliconname`) | text (verified: string, maxLength 100) | Klant - SalesTeam, Klant - Algemeen (tab Replicon Overzicht) | Accounts.`tkc_replicon_name_c` | new | `varchar(100)` |  | Feeds the Replicon URL. See section 5. |
| `Org.C$Sharepointname` (REST `CustomFields.c.sharepointname`; empty in sample) | text (verified: string, maxLength 40), label "Share Point Name" | all 6 (tab SharePoint Documenten) | Accounts.`tkc_sharepoint_name_c` | new | `varchar(40)` |  | Feeds the SharePoint URL. See section 5. |
| `Org.State` (REST `cRMModules.marketing`, `.sales`, `.service`) | **three booleans** (verified): the standard OSC CRM-module flags | tab Details (SalesTeam); left panel in the screenshot | Accounts.`tkc_crm_modules_c` | new | `multienum` with 3 items, or 3 `bool` fields. Open question 5 (keep or drop) | Service / Sales / Marketing (verified: `cRMModules` has 3 booleans, all not nullable) | The XML default `Service |  | Sales |  | Marketing` equals all three flags on. The chip text in the screenshot is a UI label for these flags. Business meaning not confirmed. |
| `Org.Login` (REST `login`) | text (verified: string, maxLength 40), label "Inloggen" | tab Details | none | drop |  |  | Customer portal login. See open question 7. Proposed: drop. |
| `Org.PasswordEncrypt` | password (standard), label "Wachtwoord" | tab Details | none | drop | | | A password. Must not go into a CRM. Proposed: drop. |
| `Org.C$CompanyImage` (REST `CustomFields.c.company_image`) | text (verified: string, maxLength 1333; file name) | Klant - SalesTeam, Klant - Algemeen | none | drop |  |  | Seems a logo file name for the customer portal. Proposed: drop. Open question 7. |
| `Org.C$UnRnCrm` (REST `CustomFields.c.unrncrm`) | text (verified: string, maxLength 20; label in OSC "Usrname RN CRM") | not in any workspace | none | drop (proposed) |  |  | Name suggests a user name for a CRM site. Likely a credential. Open question 16. |
| `Org.C$PwRnCrm` (REST `CustomFields.c.pwrncrm`) | text (verified: string, maxLength 30; label in OSC "Password RN CRM") | not in any workspace | none | drop (proposed) |  |  | Name and label say password. Do not import. Security-officer to confirm. Open question 16. |
| `Org.C$OrgTelefoonnummer` (REST `CustomFields.c.org_telefoonnummer`) | text (verified: string, maxLength 20), label "Telefoonnummer" | not in any workspace | Accounts.`phone_office` | core (proposed) |  |  | Phone number of the organization. Not on a screen. |
| `Org.C$RightnowUurtarief` (REST `CustomFields.c.rightnow_uurtarief`) | **menu** (verified, not text), 3 items, label "Uurtarief" | not in any workspace | Accounts.`tkc_rightnow_uurtarief_c` | new (proposed) | `enum` | 231 Snor, 232 Bodybuilder, 233 Angel | Rate class of the customer (same three classes as the booleans `tarief_*` below and as the opportunity menu `type_medewerker`: Angels, Bodybuilders, Snorren). Not on a screen. Open question 16. |
| `Org.C$TariefSnor` (REST `CustomFields.c.tarief_snor`) | **boolean** (verified) | not in any workspace | Accounts.`tkc_tarief_snor_c` | new (proposed) | `bool` | | Not on a screen. Open question 16. |
| `Org.C$TariefAngels` (REST `CustomFields.c.tarief_angels`) | **boolean** (verified) | not in any workspace | Accounts.`tkc_tarief_angels_c` | new (proposed) | `bool` | | Same as above. |
| `Org.C$TariefBodybuilders` (REST `CustomFields.c.tarief_bodybuilders`) | **boolean** (verified) | not in any workspace | Accounts.`tkc_tarief_bodybuilders_c` | new (proposed) | `bool` | | Same as above. |
| `Org.OrgId` (Info item) | integer ID | Info pane | Accounts.`osc_id_c` | new | `varchar(20)`, indexed (OSC ID is integer, max 9223372036854775807) |  | Import key. Required by `suitecrm-customization.md`. **Status: built (SUITE-71).** |
| `Org.Created` (Info item) | datetime | Info pane | Accounts.`date_entered` | core | | | |
| `Org.Updated` (Info item) | datetime | Info pane | Accounts.`date_modified` | core | | | |
| `Org.RuleState` (Info item) | system | Info pane | none | drop | | | OSC rule engine state. No meaning in SuiteCRM. |
| SLA list (`SlaContainer`; REST `serviceSettings.sLAInstances`) | relationship | Left panel | none | **drop** | | | Owner decision 2026-11-20: SLA dropped. |

Counts (40 rows, recounted after REST 2026-11-20; contracts in section 5a are counted separately: 6 new fields plus `osc_id_c`): **core 7** (`Name`, `SalesAcctId`, `OrgRelType`, `Oaddr`, `Created`, `Updated`, and proposed `org_telefoonnummer` to `phone_office`), **new 26** (`osc_id_c`, 19 workspace fields, `State` as one field, 5 REST-only proposals), **drop 7** (`Login`, `PasswordEncrypt`, `CompanyImage`, `RuleState`, `unrncrm`, `pwrncrm`, SLA list). Rows marked "(proposed)" wait for open question 16.
REST has 29 custom fields on Organization: 22 appear in the workspaces, 7 appear only in REST (`unrncrm`, `pwrncrm`, `org_telefoonnummer`, `rightnow_uurtarief`, `tarief_snor`, `tarief_angels`, `tarief_bodybuilders`). `PasswordEncrypt` is a standard field that REST did not send.
Menu value lists: **all read** (section 2 and `data/osc/rest-output/v/`). Seven custom fields are menus: `org_rel_type`, `auto_upgrade`, `rightnow_version`, `rightnow_future_version`, `pod`, `oracle_datacenter`, `rightnow_uurtarief`.

**Build status (SUITE-63, 2026-11-22): built.** These 14 fields exist as vardefs: `tkc_has_rightnow_c`, `tkc_rightnow_version_c`, `tkc_rightnow_future_version_c`, `tkc_pod_c`, `tkc_oracle_datacenter_c`, `tkc_auto_upgrade_c`, `tkc_projects_on_portal_c`, `tkc_console_url_c`, `tkc_tooling_c`, `tkc_contract_begin_c`, `tkc_contract_end_c`, `tkc_replicon_name_c`, `tkc_sharepoint_name_c`, `tkc_debtor_number_c`. The core field `account_type` has the TKC list `account_type_dom` and the default `tkc_relation`. Details: `docs/data-model.md` § Accounts.
Status of the other rows: `osc_id_c` is built on Accounts (SUITE-71; varchar 20, index `idx_accounts_osc_id_c`). The AOS_Contracts `osc_id_c` rows stay planned (SUITE-144). The Finance fields (hourly rates, expected revenue, other financial terms) are built in the module `tkc_AccountFinance`, not on Accounts (SUITE-64). The target names in the six Finance rows above are replaced by these fields: `rate_angels`, `rate_snor`, `rate_body`, `expected_revenue_current_fy`, `expected_revenue_next_fy`, `other_financial_terms`. See `docs/data-model.md` § tkc_AccountFinance. The import is not built. `tkc_crm_modules_c` and the seven REST-only fields are not built (open questions 5 and 16).

Existing SuiteCRM custom Accounts fields in this fork: only the four `jjwg_maps_*_c` overrides (Google Maps module). There is no collision with the proposed `tkc_*` names.

### SuiteCRM core Accounts fields that OSC does not use in these workspaces

`phone_office`, `phone_alternate`, `phone_fax`, `website`, `email1`, `industry`, `annual_revenue`, `employees`, `rating`, `ownership`, `ticker_symbol`, `sic_code`, `description`, `shipping_address_*`.
The workspaces do not show standard OSC Organization fields for phone, e-mail or web site. Open question 10.
Layout in SuiteCRM must decide whether to hide them.

## 3. Actions and buttons mapping

| OSC action | SuiteCRM action | Level |
|---|---|---|
| `Save` | Save | core |
| `SaveAndClose` | Save, then return to the list (or "Save and continue" pattern) | core / frontend (the Angular edit view may need a custom toolbar button) |
| `New` | Create | core |
| `Refresh` | Reload the record (browser reload or the refresh action of the detail view) | core |
| `Appointment` | Schedule a Meeting or Call linked to the account (Activities subpanel "Schedule Meeting") | core |
| `Print` / quick access `Print` | Print view (`Print as PDF` needs the AOS PDF template) | core (detail view `Print`); PDF is custom |
| `Copy` | Duplicate | core |
| `Delete` | Delete | core |
| `SpellCheck` | Browser spell check | drop (browser function) |
| `EditorLinks` | none | drop |
| `Info` | Record meta (created, modified, ID) | core (detail view footer) |
| Bookmark (screenshot only) | Favorites / "Add to favorites" | core |
| Grid: `Openen` | Row link / View | core |
| Grid: `Nieuw item toevoegen` (dropdown) | Subpanel "Create" | core |
| Grid: `Bestaand item toevoegen` | Subpanel "Select" | core |
| Grid: `Wissen` | Subpanel "Remove" (unlink) | core |
| Grid: `Verwijderen` | Delete the related record | core |
| Grid: `Afdrukken` | Subpanel print | **gap**. No standard subpanel print. Open question 11. |
| Grid: `Kopiëren` | none | **gap** (no copy of a related record from a subpanel) |

## 4. Workflow mapping

| OSC workflow step | Meaning | SuiteCRM option | Level |
|---|---|---|---|
| Decision "Type" on `C$OrgRelType` selecting 1 of 6 workspaces | Layout depends on the organization type | SuiteCRM has one detail layout per module. **No equivalent** for "pick a workspace by field value". Options: (a) one layout, with panels and fields that show or hide by `account_type` (field `logic` / display logic in vardefs, TBC by architect); (b) a custom Angular detail view in `extensions/tkc/` that picks a layout by type. | **Gap.** Architect decision |
| Decision "Profile of the logged-in account = Sales" (profile id 73), only for Klant | Sales team sees finance fields (hourly rates, expected revenue, contract dates, Debiteurnummer). Other staff sees the RightNow technical block and Site Werkzaamheden | SuiteCRM Roles with field-level access, plus display logic by role. No "layout by role" | **Gap.** Architect decision. Security-officer must see this: it hides financial data |
| Default type 183 (Relatie) when a new organization is created | Default dropdown value | Vardef default for `account_type` | core (config) |
| Type empty or unknown → Relatie workspace | Fallback | Fallback in the layout logic | custom |
| Inactive rule "Wel of niet tonen RN Versie" | none | nothing | drop |

The mapping of workflow to an Angular design must be tested for the 6 variants. A simple option: three groups.
Group A (Klant, SalesTeam variant), group B (Klant, general variant), group C (Prospect, Leverancier, Relatie, Oud Klant: same fields, different tabs).

## 5. Relationships

| OSC relation | SuiteCRM relationship | Status |
|---|---|---|
| Organization 1—n Contact | `accounts` → `contacts` (`accounts_contacts`) | core |
| Organization parent—child (`OrgHierarchy`) | `accounts.parent_id` (`member_of`/`members`) | core |
| Organization 1—n Opportunity (`OpportunitiesView`) | `accounts_opportunities` | core |
| Organization 1—n Incident (`IncidentView`) | `accounts` → `cases` | core |
| Organization 1—n Task | `tasks` parent (Accounts) | core |
| Organization notes (`OrgNotes`) | `notes` parent (Accounts) | core |
| Organization file attachments | `documents_accounts` or note attachments | core |
| Organization audit log | `accounts_audit` (fields with `audited=true`) | core. Custom fields must be marked audited if they must show in the log |
| Organization 1—n `Support$Project` (FK `Org_id`) | `Project` module (`projects_accounts`) or a custom module | **drop**: out of scope (owner decision) |
| Organization 1—n `Support$SupportContract` (FK `Organization`) | `AOS_Contracts` (`contract_account_id`) or a `tkc_` module | in scope, section 5a |
| Organization 1—n `Support$LicentieContract` (FK `Organization`) | `AOS_Contracts` with a type, or a `tkc_` module | in scope, section 5a |
| Organization n—m SLA (`SlaContainer`) | none in SuiteCRM | **drop** (owner decision) |
| Organization → external page: Replicon client page (URL template with `$p_orgcf_109`) | a `[custom]` URL built from `tkc_replicon_name_c` (link on the detail view, or a frame) | custom |
| Organization → external page: SharePoint/TFS dashboard (URL template with the org name or `$p_orgcf_112`) | a `[custom]` URL built from `tkc_sharepoint_name_c` | custom |
| Organization → report "Site Werkzaamheden" (id 101032) | a subpanel or a report (AOR_Reports) | TBC open question 4 |

Note on the two URL templates: the XML also contains a different host in the Browser controls of other workspaces (an older TFS server). Which URL is the current one is open question 12. In the templates, `$p_orgcf_109` and `$p_orgcf_112` are custom field IDs. They probably point to `C$Repliconname` and `C$Sharepointname`. This is not verified.
Embedded frames of external sites are often blocked by the other site (`X-Frame-Options`). A link that opens a new tab is the safer design.

## 5a. Contracts (in scope)

Owner decision: `Support.SupportContract` and `Support.LicentieContract` are in scope. `Support.Project` is out of scope (`drop`).
Verified by REST schema (2026-11-20, `metadata-catalog/Support.SupportContract` and `.../Support.LicentieContract`). Both objects have a link to Organization (n:1), file attachments and notes. Labels are Dutch.

### Support.SupportContract (REST API names)

| OSC field (REST) | OSC type | Label | SuiteCRM target | Exists? | New field type/len | Notes |
|---|---|---|---|---|---|---|
| `SupportContractNaam` | string(60), nullable | SupportContractNaam | AOS_Contracts.`name` | core | - | OSC 60 fits the core length. |
| `SupportContractCode` | string(30), nullable | SupportContractCode | AOS_Contracts.`reference_code` | core | - | |
| `StartContract` | date, not null | StartContract | AOS_Contracts.`start_date` | core | - | |
| `EindeContract` | date, not null | EindeContract | AOS_Contracts.`end_date` | core | - | |
| `Beschrijving` | string(349525), nullable | Beschrijving | AOS_Contracts.`description` | core | - | Text. |
| `Organization` | relation to organizations | Organization | AOS_Contracts.`contract_account_id` | core | - | n:1. |
| `Opportunity` | relation to opportunities | Opportunity | AOS_Contracts.`opportunity_id` | core | - | Core has an opportunity relate field. |
| `Contact` | **integer** (not a relation), nullable | Contact | AOS_Contracts.`contact_id` | core | - | A plain contact id, not a REST relation. The import must look up the contact by OSC id (`osc_id_c`). Open question 17. |
| `Incident` | relation to incidents, nullable | Incident | none | drop (proposed) | - | No core Contracts-Cases link. Owner decides (open question 17). |
| `TypeSupport` | menu, lookup object `Support.TypeSupport` | Type Support | AOS_Contracts.`tkc_support_level_c` | new | enum | 1 Brons, 2 Zilver, 3 Goud (3 items). |
| `Kanalen` | menu, same lookup object `Support.TypeSupport` | Kanalen | AOS_Contracts.`tkc_support_channel_c` | new | enum (same list) | Label "Kanalen" (channels) but the list is Brons/Zilver/Goud. Name and list do not match. Open question 17. |
| `UrenIncontract` | integer, not null | UrenIncontract | AOS_Contracts.`tkc_hours_in_contract_c` | new | int | Hours included. |
| `ChangeDrempel` | integer, not null | Change Drempel | AOS_Contracts.`tkc_change_threshold_c` | new | int | Change threshold (unit not known). |
| `RepliconProjectName` | string(60), nullable | RepliconProjectName | AOS_Contracts.`tkc_replicon_project_c` | new | varchar(60) | Feeds the Replicon link. |
| `createdTime`, `updatedTime`, `CreatedByAccount`, `UpdatedByAccount` | datetime, staff account | - | `date_entered`, `date_modified`, `created_by`, `modified_user_id` | core | - | Staff-to-user map. |
| `id` | integer | - | AOS_Contracts.`osc_id_c` | new | varchar(20), indexed | Import key. |

### Support.LicentieContract (REST API names)

| OSC field (REST) | OSC type | Label | SuiteCRM target | Exists? | New field type/len | Notes |
|---|---|---|---|---|---|---|
| `Referentie` | string(40), nullable | Referentie | AOS_Contracts.`name` and `reference_code` | core | - | The object has no name field: use the reference for both. |
| `DatumIngang` | date, not null | Datum Ingang | AOS_Contracts.`start_date` | core | - | Start date. |
| `DatumRenewal` | date, not null | Datum Renewal | AOS_Contracts.`end_date` and `renewal_reminder_date` | core | - | Renewal date: treat as the end of the current term. Open question 17. |
| `Duur` | integer, not null | Duur | AOS_Contracts.`tkc_duration_c` | new | int | Duration. Unit (months or years) is not known. |
| `Organization` | relation to organizations | Organization | AOS_Contracts.`contract_account_id` | core | - | n:1. |
| `id` | integer | - | AOS_Contracts.`osc_id_c` | new | varchar(20), indexed | Import key. |
| `createdTime` etc. | as above | - | as above | core | - | |

Core `AOS_Contracts` fields (from `public/legacy/modules/AOS_Contracts/vardefs.php`): `name`, `reference_code` (varchar 255), `start_date`, `end_date` (date), `total_contract_value` (currency), `status` (enum `contract_status_list`), `customer_signed_date`, `company_signed_date` (date), `renewal_reminder_date` (datetimecombo), `contract_type` (enum `contract_type_list`), `contract_account` (relate Accounts), `opportunity`, `contact`, `description`, line items and totals.

**Proposal (for the architect):** one `AOS_Contracts` module for both objects. `contract_type` = Support or Licentie (two new items in `contract_type_list`). Name, reference, dates, account, opportunity, contact and description are **core**. Five fields for Support and one for Licentie are **new** (`tkc_*_c`, table above). The two objects share only 5 fields and the new fields differ: a layout that shows Support fields only when `contract_type` = Support is needed. A separate `tkc_` module is the alternative if the owner wants own status flows. Neither object has a status field, so `status` stays at a default (open question 17). Total new fields for contracts: 6 plus `osc_id_c`.

Lookup objects used by the contracts: `Support.TypeSupport` (3 items, verified). Lookup objects not used by these two contracts (verified from the schemas): `Support.TypeMedewerker` (custom object with one text field `Type`; 0 rows; not the source of the opportunity menu `c.type_medewerker`), `Support.SupportPriority` (see incident.md), `Support.SupportPrioMenu` (empty, 0 rows). Other candidate lookups named in earlier drafts (`SupportKanalen`, `werkplek`, `RightNowRelease`) are not linked from either contract schema and are not needed.
Contract record counts were not read (not asked).

## 6. Open questions

Closed: 1 (field list, types, lengths and menus verified), 2 (SLA dropped), 3 (contracts in scope, projects out). Partly closed: 5 (`State` is three CRM-module flags), 10 (`org_telefoonnummer` exists, `industry` and `numberOfEmployees` exist).

4. **Report 101032 "Site Werkzaamheden"** (Klant - Algemeen). REST has `Support.SiteWerkzaamheden`, `Support.TypeWerkzaamheden`, `Support.StatusWerkzaamhedenM`. Must this data migrate? Is it linked to the contracts?
5. **`Org.State` ("Staat").** Three CRM-module flags (service, sales, marketing). Does the business use them? Keep or drop?
6. **Field names and labels do not match.** `C$Tekstgebieduurtarief` shows "Verw omzet lopend boekjaar". `C$Tekstgebiedreiskosten` shows "Verw omzet komend boekjaar". Which meaning is right? Is expected revenue a number or text? The same for the hourly-rate fields.
7. **`Login`, `PasswordEncrypt`, `C$CompanyImage`.** Proposed: drop. Confirm. Security-officer to confirm.
8. **Staff mapping.** `SalesAcctId` to SuiteCRM users: by login or e-mail? Which user holds records of inactive staff?
9. **How is the workflow started?** The screenshot shows a Klant - Algemeen layout for a Prospect.
10. **Standard OSC fields.** `industry` and `numberOfEmployees` exist in REST. Are they, e-mail and web site in use? Migrate?
11. **Print.** What does `Afdrukken` print? Is an AOS PDF template needed?
12. **External URLs.** Replicon and SharePoint/TFS: which URL is current? Link or embedded view?
13. **Layout per type and per role.** Six screens, or one layout with conditional panels? Field-level security for the Sales-only finance block?
14. **Prospect tab "Contracten".** XML shows only Opportunities. Intended?
15. **Type "Lead".** Real accounts, or must they become Leads?
16. **Seven fields found only in REST** (`unrncrm`, `pwrncrm`, `org_telefoonnummer`, `rightnow_uurtarief`, `tarief_snor`, `tarief_angels`, `tarief_bodybuilders`). In use? Migrate or drop? `unrncrm` and `pwrncrm` look like credentials.

### Still open after REST (2026-11-20)
Address-type and state menus: DONE (second batch, 2026-11-20).
- Optional: contract record counts (`Support.SupportContract`, `Support.LicentieContract`) for the import plan. Not read.
The contracts have no status field in the schema, so no status can be migrated.
Everything else REST can answer is done. The remaining open points are business questions (section 6, questions 4 to 17).
17. **Contracts.** (a) `Kanalen` uses the list Brons/Zilver/Goud, the same list as `TypeSupport`. Is `Kanalen` a duplicate of the support level, or a wrong list? (b) `DatumRenewal` = contract end or only a reminder date? Unit of `Duur` and of `ChangeDrempel`? (c) Is the `Incident` link on SupportContract used, and is the plain integer `Contact` a contact id? (d) Contracts have no status: what status shows in SuiteCRM?

## 7. Notes for design and assessment

- **Gap: no workspace-by-value routing, no role-based layout.** This is the biggest functional gap. It needs a design decision (conditional panels or a custom Angular view).
- **Gap: no SLA.** Neither an SLA object nor an SLA clock exists in SuiteCRM core.
- **Gap: embedded browser control.** SuiteCRM has no equivalent. Use links.
- **Gap: subpanel print and copy.**
- **Data volume of new fields:** 22 new fields, most are used by only some types. Many of them (RightNow block) look like data of a software vendor's customers. Check with the user if all must migrate.
- **Customer data in inputs:** the screenshot `Screenshot 2026-11-20 at 10.58.57.png` shows a real organization name, three contact persons with e-mail addresses and the name of a staff member (Verkoper). It stays in `designs/screens/accounts/` and is not copied. The user must decide if it can stay in git. See the report to the caller.
- Dutch labels are in the XML. The SuiteCRM language files need nl_NL labels for the new fields.
