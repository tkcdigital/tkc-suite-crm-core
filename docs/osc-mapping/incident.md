# OSC Incident -> SuiteCRM Cases mapping

> **Owner decisions 2026-11-20:** SLA **dropped** (all SLA rows → `drop`). Queues **kept**. Product = flat list (Oracle Service Cloud, eGain). Category = flat list (Service Request, Complaint, Question, Change Request). Disposition: open. Projects (`Support$Project`): out of scope. Contracts: in scope. See `docs/backlog.md` § Product decisions.

Status: **DRAFT (no REST verification)**. Sources: `designs/screens/cases/tkc-incidents.xml` (OSC 26C workspace export),
`designs/screens/cases/OSC-Incident-1.png`, SuiteCRM vardefs. The OSC REST API was not available.
Field types, lengths and menu values that the XML does not state are **not** guessed. They are in section 6.
Raw parse output: `data/osc/workspace/cases.json` (gitignored).

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
"OSC type" is what the XML or screenshot proves; `?` means unverified (see section 6).
New-field names follow the rules: suffix `_c`. Final names are for the architect.

| OSC field (API name) | OSC type | Workspace location | SuiteCRM module.field | Exists? | New field type/len | Dropdown values | Notes |
|---|---|---|---|---|---|---|---|
| Incident.Subject ("Onderwerp") | text, single line (XML: Multiline=False); length ? | Algemeen | Cases.name | core | - | - | Required on new and edit (XML). Core `name` is varchar(255); OSC length unverified (Q8). |
| Incident.RefNo ("Meldingnr RN") | text ?; format looks like `YYMMDD-NNNNNN` (screenshot, one example only) | Algemeen, read-only look | Cases.osc_ref_no_c | new | varchar(30) (proposal) | - | Core `case_number` is an auto-increment integer and cannot hold this format. Keep `case_number` for new SuiteCRM cases. Index `osc_ref_no_c`. |
| Incident.IId | integer ? | Info panel only | Cases.osc_id_c | new | varchar(36), indexed | - | Import key per `suitecrm-customization.md`. |
| Incident.CId ("Contactpersoon") | relationship to Contact, 1:1 primary contact; picker uses report 8014 | Algemeen | Cases.primary_contact_c (relate to Contacts) + `primary_contact_id_c` | new | relate / id char(36) | - | Screenshot shows required (*). Core Cases has only M:N `contacts` (`contacts_cases`) and `contact_created_by` (portal creator). A primary-contact field is a gap. See Q3. |
| Incident.OrgId ("Organisatie") | relationship to Org; picker uses report 8001 | Algemeen | Cases.account_id / account_name | core | - | - | Core `account_cases`. |
| Incident.SlaiId ("SLA-exemplaar") | relationship to SLA instance | Categorisatie | Cases.sla_c ? | new | to be decided by architect | SLA names: REST needed | SuiteCRM has no SLA object. See Q2. Options: enum, or custom module `tkc_sla`. |
| Incident.SlaRespDelta ("Reactie-interval") | numeric with increment 1 (XML `Increment`); unit ? | Categorisatie, read-only (screenshot, tooltip "Reactie-interval voor dit incident") | Cases.sla_response_interval_c | new | int | - | Derived from the SLA instance in OSC. Import as stored value. See Q2. |
| Incident.SeverityId ("Prioriteit", label from screenshot) | menu (Severity) | Categorisatie | Cases.priority | core | - | Core `case_priority_dom`: P1 High, P2 Medium, P3 Low. OSC values: REST needed | The OSC value list replaces or maps onto P1-P3. Mapping table needed (Q4). |
| Incident.C$TkcPrioriteit ("TKC Prioriteit") | custom field, dropdown shown in screenshot; menu vs. other ? | Categorisatie | Cases.tkc_priority_c | new | enum | REST needed | |
| Incident.Support$SupportPriority ("Klantprioriteit") | field from package `Support`; report 30152 picker and relate icon in screenshot; probably relationship to a custom object | Categorisatie | Cases.customer_priority_c | new | enum or relate | REST needed | Type unknown (Q7). |
| Incident.C$Typeincident ("Type Incident") | custom field, dropdown in screenshot | Categorisatie | Cases.type | core | - | Core `case_type_dom` (Administration, Product, User) does not fit. Replace with OSC values: REST needed | Reuse core `type`; replace the dropdown list in a custom language extension. |
| Incident.C$Tactid ("Tact ID") | custom text, single line (XML) ; length ? | Categorisatie | Cases.tact_id_c | new | varchar(255) (proposal) | - | |
| Incident.ProdId ("Product") | menu, hierarchical (Product tree); required on new/edit; also "required for solved"=True | Categorisatie | Cases.product_c | new | dropdown, cascading with category | REST needed (`ShowParent=False`: leaf shown without parent) | Core Cases has no product. Rules set Product, Category and Disposition together with fixed ids. See Q5. |
| Incident.CatId ("Categorie") | menu, hierarchical (Category tree); required on new/edit | Categorisatie | Cases.category_c | new | dropdown | REST needed | See Q5. |
| Incident.DispId ("Reden") | menu (Disposition), required on new/edit | Categorisatie | Cases.disposition_c | new | dropdown | REST needed | Label is "Reden" (XML `LabelText`). |
| Incident.Assigned ("Toegewezen") - account part | staff account (OSC stores `account||group`) | Doorzetten | Cases.assigned_user_id | core | - | - | Core. Screenshot shows "group > account". A rule excludes 4 hard-coded accounts or groups from the menu. |
| Incident.Assigned - group part | staff group | Doorzetten | Cases.assigned_group_c ? (or SecuritySuite group) | new | to be decided | Group list: REST needed | See Q6. Rules assign a fixed group and account (Finance button) and queue by group. |
| Incident.QueueId ("Wachtrij") | menu (Queue) | Doorzetten | Cases.queue_c | new | enum | REST needed | SuiteCRM has no queues. Rules set queue by button and by group. See Q6. |
| Incident.Status.Id ("Status") | menu (Status), required (screenshot) ; status types (Unresolved/Solved/...) | Status | Cases.state + Cases.status | core | - | Core `case_state_dom` (Open, Closed) and `case_status_dom` (Open_New, Open_Assigned, Closed_Closed, Open_Pending Input, Closed_Rejected, Closed_Duplicate). OSC values: REST needed | Rules use status ids 1, 2, 3, 8 and 103. Meaning of each id is not in the XML (Q4). Each OSC status needs a (state, status) pair. |
| Incident.Created ("Datum gemaakt") | datetime, read-only | Status | Cases.date_entered | core | - | - | Import: set `date_entered` explicitly. |
| Incident.Updated ("Laatst bijgewerkt op") | datetime, read-only | Status | Cases.date_modified | core | - | - | |
| Incident.RelDue ("Eerste reactie verwacht", label inferred from field order in screenshot) | datetime, read-only | Status | Cases.sla_response_due_c | new | datetime | - | Label and meaning inferred (Q9). Calculated by the OSC SLA engine. |
| Incident.C$IndicatedSolveDate ("Verwachte oplosdatum") | custom date or datetime (calendar icon, screenshot) | Status | Cases.expected_resolution_date_c | new | date (proposal) | - | Type unverified. |
| Incident.C$CollectBeforeShare | custom boolean (XML `BooleanRenderView=Dropdown`); no label | Status, hidden unless Org id 612 (rule) | Cases.collect_before_share_c | new | bool | Yes/No | Customer-specific (one organisation). See Q10. |
| Incident.C$CollectBeforeShareMenu | custom menu ; no label | Status, hidden unless the bool above = 1 | Cases.collect_before_share_opt_c | new | enum | REST needed | Customer-specific. See Q10. |
| Incident.InterfaceId | menu (interface) | Details > Details | - | drop | - | - | Proposed: one SuiteCRM instance. Confirm that all incidents come from one interface (Q11). |
| Incident.LangId | menu (language) | Details > Details | - | drop | - | - | Proposed drop. Cases has no language field. Confirm (Q11). |
| Incident.MailboxId | menu (mailbox) | Details > Details | - | drop | - | - | Mailbox that received the mail. SuiteCRM inbound email handles routing separately. Keep in OSC. |
| Incident.Source | menu (channel/origin); value 3001 triggers the self-service banner | Details > Details | Cases.case_origin_c | new | enum | REST needed | Core Cases has no source field (the `source_dom` list exists in app lists, but no Cases field uses it). Needed for the self-service rule. |
| Incident.C$EventOpmerking | custom text, single line | Details > Details | Cases.event_remark_c | new | varchar(255) (proposal) | - | Label not in XML (Q8). Possibly a duplicate of the next field. |
| Incident.C$EventOpmerkingen | custom text, multiline (XML: Multiline=True, height 60) | Details > Details | Cases.event_remarks_c | new | text | - | Same note. |
| Incident.C$TfsProjectId | custom text, single line | Koppelingen > Projecten | Cases.tfs_project_id_c | new | varchar(50) (proposal) | - | The workspace builds a link to an internal TFS work-item URL from this value. See Q12. |
| Incident.InitialSoln | datetime (Info panel) | Info | - | drop | - | - | Proposed drop. Needed only for SLA reports. Confirm (Q2). |
| Incident.LastResp | datetime (Info panel) | Info | - | drop | - | - | Derivable from the last AOP case update. |
| Incident.Closed | datetime (Info panel) | Info | Cases.date_closed_c | new | datetime | - | Core Cases has no closed date. Needed for resolution reports. |
| Incident.CreatedBy | staff account (Info panel) | Info | Cases.created_by | core | - | - | Core. |
| Incident.RuleCtx.Escllevel | integer ? (rule-engine context) | Info | - | drop | - | - | OSC rule-engine state. Not data. |
| Incident.RuleCtx.State | rule-engine state | Info | - | drop | - | - | Same. |
| Incident thread (RichIncidentThread) | child object IncidentThread: entry type, channel, text, author, time | Berichten | AOP_Case_Updates (name, description, internal, contact_id, assigned_user_id, date_entered) | core | - | - | See section 2.1. |
| IncidentThread.EntryType | menu: values seen in XML are CUST_PROXY, NOTE, STAFF. A customer entry also exists (thread default "CustomerEntry"). Complete list: REST needed | Berichten | AOP_Case_Updates.entry_type_c | new | enum | CUSTOMER, STAFF, NOTE, CUST_PROXY (to confirm) | Core has only `internal` (bool) and `contact_id`. |
| IncidentThread.Channel | menu (default for customer entry: Phone) | Berichten | AOP_Case_Updates.channel_c | new | enum | REST needed | Gap in core. |
| Incident file attachments | FileAttachments control | Bijlagen | Notes (relationship `case_notes`) | core | - | - | Core. Attachments linked to a thread entry in OSC: confirm (Q13). |
| Incident audit log | IncidentAuditLog control | Details > Geschiedenis | Cases audit (`audited` = true) | core | - | - | Core. OSC history is not imported unless requested (Q13). |

