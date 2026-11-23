# OSC Opportunity → SuiteCRM Opportunities

Status: **VERIFIED with REST 2026-11-20 (tst1).** Customer names are replaced by placeholders; the real values are in OSC and in data/osc/rest-output/ (gitignored). Field types, lengths, nullability, all menu values, menu id 279 and the quote number structure are verified (4 batch runs). The remaining open points are business decisions. Source: `designs/screens/opportunities/tkc-opportunities.xml`
(OSC 26C SP1 export), the screenshot, and the SuiteCRM vardefs. Raw parse: `data/osc/workspace/opportunities.json` (gitignored).
**Build status (SUITE-69): built.** The 8 new fields of section 2 (`tkc_reference_c`, `tkc_closed_amount_c`, `tkc_sales_type_c`, `tkc_approach_c`, `tkc_country_c`, `tkc_initial_contact_c`, `tkc_quote_date_c`, `tkc_date_closed_actual_c`), the 3 lists, the `name` length 255, the stage list and the probability logic exist. See `docs/data-model.md` § Opportunities. `osc_id_c` is built in SUITE-71. The source-case relationship is built in SUITE-70 (`tkc_source_case_c`, `tkc_source_case_id_c`, `tkc_cases_opportunities`). Planned: the quote number action and the reference validator (E10, SUITE-138 and SUITE-139), the won-date hook, and the import. Not migrated: no data is imported yet.
The workspace is in Dutch. Labels below are the workspace labels. A label in "quotes" replaces the OSC default label.

Types, lengths and menu values were verified by REST on 2026-11-20 (schemas with `Accept: application/schema+json`, menu resources `namedIDs/opportunities/customFields/c/<field>`, aggregate ROQL counts). Counts come from tst1 (1964 opportunities) and are not production numbers.

## 0. REST findings (tst1, 2026-11-20)
Method: `GET /opportunities/<id>` on one record. Only key names and JSON value types were read. No record value is stored here. The raw response is in `data/osc/rest-output/opp-rec.json` (gitignored).
Superseded on 2026-11-20: the schema header now works and the lengths, nullability and menus are verified (see the tables in section 2 and the new subsections below).

REST names of the fields in the workspace (REST API name = workspace field):
| Workspace field | REST property | JSON type seen |
|---|---|---|
| `C$Reference` | `customFields.c.reference` | string |
| `C$Offertedatum` | `customFields.c.offertedatum` | null in sample, type unknown |
| `C$Fasewaarschijnlijkheid` | `customFields.c.fasewaarschijnlijkheid` | menu object (id, lookupName) |
| `C$Typeverkoop` | `customFields.c.typeverkoop` | null in sample, menu assumed |
| `C$Benadering` | `customFields.c.benadering` | null in sample, menu assumed |
| `Name` | `name` | string |
| `RepValue` | `salesRepresentativeValue` | object: currency, exchangeRate, value (integer in sample) |
| `ClosedValue` | `closedValue` | object: currency, exchangeRate (same currency structure) |
| `ForecastClose` | `forecastCloseDate` | string (date) |
| `InitialContact` | `initialContactDate` | null in sample (date/time) |
| `Closed` | `closedTime` | string (date/time) |
| `Terr` | `territory` | object (reference to salesTerritories) |
| `Assigned` | `assignedToAccount` | object (account reference) |
| `OrgId` / `CId` | `organization` / `primaryContact.contact` | objects |
| `Summary` | `summary` | string |

**Custom fields on Opportunity that the workspace does NOT show (new finding):**
| REST property | JSON type seen | Note |
|---|---|---|
| `customFields.c.waarschijnlijkheid` | menu object | A second probability menu. Not on the workspace. Purpose open (Q13). |
| `customFields.c.type_medewerker` | null in sample | Probably a menu from custom object `Support.TypeMedewerker`. Not on the workspace (Q13). |
| `customFields.Support.Incident`, `.Opportunity`, `.VERKOOP_ID` | null in sample | Package `Support`. `Support.Incident` is probably the source-incident link (answers part of Q7). `VERKOOP_ID` looks like a legacy sales ID (Q13). |

