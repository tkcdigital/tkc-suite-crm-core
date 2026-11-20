# OSC Organization → SuiteCRM Accounts

> **Owner decisions 2026-11-20:** SLA **dropped** (SLA container → `drop`). Contracts (`Support$SupportContract`, `Support$LicentieContract`) **in scope**. Projects (`Support$Project`) **out of scope**. See `docs/backlog.md` § Product decisions.

Status: **DRAFT v1** (SUITE-3). Source: workflow export `designs/screens/accounts/tkc-organization-workflow-with-workspaces.xml`,
the flow diagram, one screenshot and the SuiteCRM 8.10 vardefs. **No OSC REST access yet.**
Everything marked `TBC` is not verified. It needs the REST metadata (`metadata-catalog/Organization`, menu values) or an answer from the user.

Raw parse output: `data/osc/workspace/accounts.json` and `data/osc/workspace/accounts-flow.json` (gitignored).

Legend for `Exists?`: `core` = standard SuiteCRM field. `new` = we add it. `drop` = not migrated.
OSC field names in the XML use the form `Org.C$<Name>` (custom field) or `Org.<Name>` (standard field).
The REST name of a custom field is `CustomFields.c.<snake_case>` (TBC for each field).

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
`Type` of an OSC field is verified only when the XML or screenshot shows it. Everything else is `TBC`.
`Req/RO` is not set in the XML for any field (`RequiredOption` and `ReadOnlyOption` are empty). The screenshot shows a required marker on Organisatienaam and Staat. Those come from the object definition.

