# OSC Incident -> SuiteCRM Cases mapping

> **Owner decisions 2026-11-20:** SLA **dropped** (all SLA rows → `drop`). Queues **kept**. Product = flat list (Oracle Service Cloud, eGain). Category = flat list (Service Request, Complaint, Question, Change Request). Disposition: open. Projects (`Support$Project`): out of scope. Contracts: in scope. See `docs/backlog.md` § Product decisions.

Status: **PARTIALLY verified with REST 2026-11-20 (tst1)**. Customer names are replaced by placeholders; the real values are in OSC and in data/osc/rest-output/ (gitignored).  Field types, lengths, nullability, custom field schema and all menu lists are verified (4 batch runs, no incident record read; counts come from aggregate ROQL). Still open (exact list): section 9.
Sources: `designs/screens/cases/tkc-incidents.xml` (OSC 26C workspace export), `designs/screens/cases/OSC-Incident-1.png`, SuiteCRM vardefs, and one incident record read from REST (structure only, no values copied).
Raw parse output: `data/osc/workspace/cases.json`, `data/osc/rest-output/` (gitignored).

### 0. What REST verified (2026-11-20, tst1)

Method: field schemas (`metadata-catalog/incidents`, `.../customFields/c`, sub-schemas) with `Accept: application/schema+json`; menu values from the resources `namedIDs/incidents/<field>` (standard) and `namedIDs/incidents/customFields/c/<field>` (custom); collections `serviceProducts`, `serviceCategories`, `serviceDispositions`; aggregate ROQL (`SELECT x, COUNT(*) ... GROUP BY x`) for usage. No incident, contact, organisation or staff record was read. tst1 holds 4053 incidents. Raw files: `data/osc/rest-output/s/` and `v/` (gitignored). Counts are tst1 numbers: production can differ.

- Verified standard properties (REST names): `referenceNumber` string 13, `subject` string 240, `statusWithType{status,statusType}`, `severity`, `queue`, `source` (hierarchical, system-set), `channel`, `product`, `category`, `disposition`, `assignedTo{account,staffGroup}`, `primaryContact` (not nullable), `organization`, `otherContacts`, `sLAInstance`, `responseInterval`, `resolutionInterval`, `initialResponseDueTime`, `initialSolutionTime`, `closedTime`, `lastResponseTime`, `createdTime`, `updatedTime`, `language`, `interface`, `mailbox`, `threads`, `fileAttachments`.
- ROQL table names for menus (`Incidents.Severity`, `Incidents.Queue`, `Incidents.StatusWithType.Status`, `Incidents.Source`) do not exist (HTTP 400). Menus are read through the `namedIDs/...` REST resources instead. ROQL works for aggregates on `Incidents`, `Contacts`, `Opportunities`, `Organizations` and custom objects (both `COUNT(*)` and `COUNT()` work).
- Menu `severity`, `queue`, `channel`, `language`, `statusWithType.status/statusType`, `threads.entryType/channel/contentType`, `assignedTo.staffGroup`: lists in section 2 and appendices.

Labels are Dutch (as in the OSC screenshot). "Label (screenshot)" means the label was read from the screenshot, not the XML.
Where the XML has no `LabelText`, the label is the OSC default label shown in the screenshot.

## 1. Workspace summary

**Workspace:** type `Incident`, OSC 26C SP1. Spell check on save is off. Record types listed in the export:
Incident, Answer, Asset, Contact, Opportunity, Org, Support$Project, Task, Interaction.

**Ribbon (Home tab).** Save, Save and close | New, Refresh, Forward, Print, Copy, Delete (label "Verwijderen"), OSN |
Spell check | Decision tree, Propose (answer), Best answer, New service opportunity, Appointment | Links, Info.
Quick access toolbar: New, Save, Save and close, Print, Bookmark, Save and send and close, Save and send.
Info panel fields: IId, Created, Updated, InitialSoln, LastResp, RelDue, Closed, CreatedBy, RuleCtx.Escllevel, RuleCtx.State.

**Layout.** One left panel (field list, one column) and a right tab set.

| Area | Content |
|---|---|
| Top row "Functieknoppen" | 17 custom buttons (see section 3). Two have no text (image only). Two (SPAM, GEEN SPAM) are hidden on new and edit by default and shown by a rule. |
| Left panel, "Navigatieknoppen" | 4 image buttons (support contract tab, attachments tab, co-browse tab, map/browser tab) and a version text "v2.1". |
| Left panel, "Algemeen" | Self-service banner (rule-controlled), Subject, RefNo, Contact, Organisation |
| Left panel, "Categorisatie" | SLA instance, response interval, Severity, TKC priority, Customer priority, Incident type, Tact ID, Product, Category, Disposition |
| Left panel, "Doorzetten" | Assigned (group and account), Queue |
| Left panel, "Status" | Status, Created, Updated, RelDue, Indicated solve date, then 2 hidden "collect before share" fields. A "Hulp" hyperlink runs a help script. |
| Tab "Berichten" | Quick-text buttons (row "Kies tekstentry": Klant, Reactie, Prive; row "Standaardteksten": Aanhef, Afsluiting, Storing, Meer info nodig) and the rich incident thread |
| Tab "OSN" | Collaboration (Oracle Social Network) control |
| Tab "Bijlagen" | File attachments |
| Tab "Details" | Sub-tab "Geschiedenis" (audit log), sub-tab "Details" (InterfaceId, LangId, MailboxId, Source, EventOpmerking, EventOpmerkingen) |
| Tab "Hulpmiddelen" | Sub-tabs: Beslisbomen (decision tree control + "Start" link), Scripts (script control), Browser (link to an internal spreadsheet, embedded browser with a maps URL built from the contact address), Co Browsing |
| Tab "Supportcontract" | 1 report (report id 100641, content unknown) |
| Tab "Koppelingen" | Sub-tabs: "Contactpersonen aan incident" (contacts relationship), "Verkoopkansen" (2 reports: for this incident, per organisation), "Projecten" (TFS project id text, "Open TFS project" link, Support$Project related records) |
| Tab "Taken" | Tasks relationship |

**Thread control (RichIncidentThread) settings:** vertical orientation; can add response, customer entry and note;
can use standard text, smart assistant, KB search; can add CC and BCC; "send on save" allowed, default "never";
default thread on new = customer entry; default channel for customer entry = Phone; no status change or reassign on response.

**Workspace rules:** 48 rules (41 active, 7 inactive: 4 "required" toggles, delete warning, taxonomy help, one customer-specific change rule). See section 4.

## 2. Field mapping table

`Exists?`: `core` = standard SuiteCRM field, `new` = we add it, `drop` = not migrated.
"OSC type" is verified by REST schema (2026-11-20) unless the cell shows `?`.
New-field names follow the rules: suffix `_c`. Final names are for the architect.