Standard fields that exist in OSC but are not on the workspace: `costOfSale`, `managerCommit`, `managerValue`, `returnValue`, `salesRepresentativeCommit`, `leadReject*`, `lostTime`, `recallTime`, `winLossReason`, `winLossDescription`, `stageWithStrategy`, `source`, `otherContacts`, `quotes`. `drop` (not on the workspace).

**Verified value lists (standard objects):**
- `opportunityStatuses` (standard Status, `statusWithType.status`): 9 Open, 10 "- Niet-actief", 11 Gewonnen, 12 Verloren, 13 "- Dood", 14 Lead, 15 "-Weigeren".
- `salesTerritories` (`Terr`, label "Land"): 1 Nederland, 2 Duitsland, 3 Belgie, 4 Overig, 5 Denemarken, 6 Zweden, 7 Noorwegen, 8 Luxemburg. So `Terr` is the standard Territory object, not a custom menu (answers Q8). Overig = id 4.

**REST verification 2026-11-20 (schemas, menus, counts).**
- Custom fields of package `c` on Opportunity (7, all verified): `reference` string 12, `offertedatum` date, `fasewaarschijnlijkheid` menu, `waarschijnlijkheid` menu, `typeverkoop` menu, `benadering` menu, `type_medewerker` menu. Package `Support`: `Incident` (relation to incidents), `Opportunity` (relation to opportunities), `VERKOOP_ID` (integer).
- `waarschijnlijkheid` (not on the workspace): a second, older probability menu with 5 items: 192 "00% - Lead", 188 "20% - Offerte Meerdere aanbieders", 189 "40% - Offerte Enige aanbieder", 190 "80% - Toezegging Klant", 191 "100% - Gewonnen". Counts: 192: 1250, 191: 493, 189: 79, 188: 77, 190: 15, empty 50. It is the old scale. The workspace uses `fasewaarschijnlijkheid`. Proposal: drop `waarschijnlijkheid` (owner confirms, question 13).
- `type_medewerker` (not on the workspace): menu with 3 items: 213 Bodybuilders, 214 Angels, 215 Snorren (the same three rate classes as the organisation field `rightnow_uurtarief`). Not used on the workspace. Custom object `Support.TypeMedewerker` is unrelated: it has 0 rows.
- Standard status types (`statusWithType.statusType`): 11 Lead, 12 Afwijzen, 6 Actief, 7 Inactief, 10 Dood, 8 Gesloten, 9 Verloren. Standard stages (`stageWithStrategy.stage`): 16 Inventarisatie, 13 Offerte, 14 Opdracht, 15 Afgewezen. Both are OSC standard menus. Their use in tst1 was not counted.
- `Support.Incident` links an opportunity to its source incident (REST relation to `incidents`). This answers Q7: the link field is `Support.Incident` on the Opportunity (n:1: one incident per opportunity field, but one incident can have many opportunities).
- Field lengths: `name` 80, `summary` 1333, `reference` 12. Dates: `forecastCloseDate`, `initialContactDate`, `offertedatum` date only. `closedTime` is a datetime.

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
| `Opportunity.C$Reference` ("Reference") | custom string (verified: maxLength 12, nullable) | Overzicht r2c0 | Opportunities.`tkc_reference_c` | new, built (SUITE-69) | varchar(12), non-unique index; validator and duplicate warning planned (SUITE-139) | n/a | Quote number. Verified format: `OFT` + two-digit year + `-` + four digits (10 characters, example shape `OFTYY-NNNN`). Created by the "Auto Offerte Nr" add-in from the counter object `CRM.offerteNr` (section 6). Candidate for the list view. |
| `Opportunity.Name` ("Naam van verkoopkans") | string (verified: maxLength 80, minLength 1, nullable in REST; required by the workspace) | Overzicht r2c1 | Opportunities.`name` | core | n/a | n/a | **Mismatch:** SuiteCRM core `name` is varchar(50) (`modules/Opportunities/vardefs.php`), OSC allows 80. A vardef override to `len` 255 is needed, or names longer than 50 are cut. Required on new and edit in OSC. |
| `Opportunity.C$Fasewaarschijnlijkheid` ("Status") | custom **menu** (verified: object id + lookupName), nullable; 8 items | Overzicht r3c0 | Opportunities.`sales_stage` + `probability` | core | n/a | 281 "0% - Lead", 274 "5% - Kwalificeren", 275 "10% - Analyse", 276 "25% - Offerte meerdere aanbieders", 277 "50% - Offerte enige aanbieder", 278 "80% - Mondelinge toezegging", 279 "100% - Gewonnen", 280 "Verloren" (REST list, complete). Counts in tst1 (1964 opportunities): 279: 902, 281: 450, 280: 403, 275: 95, 274: 80, 277: 12, 276: 11, 278: 9, empty 2 | Each value holds a stage and a probability (the percent in the label; "Verloren" = 0). Proposal for `sales_stage` and `probability`: see section 7. Menu id 279 = "100% - Gewonnen" (rule in section 3). |
| `Opportunity.Assigned` ("Eigenaar verkoopkans") | Account (staff) reference | Overzicht r3c1 | Opportunities.`assigned_user_id` | core | n/a | n/a | Default = current user on New. Import needs a staff-to-user map (Q9). |
| `Opportunity.RepValue` ("Order Waarde") | currency object `salesRepresentativeValue` (verified): currency (menu, not null), exchangeRate, value (number, not null). The object itself is nullable | Overzicht r4c0 | Opportunities.`amount` (+ `currency_id`) | core | n/a | n/a | SuiteCRM `amount` is required, currency type. Null object in OSC: import needs a default amount (0). Question 12. |
| `Incident.RefNo` ("Gemaakt vanuit incidentnr") | string, read-only (Incident field shown on Opportunity workspace) | Overzicht r4c1 | Opportunities → Cases (new relationship) | new | relationship, see section 4 | n/a | Shows the reference number of the source incident. Empty in the screenshot. The relation field is `Support.Incident` on the Opportunity (verified by REST schema). |
| `Opportunity.ClosedValue` ("Gesloten order waarde") | currency object `closedValue` (verified, nullable): same structure as above (currency, exchangeRate, value) | Overzicht r5c0 | Opportunities.`tkc_closed_amount_c` | new | currency | n/a | SuiteCRM has one `amount` only. Alternative: reuse `amount` after close (Q5). |
| `Opportunity.OrgId` ("Organisatie") | Organization reference | Overzicht r5c1 | Opportunities.`account_id` / `account_name` | core | n/a | n/a | Search report 8001 in OSC. SuiteCRM: `account_name` is required in the vardef. |
| `Opportunity.C$Typeverkoop` ("Cross- up sell") | custom **menu** (verified), nullable; 2 items | Overzicht r6c0 | Opportunities.`tkc_sales_type_c` | new | enum | 286 Cross sell, 287 Up sell. Counts: empty 1948, Up sell 10, Cross sell 6 | Core `opportunity_type` (Existing/New Business) has other meaning: keep the new field. Used on 16 of 1964 opportunities only. |
| `Opportunity.CId` ("Contactpersoon") | Contact reference | Overzicht r6c1 | Opportunities.`contacts` (relationship `opportunities_contacts`) | core | n/a | n/a | OSC holds one primary contact. SuiteCRM is many-to-many. Import sets one link. Reports 8014 and 9033 are search reports. |
| `Opportunity.C$Benadering` ("Benadering") | custom **menu** (verified), nullable; 4 items | Overzicht r7c0 | Opportunities.`tkc_approach_c` (name open) | new | enum | 282 Website, 283 Telefoon, 284 Social, 285 Netwerk. Counts: empty 1891, Netwerk 33, Website 34, Telefoon 5, Social 1 | Meaning "approach": how the lead came in. Overlaps core `lead_source` (`lead_source_dom`: Web Site, Cold Call, ...). Used on 73 of 1964. Option: map to `lead_source` with a TKC list (Website, Telefoon, Social, Netwerk) instead of a new field. Question 6. |
| `Opportunity.Terr` ("Land") | standard Territory reference (verified: `salesTerritories`) | Overzicht r7c1 | Opportunities.`tkc_country_c` | new | enum | Nederland, Duitsland, Belgie, Overig, Denemarken, Zweden, Noorwegen, Luxemburg (verified REST, 8 values; OSC ids 1-8). | Verified: standard Territory (`salesTerritories`) relabelled "Land". SuiteCRM Opportunities has no country field. |
| `Opportunity.Summary` ("Beschrijving") | string (verified: maxLength 1333, nullable), multiline | Overzicht r8 (span 2) | Opportunities.`description` | core | n/a | n/a | SuiteCRM `description` is text: fits. |
| `Opportunity.InitialContact` ("Initial contact") | **date** (verified: `initialContactDate`, date limits, no time; nullable) | Details r1c0 | Opportunities.`tkc_initial_contact_c` | new | date | n/a | No SuiteCRM counterpart. Label is the OSC default (Q3). |
| `Opportunity.C$Offertedatum` ("Offertedatum") | custom **date** (verified: date limits, no time; nullable) | Details r1c1 | Opportunities.`tkc_quote_date_c` | new | date | n/a | Could be derived from the linked AOS quote instead (Q4). |
| `Opportunity.ForecastClose` ("Verwachte Order Datum") | **date** (verified: `forecastCloseDate`, nullable) | Details r2c0 | Opportunities.`date_closed` | core | n/a | n/a | In SuiteCRM `date_closed` is the expected close date (required). |
| `Opportunity.Closed` ("Closed") | **datetime** in REST (`closedTime`, nullable); the workspace shows the date only | Details r2c1 | Opportunities.`tkc_date_closed_actual_c` | new | date (time part is dropped on import) | n/a | Actual close date. The rule sets it to now + 1 month on stage 279. SuiteCRM has no second close date (Q5). REST holds a datetime: import keeps the date part. |
| `Opportunity.Created` | datetime, system | Info panel | Opportunities.`date_entered` | core | n/a | n/a | Keep OSC value on import. |
| `Opportunity.CreatedBy` | Account, system | Info panel | Opportunities.`created_by` | core | n/a | n/a | Staff-to-user map (Q9). |
| `Opportunity.Updated` | datetime, system | Info panel | Opportunities.`date_modified` | core | n/a | n/a | |
| `Opportunity.UpdatedBy` | Account, system | Info panel | Opportunities.`modified_user_id` | core | n/a | n/a | |
| `Opportunity.OpId` | integer ID, system | Info panel | Opportunities.`osc_id_c` | new | varchar(20), indexed (OSC ID is integer, up to 9223372036854775807) | n/a | Import key (project rule: `osc_id_c`, indexed). **Status: built (SUITE-71).** See `docs/data-model.md` § OSC import key. |
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
| Rule: Status = "100% - Gewonnen" (id 279) → Closed = now + 1 month | Workflow or logic hook on `sales_stage` change that sets `tkc_date_closed_actual_c` | workflow (AOW) or custom hook. Menu id 279 = "100% - Gewonnen" (verified): the rule runs on a won deal. See Q2. |
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
| Incident (source incident, `Support.Incident` on the Opportunity, shown as `Incident.RefNo`) | New relationship Opportunities ↔ Cases (one case, many opportunities; field on the Opportunity side) | **Status: built (SUITE-70).** Relationship `tkc_cases_opportunities` (1:N, foreign key `opportunities.tkc_source_case_id_c`). Fields `tkc_source_case_c` (relate) and `tkc_source_case_id_c` (id). Cases has an "Opportunities" subpanel. E4 sets `tkc_source_case_id_c` from the case `osc_id_c` (step 6). No layout shows the field yet (E10). Direction verified: REST relation on the Opportunity. |
| Products, Quotes | `opportunity_aos_quotes` (AOS_Quotes) and AOS_Products via quote line items | Core relationship exists. No OSC grid in this workspace. Need source of truth for TKC quotes (Q4). |