| OSC field (API name) | OSC type | Workspace location | SuiteCRM module.field | Exists? | New field type/len | Dropdown values | Notes |
|---|---|---|---|---|---|---|---|
| `Org.Name` | text (standard, required per screenshot) | Left panel row 0, all 6 | Accounts.`name` | core | | | Label "Organisatienaam". |
| `Org.SalesAcctId` | FK to staff account (standard "Sales Account") | Left panel row 1, all 6 | Accounts.`assigned_user_id` | core | | | Shown as "Verkoper". Staff-to-user mapping needed for import (open question 8). |
| `Org.C$OrgRelType` | menu (custom), label "Type" | Left panel row 2, all 6; drives the workflow | Accounts.`account_type` | core | enum, replace the list with a TKC list in `account_type_dom` | Lead (id 273), Prospect (180), Klant (181), Leverancier (182), Relatie (183), Oud Klant (187). Default in workspaces: 183 (Relatie). The list may have more items (path "Other"). TBC via REST | Core field `account_type` exists but has an English list. Architect: replace the list by a custom override, or add `tkc_relatie_type_c`. Values from workflow path labels. Need REST to confirm full list and sort order. |
| `Org.Oaddr` | address (standard compound field), label "Facturering" | Left panel row 3, all 6 | Accounts.`billing_address_street`, `_city`, `_state`, `_postalcode`, `_country` | core | | | Label means billing address. Components of the OSC address field: TBC via REST. `shipping_address_*` stay unused. |
| `Org.C$Uurtariefangels` | text, multi-line (XML `Multiline=True`), label "Uurtarief angels" | Klant - SalesTeam, left panel | Accounts.`tkc_uurtarief_angels_c` | new | `text` (TBC: varchar or currency?) | | Hourly rate. A numeric rate may fit `currency`. Needs the real type. |
| `Org.C$Uursnor` | TBC, label "Uurtarief snor" | Klant - SalesTeam | Accounts.`tkc_uurtarief_snor_c` | new | `text` (TBC) | | Same as above. |
| `Org.C$Uurtariefbody` | TBC, label "Uurtarief body" | Klant - SalesTeam | Accounts.`tkc_uurtarief_body_c` | new | `text` (TBC) | | Same as above. |
| `Org.C$Tekstgebieduurtarief` | text area (name suggests), label "Verw omzet lopend boekjaar" | Klant - SalesTeam | Accounts.`tkc_verw_omzet_lopend_c` | new | `text` (TBC) | | Field name says "hourly rate". Label says "expected revenue, current fiscal year". Name and label do not match. Open question 6. |
| `Org.C$Tekstgebiedreiskosten` | text area (name suggests), label "Verw omzet komend boekjaar" | Klant - SalesTeam | Accounts.`tkc_verw_omzet_komend_c` | new | `text` (TBC) | | Field name says "travel costs". Label says "expected revenue, next fiscal year". Open question 6. |
| `Org.C$Tekstgebiedoverig` | text area (name suggests), label "Overige financiële afspraken" | Klant - SalesTeam | Accounts.`tkc_overige_fin_afspraken_c` | new | `text` | | Free text. |
| `Org.C$Tooling` | TBC, label "Overige tooling" | Klant - SalesTeam | Accounts.`tkc_tooling_c` | new | `text` (TBC) | | |
| `Org.C$HasRightnow` | TBC; screenshot shows a Ja/Nee dropdown, label "Heeft RightNow ?" | Klant - Algemeen, Prospect, Leverancier, Relatie, Oud Klant | Accounts.`tkc_has_rightnow_c` | new | `bool` or `enum` yes/no (TBC) | Ja / Nee | Used in the inactive rule. |
| `Org.C$RightnowVersion` | menu (screenshot), label "RightNow Version" (XML label "Versie" in SalesTeam) | all 6 | Accounts.`tkc_rightnow_version_c` | new | `enum` | TBC via REST | Menu values not in the XML. |
| `Org.C$RightnowFutureVersion` | menu (screenshot), label "RightNow Future Version" | Klant - Algemeen, Prospect, Leverancier, Relatie, Oud Klant | Accounts.`tkc_rightnow_future_version_c` | new | `enum` | TBC via REST | Values look like release names with a month. Verify. |
| `Org.C$Pod` | menu (screenshot), label "POD" | Klant - Algemeen | Accounts.`tkc_pod_c` | new | `enum` | TBC via REST | |
| `Org.C$OracleDatacenter` | menu (screenshot), label "Oracle datacenter" | Klant - Algemeen | Accounts.`tkc_oracle_datacenter_c` | new | `enum` | TBC via REST | |
| `Org.C$AutoUpgrade` | Ja/Nee dropdown (screenshot), label "Auto Upgrade Program" | Klant - Algemeen | Accounts.`tkc_auto_upgrade_c` | new | `bool` or `enum` (TBC) | Ja / Nee | |
| `Org.C$ProjectsOnPortal` | Ja/Nee dropdown (screenshot), label "Projects On Portal" | Klant - Algemeen | Accounts.`tkc_projects_on_portal_c` | new | `bool` or `enum` (TBC) | Ja / Nee | |
| `Org.C$ConsoleUrl` | URL/text (screenshot shows a web icon), label "Console URL" | all 6 | Accounts.`tkc_console_url_c` | new | `url`, varchar(255) (TBC) | | |
| `Org.C$BegindatumContract` | TBC (name suggests date) | Klant - SalesTeam, Prospect, Leverancier, Relatie, Oud Klant | Accounts.`tkc_contract_begin_c` | new | `date` (TBC) | | Contract start. Not on Klant - Algemeen. |
| `Org.C$EinddatumContract` | TBC (name suggests date) | same as above | Accounts.`tkc_contract_end_c` | new | `date` (TBC) | | Contract end. |
| `Org.C$Debiteurnummer` | TBC (name suggests text), label "Debiteur Nummer" | all 6 | Accounts.`tkc_debiteurnummer_c` | new | `varchar(50)` (TBC) | | Debtor number. Key for the finance system. Consider an index. |
| `Org.C$Repliconname` | TBC (text) | Klant - SalesTeam, Klant - Algemeen (tab Replicon Overzicht) | Accounts.`tkc_replicon_name_c` | new | `varchar(255)` (TBC) | | Feeds the Replicon URL. See section 5. |
| `Org.C$Sharepointname` | TBC (text), label "Share Point Name" | all 6 (tab SharePoint Documenten) | Accounts.`tkc_sharepoint_name_c` | new | `varchar(255)` (TBC) | | Feeds the SharePoint URL. See section 5. |
| `Org.State` | TBC. Multi-select chips in the screenshot (3 chips), label "Staat", required marker. XML default value `Service||Sales||Marketing` | tab Details (SalesTeam); left panel in the screenshot | Accounts.`tkc_state_c` | new | `multienum` (TBC) | Items shown in the screenshot are not the same as the XML default (the screenshot shows "Outreach" and "Verkoopkansen"). TBC via REST | Meaning is unclear. Open question 5. |
| `Org.Login` | text (standard organization login), label "Inloggen" | tab Details | none | drop | | | Customer portal login. See open question 7. Proposed: drop. |
| `Org.PasswordEncrypt` | password (standard), label "Wachtwoord" | tab Details | none | drop | | | A password. Must not go into a CRM. Proposed: drop. |
| `Org.C$CompanyImage` | text (screenshot shows a file name in a text area), label "company_image" | Klant - SalesTeam, Klant - Algemeen | none | drop | | | Seems a logo file name for the customer portal. Proposed: drop. Open question 7. |
| `Org.OrgId` (Info item) | integer ID | Info pane | Accounts.`osc_id_c` | new | `int` or `varchar(20)`, indexed | | Import key. Required by `suitecrm-customization.md`. |
| `Org.Created` (Info item) | datetime | Info pane | Accounts.`date_entered` | core | | | |
| `Org.Updated` (Info item) | datetime | Info pane | Accounts.`date_modified` | core | | | |
| `Org.RuleState` (Info item) | system | Info pane | none | drop | | | OSC rule engine state. No meaning in SuiteCRM. |
| SLA list (`SlaContainer`, not a field) | relationship | Left panel | none | drop (open) | | | See open question 2. |