**Counts (rows in this table):** core 12 | new 24 | drop 7 | total 43.
New includes the import keys `osc_id_c` and `osc_ref_no_c`.

### 2.1 Message thread vs. AOP_Case_Updates

| OSC thread concept | SuiteCRM (AOP) | Gap |
|---|---|---|
| Entry, customer (STAFF absent, customer author) | AOP_Case_Updates with `contact_id` set, `internal` = 0 | Core handles this for portal and inbound email. |
| Entry, staff response | AOP_Case_Updates, author = user, `internal` = 0 | Core: AOP emails the contact when a staff update is saved. Check the template flow (Q14). |
| Entry, private note | AOP_Case_Updates with `internal` = 1 | Core. |
| Entry, customer proxy (staff logs what the customer said, e.g. by phone) | No equivalent. | New `entry_type_c`. |
| Entry channel (Phone, Email, ...) | No equivalent. | New `channel_c`. |
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
| Wachtend | Status.Id := 3 | Set status to "waiting" equivalent | custom action (one click) |
| Bijgewerkt (rule "Beantwoord") | Status.Id := 8 | Set status | custom action |
| Onopgelost | Status.Id := 1 | Set status | custom action |
| Opgelost | Status.Id := 2, then SaveAndClose | Set state Closed, save | custom action |
| Rule "optioneel" statuses visibility (rule 13) | Show Opgelost, Bijgewerkt, Wachtend when status in {empty, 2, 1, 8, 103} | Show status buttons by current status | custom (display logic) |
| Doorsturen Finance | Set Product, Category, Disposition to fixed values, Status := 2, assign a fixed group and account, then Forward | One-click "route to Finance": set fields, assign, close | custom action |
| CX Oplossen | Fixed Product, Category, Disposition; Assigned := current user; Status := 2; SaveAndClose | One-click "solve as CX" | custom action |
| eGain Oplossen | Same, other fixed product and category values; solved; SaveAndClose | One-click "solve as eGain" | custom action |
| Unlabelled "Oracle case" button (screenshot: "assign to me") | Fixed Product, Category, Disposition; Assigned := current user (no close) | One-click "take case, preset taxonomy" | custom action |
| Unlabelled "eGain case" button (screenshot: "assign to me") | Same, with the eGain values | Same | custom action |
| SPAM | Set **Contact** type := 14 (spam), Queue := 24, Status := 2, relax required fields, Product := 545, SaveAndClose | Mark as spam: updates the contact type **and** the case | custom action (writes to Contacts) |
| GEEN SPAM | Clear Contact type, Queue := 2, Status := 1, SaveAndClose | Undo spam | custom action |
| CX Support | Open an external OSC login URL in the browser tab, focus tab | Link to external URL | custom (link) or drop |
| Navigation buttons (4) | Focus tab: Supportcontract, Bijlagen, Co Browsing; Browser tab (map for contact address) | Anchor to subpanel or tab; map: `jjwg_Maps` is installed, uses contact address | core / custom |
| Klant / Reactie / Prive | Add thread entry with standard text 40 as CUST_PROXY / STAFF / NOTE | "Add update" with type, optional blank text | custom (needs `entry_type_c`) |
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
| Kopie van Verberg Doorgeven Behandelaar (active) | Editor loaded | Source = 3001 | Show banner "Let op! Selfservicedeskmelding. Alle reacties zijn zichtbaar voor de aanmelder." ; otherwise hide | Field display logic or Angular banner on `case_origin_c` |
| Nieuwe regel, status buttons (active) | Editor loaded, Status changed | Status in {empty, 2, 1, 8, 103} | Show Opgelost, Bijgewerkt, Wachtend | Button visibility by status |
| Nieuwe regel, assigned menu (active) | Editor loaded | none | Remove 4 hard-coded accounts or groups from the Assigned menu | Filter on user/group list (custom); IDs not copied here |
| Nieuwe regel, queue by group (active) | Assigned changed | Group = one specific group | Queue := 19 | AOW workflow or logic hook (before save) |
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
| Incident -> Contact (primary) | Field CId | New relate field `primary_contact_c` (1:N Contacts -> Cases) | Core `contacts_cases` is M:N. Keep it for additional contacts. |
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
9. **Case number format** (`YYMMDD-NNNNNN` in the screenshot) differs from the core integer counter.
10. **Contact type changed from the case** (SPAM button writes Contact.CtypeId). Needs a cross-module action.