## 5. Open questions

CLOSED by REST 2026-11-20: Q1 (menu values), Q2 (id 279 = "100% - Gewonnen"), Q3 (types and lengths), Q7 (link field is `Support.Incident`), Q8 (`Terr` is the standard Territory, 8 values; open only: dropdown or relationship).

4. **Quote number.** Structure verified (section 6, "Quote number"). The add-in code is not available. Does the number restart each year? Does the owner want AOS_Quotes, or only the number (proposal: only the number)?
5. **Two amounts and two close dates.** OSC has Order value + Closed order value and Expected + Actual close date. SuiteCRM has `amount` and `date_closed` only. Keep both pairs as new fields (proposed) or fold them?
6. **Benadering vs lead source.** Values: Website, Telefoon, Social, Netwerk (73 of 1964 opportunities have one). New field `tkc_approach_c`, or core `lead_source` with a TKC list?
9. **Staff mapping.** Which OSC staff accounts map to which SuiteCRM users (Assigned, CreatedBy, UpdatedBy)? Needs a user list from the user, not from this document.
10. **Buttons.** What do Forward and Reject do in TKC practice? Print: which output is wanted? Attachments: Documents or Notes?
11. **Audit log.** Must the OSC audit history migrate, or only SuiteCRM auditing from go-live?
12. **Core required fields.** SuiteCRM requires `account_name`, `amount`, `date_closed` and `sales_stage`. In OSC, only Name is required (the value object and the organisation can be empty). Opportunities without organisation or value need a default or a relaxed vardef (architect). Also `name` length: OSC 80, SuiteCRM 50 (vardef override to 255).
13. **Fields not on the workspace.** `c.waarschijnlijkheid` (old probability scale, 5 items), `c.type_medewerker` (Bodybuilders/Angels/Snorren, no value counts read), `Support.VERKOOP_ID` (integer). Proposal: drop `waarschijnlijkheid`; keep or drop the other two?
14. **Standard Status.** The workspace shows the custom menu as "Status". The standard Status (9 Open, 10 - Niet-actief, 11 Gewonnen, 12 Verloren, 13 - Dood, 14 Lead, 15 -Weigeren) and the standard Stage exist too. Their use was not counted. Which one drives won/lost reports? Proposal: the custom menu (it holds 1962 of 1964 values).
15. **Closed date rule.** The rule sets `Closed` = now + 1 month when the deal becomes "100% - Gewonnen". Is a month after the win the intended close date?