Counts: **core 6** (`Name`, `SalesAcctId`, `OrgRelType`, `Oaddr`, `Created`, `Updated`), **new 22** (21 OSC custom/standard fields + `osc_id_c`), **drop 4** (`Login`, `PasswordEncrypt`, `CompanyImage`, `RuleState`). The SLA list is a relationship, not a field (section 4).
Of the 21 new OSC fields, only the menu fields have an `enum` type that the screenshot shows. All other types are `TBC`.

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
| Organization 1—n `Support$Project` (FK `Org_id`) | `Project` module (`projects_accounts`) or a custom module | **TBC** open question 3 |
| Organization 1—n `Support$SupportContract` (FK `Organization`) | `AOS_Contracts` (`account_id`) or a custom module | **TBC** open question 3 |
| Organization 1—n `Support$LicentieContract` (FK `Organization`) | `AOS_Contracts` with a type, or a custom module | **TBC** open question 3 |
| Organization n—m SLA (`SlaContainer`) | none in SuiteCRM | **Gap** open question 2 |
| Organization → external page: Replicon client page (URL template with `$p_orgcf_109`) | a `[custom]` URL built from `tkc_replicon_name_c` (link on the detail view, or a frame) | custom |
| Organization → external page: SharePoint/TFS dashboard (URL template with the org name or `$p_orgcf_112`) | a `[custom]` URL built from `tkc_sharepoint_name_c` | custom |
| Organization → report "Site Werkzaamheden" (id 101032) | a subpanel or a report (AOR_Reports) | TBC open question 4 |

Note on the two URL templates: the XML also contains a different host in the Browser controls of other workspaces (an older TFS server). Which URL is the current one is open question 12. In the templates, `$p_orgcf_109` and `$p_orgcf_112` are custom field IDs. They probably point to `C$Repliconname` and `C$Sharepointname`. This is not verified.
Embedded frames of external sites are often blocked by the other site (`X-Frame-Options`). A link that opens a new tab is the safer design.

## 6. Open questions