## 7. Open questions

Numbered for reference. **Top 5** (the answers block the design) are marked **TOP**.

1. **Q1 (TOP).** OSC REST access: the connection is not set up. Every row marked `?` or "REST needed" needs a metadata call (`metadata-catalog/incidents` and menu values for Status, Severity, Product, Category, Disposition, Queue, Source, EntryType, Channel and each custom menu).
2. **Q2 (TOP).** SLA: is the SLA engine in scope? SuiteCRM has none. Options: (a) drop SLA and keep the three date fields as plain data, (b) build a simple SLA module, (c) buy or use AOW timers. Do the agents work to the response and solve dates today?
3. **Q3 (TOP).** Primary contact: add a 1:N `primary_contact_c` relate field (our proposal), or reuse core `contact_created_by`? The screenshot marks Contact as required.
4. **Q4 (TOP).** Status and priority mapping: what are the OSC status names, ids and status types (1, 2, 3, 8, 103 appear in rules) and the severity list? Which become Open and which Closed? Does the business accept replacing `case_status_dom` and `case_priority_dom`?
5. **Q5 (TOP).** Product, Category, Disposition: how deep are the trees, how many nodes, are Product and Category independent or cascading? Option A: dropdowns with cascading values. Option B: use `AOS_Products` and `AOS_Product_Categories` (category parent exists). Option C: a custom taxonomy module. Needs node counts and the architect's decision.
6. **Q6.** Assignment and queues: how are group and queue used in practice? Is a SuiteCRM security group the right counterpart of an OSC staff group? Are queues a separate concept (routing/reporting) or a view on the group?
7. **Q7.** `Support$SupportPriority`: what object or menu is behind "Klantprioriteit"? Is it one customer-level value copied onto the incident?
8. **Q8.** Lengths of Subject, Tact ID, EventOpmerking, TfsProjectId, and the labels and purpose of `C$EventOpmerking` and `C$EventOpmerkingen` (two similar fields).
9. **Q9.** Confirm that `RelDue` = "Eerste reactie verwacht" and `C$IndicatedSolveDate` = "Verwachte oplosdatum". I inferred this from the field order in the screenshot. Confirm the type of `C$IndicatedSolveDate` (date or datetime).
10. **Q10.** `C$CollectBeforeShare` and its menu: customer-specific (shown only for one organisation). Migrate, or keep as a note?
11. **Q11.** Confirm drop for InterfaceId, LangId, MailboxId, InitialSoln, LastResp. Does the new system need multi-language or multi-interface incidents?
12. **Q12.** What is `Support$Project`? Is it the same as a TFS work item? Does the TFS link stay (keep a text field and a link), or is TFS replaced?
13. **Q13.** Import scope: the full thread history, attachments and audit log, or only the latest N months? Do attachments hang on thread entries?
14. **Q14.** Email behaviour: on staff response, does SuiteCRM AOP send to the contact in the same way? Needed for "Verzenden" and "Verzenden en sluiten". Also how the inbound mailbox creates cases.
15. **Q15.** Out-of-scope confirmation: smart assistant, decision trees, co-browse, OSN, KB propose and best answer, service opportunity button.
16. **Q16.** Text of the standard texts (ids 10, 20, 33, 40, 41, 341, 371). The XML holds only ids. Export them from OSC (Standard Text list) into email templates.
17. **Q17.** Which workspace rules stay as UI logic (angular display logic) and which become AOW or logic hooks (architect)?
18. **Q18.** What do reports 100641, 100672, 100622 (Supportcontract, Verkoopkansen) show? Columns and the object they read.

## 8. Privacy notes on the inputs

- The **screenshot** shows real data: a staff member's name in "Toegewezen", an incident number, a customer contact name, and the full text
  of a vendor notification email. Nothing from it was copied here. Do not use the screenshot in mockups or artifacts without blurring.
- The **XML** has no incident records. It does contain: one inactive rule that names a customer organisation and a person
  (not copied), hard-coded OSC ids for staff accounts, groups, one organisation (612) and one contact, an internal TFS URL, and a link to an
  internal spreadsheet whose file name suggests it holds customer site logins. Treat that link as sensitive; do not follow or copy it.