## 6. Verified details and proposals

### 6.1 Value lists (REST, tst1, 2026-11-20)
See the rows in section 2 and the section 0 notes. Raw lists: `data/osc/rest-output/v/cm-opportunities-*-A.json`.

### 6.2 Quote number
- Custom object `CRM.offerteNr` (verified schema): `autoNr` string 12 (not null), `locked` boolean (not null), `quoteNo_DM` string 25 (nullable, empty in the sample). The table holds **one row**: a counter. The "Auto Offerte Nr" add-in reads the counter, sets `locked`, writes the next number to `C$Reference`, and unlocks.
- Format of the number (from the counter and from the last 10 `C$Reference` values): `OFT` + two-digit year + `-` + four digits. All 10 sampled references have prefix `OFT` and year 24. The sequence runs per year (assumed, not verified).
- **Proposal:** generate the number in SuiteCRM, not in a button add-in. A before-save logic hook (or an Angular record action "Generate quote number") builds `OFT` + `YY` + `-` + a zero-padded 4-digit sequence. The sequence lives in a small table (`tkc_sequences`: name, year, last value), updated in one database transaction with a row lock, so two users never get one number. Store the value in `tkc_reference_c` (varchar 12). SUITE-69 built the field with a non-unique index. The validator and the duplicate warning are planned (SUITE-139). The import keeps the OSC value, and the sequence starts above the highest imported number for the current year. This does not use `AOS_Quotes.number` (a core integer counter) because the format differs.
- **Status (SUITE-62): the counter service is built.** The table `tkc_sequences` and the service `Sequence` exist (see `docs/data-model.md` § Tables and `docs/architecture.md` § Sequences). The service does not use one transaction with a row lock. It uses an atomic UPDATE on one row, so two users never get one number. The counter name for the quote number is `quote`. The logic hook or record action that builds `OFT` + `YY` + `-` + the 4-digit number and writes `tkc_reference_c` is planned (E10). The import calls `setLastValue()` with the highest imported number for each year.
- The `locked` flag and `quoteNo_DM` are not migrated (the lock is replaced by the transaction; `quoteNo_DM` is empty in the sample, question 4).