1. **Menu values and types of all custom fields (blocking for import and field creation).** The XML has no field definitions. Needs REST (`metadata-catalog/Organization`, menu lists of `C$OrgRelType`, `C$RightnowVersion`, `C$RightnowFutureVersion`, `C$Pod`, `C$OracleDatacenter`, `State`) or a screenshot of the Admin site. Also: field length, required flag, default, and whether `Org.State` is a multi-select.
2. **SLAs.** The left panel has an SLA container ("SLA's" with an Add button). SuiteCRM has no SLA module and no SLA clock. Is SLA data on organizations in use? What must the SLA do on a case (due date, escalation)? Options: (a) drop, (b) a custom `tkc_sla` module with relationship Accounts n—m and a field on Cases, (c) a free-text/dropdown field "SLA".
3. **Custom OSC objects `Support$Project`, `Support$SupportContract`, `Support$LicentieContract`.** They appear as grids on the Klant workspaces (Leverancier also shows Projects). Are they in the SUITE scope? Which SuiteCRM target: core `Project` and `AOS_Contracts`, or new custom modules? Needs their field lists (REST) and a view from the user.
4. **Report 101032 "Site Werkzaamheden"** (Klant - Algemeen). What data does it show, and does it need to migrate?
5. **`Org.State` ("Staat").** The screenshot shows three chips (Service, Outreach, Verkoopkansen) and a required marker. The XML default is `Service||Sales||Marketing`. What does it mean for the business? Is it the same as the standard OSC organization "state"? Keep or drop?
6. **Field names and labels do not match.** `C$Tekstgebieduurtarief` shows "Verw omzet lopend boekjaar" and `C$Tekstgebiedreiskosten` shows "Verw omzet komend boekjaar". Which meaning is right? Is expected revenue a number (currency) or free text? The same question for the three hourly-rate fields.
7. **Fields `Login`, `PasswordEncrypt`, `C$CompanyImage`.** Proposed: drop. Confirm. If the customer portal or another system reads them, name that system. A password must not be imported (GDPR/security). The security-officer should confirm.
8. **Staff mapping.** `SalesAcctId` ("Verkoper") must map to SuiteCRM users. How are the OSC staff accounts mapped to SuiteCRM users (by login or e-mail)? Which user holds records of inactive staff?
9. **How is the workflow started?** The screenshot shows a Klant - Algemeen layout for an organization of type "Prospect". Is the workflow not always used? Which workspace opens from a search result or from a contact? Is the workflow in production the same as this export?
10. **Standard OSC fields.** The workspaces show no phone, e-mail, web site or industry. Is that true for the org data? Do any of them exist in OSC data, and do we migrate them? SuiteCRM has them in core.
11. **Print.** What does `Afdrukken` print (the record or the grid)? Is a PDF template for the account needed (AOS PDF templates)?
12. **External URLs.** Replicon and SharePoint/TFS: which URL is current? One export shows an older TFS host. Is a new-tab link enough, or is an embedded view needed? Are the sites still in use?
13. **Layout per type and per role.** Does the business want to keep six different screens? A single layout with conditional panels is easier to maintain. Does the Sales-only finance block need field-level security?
14. **Types without a tab list.** Prospect has the tab "Contracten" but the XML shows only Opportunities. Is that the intended content?
15. **Type "Lead".** Lead (273) is routed to the Prospect workspace. In SuiteCRM there is a separate Leads module. Are organizations of type Lead real accounts, or must they become Leads?

## 7. Notes for design and assessment

- **Gap: no workspace-by-value routing, no role-based layout.** This is the biggest functional gap. It needs a design decision (conditional panels or a custom Angular view).
- **Gap: no SLA.** Neither an SLA object nor an SLA clock exists in SuiteCRM core.
- **Gap: embedded browser control.** SuiteCRM has no equivalent. Use links.
- **Gap: subpanel print and copy.**
- **Data volume of new fields:** 22 new fields, most are used by only some types. Many of them (RightNow block) look like data of a software vendor's customers. Check with the user if all must migrate.
- **Customer data in inputs:** the screenshot `Screenshot 2026-11-20 at 10.58.57.png` shows a real organization name, three contact persons with e-mail addresses and the name of a staff member (Verkoper). It stays in `designs/screens/accounts/` and is not copied. The user must decide if it can stay in git. See the report to the caller.
- Dutch labels are in the XML. The SuiteCRM language files need nl_NL labels for the new fields.