| OSC field (API name) | OSC type | Workspace location | SuiteCRM module.field | Exists? | New field type/len | Dropdown values | Notes |
|---|---|---|---|---|---|---|---|
| Incident.Subject ("Onderwerp") | text, single line; verified: string, **maxLength 240**, nullable in REST (required by the workspace) | Algemeen | Cases.name | core | - | - | Required on new and edit (XML). Core `name` is varchar(255): OSC 240 fits. |
| Incident.RefNo ("Meldingnr RN") | string, **maxLength 13**, not null, read-only (REST `referenceNumber`). Format `YYMMDD-NNNNNN` has 13 characters: length matches the screenshot example | Algemeen, read-only look | Cases.tkc_case_ref_c | new | varchar(13) (use 20 for safety) | - | Core `case_number` is an auto-increment integer and cannot hold this format. Keep `case_number` for new SuiteCRM cases. Index `tkc_case_ref_c`. |
| Incident.IId | integer (REST `id`, up to 9223372036854775807) | Info panel only | Cases.osc_id_c | new | varchar(20), indexed | - | Import key per `suitecrm-customization.md`. **Status: built (SUITE-71).** `osc_id_c` is not `tkc_case_ref_c`. |
| Incident.CId ("Contactpersoon") | relationship to Contact, 1:1 primary contact; verified: `primaryContact` object is **not nullable** (required in OSC) | Algemeen | Cases.tkc_primary_contact_c (relate to Contacts) + `tkc_primary_contact_id_c` | new | relate / id char(36) | - | Screenshot shows required (*). Core Cases has only M:N `contacts` (`contacts_cases`) and `contact_created_by` (portal creator). A primary-contact field is a gap. See Q3. |
| Incident.OrgId ("Organisatie") | relationship to Org (verified: `organization`, nullable) | Algemeen | Cases.account_id / account_name | core | - | - | Core `account_cases`. |
| Incident.SlaiId ("SLA-exemplaar") | relationship to SLA instance | Categorisatie | - | drop | - | - | Owner decision 2026-11-20: SLA dropped. |
| Incident.SlaRespDelta ("Reactie-interval") | numeric with increment 1 (XML `Increment`); unit ? | Categorisatie, read-only (screenshot, tooltip "Reactie-interval voor dit incident") | - | drop | - | - | Owner decision: SLA dropped. |
| Incident.SeverityId ("Prioriteit", label from screenshot) | menu (verified, `severity`, nullable), 4 items | Categorisatie | Cases.priority | core | dropdown: replace `case_priority_dom` | 1 Prioriteit 1, 2 Prioriteit 2, 3 Prioriteit 3, 4 Prioriteit 4. Counts in tst1 (4053 incidents): empty 4036, 4: 7, 3: 6, 2: 3, 1: 1 | Core `case_priority_dom` has P1, P2, P3 only (High, Medium, Low). Proposal: keep keys P1-P3, add key `tkc_P4` (A13), labels "Priority 1..4" (A20) in a custom language extension (built in SUITE-66). Used on only 17 of 4053 incidents. Mapping 1:1 (Q4). |
| Incident.C$TkcPrioriteit ("TKC Prioriteit") | custom **menu** (verified `c.tkc_prioriteit`), 4 items | Categorisatie | Cases.tkc_priority_c | new | enum | 209 Geen impact, 210 Enige impact, 211 Verstoring productieproces, 212 Direct actie vereist | An impact scale, not a priority number. Same four labels as the older menu `c.prioriteit` (ids 84, 74, 75, 76), which is not on the workspace. |
| Incident.Support$SupportPriority ("Klantprioriteit") | relation to custom object `Support.SupportPriority` (verified; REST name `Support.SupportPriority`), nullable | Categorisatie | Cases.tkc_customer_priority_c | new | relate to a small custom module or enum (see 2.3) | See 2.3. The list of distinct `klant_prioriteit` values was not read. | Not a menu: a lookup row per support contract (114 rows in tst1). See 2.3 and Q7. |
| Incident.C$Typeincident ("Type Incident") | custom **menu** (verified `c.typeincident`), 4 items | Categorisatie | Cases.type | core | - | 184 Support verzoek, 185 Algemene Communicatie, 186 Info bericht, 195 Project Communicatie | Reuse core `type`; replace the `case_type_dom` list (Administration, Product, User) in a custom language extension. |
| Incident.C$Tactid ("Tact ID") | custom text (verified: string, **maxLength 10**) | Categorisatie | Cases.tkc_tact_id_c | new | varchar(10) | - |  |
| Incident.ProdId ("Product") | menu, hierarchical (verified: `serviceProducts`, nullable, name max 80); required on new/edit in the workspace. Tree verified: 50 nodes, 9 roots, 4 levels | Categorisatie | Cases.tkc_product_c (SuiteCRM flat list) | new | dropdown (flat, owner list) | **SuiteCRM list (owner decision 2026-11-20): Oracle Service Cloud, eGain. No OSC tree import.** Import mapping by tree root: roots 441 RightNow (5 nodes) and 472 Oracle Service Cloud (31 nodes) and all their descendants (including 545 Spam) -> Oracle Service Cloud; root 586 eGain -> eGain; every other node (Marketing, Finance, Algemeen, TKC Fresh, KLANTEN and its customer children, FreshWorks) -> empty, logged in the import log. Full list: Appendix A | Mapping of the "Oracle" button ids: product 441 maps to Oracle Service Cloud (RightNow is the former product name: owner confirms, Q5). Usage in tst1: product on 71 of 4053 incidents; 26 map (RightNow tree 18, Oracle Service Cloud tree 7, eGain 1), 45 stay empty. |
| Incident.CatId ("Categorie") | menu, hierarchical (verified: `serviceCategories`, nullable, name max 80); required on new/edit in the workspace. Tree verified: 70 nodes, 20 roots, 2 levels | Categorisatie | Cases.tkc_category_c (SuiteCRM flat list) | new | dropdown (flat, owner list) | **SuiteCRM list (owner decision 2026-11-20): Service Request, Complaint, Question, Change Request. No OSC category tree import.** The four values do not exist as OSC categories. Import source is the OSC **disposition**: 468 Service request -> Service Request; 469 Change request -> Change Request; 467 Vraag -> Question; 470 Issue and all other dispositions -> empty (logged); Complaint has no OSC source. OSC category values (70 nodes, Appendix A) -> not imported (logged) | Usage in tst1: category on 51 of 4053 incidents (not imported); disposition on 72, of which 21 map (467: 11, 468: 4, 469: 6) and 51 stay empty. Whether to keep the OSC category text in a separate field is Q5. |
| Incident.DispId ("Reden") | menu, hierarchical (verified: `serviceDispositions`, nullable, name max 80), required on new/edit in the workspace. Tree verified: 15 nodes, 11 roots, 2 levels (486 Finance has 4 children) | Categorisatie | Cases.tkc_disposition_c (only if the owner keeps it) - **OPEN** | new | open (owner has not decided) | 15 values (Appendix A). 468/469/467 feed the SuiteCRM Category list (see CatId row). Disposition itself: owner has not decided | Label is "Reden" (XML `LabelText`). **Disposition stays an open question (Q5).** If it drops, its values only survive through the Category mapping. |
| Incident.Assigned ("Toegewezen") - account part | staff account (OSC stores account and group, joined by a double bar) | Doorzetten | Cases.assigned_user_id | core | - | - | Core. Screenshot shows "group > account". A rule excludes 4 hard-coded accounts or groups from the menu. |
| Incident.Assigned - group part | staff group (verified: `assignedTo.staffGroup`, NamedID, 14 groups) | Doorzetten | Cases.tkc_assigned_group_c ? (or SecuritySuite group) | new | to be decided | 14 groups (Appendix B) | Group names only, no staff names. See Q6. The Finance button assigns group 107042 = "TKC - Finance" (verified) plus a fixed account, and a rule sets the queue by group. |
| Incident.QueueId ("Wachtrij") | menu (verified: `queue`, nullable), 12 items | Doorzetten | Cases.tkc_queue_c | new | enum | 2 TKC - Support 1e lijn, 20 TKC - Support 2e lijn, 21 TKC - Change Request, 25 InfoTKCDigital, 11 Feedback op antwoorden, 17 Info, 18 RightNow - Automail, 19 Finance, 22 Archief, 23 Offertes, 24 SPAM, 26 Consulaire Aangelegenheden. Counts: 19 Finance 3583, 25 InfoTKCDigital 272, 24 SPAM 163, 2 TKC - Support 1e lijn 34, empty 1; the other 7 have no incident | Owner decision: queues kept. 88% of incidents sit in queue "Finance" (id 19). Queues 20, 21 have no incident. |
| Incident.Status.Id ("Status") | menu (verified: `statusWithType.status` plus `statusType`, both not nullable), 12 statuses, 3 status types | Status | Cases.state + Cases.status | core | - | Statuses: 1 Onopgelost, 104 In behandeling, 109 Wacht op Oracle, 110 Wacht op Freshworks, 111 Feature Request, 3 Wacht op aanmelder - info benodigd, 8 Bijgewerkt, 108 Gereed - wacht op akkoord aanmelder, 101 On-hold, 112 CA Jira, 2 Opgelost, 113 Gearchiveerd. Status types: 1 Onopgelost, 2 Opgelost, 3 Wachtend. Mapping and counts: section 10 | Rules use ids 1, 2, 3, 8 and 103: **103 does not exist** in tst1 (section 10). Each status needs a (state, status) pair: proposal in section 10. |
| Incident.Created ("Datum gemaakt") | datetime, read-only | Status | Cases.date_entered | core | - | - | Import: set `date_entered` explicitly. |
| Incident.Updated ("Laatst bijgewerkt op") | datetime, read-only | Status | Cases.date_modified | core | - | - | |
| Incident.RelDue ("Eerste reactie verwacht", label inferred from field order in screenshot) | datetime, read-only | Status | - | drop | - | - | SLA dropped (owner decision). REST name `initialResponseDueTime`. Label inferred (Q9). |
| Incident.C$IndicatedSolveDate ("Verwachte oplosdatum") | custom **date** (verified `c.indicated_solve_date`, date only; REST label "Verwachte oplosdatum") | Status | Cases.tkc_expected_resolution_date_c | new | date | - | Label confirmed by REST. |
| Incident.C$CollectBeforeShare | custom boolean (verified `c.collect_before_share`, nullable; REST label "verzameling?"; XML `BooleanRenderView=Dropdown`) | Status, hidden unless Org id 612 (rule) | Cases.tkc_collect_before_share_c | new | bool | Yes/No | Customer-specific (one organisation). See Q10. |
| Incident.C$CollectBeforeShareMenu | custom **menu** (verified `c.collect_before_share_menu`; REST label "Verzameling menu"), 3 items | Status, hidden unless the bool above = 1 | Cases.tkc_collect_before_share_opt_c | new | enum | 323 Case samenvoegen, 324 Case items verwijderen, 325 Dubbele cases opruimen | Customer-specific. The three values read as merge-case actions. See Q10. |
| Incident.InterfaceId | menu (verified: `interface`, `siteInterfaces`, not nullable) | Details > Details | - | drop | - | - | Proposed: one SuiteCRM instance. Confirm that all incidents come from one interface (Q11). |
| Incident.LangId | menu (verified: `language`, nullable), 2 items | Details > Details | - | drop | - | 5 en_GB, 12 nl_NL | Proposed drop. Cases has no language field. Confirm (Q11). |
| Incident.MailboxId | menu (verified: `mailbox`, `mailboxes`, nullable) | Details > Details | - | drop | - | - | Mailbox that received the mail. SuiteCRM inbound email handles routing separately. Keep in OSC. |
| Incident.Source | menu, hierarchical (verified: `namedIDHierarchies/incidents/source`, **not nullable, not writable**: system-set), 263 items | Details > Details | Cases.tkc_case_origin_c | new | enum (short list) | Value 3001 = "Een vraag stellen" (Ask a Question, self-service). Used values in tst1: 5001 Techmail - Service-postvak 4032, 3019 Eindgebruiker verbinden 17, 17001 Webconsole: incident-editor 3, 1001 Incident-editor 1. Value 3001 has **0 incidents** in tst1 | 263 system values (mail, API, editors, daemons). Proposal: migrate to a short list: `email` (5001), `selfservice` (3001, 3019), `staff` (1001, 17001), `other`. The banner rule on 3001 never fires on the tst1 data. Owner confirms (Q20). |
| Incident.C$EventOpmerking | custom text, single line (verified: string, **maxLength 255**; REST label "Event opmerking") | Details > Details | Cases.tkc_event_remark_c | new | varchar(255) | - | Label not in XML (Q8). Possibly a duplicate of the next field. |
| Incident.C$EventOpmerkingen | custom text, multiline (verified: string, **maxLength 1333**; REST label "event_opmerkingen") | Details > Details | Cases.tkc_event_remarks_c | new | text | - | Different length and a different label from the single-line field: both are separate fields. Purpose unknown (Q8). |
| Incident.C$TfsProjectId | custom text (verified: string, **maxLength 6**) | Koppelingen > Projecten | Cases.tkc_tfs_project_id_c | new | varchar(10) | - | OSC allows 6 characters only. The workspace builds a link to an internal TFS work-item URL from this value. See Q12. |
| Incident.InitialSoln | datetime (Info panel) | Info | - | drop | - | - | SLA dropped (owner decision). |
| Incident.LastResp | datetime (verified: `lastResponseTime`, nullable) | Info | - | drop | - | - | Derivable from the last AOP case update. |
| Incident.Closed | datetime (verified: `closedTime`, nullable) | Info | Cases.tkc_date_closed_c | new | datetime | - | Core Cases has no closed date. Needed for resolution reports. |
| Incident.CreatedBy | staff account (Info panel) | Info | Cases.created_by | core | - | - | Core. |
| Incident.RuleCtx.Escllevel | integer ? (rule-engine context) | Info | - | drop | - | - | OSC rule-engine state. Not data. |
| Incident.RuleCtx.State | rule-engine state | Info | - | drop | - | - | Same. |
| Incident thread (RichIncidentThread) | child object `threads` (verified): account (staff), channel, contact, contentType, createdTime, displayOrder, entryType, id, mailHeader, text | Berichten | AOP_Case_Updates (name, description, internal, contact_id, assigned_user_id, date_entered) | core | - | - | `text` is a string up to 349525 characters and 1 MB (verified). The core `description` column of the Basic template is type `text` (a 64 KB MySQL column): add a vardef override to a larger type, or long mails are cut. Also `mailHeader` (up to 349525) has no core field: drop it. `contentType`: 1 text/plain, 2 text/html. See 2.1. |
| IncidentThread.EntryType | menu (verified: `threads.entryType`, not nullable), 8 items | Berichten | AOP_Case_Updates.tkc_entry_type_c | new | enum | 1 Opmerking, 2 Werknemeraccount, 3 Klant, 4 Klant-proxy, 5 Chat, 6 Regelnotitie, 7 Regelnotitiesjabloon, 8 Spraakintegratie. API codes seen in the XML: NOTE (1), STAFF (2), CUSTOMER (3), CUST_PROXY (4) | Proposal list for `tkc_entry_type_c`: note, staff, customer, customer_proxy (the rest are system entries: chat, rule note, rule note template, voice). Counts of thread entries by type were not read. |
| IncidentThread.Channel | menu (verified: `threads.channel`, nullable), 47 items (44 distinct names) | Berichten | AOP_Case_Updates.tkc_channel_c | new | enum | Default for a new customer entry: Telefoon (3). Names: Boeken, Breedband nieuws, Chat, Community, [customer E] nieuws, E-mail (ids 1, 2, 9), Facebook (4 variants), Fax, Instagram (3), LinkedIn (3), SRM Engage, Sina Weibo (3), Snapchat (3), Telefoon, [customer D] (2), Tumblr (3), Twitter (4), WeChat (3), Web (ids 6, 7), WhatsApp (3), YouTube, [customer C] (2). Full list: Appendix B | The incident-level `channel` has the same 47 items; tst1 uses only E-mail (4032) and Web (17), empty 4. Proposal: a short list (E-mail, Telefoon, Web, Chat, Fax, Social, Other) and map the rest to Social or Other. Owner confirms (Q20). |
| Incident file attachments | FileAttachments control | Bijlagen | Notes (relationship `case_notes`) | core | - | - | Core. Attachments linked to a thread entry in OSC: confirm (Q13). |
| Incident audit log | IncidentAuditLog control | Details > Geschiedenis | Cases audit (`audited` = true) | core | - | - | Core. OSC history is not imported unless requested (Q13). |

