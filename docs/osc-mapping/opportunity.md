# OSC Opportunity → SuiteCRM Opportunities

Status: **DRAFT for user review.** No OSC REST access yet. Source: `designs/screens/opportunities/tkc-opportunities.xml`
(OSC 26C SP1 export), the screenshot, and the SuiteCRM vardefs. Raw parse: `data/osc/workspace/opportunities.json` (gitignored).
The workspace is in Dutch. Labels below are the workspace labels. A label in "quotes" replaces the OSC default label.

Every type, length and menu value marked **unverified** comes from neither the XML nor the SuiteCRM code. See Open questions.

## 1. Workspace summary

Workspace title bar: "TKC - Verkoopkans". Record types in the export: Opportunity, Contact, Incident, Org, Task.

| Tab | Content |
|---|---|
| Overzicht (Summary), 2 columns | Button "Auto Offerte Nr" + add-in `RightNowAutoOfferteNrBUI`; Reference, Name (required), Status, Owner, Order value, Created-from-incident no., Closed order value, Organisation, Contact, Cross/up sell, Benadering, Country, Description (multiline) |
| Details, title "Voltooiingsgegevens" | Initial contact, Quote date, Expected order date, Close date (no time), Notes grid (`OpportunityNotes`) |
| Taken (Tasks) | Tasks grid (report 9001, search report 8009, not run on New) |
| Bijlagen (Attachments) | File attachments grid |
| Controlelogboek (Audit log) | Audit log grid |

Info panel (record metadata): Created, CreatedBy, Updated, UpdatedBy, RuleCtx.Escllevel, RuleCtx.State, OpId.

Ribbon (Home): Save, Save and close, New, Refresh, Forward, Print, Copy, Delete, Spell check, Reject, Appointment, Links (RightNow link), Info.
Quick access: New, Save, Save and close, Print, Bookmark.

### Workspace rules (4)
| Rule | Active | Trigger | Condition | Action |
|---|---|---|---|---|
| "Status Menu opbouwen" | no | Editor loaded | none | none (empty) |
| "Nieuwe regel" (1) | yes | Editor loaded | Editor mode = New | `Opportunity.Assigned` = logged-in staff |
| "Nieuwe regel" (2) | yes | `C$Fasewaarschijnlijkheid` changed | value = menu id 279 | `Opportunity.Closed` = now + 1 month |
| "Run AutoOfferteNr" | yes | Button "Auto Offerte Nr" clicked | none | Fire named event `AutoOfferteNr` |

## 2. Field mapping

SuiteCRM module: `Opportunities` (vardefs object `Opportunity`). Existing custom Opportunities vardefs: only four `jjwg_maps_*_c` overrides (Google Maps). Nothing TKC-specific exists yet.
Field names for new fields follow the rule: suffix `_c`, prefix `tkc_`.

