# OSC Contact -> SuiteCRM Contacts

Status: **DRAFT for user review.** Source: workspace export `designs/screens/contacts/tkc-contact.xml`,
one screenshot, `readme.md`, SuiteCRM vardefs. OSC REST was **not** available. Field types, lengths
and menu values of custom fields are **not verified** and are listed in section 5.
Raw parse output: `data/osc/workspace/contacts.json` (gitignored).

Export: Oracle Service Cloud 26C SP1, client 26.8.0.298, workspace type `Contact`. Workspace labels
are Dutch.

## 1. Workspace summary

Record types in the export: Contact, Org, Incident, Opportunity, Task, Asset, Interaction.

### Tabs

| # | Tab (label key) | Content |
|---|---|---|
| 1 | Overzicht (`SUMMARY_LBL`) | Field table, 2 columns x 13 rows (section 2). Summary panel on the first tab. |
| 2 | Verkoopkansen (`OPPORTUNITIES_LBL`) | Grid `OpportunitiesView`, report 9030, row count shown. |
| 3 | Opmerkingen (`NOTES_LBL`) | `ContactNotes` control. |
| 4 | Taken (`TASKS_LBL`) | Grid `Tasks`, report 9016, search report 8012, row count shown. |
| 5 | Incidenten (`INCIDENTS_LBL`) | Grid `IncidentView`, report 9029, row count shown. |
| 6 | Onderzoeken (`SURVEYS_LBL`) | Grid `SurveyHistoryView`, report 10012. |
| 7 | Outreach-activiteit (`OUTREACH_ACTIVITY_LBL`) | Grid `MarketingActivityView`, report 10009. |
| 8 | Bijlagen (`ATTACHMENTS_LBL`) | `FileAttachments` control. |
| 9 | Controlelogboek (`AUDIT_LOG_LBL`) | `ContactAuditLog`, report 9050. |
| 10 | Nieuw tabblad 1 (custom) | Embedded browser control. URL is a PHP page on the OSC site (`agentinfoproxy.php`) that gets the agent session ID. Purpose unknown. |

The export does not contain the column lists of the report grids. They are in the OSC reports
9030, 9016, 8012, 9029, 10012, 10009, 9050 (see section 5).

### Info bar (header)
`Contact.CId`, `Created`, `Updated`, `RuleState`.

### Ribbon (HOME tab) and quick access toolbar
- Save group: Save, Save and close.
- Actions group: New, Refresh, Appointment, Print, Copy, Delete, Reset password.
- Proofing: Spell check.
- Links and info: Editor links (one link, rightnow.com), Info.
- Quick access toolbar: New, Save, Save and close, Print, Bookmark.
- The screenshot also shows "Accepteren" (Accept) and "Afwijzen" (Reject). They are **not** in the
  XML. They are generic assignment buttons of the OSC console, not part of this workspace.

### Workspace rules
**None** in the export (no `Rule` elements). `RuleState` appears only in the info bar. If the
OSC site has workspace rules for Contact, they must be exported separately (open question 7).

## 2. Field mapping

Legend: `core` = standard SuiteCRM field, `new` = we add it, `drop` = not migrated.
OSC type column: "unverified" means the XML does not state the type. "Boolean (dropdown)"
means the XML has `BooleanRenderView="Dropdown"`: the field is a Yes/No field shown as a dropdown.
It is shown with the value "[Geen waarde]" (no value) in the screenshot. So it may allow an empty
value (OSC booleans are normally Yes/No; confirm via REST).
API names for custom fields follow the pattern `Contact.CustomFields.c.<name>`; the exact
spelling and case come from the REST metadata (open question 1).