**Counts (rows in this table, recounted after REST 2026-11-20; no row added or removed):** core 12 | new 21 | drop 10 | total 43.
New includes the import keys `osc_id_c` and `tkc_case_ref_c`.

### 2.4 Build status (SUITE-66, 2026-11-22)

Status values: planned, built, migrated. Details: `docs/data-model.md` § Cases.

| SuiteCRM item | Status |
|---|---|
| `Cases.tkc_case_ref_c` (varchar 20, index) | built (SUITE-66). Number format not built (E8, A12). |
| `Cases.tkc_priority_c` (list `tkc_impact_list`) | built (SUITE-66) |
| `Cases.tkc_customer_priority_c` (varchar 255; see 2.3) | built (SUITE-66) as plain text |
| `Cases.tkc_tact_id_c`, `tkc_event_remark_c`, `tkc_event_remarks_c`, `tkc_tfs_project_id_c` | built (SUITE-66) |
| `Cases.tkc_product_c`, `tkc_category_c` (flat owner lists) | built (SUITE-66) |
| `Cases.tkc_queue_c` (12 queues, index) | built (SUITE-66) |
| `Cases.tkc_expected_resolution_date_c`, `tkc_date_closed_c` | built (SUITE-66) |
| `Cases.tkc_case_origin_c` (4 keys) | built (SUITE-66) |
| `case_status_dom` (12 statuses, section 10.1) | built (SUITE-66). Keys as in 10.1. |
| `case_priority_dom` (4 keys) | built (SUITE-66). The fourth key is `tkc_P4`, not `P4` (A13). The default is empty. |
| `case_type_dom` (4 types) | built (SUITE-66). The keys are `tkc_support_request`, `tkc_general_communication`, `tkc_info_message` and `tkc_project_communication`. |
| `Cases.tkc_disposition_c` | planned. The owner has not decided (Q5). |
| `Cases.tkc_assigned_group_c` | planned (Q6) |
| Thread `mailHeader` field | planned |
| `AOP_Case_Updates.tkc_entry_type_c` (list `tkc_entry_type_list`, 4 keys: `note`, `staff`, `customer`, `customer_proxy`; default `staff`) | built (SUITE-68). The `before_save` preset in the create form is planned (E9-S1). |
| `AOP_Case_Updates.tkc_channel_c` (list `tkc_channel_list`, 7 keys) | built (SUITE-68) |
| `AOP_Case_Updates.osc_id_c` (varchar 20, index; import key of the thread rows) | built (SUITE-68). The key is ready for the import. |
| `AOP_Case_Updates.description` as `longtext` (thread text up to 1 MB, Q13) | built (SUITE-68). The importer must save every thread entry through the bean (`SugarBean::save()` → `cleanBean()` → `purify_html` purifies every `text`/`longtext` field); it never writes `description` with raw or bulk SQL. If it cleans the text before the save, it uses `SugarCleaner::cleanHtml($html, true)`: the default `cleanHtml($html)` only decodes entities and does not purify (SUITE-68 security finding 1, E9/SUITE-134). |
| Thread CC and BCC, attachments on thread entries | planned |
| Import of thread data | not started (no row is migrated) |
| `Cases.tkc_collect_before_share_c`, `tkc_collect_before_share_opt_c` | planned (Q10) |
| `Cases.osc_id_c` (varchar 20, index `idx_cases_osc_id_c`; import key of the cases) | built (SUITE-71). The thread key `AOP_Case_Updates.osc_id_c` is built in SUITE-68. |
| Cases primary contact fields | planned (relationship story) |
| Import of Cases data | not started (no row is migrated) |