| OSC field (API name) | OSC type | Workspace location | SuiteCRM module.field | Exists? | New field type/len | Dropdown values | Notes |
|---|---|---|---|---|---|---|---|
| `Opportunity.C$Reference` ("Reference") | custom field, text (unverified: type, length) | Overzicht r2c0 | Opportunities.`tkc_reference_c` | new | varchar, length open (Q3) | n/a | Business reference / quote number. The "Auto Offerte Nr" add-in probably fills it (Q4). Candidate to show in list view. |
| `Opportunity.Name` ("Naam van verkoopkans") | string (unverified length) | Overzicht r2c1 | Opportunities.`name` | core | n/a | n/a | Required on new and edit in OSC. SuiteCRM `name`: varchar 50, required. Check OSC length against 50 (Q3). |
| `Opportunity.C$Fasewaarschijnlijkheid` ("Status") | custom menu (unverified) | Overzicht r3c0 | Opportunities.`sales_stage` + `probability` | core | n/a | Unknown. Screenshot shows one value "0% - Lead", so each value holds a stage and a probability. Full list needs REST (Q1). | Required on new. Replace the core `sales_stage_dom` list with the OSC list, in a custom dropdown. `probability` is derived from the value (percent in the label). Menu id 279 triggers a rule (section 3). Core stages (Prospecting ... Closed Won/Closed Lost) do not match. |
| `Opportunity.Assigned` ("Eigenaar verkoopkans") | Account (staff) reference | Overzicht r3c1 | Opportunities.`assigned_user_id` | core | n/a | n/a | Default = current user on New. Import needs a staff-to-user map (Q9). |
| `Opportunity.RepValue` ("Order Waarde") | currency (EUR shown) | Overzicht r4c0 | Opportunities.`amount` (+ `currency_id`) | core | n/a | n/a | SuiteCRM `amount` is required, currency type. OSC currency shown as EUR in screenshot. Check that the OSC field is currency, not decimal (Q3). |
| `Incident.RefNo` ("Gemaakt vanuit incidentnr") | string, read-only (Incident field shown on Opportunity workspace) | Overzicht r4c1 | Opportunities → Cases (new relationship) | new | relationship, see section 4 | n/a | Shows the reference number of the source incident. Empty in the screenshot. The relation field on the OSC Opportunity is not in the XML (Q7). |
| `Opportunity.ClosedValue` ("Gesloten order waarde") | currency (unverified) | Overzicht r5c0 | Opportunities.`tkc_closed_amount_c` | new | currency | n/a | SuiteCRM has one `amount` only. Alternative: reuse `amount` after close (Q5). |
| `Opportunity.OrgId` ("Organisatie") | Organization reference | Overzicht r5c1 | Opportunities.`account_id` / `account_name` | core | n/a | n/a | Search report 8001 in OSC. SuiteCRM: `account_name` is required in the vardef. |
| `Opportunity.C$Typeverkoop` ("Cross- up sell") | custom menu (unverified) | Overzicht r6c0 | Opportunities.`tkc_sales_type_c` | new | enum | Unknown (Q1). Screenshot shows "[Geen waarde]" as empty state. | Core `opportunity_type` has only Existing Business / New Business. Do not reuse until values are known. |
| `Opportunity.CId` ("Contactpersoon") | Contact reference | Overzicht r6c1 | Opportunities.`contacts` (relationship `opportunities_contacts`) | core | n/a | n/a | OSC holds one primary contact. SuiteCRM is many-to-many. Import sets one link. Reports 8014 and 9033 are search reports. |
| `Opportunity.C$Benadering` ("Benadering") | custom menu (unverified) | Overzicht r7c0 | Opportunities.`tkc_approach_c` (name open) | new | enum | Unknown (Q1). "Benadering" means "approach". May overlap core `lead_source` (Q6). | Label not changed in the XML. |
| `Opportunity.Terr` ("Land") | menu / Territory (unverified) | Overzicht r7c1 | Opportunities.`tkc_country_c` | new | enum | Screenshot shows value "Overig" (other). Full list unknown (Q1). | Name suggests the standard Territory field relabelled "Land". Verify (Q8). SuiteCRM Opportunities has no country field. |
| `Opportunity.Summary` ("Beschrijving") | string, multiline | Overzicht r8 (span 2) | Opportunities.`description` | core | n/a | n/a | |
| `Opportunity.InitialContact` ("Initial contact") | unverified (likely date) | Details r1c0 | Opportunities.`tkc_initial_contact_c` | new | date (unverified) | n/a | No SuiteCRM counterpart. Label is the OSC default (Q3). |
| `Opportunity.C$Offertedatum` ("Offertedatum") | custom field, probably date (unverified) | Details r1c1 | Opportunities.`tkc_quote_date_c` | new | date (unverified) | n/a | Could be derived from the linked AOS quote instead (Q4). |
| `Opportunity.ForecastClose` ("Verwachte Order Datum") | date | Details r2c0 | Opportunities.`date_closed` | core | n/a | n/a | In SuiteCRM `date_closed` is the expected close date (required). |
| `Opportunity.Closed` ("Closed") | date, no time portion | Details r2c1 | Opportunities.`tkc_date_closed_actual_c` | new | date | n/a | Actual close date. Rule sets it to now + 1 month on one status. SuiteCRM has no second close date (Q5). |
| `Opportunity.Created` | datetime, system | Info panel | Opportunities.`date_entered` | core | n/a | n/a | Keep OSC value on import. |
| `Opportunity.CreatedBy` | Account, system | Info panel | Opportunities.`created_by` | core | n/a | n/a | Staff-to-user map (Q9). |
| `Opportunity.Updated` | datetime, system | Info panel | Opportunities.`date_modified` | core | n/a | n/a | |
| `Opportunity.UpdatedBy` | Account, system | Info panel | Opportunities.`modified_user_id` | core | n/a | n/a | |
| `Opportunity.OpId` | integer ID, system | Info panel | Opportunities.`osc_id_c` | new | int or varchar(20), indexed | n/a | Import key (project rule: `osc_id_c`, indexed). |
| `Opportunity.RuleCtx.Escllevel` | system rule context | Info panel | none | drop | n/a | n/a | Escalation level. Rule engine state, no SuiteCRM meaning. |
| `Opportunity.RuleCtx.State` | system rule context | Info panel | none | drop | n/a | n/a | Rule engine state. |

| Exists? | Count | Fields |
|---|---|---|
| core | 12 | Name, Status (stage; `probability` is derived), Assigned, RepValue, OrgId, CId, Summary, ForecastClose, Created, CreatedBy, Updated, UpdatedBy |
| new | 10 | Reference, RefNo (relationship), ClosedValue, Typeverkoop, Benadering, Terr, InitialContact, Offertedatum, Closed, OpId (`osc_id_c`) |
| drop | 2 | RuleCtx.Escllevel, RuleCtx.State |

### Not in the workspace
The readme describes expected revenue, win probability, close date, sales stages (Lead, Active, Inactive, Won, Lost), products and quotes. The export shows **no** probability field, **no** products grid, **no** quotes grid and **no** reports grid. Only the combined "Status" menu carries the probability. Stages Lead / Active / Inactive / Won / Lost are not confirmed by the export (Q1). The readme text reads like generic OSC documentation, not like this TKC workspace.