| OSC field (API name) | OSC type | Workspace location | SuiteCRM module.field | Exists? | New field type/len | Dropdown values | Notes |
|---|---|---|---|---|---|---|---|
| `Contact.Name.First` | Text (standard) | Overzicht r0 c0, required on new and edit | Contacts.`first_name` | core | | | varchar(100). |
| `Contact.Name.Last` | Text (standard) | Overzicht r1 c0, required on new and edit | Contacts.`last_name` | core | | | varchar(100). Required in SuiteCRM too. |
| `Contact.Emails` (control `Email`) | Email address list (standard) | Overzicht r2 c0 | Contacts.`email1` (primary) via `email_addresses` relation | core | | | See section 4. The control shows the primary address with an address-type dropdown. Per-address flags (opt-out, invalid) map to the SuiteCRM per-address flags. |
| `Contact.Phones` (control `PhOffice`, label "Mobiele telefoon", default phone type 1) | Phone list (standard) | Overzicht r3 c0 | Contacts.`phone_mobile` | core | | | The label is Dutch for "Mobile phone", the field id is Office phone. TKC uses it as mobile. Other phone types (office, home, fax) are in OSC but not on this workspace: `phone_work`, `phone_home`, `phone_fax`. See open question 3. |
| `Contact.Address` (control `Addr`) | Address (composite, standard) | Overzicht r4-5 c0, 2 rows high | Contacts.`primary_address_street`, `_city`, `_state`, `_postalcode`, `_country` | core | | | Composite OSC control edited in a popup. Sub-fields (street, city, province, postal code, country) are standard OSC fields. |
| `Contact.State` (label "Staat", required in screenshot) | Unverified | Overzicht r0 c1 | `tkc_contact_state_c` (tentative) | new | enum, varchar(100) | Not known | Screenshot shows the value as a chip with an "x", so it may be multi-select. Meaning unclear: "Staat" can mean state/province or record state. Not guessed. Open question 2. |
| `Contact.OrgId` | Integer, link to Organization (standard) | Overzicht r1 c1, report 8001 (search) | Contacts.`account_id` / `account_name` | core | | | Relationship `accounts_contacts`. |
| `Contact.SalesAcctId` (label "Verkoper") | Link to Account (staff) | Overzicht r2 c1 | Contacts.`assigned_user_id` | core | | | OSC staff account becomes SuiteCRM user. Needs an OSC account to user mapping in the import. Confirm that "Verkoper" is the owner (open question 6). |
| `Contact.Title` | Text (standard) | Overzicht r3 c1 | Contacts.`title` | core | | | |
| `Contact.Login` (label "Aanmelding") | Text (standard, portal login) | Overzicht r4 c1 | none | drop | | | Customer portal login. SuiteCRM customer portal is out of scope. In the screenshot the value equals the email address. Confirm (open question 5). |
| `SlaContainer` (label "SLA's") | Relationship control (Contact to SLA instance) | Overzicht r5 c1 | none | drop | | | SuiteCRM has no SLA entity on Contacts. SLAs are an Incident topic. See section 4. |
| `Contact.CustomFields.c.oscusergroup` (label "OSC Usergroup") | Boolean (dropdown) | Overzicht r6 c0 | `tkc_osc_usergroup_c` | new | bool | Yes / No (to be verified) | User group flag (member of OSC user group). Needs business confirmation. |
| `Contact.MaOptIn` (label "Opt-in overal toepassen") | Boolean (standard marketing field) | Overzicht r6 c1 | Contacts.`email_opt_out` (inverted), plus `lawful_basis` where a consent basis is needed | core | | | "Apply opt-in everywhere". Inverse of `email_opt_out`. Decision needed: also store in `lawful_basis` (open question 8). |
| `Contact.CustomFields.c.kmmdeelname` (label "KMM") | Boolean (dropdown) | Overzicht r7 c0 | `tkc_kmm_c` | new | bool | Yes / No (to be verified) | KMM participation ("deelname" = participation). |
| `Contact.CustomFields.c.functieniveau` (label "Functie niveau") | Unverified (not a boolean, no dropdown flag; screenshot shows a dropdown) | Overzicht r7 c1 | `tkc_function_level_c` | new | enum, varchar(100) | **Not known** | Probably a menu (function level). Values from REST. Open question 1. |
| `Contact.CustomFields.c.tkccafedeelname` (label "TKC Cafe") | Boolean (dropdown) | Overzicht r8 c0 | `tkc_tkc_cafe_c` | new | bool | Yes / No (to be verified) | TKC Cafe participation. |
| `Contact.CustomFields.c.language` (label "Taal") | Unverified (menu, screenshot shows value "Nederlands") | Overzicht r8 c1 | `tkc_language_c` (or core `preferred_language` if one exists in the vardefs; not found in Contacts vardefs) | new | enum, varchar(10) | **Not known** | Screenshot shows a dropdown with one language value. Full list from REST. |
| `Contact.CustomFields.c.servicetomorrow` (label "Service Tomorrow") | Boolean (dropdown) | Overzicht r9 c0 | `tkc_service_tomorrow_c` | new | bool | Yes / No (to be verified) | |
| `Contact.CustomFields.c.nieuwsbrief` (label "Nieuwsbrief") | Boolean (dropdown) | Overzicht r9 c1 | `tkc_newsletter_c` | new | bool | Yes / No (to be verified) | Newsletter subscription. Screenshot shows value "Nee" (No). Related to marketing consent: see open question 8. |
| `Contact.CustomFields.c.freshworksusergroup` (label "Freshworks Usergroup") | Boolean (dropdown) | Overzicht r10 c0 | `tkc_freshworks_usergroup_c` | new | bool | Yes / No (to be verified) | |
| `Contact.CtypeId` (label "Type contactpersoon") | Menu (standard contact type) | Overzicht r10 c1 | `tkc_contact_type_c` | new | enum, varchar(100) | **Not known** | Core has no contact type. `lead_source` has a different meaning. Values from REST. |
| `Contact.CustomFields.c.ert` (label "ERT") | Boolean (dropdown) | Overzicht r11 c0 | none (proposed) | drop | | | Name suggests a test or experiment field. Confirm with the user (open question 4). |
| `Contact.CustomFields.c.test` (label "Test") | Boolean (dropdown) | Overzicht r12 c0 | none (proposed) | drop | | | Name "Test". Likely a test field. Confirm (open question 4). |
| `Contact.CId` (info bar) | Integer ID (standard) | Info bar | `osc_id_c` (rule: every imported record keeps its OSC key) | new | int(11) or varchar(36), indexed | | Import key for idempotent re-imports. Contact ID and Contact reference number may differ; use the numeric `ID`. |
| `Contact.Created` | DateTime (standard) | Info bar | Contacts.`date_entered` | core | | | Import sets it explicitly. |
| `Contact.Updated` | DateTime (standard) | Info bar | Contacts.`date_modified` | core | | | |
| `Contact.RuleState` | System field | Info bar | none | drop | | | Rule engine state. Not data. |