### 2.2 Custom fields (package `c`, verified by REST schema 2026-11-20)

All 35 fields come from `metadata-catalog/incidents/customFields/c` (header `Accept: application/schema+json`). All are nullable. Date fields: `minimumDate` = date only, `minimumDateTime` = datetime. Menu values come from `namedIDs/incidents/customFields/c/<field>`. The menu `c.klantnaam` has 11 values (ids 61 to 70 and 151), all customer names: replaced by placeholders `[customer F1]` to `[customer F11]` in order of id (names in the gitignored raw file). Fields not on the workspace are migrate-or-drop candidates for the owner (Q19). Proposal for those: drop (data minimisation), except where a row says otherwise.

| REST API name | Type | Length / limits | OSC label | Menu values (id name) | On workspace |
|---|---|---|---|---|---|
| c.incident_survey_text | string | max 1333 | incident_survey_text |  | no |
| c.rating_a_id | string | max 5 | Answer ID |  | no |
| c.answer_rating | string | max 1 | Answer Rating |  | no |
| c.webformid | string | max 2 | WebFormID |  | no |
| c.foreward_to | string | max 255 | Foreward To |  | no |
| c.klantnaam | menu |  | KlantNaam | 11 customer names (ids 61 to 70, 151), placeholders `[customer F1..F11]`, see note above | no |
| c.prioriteit | menu |  | Prioriteit | 84 Geen impact; 74 Enige impact; 75 Verstoring productieproces; 76 Direct actie vereist | no |
| c.klantprioriteit | menu |  | Klant prioriteit | 156 Prioriteit 1; 157 Prioriteit 2; 158 Prioriteit 3; 159 Prioriteit 4 | no (workspace "Klantprioriteit" is the relation `Support.SupportPriority`) |
| c.feedback_bron | menu |  | Feedback bron | 160 e-mail; 161 chat; 162 twitter | no |
| c.incident_impact | string | max 20 | Incident Impact |  | no |
| c.tkc_prioriteit | menu |  | TKC Prioriteit | 209 Geen impact; 210 Enige impact; 211 Verstoring productieproces; 212 Direct actie vereist | yes |
| c.automatische_handtekening | boolean |  | Automatische handtekening |  | no |
| c.typeincident | menu |  | Type Incident | 184 Support verzoek; 185 Algemene Communicatie; 186 Info bericht; 195 Project Communicatie | yes |
| c.indicated_solve_date | date |  | Verwachte oplosdatum |  | yes |
| c.datum_afspraak | date |  | Datum afspraak |  | no |
| c.benodigde_uren | integer | 0 to 999 | Benodgde uren |  | no |
| c.hyperlink_url | string | max 255 | Hyperlink/URL |  | no |
| c.geboekte_uren | integer | 0 to 999 | Geboekte uren |  | no |
| c.tfs_project_id | string | max 6 | TFS Project ID |  | yes |
| c.webform_naam | string | max 255 | Naam |  | no |
| c.webform_organisatie | string | max 100 | Organisatie |  | no |
| c.webform_telefoonnummer | string | max 20 | Telefoonnummer |  | no |
| c.voornaam | string | max 255 | Voornaam |  | no |
| c.achternaam | string | max 255 | Achternaam |  | no |
| c.event_opmerking | string | max 255 | Event opmerking |  | yes |
| c.event_opmerkingen | string | max 1333 | event_opmerkingen |  | yes |
| c.tactid | string | max 10 | Tact ID |  | yes |
| c.ssvtestveld | string | max 10 | ssv_testveld |  | no |
| c.prio_support_new | menu |  | Prio support | 300 Prioriteit 1; 301 Prioriteit 2; 302 Prioriteit 3; 303 Prioriteit 4 | no |
| c.feedback_site_menu | menu |  | FeedbackSiteMenu | 315 Compliment; 316 Onjuiste inhoud; 317 Technische fout; 318 Suggestie | no |
| c.collect_before_share | boolean |  | verzameling? |  | yes |
| c.collect_before_share_menu | menu |  | Verzameling menu | 323 Case samenvoegen; 324 Case items verwijderen; 325 Dubbele cases opruimen | yes |
| c.topdeskref | string | max 20 | TopdeskID |  | no |
| c.archiveren_op | datetime |  | Archiveren op |  | no |
| c.remindon | date |  | RemindOn |  | no |

Package `Support` (verified): `Support.Opportunity` (relation to opportunities), `Support.Project` (relation to `Support.Project`, out of scope), `Support.SupportPriority` (relation to the custom object `Support.SupportPriority`, see 2.3). Packages `CO` (`relatedincident`) and `REL` (`Answer`) were not read.

### 2.3 `Support.SupportPriority` ("Klantprioriteit")