### 6.3 `sales_stage` and `probability` (built, SUITE-69)
Status: built. The file `en_us.tkc_sales_stage_dom.php` holds the lists. The labels are English, for example "5% - Qualify" (the table below shows the Dutch OSC labels). The probability logic is `tkc_probability_logic.php`. See `docs/data-model.md` § Core overrides.
Core `sales_stage_dom` has 10 keys. Core `sales_probability_dom` has the same keys with a number each. OSC has 8 values. Rule: never rename a core key. Relabel and re-use a key where the meaning fits, add a key (prefix `tkc_`) where it does not, hide the keys that stay unused.

| OSC value (id) | SuiteCRM `sales_stage` key | Label (nl) | Probability |
|---|---|---|---|
| 281 0% - Lead | `Prospecting` (core key) | 0% - Lead | 0 |
| 274 5% - Kwalificeren | `Qualification` (core key) | 5% - Kwalificeren | 5 |
| 275 10% - Analyse | `Needs Analysis` (core key) | 10% - Analyse | 10 |
| 276 25% - Offerte meerdere aanbieders | `tkc_quote_multi` (new key) | 25% - Offerte meerdere aanbieders | 25 |
| 277 50% - Offerte enige aanbieder | `tkc_quote_single` (new key) | 50% - Offerte enige aanbieder | 50 |
| 278 80% - Mondelinge toezegging | `Negotiation/Review` (core key) | 80% - Mondelinge toezegging | 80 |
| 279 100% - Gewonnen | `Closed Won` (core key) | 100% - Gewonnen | 100 |
| 280 Verloren | `Closed Lost` (core key) | Verloren | 0 |

- Implementation: a custom language extension in `public/legacy/custom/Extension/application/Ext/Language/` that overrides `sales_stage_dom` (8 keys, in this order) and `sales_probability_dom` (same keys). The core keys `Value Proposition`, `Id. Decision Makers`, `Perception Analysis` and `Proposal/Price Quote` leave the list. Core code uses `Closed Won` and `Closed Lost` (dashlets, reports), so those two keep their keys.
- `probability` fills from the stage (core behaviour). The OSC percent in the label and the probability must agree: they do.
- Import (E4, planned): map the OSC stage menu id to the key in the table. Empty (2 records) gets `Prospecting`.
- Risk: existing SuiteCRM demo or report content that uses the removed keys. Check `AOR_Reports` and dashlets in the architect review.
- The old menu `waarschijnlijkheid` is not mapped (proposal: drop).

## 7. Counts and what stays open
Rows in section 2: core 12, new 10, drop 2 (24 rows, unchanged by REST). The REST-only fields (`waarschijnlijkheid`, `type_medewerker`, `VERKOOP_ID`) are proposed `drop` (question 13) and are not in section 2.
Still open for REST: none that REST can answer. Optional: usage counts of the standard Status and Stage (question 14).