Counts: **core 11** (first, last, email, phone, address, org, sales rep, title, opt-in, created, updated),
**new 11** (State, 8 custom fields incl. Functieniveau/Language, contact type, `osc_id_c`),
**drop 5** (Login, SLA, ERT, Test, RuleState).
Detail: new = State, Oscusergroup, KMM, TKC Cafe, Service Tomorrow, Freshworks Usergroup,
Functieniveau, Language, Nieuwsbrief, CtypeId, `osc_id_c`.

Existing custom vardefs in `public/legacy/custom/Extension/modules/Contacts/Ext/Vardefs/`: only
`jjwg_maps_*` overrides (Google Maps module). No conflict with the proposed names.

### OSC standard Contact fields that exist but are NOT on this workspace
Not in the export, so not mapped: second and third email, other phone types, mailing address
subfields as separate fields, channel IDs (chat, social), `ContactType`-related fields, the
`Opted-out` flags, last-contact dates, and other custom fields not placed on the workspace.
The readme mentions "channel IDs" and "communication preferences". Section 5 asks whether the
import must cover these.

## 3. Actions / buttons mapping

| OSC action | SuiteCRM action | Level |
|---|---|---|
| Save | Edit view Save | core |
| Save and close | Save, then return to list/detail | core (Save, then Cancel; or the "Save" returns to detail) |
| New | Create record | core |
| Refresh | Browser refresh / Angular reload of the record | core |
| Appointment | Schedule Meeting / Schedule Call quick action on the Contact | core (Meetings, Calls subpanels or quick actions) |
| Print | Print / export to PDF from detail view | core (PDF template optional) |
| Copy | Duplicate record | core |
| Delete | Delete record | core |
| Reset password | Customer portal password reset | drop (follows the `Login` drop). Open question 5. |
| Spell check | Browser spell check | drop |
| Editor links, Info | none | drop |
| Bookmark (QAT) | Favorites | core (SuiteCRM 8 favorites) |
| Accept / Reject (screenshot only) | Assignment queue accept/reject | drop for Contact. Belongs to Incident queue work. |
| Browser tab "Nieuw tabblad 1" | Embedded iframe tab | drop until its purpose is known (open question 9). Would be custom if kept. |

No workspace rules exist in the export, so no workflow mapping for rules.

## 4. Relationships