Verified schema of the custom object (114 rows in tst1): `klant_prioriteit` string 255 (not null), `prio_uitleg` text (max 349525), `sla_uitleg` text (max 349525), `severity_id` integer (not null), `tkc_prioriteit_id` integer (nullable), `sup_id` relation to `Support.SupportContract` (not null), `Incident` integer (nullable). `Support.SupportPrioMenu` (empty) links back to it.
Reading: a priority table **per support contract**. Each row says: for this contract, customer priority "X" means severity N and TKC priority M, with an explanation text and an SLA text. The incident field `Support.SupportPriority` points to one such row. The workspace picker (report 30152) selects it.
Consequences: (1) it is not a menu and the value list depends on the contract; (2) the SLA texts relate to the dropped SLA feature; (3) distinct values of `klant_prioriteit` (REST 2026-11-20, 106 distinct): the text is `[customer] - <impact label>`: about 23 customers times the four impact labels of `c.tkc_prioriteit` (Geen impact, Enige impact, Verstoring productieproces, Direct actie vereist; two rows have a typo), plus about 14 generic rows (Kritiek, Hoog, Normaal, Laag, "severity N" variants, outage-style names). Customer names are not copied here. So the value is a per-customer priority label that repeats the impact scale: it carries little beyond the TKC priority. Proposal until the owner decides (Q7): migrate the chosen row as the text `klant_prioriteit` into `tkc_customer_priority_c` (varchar 255), do not build the table, and keep the explanation texts in OSC. Better: drop it, because the label only repeats customer name plus impact. The older menu `c.klantprioriteit` (Prioriteit 1 to 4) is not the workspace field.

### 2.1 Message thread vs. AOP_Case_Updates

| OSC thread concept | SuiteCRM (AOP) | Gap |
|---|---|---|
| Entry, customer (STAFF absent, customer author) | AOP_Case_Updates with `contact_id` set, `internal` = 0 | Core handles this for portal and inbound email. |
| Entry, staff response | AOP_Case_Updates, author = user, `internal` = 0 | Core: AOP emails the contact when a staff update is saved. Check the template flow (Q14). |
| Entry, private note | AOP_Case_Updates with `internal` = 1 | Core. |
| Entry, customer proxy (staff logs what the customer said, e.g. by phone) | No equivalent. | New `tkc_entry_type_c`. |
| Entry channel (Phone, Email, ...) | No equivalent. | New `tkc_channel_c`. |
| CC and BCC on a response | Emails module (separate record) | Gap in the thread editor. |
| Standard text insert buttons | Email templates exist, but no quick-insert in the case update box | Custom UI action (frontend). |
| Smart assistant, KB search, propose and best answer | AOK_KnowledgeBase exists, no suggestion engine | Gap. Out of scope for the first build (Q15). |
| Threads sort, filter, collapse | Core 8 shows case updates | Check in the 8.10 UI. |

Case-level field `update_text` (core) is the input that creates an AOP update on save. The `aop_case_updates` link and
`aop_case_updates_threaded` field are core.

## 3. Actions and buttons mapping

OSC buttons run workspace rules. "Level": `core` = standard SuiteCRM, `workflow` = AOW or logic hook, `custom` = frontend/backend extension.

| OSC action (label, rule) | What it does (from XML) | SuiteCRM action | Level |
|---|---|---|---|
| Opslaan / Save | RecordCommand Save | Save | core |
| Opslaan-sluiten / Save and close | RecordCommand SaveAndClose | Save and return to list | core |
| Nieuw, Vernieuwen, Afdrukken, Kopie, Verwijderen | ribbon standard | New, refresh, print (PDF), duplicate, delete | core |
| Bladwijzer / Bookmark | ribbon | Favorites | core |
| Verzenden / Save and send (and close) | RecordCommand SaveAndSend | Save with a staff (non-internal) update, which AOP emails to the contact | core + check (Q14) |
| Doorsturen / Forward (button + ribbon) | RecordCommand Forward (opens the forward dialog) | Email compose from the case | core (check) |
| Wachtend | Status.Id := 3 ("Wacht op aanmelder - info benodigd", verified) | Set `status` `Open_Pending Input` | custom action (one click) |
| Bijgewerkt (rule "Beantwoord") | Status.Id := 8 ("Bijgewerkt") | Set `status` `Open_Updated` (new key) | custom action |
| Onopgelost | Status.Id := 1 ("Onopgelost") | Set `status` `Open_New` | custom action |
| Opgelost | Status.Id := 2 ("Opgelost", type Opgelost), then SaveAndClose | Set state Closed (`Closed_Closed`), save | custom action |
| Rule "optioneel" statuses visibility (rule 13) | Show Opgelost, Bijgewerkt, Wachtend when status in {empty, 2, 1, 8, 103} (103 does not exist: drop it) | Show status buttons by current status | custom (display logic) |
| Doorsturen Finance | Set Product 478 (Finance), Category 479 (separator "FINANCE"), Disposition 486 (Finance), Status := 2, assign group TKC - Finance (107042) and a fixed account, then Forward | One-click "route to Finance": set fields, assign, close | custom action |
| CX Oplossen | Product 441 (RightNow), Category 495 (CX), Disposition 468 (Service request); Assigned := current user; Status := 2; SaveAndClose | One-click "solve as CX" | custom action |
| eGain Oplossen | Product 551, Category 552 (both missing in tst1, Q21), Disposition 468; solved; SaveAndClose | One-click "solve as eGain" | custom action |
| Unlabelled "Oracle case" button (screenshot: "assign to me") | Fixed Product, Category, Disposition; Assigned := current user (no close) | One-click "take case, preset taxonomy" | custom action |
| Unlabelled "eGain case" button (screenshot: "assign to me") | Same, with the eGain values | Same | custom action |
| SPAM | Set **Contact** type := 14 (SPAM), Queue := 24 (SPAM), Status := 2 (Opgelost), relax required fields, Product := 545 (Spam), SaveAndClose | Mark as spam: updates the contact type **and** the case | custom action (writes to Contacts) |
| GEEN SPAM | Clear Contact type, Queue := 2 (TKC - Support 1e lijn), Status := 1 (Onopgelost), SaveAndClose | Undo spam | custom action |
| CX Support | Open an external OSC login URL in the browser tab, focus tab | Link to external URL | custom (link) or drop |
| Navigation buttons (4) | Focus tab: Supportcontract, Bijlagen, Co Browsing; Browser tab (map for contact address) | Anchor to subpanel or tab; map: `jjwg_Maps` is installed, uses contact address | core / custom |
| Klant / Reactie / Prive | Add thread entry with standard text 40 as CUST_PROXY / STAFF / NOTE | "Add update" with type, optional blank text | custom (needs `tkc_entry_type_c`) |
| Aanhef, Afsluiting, Storing, Meer info nodig | Add STAFF entry with standard text 33, 10, 20, 41 | Insert email template text into the update box | custom action. Text bodies not in the XML (Q16). |
| Hulp (hyperlink) | Focus Scripts tab, run script 100442 | Drop (OSC guided-assistance scripts) | drop |
| Beslisboom "Start" | Invoke decision tree 63 | No equivalent | drop (Q15) |
| Ribbon: Propose, BestAnswer, DecisionTree, Spell check, Appointment, New service opp | OSC standard tools | Appointment = new Meeting; service opp = new Opportunity linked to case (needs relationship); spell check = browser; rest drop | mixed (see Q15) |
| OSN tab (collaboration) | Oracle Social Network | No equivalent | drop |
| Co Browsing | Oracle co-browse | No equivalent | drop |
| Delete rule (inactive) | Message: incident is archived, not deleted | Soft delete is core (`deleted` flag) | core |

## 4. Workspace rules mapping