## 3. Actions and buttons mapping

| OSC action | SuiteCRM action | Type |
|---|---|---|
| Save, Save and close | Save, Save and Continue / Save | core |
| New | Create | core |
| Refresh | Reload record (browser / refresh action) | core |
| Print | Print / PDF of the record | core (print) or custom PDF template, open (Q10) |
| Bookmark (quick access) | Favorites | core |
| Copy | Duplicate | core |
| Delete | Delete | core |
| Forward | Reassign owner (change Assigned user) or share by email | core, intent open (Q10) |
| Spell check | Browser spell check | drop |
| Reject | Unknown semantics for opportunities (OSC "reject" returns a record to its queue) | open (Q10); likely drop |
| Appointment | Schedule Meeting / Call (activities) | core |
| Links (RightNow), Info | none | drop |
| Button "Auto Offerte Nr" + add-in `RightNowAutoOfferteNrBUI` + named event `AutoOfferteNr` | Generate quote number and write it to `tkc_reference_c`. Logic is not in the export. | custom (logic hook or button action) after Q4 |
| Rule: New → Assigned = current user | Default assigned user = current user | core (SuiteCRM default) |
| Rule: Status = menu id 279 → Closed = now + 1 month | Workflow or logic hook on `sales_stage` change that sets `tkc_date_closed_actual_c` | workflow (AOW) or custom hook. Menu id 279 label is unknown (Q2). |
| Rule "Status Menu opbouwen" (inactive, empty) | none | drop |

## 4. Relationships

| OSC relation | SuiteCRM relationship | Notes |
|---|---|---|
| Opportunity.OrgId → Organization | `accounts_opportunities` (Accounts) | core |
| Opportunity.CId → Contact | `opportunities_contacts` (Contacts) | core. OSC: one contact. SuiteCRM: many. |
| Opportunity.Assigned → Account (staff) | `assigned_user_id` → Users | core |
| OpportunityNotes grid | `opportunity_notes` (Notes) | core subpanel |
| Tasks grid | `opportunity_tasks` (Tasks) | core subpanel |
| FileAttachments grid | `documents_opportunities` or Notes with attachment | core. Choose one (Q10). |
| Audit log grid | Field audit (`audited` on vardefs) + Change Log view | core, needs `audited` flags per field. Not migrated as history unless Q11 says so. |
| Incident (source incident, `Incident.RefNo`) | New relationship Opportunities ↔ Cases | new. Core has no `opportunity_cases` relationship. Needs architect review. Direction (one case → many opportunities) open (Q7). |
| Products, Quotes | `opportunity_aos_quotes` (AOS_Quotes) and AOS_Products via quote line items | Core relationship exists. No OSC grid in this workspace. Need source of truth for TKC quotes (Q4). |

## 5. Open questions

1. **Menu values.** What are the values of the menus `C$Fasewaarschijnlijkheid` (Status), `C$Typeverkoop`, `C$Benadering` and `Terr` (Land), and the percent for each Status value? Needs REST metadata or the reference site. Are the stages Lead, Active, Inactive, Won, Lost from the readme right?
2. **Menu id 279.** Which Status value is id 279 (the rule sets Closed to now + 1 month)? Is it a won stage?
3. **Types and lengths.** Type and length of `C$Reference`, `C$Offertedatum`, `InitialContact`, `Name`, `RepValue`, `ClosedValue`. None are in the XML. Needs REST `metadata-catalog/Opportunity`.
4. **Quote number.** What does the add-in `RightNowAutoOfferteNrBUI` do (format, sequence)? Is the quote done in OSC only as a number, or should it map to AOS_Quotes? Is `C$Reference` the same value?
5. **Two amounts and two close dates.** OSC has Order value + Closed order value and Expected + Actual close date. SuiteCRM has `amount` and `date_closed` only. Keep both pairs as new fields (proposed) or fold them?
6. **Benadering vs lead source.** Does "Benadering" mean the lead source (core `lead_source`) or something else?
7. **Source incident.** How does the Opportunity link to the Incident in OSC (field name, one-to-many)? Is it needed in SuiteCRM, or only for history?
8. **Land.** Is `Terr` the standard Territory object relabelled "Land"? Should it be a country list or a territory relation?
9. **Staff mapping.** Which OSC staff accounts map to which SuiteCRM users (Assigned, CreatedBy, UpdatedBy)? Needs a user list from the user, not from this document.
10. **Buttons.** What do Forward and Reject do in TKC practice? Print: which output is wanted? Attachments: Documents or Notes?
11. **Audit log.** Must the OSC audit history migrate, or only SuiteCRM auditing from go-live?
12. **Core required fields.** SuiteCRM requires `account_name`, `amount`, `date_closed` and `sales_stage`. In OSC, only Name is required. Opportunities without organisation or value need a default or a relaxed vardef (architect).