| OSC relation | SuiteCRM relationship | Notes |
|---|---|---|
| Contact to Organization (`OrgId`) | `accounts_contacts` (Accounts subpanel on Contacts: `account_id`) | core. Many contacts to one account in the Contact record. SuiteCRM also allows several accounts per contact; the import uses one. |
| Contact to Opportunities (tab Verkoopkansen) | `opportunities_contacts` | core subpanel "Opportunities". |
| Contact to Incidents (tab Incidenten) | `contacts_cases` | core subpanel "Cases". OSC Incident requester is one Contact. In SuiteCRM Cases has `account_id`, and the Contacts relation is many to many. |
| Contact to Tasks (tab Taken) | `contact_tasks` | core subpanel "Tasks". |
| Notes (tab Opmerkingen) | `contact_notes` | core subpanel "History/Notes". OSC notes are a thread of entries; map to Notes records. |
| Attachments (tab Bijlagen) | `contact_notes` with file, or Documents (`documents_contacts`) | core. Choose one (open question 10). |
| Surveys (tab Onderzoeken) | none | drop. Survey history is out of scope unless the user wants it. |
| Outreach activity (tab Outreach-activiteit) | `contact_campaign_log` (Campaign Log) | core relation; data would come from OSC marketing activity (probably not imported). |
| Audit log (tab Controlelogboek) | SuiteCRM audit (`contacts_audit` table, Audit tab in detail view) | core. History is not imported; OSC audit stays in OSC. |
| SLAs (`SlaContainer`) | none | drop. SuiteCRM has no SLA model. If SLA is required, treat as a Cases/Accounts topic in a later story. |
| Contact to staff account (`SalesAcctId`) | `assigned_user_link` (`assigned_user_id`) | core. |
| Multiple emails | `contacts_email_addresses` and `contacts_email_addresses_primary` via `email_addresses` | core. Primary plus extra addresses; each address has own opt-out and invalid flags. |
| Multiple phones | Fields `phone_mobile`, `phone_work`, `phone_home`, `phone_fax`, `phone_other` | core. One value per type; a second number of one type has no home. If OSC needs more, add custom fields (open question 3). |
| Channel IDs (chat, social) | none | Not on workspace. If needed: new fields `tkc_chat_id_c`, `tkc_social_id_c` (varchar 255), or a new related module. Open question 3. |

## 5. Open questions for the user

REST access would answer questions 1, 2, 6 and parts of 3 and 8 (metadata and menu values).

1. **Custom field types, lengths and menu values.** Not verifiable now. Needed from REST
   (`metadata-catalog/Contact` and the menu definitions): exact API names, and the data types of
   `functieniveau`, `language`, `CtypeId`, the "Staat" field, and the nine boolean flags
   (does an empty value exist besides Yes/No?). Dropdown lists will be copied from REST, not guessed.
2. **What is "Staat" (State)?** It is required in the screenshot and shown as a chip with the value
   "Service". Is it the contact status, the address state/province, or a custom field?
   Is it single or multi select? Does it drive other behavior?
3. **Phones, emails and channel IDs.** The workspace shows only one email slot and one phone slot
   (labelled "Mobile"). Do we import the other emails, phone types (office, home, fax) and the
   chat/social channel IDs from the readme? SuiteCRM covers emails (relation) and four phone types;
   channel IDs need new fields.
4. **Drop ERT and Test?** Both look like test fields. Confirm they are not used.
5. **Customer portal.** `Login` and the Reset password button imply portal accounts. Is a customer
   portal in scope for SuiteCRM? Proposal: no (drop).
6. **"Verkoper" (`SalesAcctId`).** Confirm it is the record owner. Needs an OSC staff account to
   SuiteCRM user mapping; which staff accounts are migrated?
7. **Workspace rules.** None in this export. Does the OSC site have Contact workspace rules or
   business rules (for example on opt-in or on contact type)? If yes, export them.
8. **Consent model.** How do `MaOptIn` ("apply opt-in everywhere"), `Nieuwsbrief` (newsletter) and
   per-address opt-out relate to each other? Proposal: `MaOptIn` to `email_opt_out` (inverted) and
   `lawful_basis`; `Nieuwsbrief` a separate custom flag. Needs Legal/security-officer input (GDPR).
9. **"Nieuw tabblad 1" browser tab.** What does `agentinfoproxy.php` show? Keep as an iframe tab or drop?
10. **Attachments and notes.** Import OSC attachments (files)? Which of Documents or Notes+file? Which volume?
11. **Report grid columns.** The columns of reports 9030, 9016, 8012, 9029, 10012, 10009, 9050 are not in
    the export. Needed to design the subpanel columns.

## 6. Notes for design and next steps
- The summary tab is 23 controls in two columns. 10 of them are TKC custom fields (mostly Yes/No flags
  on TKC programs: KMM, TKC Cafe, Service Tomorrow, user groups). Candidate for one grouped panel
  "TKC programs and groups" in the mockup.
- Screenshot: it shows a record with real contact data (name, email address, login) in the form fields.
  Not copied here. See the report to the user.
- Next step: user review of this draft, then REST metadata (when access is ready) to fill the
  "Not known" cells, then the ux-designer builds the mockup.