| Rule (name in XML, active?) | Trigger | Condition | Action | SuiteCRM equivalent |
|---|---|---|---|---|
| Editor geladen (active) | Editor loaded | Mode = New | Add a NOTE entry with standard text 371 and a CUST_PROXY entry with standard text 341 | Pre-filled update on create (custom; template content unknown, Q16) |
| Kopie van Verberg Doorgeven Behandelaar (active) | Editor loaded | Source = 3001 ("Een vraag stellen"; 0 incidents in tst1) | Show banner "Let op! Selfservicedeskmelding. Alle reacties zijn zichtbaar voor de aanmelder." ; otherwise hide | Field display logic or Angular banner on `tkc_case_origin_c` |
| Nieuwe regel, status buttons (active) | Editor loaded, Status changed | Status in {empty, 2, 1, 8, 103} | Show Opgelost, Bijgewerkt, Wachtend | Button visibility by status |
| Nieuwe regel, assigned menu (active) | Editor loaded | none | Remove 4 hard-coded accounts or groups from the Assigned menu | Filter on user/group list (custom); IDs not copied here |
| Nieuwe regel, queue by group (active) | Assigned changed | Group = one specific group | Queue := 19 (Finance) | AOW workflow or logic hook (before save) |
| SPAM Knop (active) | Editor loaded | Contact type = 14 | Show GEEN SPAM, else show SPAM | Button visibility on the contact type |
| Nieuwe regel, CollectBeforeShare (active) | Editor loaded | Org id = 612 | Show `C$CollectBeforeShare` | Display logic by account. Hard-coded org id: customer-specific. |
| Nieuwe regel, CollectBeforeShareMenu (active) | Editor loaded, field changed | `C$CollectBeforeShare` = 1 | Show the menu, else hide | Field dependency (core vardef `dependency`) |
| smart, Home, Hulp, Beslisboom, Co-Browsing, Bijlagen, CX Support, tab focus rules (active) | Named or button or hyperlink | none | Focus a tab, open a URL, run a script, show a message ("Klik op de Smartassistant en open de FAQ") | UI navigation only. See section 3. |
| Button rules (Save, Forward, status, thread text, SPAM, Finance, CX, eGain, ...) | Button clicked | none | See section 3 | See section 3 |
| Kopie van optioneel x4 (inactive) | Editor loaded, Category or Product changed | value empty | Make Category or Product required, else optional | Drop. Product, Category, Disposition are always required in the layout. |
| Customer-specific change rule (inactive; name contains a customer name, omitted) | Editor loaded, Disposition changed | Disposition = fixed id AND org name = one customer | Set contact to a fixed id and show a warning about an approval step | Drop. Customer-specific and inactive. Contains a customer name and a person name: not copied. |
| Hulp bij taxonomie instellen (inactive) | none | none | Focus Scripts tab, run script, fire "hulp" | Drop |
| Verwijderen (inactive) | none | none | Message that the incident is archived, not deleted | Core soft delete |

Rule count: 48 total in the export; 7 inactive.
Rules write **required** and **hidden** states client-side. SuiteCRM 8 supports field `displayLogic`, `logic` and `dependency` in
view metadata, and AOW for server-side. The architect decides which rule becomes which (Q17).

## 5. Relationships

| OSC relation | OSC control | SuiteCRM relationship | Notes |
|---|---|---|---|
| Incident -> Contact (primary) | Field CId | New relate field `tkc_primary_contact_c` (1:N Contacts -> Cases) | Core `contacts_cases` is M:N. Keep it for additional contacts. |
| Incident <-> Contact (all) | Contacts relationship (tab Koppelingen) | `contacts_cases` (core, M:N) | Core. |
| Incident -> Org | Field OrgId | `account_cases` (core) | Core. |
| Incident -> Thread | RichIncidentThread | `cases_aop_case_updates` (core) | See 2.1. |
| Incident -> Attachments | FileAttachments | `case_notes` (core) | |
| Incident -> Tasks | Tasks | `case_tasks` (core) | Core subpanel. |
| Incident -> Support$Project | Support$ProjectRelatedRecordsView (FK `i_id`) | `projects_cases` (core, Project module) or new custom module | Depends on what Support$Project is (Q12). |
| Incident -> Opportunity | Report 100672 ("voor dit incident") and report 100622 ("per organisatie"), ribbon "New service opp" | No core relationship Cases-Opportunities | New M:N relationship (vardef) or drop. Report contents unknown (Q18). |
| Incident -> Support contract | Report 100641 | AOS_Contracts via Account, shown as a case subpanel or on the account | Content unknown (Q18). |
| Incident -> Answer (KB) | Propose, BestAnswer | AOK_KnowledgeBase has no Cases link in core | Drop for now (Q15). |
| Incident -> Asset | Record type Asset in export, not on this workspace | none | Out of scope. |
| Incident audit | IncidentAuditLog | Audit table (core) | |
| Incident -> Staff account | Field Assigned | `assigned_user_id` (core) | |
| Incident -> Queue, SLA instance | QueueId, SlaiId | new | See Q2, Q6. |

## 6. Features of OSC Incident that SuiteCRM Cases lacks (design and assessment)

1. **SLA engine**: SLA instance, response interval, response due, solve due, escalation level (`RuleCtx`). No SuiteCRM equivalent. Needs custom code or drop.
2. **Queues** and group-based assignment. Core has only `assigned_user_id`. AOP has a round-robin assign manager (`AOPAssignManager`) but no queue.
3. **Hierarchical Product / Category / Disposition** on the case. Core Cases has none of the three.
4. **Primary contact (1:N)**. Core Cases links contacts M:N only.
5. **Thread entry types and channel** (customer proxy, phone entries). AOP has only `internal` and `contact_id`.
6. **One-click status and taxonomy buttons** (about 9 buttons that set 3 to 8 fields at once). Needs custom record actions.
7. **Client-side workspace rules** (required, hidden, set value, menu filters). SuiteCRM has display logic and dependencies, but no rule designer. Each rule is a developer task.
8. **Standard text quick-insert, smart assistant, decision trees, co-browse, OSN, spell check on save.** No equivalent.
9. **Case number format** (`YYMMDD-NNNNNN` in the screenshot) differs from the core integer counter. Status (SUITE-62): the sequence service exists (`docs/architecture.md` § Sequences). The counter name and the format of `tkc_case_ref_c` are not decided (A12). They are built in E8.
10. **Contact type changed from the case** (SPAM button writes Contact.CtypeId). Needs a cross-module action.

## 7. Open questions

Numbered for reference. **Top 5** (the answers block the design) are marked **TOP**. Status after the REST run of 2026-11-20 is in each line.

1. **Q1 (CLOSED 2026-11-20).** REST metadata, menu values and usage counts are read (section 0). What stays open for REST is in section 9.
2. **Q2 (CLOSED 2026-11-20).** SLA dropped by the owner.
3. **Q3 (TOP).** Primary contact: add a 1:N `tkc_primary_contact_c` relate field (our proposal), or reuse core `contact_created_by`? REST confirms that `primaryContact` is **required** in OSC.
4. **Q4 (TOP, lists verified, decision open).** Status list (12) with status types and severity list (4) are verified: section 10.1 and the Severity row. Does the owner accept the mapping proposal (12 `status` keys, 4 priority keys)? Status type of 3, 110, 111, 112, 113 was not read.
5. **Q5 (TOP; owner lists decided 2026-11-20, mapping in 10.3; open parts below).** Product (50 nodes), Category (70) and Disposition (15) are listed (Appendix A). Decided: flat lists (Product: Oracle Service Cloud, eGain; Category: Service Request, Complaint, Question, Change Request). Open: (a) does Disposition stay in SuiteCRM, and with which values (**open**)? (b) confirm that 441 RightNow = Oracle Service Cloud and that 470 Issue stays unmapped (or becomes Complaint); (c) keep the 51 OSC category values as text somewhere, or lose them?
6. **Q6.** Assignment and queues: 14 staff groups and 12 queues are listed. 88% of incidents are in queue "Finance" (id 19). How are group and queue used in practice? Is a SuiteCRM security group the counterpart of a staff group? Are queues routing or reporting?
7. **Q7.** `Support.SupportPriority` is a per-contract priority table (2.3). Is "Klantprioriteit" a value the agent picks, or does it derive from the contract? Migrate as text (proposal) or build the table?
8. **Q8 (partly closed).** Lengths verified: Subject 240, Tact ID 10, EventOpmerking 255, EventOpmerkingen 1333, TfsProjectId 6. Open: purpose of the two event-remark fields.
9. **Q9 (partly closed).** `C$IndicatedSolveDate` = "Verwachte oplosdatum", type date (verified). `RelDue` label ("Eerste reactie verwacht") is still inferred from the screenshot.
10. **Q10.** `C$CollectBeforeShare` and its menu (323 Case samenvoegen, 324 Case items verwijderen, 325 Dubbele cases opruimen): customer-specific, shown for one organisation. Migrate, or drop?
11. **Q11.** Confirm drop for InterfaceId, LangId (2 values: en_GB, nl_NL), MailboxId, InitialSoln, LastResp. Does the new system need multi-language incidents?
12. **Q12.** What is `Support$Project`? Is it a TFS work item? The TFS id is a string of at most 6 characters. Keep a text field and a link?
13. **Q13.** Import scope: the full thread history, attachments and audit log, or only the latest N months? Do attachments hang on thread entries? Thread `text` can be 1 MB: the core column is too small (row "Incident thread").
14. **Q14.** Email behaviour: does SuiteCRM AOP send to the contact on a staff response like OSC? Also how the inbound mailbox creates cases (source 5001 "Techmail - Service-postvak" is 99.7% of incidents).
15. **Q15.** Out-of-scope confirmation: smart assistant, decision trees, co-browse, OSN, KB propose and best answer, service opportunity button.
16. **Q16.** Text of the standard texts (ids 10, 20, 33, 40, 41, 341, 371). `standardContents` exists in REST but its bodies were not read (they may hold customer text).
17. **Q17.** Which workspace rules stay as UI logic and which become AOW or logic hooks (architect)?
18. **Q18.** What do reports 100641, 100672, 100622 show?
19. **Q19.** The 26 custom fields that are not on the workspace (section 2.2): migrate or drop? Proposal: drop.
20. **Q20.** Source and channel: accept the short lists (source: email, selfservice, staff, other; channel: E-mail, Telefoon, Web, Chat, Fax, Social, Other)? The full OSC lists have 263 and 47 values.
21. **Q21.** The eGain buttons use product 551 and category 552, which tst1 does not have; status 103 does not exist in tst1. Production check **not done**: the production call was denied by the permission system (needs the user's own approval). The user must approve the call himself, or ask the OSC admin.

## 8. Privacy notes on the inputs

- The **screenshot** shows real data: a staff member's name in "Toegewezen", an incident number, a customer contact name, and the full text
  of a vendor notification email. Nothing from it was copied here. Do not use the screenshot in mockups or artifacts without blurring.
- The **XML** has no incident records. It does contain: one inactive rule that names a customer organisation and a person
  (not copied), hard-coded OSC ids for staff accounts, groups, one organisation (612) and one contact, an internal TFS URL, and a link to an
  internal spreadsheet whose file name suggests it holds customer site logins. Treat that link as sensitive; do not follow or copy it.

## 9. Still open after the REST run (2026-11-20)

Done in the second TST batch (b5): parent links of the three taxonomy trees, `klant_prioriteit` distinct values, `c.klantnaam` menu values.
REST-answerable, still open:
1. Status type of statuses 3, 110, 111, 112 and 113. The `namedIDs/.../status/<id>` resource sends only id and name. No ROQL table for statuses was found (3 names tried: `Incidents.StatusWithType`, `StatusWithType.Status`, `Statuses`). The type shows only through incidents that use the status, and none does.
2. Thread counts by entry type and channel. ROQL tables `Incidents.Threads` and `IncidentThreads` were refused (HTTP 400). The correct ROQL table name for threads is unknown.
3. Contact `name` and `address` sub-field lengths (see contact.md): needs the schema header, which cannot be combined with menu reads in one batch.
4. Production check of product 551, category 552 and status 103: **not run** (permission denied, see 10.2).
Business: Q3 to Q7, Q10 to Q21.

## 10. Resolved ids and mapping proposals (REST 2026-11-20, tst1)

### 10.1 Status, status type and SuiteCRM `state` / `status`

Status types (verified): 1 Onopgelost, 2 Opgelost, 3 Wachtend. The type of a status was read from incidents that use it (aggregate ROQL). For statuses with no incident the type was **not** read.

| OSC status id | Name | OSC status type | Incidents (tst1) | SuiteCRM `state` | SuiteCRM `status` key (proposal) |
|---|---|---|---|---|---|
| 1 | Onopgelost | 1 Onopgelost | 1644 | Open | `Open_New` (core) |
| 104 | In behandeling | 1 Onopgelost | 256 | Open | `Open_Assigned` (core, relabel) |
| 8 | Bijgewerkt | 1 Onopgelost | 1984 | Open | `Open_Updated` (new key) |
| 101 | On-hold | 1 Onopgelost | 2 | Open | `Open_OnHold` (new key) |
| 109 | Wacht op Oracle | 3 Wachtend | 2 | Open | `Open_WaitOracle` (new key) |
| 108 | Gereed - wacht op akkoord aanmelder | 3 Wachtend | 2 | Open | `Open_AwaitApproval` (new key) |
| 3 | Wacht op aanmelder - info benodigd | not read | 0 | Open | `Open_Pending Input` (core) |
| 110 | Wacht op Freshworks | not read | 0 | Open | `Open_WaitFreshworks` (new key) |
| 111 | Feature Request | not read | 0 | Open | `Open_FeatureRequest` (new key) |
| 112 | CA Jira | not read | 0 | Open | `Open_CAJira` (new key) |
| 2 | Opgelost | 2 Opgelost | 163 | Closed | `Closed_Closed` (core) |
| 113 | Gearchiveerd | not read | 0 | Closed (assumed) | `Closed_Archived` (new key) |
| 103 | **not in the status list** | - | 0 | - | - |

Rule: type 2 gives `state` Closed. Types 1 and 3 give Open. 12 OSC statuses become 12 `status` keys (2 core keys reused: `Open_New`, `Open_Assigned`, `Open_Pending Input`, `Closed_Closed`: 4; 8 new keys). Core keys `Closed_Rejected` and `Closed_Duplicate` stay unused. Implementation: a custom language extension for `case_status_dom` (keys keep the `Open_` / `Closed_` prefix that core uses to match the state). Architect confirms.
Status **103**: the rule "status buttons" lists 103, but no such status exists in tst1. It was probably deleted. The condition value is dead: drop it.

### 10.2 Hard-coded ids in the buttons and rules

| Id in XML | Field | Meaning (REST) | Used by |
|---|---|---|---|
| Status 1, 2, 3, 8 | Status | 1 Onopgelost, 2 Opgelost, 3 Wacht op aanmelder - info benodigd, 8 Bijgewerkt | Buttons Onopgelost, Opgelost, Wachtend, Bijgewerkt; rules |
| Status 103 | Status | does not exist in tst1 | rule "status buttons" |
| Queue 19 | Queue | Finance | rule "queue by group"; 3583 incidents |
| Queue 24 | Queue | SPAM | SPAM button |
| Queue 2 | Queue | TKC - Support 1e lijn | GEEN SPAM button |
| Contact type 14 | Contact.CtypeId | SPAM | SPAM button and rule |
| Product 441 / Category 495 / Disposition 468 | taxonomy | RightNow / CX / Service request | buttons "CX Oplossen", "Oracle case" |
| Product 551 / Category 552 / Disposition 468 | taxonomy | **551 and 552 do not exist in tst1** (verified with the full parent listing: not among the 50 products or 70 categories) / Service request | buttons "eGain Oplossen", "eGain case" |
| Product 478 / Category 479 / Disposition 486 | taxonomy | Finance / "---------- FINANCE----------" (a separator item used as a value) / Finance | button "Doorsturen Finance" |
| Product 545 | taxonomy | Spam | SPAM button |
| Group 107042 | Assigned (group) | TKC - Finance | button "Doorsturen Finance" |
| Source 3001 | Source | Een vraag stellen (Ask a Question) | self-service banner rule; 0 incidents in tst1 |
| Org 612, contact ids, account ids | - | not resolved (record data, not read) | CollectBeforeShare rule, buttons |

The eGain buttons point to ids that tst1 does not have. The tst1 product list has "eGain" as id 586 and category "eGain" as 585 (child of 557 eGain). Production lookup for 551, 552 and status 103: **NOT DONE**. The coordinator relayed an approval, but the permission system denied the `PROD --prod-approved` call, because only the user can approve a production call. Batch file ready: `data/osc/batches/b6-prod.tsv` (3 read-only metadata GETs). Until then the buttons map by intent: eGain buttons -> product eGain, and category/disposition per the mapping above. Q21.

### 10.3 Owner lists and the import mapping (decision 2026-11-20)

The owner keeps flat SuiteCRM lists. The OSC trees are **not** imported as trees. Parent links were read (REST 2026-11-20): products 50 nodes, 9 roots, 4 levels; categories 70 nodes, 20 roots, 2 levels; dispositions 15 nodes, 11 roots, 2 levels.

| SuiteCRM list | Values | OSC source for the import | Mapping | Unmapped |
|---|---|---|---|---|
| Product | Oracle Service Cloud, eGain | `product` (root of its tree) | root 441 RightNow and root 472 Oracle Service Cloud, with all descendants -> Oracle Service Cloud. Root 586 eGain -> eGain | any other root (9 roots: Marketing, Finance, Algemeen, TKC Fresh, KLANTEN, FreshWorks): empty, one line per record in the import log (count and OSC id only) |
| Category | Service Request, Complaint, Question, Change Request | `disposition` (the owner list equals the OSC dispositions, not the OSC categories) | 468 Service request -> Service Request; 469 Change request -> Change Request; 467 Vraag -> Question | 470 Issue and 11 other dispositions: empty, logged. "Complaint": no OSC source. OSC `category` values: not imported, logged |
| Disposition | **open** | `disposition` | not decided | - |

Notes:
- The owner's Category list matches OSC Disposition for 3 of 4 values. If the owner prefers 470 Issue -> Complaint, that is a one-line change in the mapping.
- Usage in tst1 (4053 incidents): product on 71 (26 map), category on 51 (none map), disposition on 72 (21 map). The migration loses little: 3982 incidents have no product at all.
- Hard-coded ids in buttons map through the same table: 441 -> Oracle Service Cloud, 586 and (if valid) 551 -> eGain, disposition 468 -> Service Request.
- The import log records only OSC ids and counts, never record content ([data-privacy.md](../../.claude/rules/data-privacy.md)).

## Appendix A. Taxonomy lists (REST, tst1, id name; parents in 10.3 counts)

Customer names are replaced by placeholders; the real values are in OSC and in data/osc/rest-output/ (gitignored).

Products (50): 441 RightNow; 442 Smart Start Knowledgebase; 443 Knowledgebase Lifecycle Management; 444 Forward & Track; 472 Oracle Service Cloud; 476 Marketing; 477 Evenement; 478 Finance; 488 Algemeen; 496 TKC Fresh; 502 Console; 503 Customer Portal; 504 Addins; 505 CPM; 506 Analytics; 507 Workflow/Workspace; 508 API; 509 KLANTEN; 510 [customer P1]; 511 [customer P2]; 512 Controllers; 514 Libraries; 515 Helpers; 516 Models; 517 Views; 518 Widgets; 519 Config; 520 Development; 521 Javascript; 522 Assets; 523 Profielen; 524 WF Scipts; 525 Navigatiesets; 526 Custom Objects; 527 Custom Fields; 528 Mailboxes; 529 Regels; 530 Addins; 531 CPM; 532 Object Lifecycle; 533 [customer P3]; 539 [customer P4]; 540 TKC; 541 FreshWorks; 543 [customer P5]; 544 Answers; 545 Spam; 550 Support; 582 kennisbank; 586 eGain.

Categories (70): 446 Agent Console; 447 End user Portal; 448 Integration; 449 Analytics/reports; 450 Chat; 451 Social; 452 Opportunities; 453 Feedback; 454 Marketing; 455 Contacts/organization; 456 Incidents; 457 Co-browse; 458 Customization; 459 Connectivity; 460 Admin area; 461 PTA; 462 Look and Feel; 463 Widgets; 464 Mobile; 465 End-user; 466 Custom; 471 Answers; 473 ---------- DEVELOPMENT ----------; 474 Templates; 475 Basics; 479 ---------- FINANCE----------; 480 Factuur; 481 ---------- MARKETING----------; 482 Afmelding; 489 Algemeen; 493 Inkoop; 495 CX; 497 ---------- Freshdesk----------; 498 Portal; 499 Agent Login; 500 Admin functionality; 501 Chat; 534 -------------BB KMS---------------; 535 TKC Product; 536 Wijziginsverzoek; 537 TKC Beheer; 538 Bug; 546 spam; 548 spam; 549 Visuals; 554 OSC .NET Console; 555 OSC Browser User Interface; 556 OSC Kennisbank; 557 eGain; 558 Freshworks; 559 Freshdesk; 560 Widget; 561 Antwoorden; 562 Look & Feel; 566 Intergraties; 567 SSO (Single Sign-on); 568 Configuratie; 569 Antwoorden; 570 Analytics/Rapportages; 571 Workspaces; 572 Regels; 573 Intergraties; 574 SSO (Single Sign-on); 575 Configuratie; 576 Accountmanagment; 577 Analytics/Rapportages; 578 Workspaces; 579 Regels; 580 Intergraties; 585 eGain.

Dispositions (15): 467 Vraag; 468 Service request; 469 Change request; 470 Issue; 483 Afmelding; 484 Algemeen - marketing; 485 Betaalherinnering; 486 Finance; 487 Factuur; 490 Algemeen; 491 Betaling; 492 Upgrade; 494 Inkooporder; 547 Spam; 581 Incident.

## Appendix B. Other lists (REST, tst1)

Staff groups (14, names only): 100196 TKC - Software developer; 110993 TKC - Business consultant; 107042 TKC - Finance; 108599 TKC - Sales; 110994 Director; 110992 Add-in; 108878 TKC - KM; 100001 TKC - Technical consultant; 100096 TKC - Test Accounts; 107056 TKC - Administrator; 108651 Gedeactiveerd; 114973 Frontline; 116839 TKC - Office; 115459 special group.

Incident and thread channel (47): 5 Boeken; 1003 Breedband nieuws; 8 Chat; 10 Community; 1008 [customer E] nieuws; 2 E-mail; 9 E-mail; 1 E-mail; 14 Facebook; 19 Facebook gemengd; 17 Facebook openbaar; 18 Facebook privé; 4 Fax; 25 Instagram gemengd; 23 Instagram openbaar; 24 Instagram privé; 34 LinkedIn gemengd; 32 LinkedIn openbaar; 33 LinkedIn privé; 31 Sina Weibo gemengd; 29 Sina Weibo openbaar; 30 Sina Weibo privé; 43 Snapchat gemengd; 41 Snapchat openbaar; 42 Snapchat privé; 16 SRM Engage; 3 Telefoon; 1002 [customer D] - douches; 1001 [customer D] - Niews; 37 Tumblr gemengd; 35 Tumblr openbaar; 36 Tumblr privé; 11 Twitter; 22 Twitter gemengd; 20 Twitter openbaar; 21 Twitter privé; 6 Web; 7 Web; 28 WeChat gemengd; 26 WeChat openbaar; 27 WeChat privé; 40 WhatsApp gemengd; 38 WhatsApp openbaar; 39 WhatsApp privé; 12 YouTube; 1004 [customer C] - Klachten en Storingen; 1007 [customer C] forum.

Thread content type: 1 text/plain; 2 text/html.

Language (incident): 5 en_GB; 12 nl_NL.

Source: 263 system values (hierarchical, `namedIDHierarchies/incidents/source`). Full list in `data/osc/rest-output/v/inc-source-h.json`. Used in tst1: 5001 Techmail - Service-postvak (4032), 3019 Eindgebruiker verbinden (17), 17001 Webconsole: incident-editor (3), 1001 Incident-editor (1). Id 3001 is "Een vraag stellen".
