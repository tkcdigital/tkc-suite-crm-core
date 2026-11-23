# OSC Contact -> SuiteCRM Contacts

Status: **PARTIALLY verified with REST 2026-11-20 (tst1).** Customer names are replaced by placeholders; the real values are in OSC and in data/osc/rest-output/ (gitignored). Types, lengths (custom fields), nullability and all menu values are verified (4 batch runs). Still open for REST: the lengths of the standard `name` and `address` sub-fields (see section 5, "Still open").
Source: workspace export `designs/screens/contacts/tkc-contact.xml`, one screenshot, `readme.md`, SuiteCRM vardefs.
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
spelling and case come from the REST metadata (verified by REST 2026-11-20, see 2a-1).

| OSC field (API name) | OSC type | Workspace location | SuiteCRM module.field | Exists? | New field type/len | Dropdown values | Notes |
|---|---|---|---|---|---|---|---|
| `Contact.Name.First` | Text, standard; length not verified (sub-schema `contacts/name` not read) | Overzicht r0 c0, required on new and edit | Contacts.`first_name` | core |  |  | varchar(100). |
| `Contact.Name.Last` | Text, standard; length not verified (sub-schema `contacts/name` not read) | Overzicht r1 c0, required on new and edit | Contacts.`last_name` | core |  |  | varchar(100). Required in SuiteCRM too. |
| `Contact.Emails` (control `Email`) | Email list (verified): item = address (string 255, not null), addressType (menu, not null), invalid (bool), certificate | Overzicht r2 c0 | Contacts.`email1` (primary) via `email_addresses` relation | core |  |  | Address types (verified): 0 E-mailadres - Primair, 1 Alternatief e-mailadres 1, 2 Alternatief e-mailadres 2. Only `invalid` is per address. There is **no per-address opt-out** in OSC: opt-in is on `marketingSettings` (see MaOptIn). Primary goes to `email1`, the two alternatives to extra `email_addresses` rows. SuiteCRM address length 255: fits. |
| `Contact.Phones` (control `PhOffice`, label "Mobiele telefoon", default phone type 1) | Phone list (verified): item = number (string 40, not null), phoneType (menu, not null). Default type 1 = Mobiele telefoon | Overzicht r3 c0 | Contacts.`phone_mobile` | core |  |  | Phone types (verified): 0 Telefoon werk, 1 Mobiele telefoon, 2 Fax/telefoon, 3 Telefoon assistent, 4 Telefoon privé. The workspace slot is type 1 (mobile): `phone_mobile`. Other types map to `phone_work` (0), `phone_fax` (2), `phone_other` (3), `phone_home` (4) if the owner wants them (question 3). SuiteCRM phone length 100: fits. |
| `Contact.Address` (control `Addr`) | Address (composite, standard); sub-field lengths not verified (sub-schema `contacts/address` not read) | Overzicht r4-5 c0, 2 rows high | Contacts.`primary_address_street`, `_city`, `_state`, `_postalcode`, `_country` | core |  |  | Composite OSC control edited in a popup. Sub-fields (street, city, province, postal code, country) are standard OSC fields. |
| `Contact.State` (label "Staat", required in screenshot) | **3 booleans** `cRMModules.marketing`, `.sales`, `.service` (verified in schema: all not nullable). No `state` property exists on Contact. Screenshot chip "Service" fits | Overzicht r0 c1 | `tkc_crm_modules_c` | new | multienum (3 items), or 3 `bool` fields | Marketing, Sales, Service (fixed set from the REST structure) | Confirmed by schema as the only 3-value structure that fits "Staat"; the business meaning (module access of the contact) is still for the owner to confirm. Default in the XML is not shown. Question 2. |
| `Contact.OrgId` | Integer, link to Organization (standard) | Overzicht r1 c1, report 8001 (search) | Contacts.`account_id` / `account_name` | core | | | Relationship `accounts_contacts`. |
| `Contact.SalesAcctId` (label "Verkoper") | Link to Account (staff) | Overzicht r2 c1 | Contacts.`assigned_user_id` | core | | | OSC staff account becomes SuiteCRM user. Needs an OSC account to user mapping in the import. Confirm that "Verkoper" is the owner (open question 6). |
| `Contact.Title` | Text, standard (verified: string 80, nullable) | Overzicht r3 c1 | Contacts.`title` | core |  |  | SuiteCRM `title` varchar(255): fits. |
| `Contact.Login` (label "Aanmelding") | Text, standard (verified: string 255, nullable) | Overzicht r4 c1 | none | drop |  |  | Customer portal login. SuiteCRM customer portal is out of scope. In the screenshot the value equals the email address. Confirm (open question 5). |
| `SlaContainer` (label "SLA's") | Relationship control (Contact to SLA instance) | Overzicht r5 c1 | none | drop | | | SuiteCRM has no SLA entity on Contacts. SLAs are an Incident topic. See section 4. |
| `Contact.CustomFields.c.oscusergroup` (label "OSC Usergroup") | Boolean (verified), nullable | Overzicht r6 c0 | `tkc_osc_usergroup_c` | new | bool | Yes / No. Tst1 counts (7981 contacts): empty 7749, No 191, Yes 41 | Needs business confirmation. Nullable in REST. Tst1 data has three states (see counts). SuiteCRM `bool` has no empty state: import maps empty to No. |
| `Contact.MaOptIn` (label "Opt-in overal toepassen") | Boolean (verified: `marketingSettings.marketingOptIn`, nullable). `surveyOptIn` also exists (not on workspace) | Overzicht r6 c1 | Contacts.`email_opt_out` (inverted), plus `lawful_basis` where a consent basis is needed | core |  |  | "Apply opt-in everywhere". Inverse of `email_opt_out`. Decision needed: also store in `lawful_basis` (open question 8). |
| `Contact.CustomFields.c.kmmdeelname` (label "KMM") | Boolean (verified), nullable | Overzicht r7 c0 | `tkc_kmm_c` | new | bool | Yes / No. Counts: empty 7028, Yes 828, No 125 | KMM participation. Nullable in REST. Tst1 data has three states (see counts). SuiteCRM `bool` has no empty state: import maps empty to No. |
| `Contact.CustomFields.c.functieniveau` (label "Functie niveau") | **Menu** (verified: id + lookupName), nullable | Overzicht r7 c1 | `tkc_function_level_c` | new | enum, varchar(10) | 297 C, 298 M, 299 O (3 items; the values are single letters, the meaning is not in OSC). Counts: C 308, M 1251, O 982, empty 5440 | Menu is verified. The letters have no explanation in the metadata: owner to give the labels (question 13). |
| `Contact.CustomFields.c.tkccafedeelname` (label "TKC Cafe") | Boolean (verified), nullable | Overzicht r8 c0 | `tkc_tkc_cafe_c` | new | bool | Yes / No. Counts: empty 7317, Yes 614, No 50 | TKC Cafe participation. Nullable in REST. Tst1 data has three states (see counts). SuiteCRM `bool` has no empty state: import maps empty to No. |
| `Contact.CustomFields.c.language` (label "Taal") | **Menu** (verified: id + lookupName), nullable, label "language" | Overzicht r8 c1 | `tkc_language_c` (or core `preferred_language` if one exists in the vardefs; not found in Contacts vardefs) | new | enum, varchar(10) | 288 Nederlands, 289 Engels (2 items). Counts: Nederlands 7966, Engels 15, empty 0 | SuiteCRM Contacts has no language field in core vardefs. Keys proposal: `nl`, `en`. Every contact has a value in tst1. |
| `Contact.CustomFields.c.service_tomorrow` (label "Service Tomorrow") | Boolean (verified), nullable | Overzicht r9 c0 | `tkc_service_tomorrow_c` | new | bool | Yes / No. Counts: empty 6725, Yes 1226, No 30 | Nullable in REST. Tst1 data has three states (see counts). SuiteCRM `bool` has no empty state: import maps empty to No. |
| `Contact.CustomFields.c.nieuwsbrief` (label "Nieuwsbrief") | Boolean (verified), nullable | Overzicht r9 c1 | `tkc_newsletter_c` | new | bool | Yes / No. Counts: No 6857, Yes 1121, empty 3 | Newsletter subscription. Almost never empty: import empty as No. Related to marketing consent: question 8. |
| `Contact.CustomFields.c.freshworks_usergroup` (label "Freshworks Usergroup") | Boolean (verified), nullable | Overzicht r10 c0 | `tkc_freshworks_usergroup_c` | new | bool | Yes / No. Counts: empty 7977, No 4, Yes 0 | Used on only 4 contacts and never Yes. Candidate to drop (question 4). |
| `Contact.CtypeId` (label "Type contactpersoon") | Menu (verified: `contactType`, NamedID, nullable), 14 items | Overzicht r10 c1 | `tkc_contact_type_c` | new | enum, varchar(10) | 1 TKBC Prospect, 2 TKBC Klant, 3 TKBC support, 4 TKBC sales, 5 SSC Prospect, 6 SSC Klant, 7 SSC Support, 8 SSC Sales, 9 [customer G] - klant, 10 [customer H] Klant, 11 [customer H] Leverancier, 12 FS, 13 TKC - Bodybuildert, 14 SPAM. Counts: empty 7503, 1: 41, 2: 43, 3: 8, 4: 1, 5: 1, 6: 1, 10: 7, 12: 3, 13: 8, 14 (SPAM): 365. Items 7, 8, 9, 11 are unused | Value 14 = SPAM is the value that the incident SPAM button sets. 365 contacts are SPAM in tst1: the owner decides if they are imported (question 14). |
| `Contact.CustomFields.c.ert` (label "ERT") | Boolean (verified), nullable | Overzicht r11 c0 | none (proposed) | drop |  | Yes / No. Counts: empty 7975, Yes 3, No 3 | Set on 6 contacts only. Proposed drop stays (question 4). |
| `Contact.CustomFields.form.test` (package `form`, label "Test") | Boolean (verified), nullable | Overzicht r12 c0 | none (proposed) | drop |  | Counts: empty 7981 (all) | No contact has a value. Drop confirmed by data. |
| `Contact.CId` (info bar) | Integer ID (standard) | Info bar | `osc_id_c` (rule: every imported record keeps its OSC key) | new | varchar(20), indexed (OSC ID integer up to 9223372036854775807) |  | Import key for idempotent re-imports. Contact ID and Contact reference number may differ; use the numeric `ID`. **Status: built (SUITE-71).** See `docs/data-model.md` § OSC import key. |
| `Contact.Created` | DateTime (standard) | Info bar | Contacts.`date_entered` | core | | | Import sets it explicitly. |
| `Contact.Updated` | DateTime (standard) | Info bar | Contacts.`date_modified` | core | | | |
| `Contact.RuleState` | System field | Info bar | none | drop | | | Rule engine state. Not data. |

Counts (recounted, 27 rows): **core 11** (first, last, email, phone, address, org, sales rep, title, opt-in, created, updated),
**new 11** (State, 8 custom fields incl. Functieniveau/Language, contact type, `osc_id_c`),
**drop 5** (Login, SLA, ERT, Test, RuleState).
Detail: new = State, Oscusergroup, KMM, TKC Cafe, Service Tomorrow, Freshworks Usergroup,
Functieniveau, Language, Nieuwsbrief, CtypeId, `osc_id_c`.

Existing custom vardefs in `public/legacy/custom/Extension/modules/Contacts/Ext/Vardefs/`: only
`jjwg_maps_*` overrides (Google Maps module). No conflict with the proposed names.

### 2a. REST findings (tst1, 2026-11-20)
Method: `GET contacts?limit=1` and `GET contacts/<id>` for one record, parsed in `data/osc/rest-output/`.
Only key names and JSON types were read. The list endpoint is `contacts`; ROQL table is `Contacts`
(`Contact` fails). Test environment holds 7981 contacts.

- **Collection name** is `contacts`. `metadata-catalog/Contact` gives 404; `metadata-catalog/contacts`
  gives only links. The field schema needs `Accept: application/schema+json`, which `osc-get.sh` cannot send.
- **API names corrected** (REST differs from the XML-derived guess): `service_tomorrow`,
  `freshworks_usergroup` (with underscore). `test` and `test2` are in package `form`
  (`CustomFields.form.test`), not `c`. `ert` is `c.ert`.
- **Custom fields in package `c`** (all present on the sample): `custid`, `contact_survey_text`,
  `deelname_event` (JSON bool), `kmmdeelname`, `kmmbedeelname`, `oscusergroup`, `tkccafedeelname`,
  `service_tomorrow`, `kmm_en`, `language` (object `id` + `lookupName`: **menu field**, confirmed),
  `deelnemer_st`, `functieniveau`, `freshworks_usergroup`, `ert`, `favoriete_drank`, `andere_suggesties`,
  `reden_geen_komst`, `rn_client`, `nieuwsbrief` (JSON bool), `events`, `secure_id`, `force_update` (JSON int),
  `cp_first_widget`, `support_contact_type`. Fields that are null on the sample show no type.
  Not on the workspace and not mapped: `custid`, `contact_survey_text`, `deelname_event`, `kmmbedeelname`,
  `kmm_en`, `deelnemer_st`, `favoriete_drank`, `andere_suggesties`, `reden_geen_komst`, `rn_client`,
  `events`, `secure_id`, `force_update`, `cp_first_widget`, `support_contact_type`, `form.test2` (question 12).
- **Standard structures** present: `name{first,last}`, `emails` (collection), `phones` (collection),
  `address{street,city,postalCode,stateOrProvince,country}`, `channelUsernames` (collection),
  `contactType` (NamedID, null on sample), `organization`, `salesSettings{salesAccount,acquiredDate}`,
  `marketingSettings{marketingOptIn,surveyOptIn,emailFormat,contactLists,marketingOrganizationName,...}`,
  `source` (NamedID hierarchy), `login`, `disabled`, `title`, `externalReference`, `fileAttachments`, `notes`,
  `serviceSettings{sLAInstances,...}`. `cRMModules{marketing,sales,service}` are three JSON bools.
- **"Staat" (schema-confirmed as `cRMModules`, 2026-11-20; business meaning open):** the screenshot chip value "Service" fits `cRMModules`
  (Marketing / Sales / Service module access flags). The workspace field id is `State`; REST has no
  `state` property on Contact. If confirmed it is three booleans, not a menu. Proposal: three bool fields
  or one multienum `tkc_crm_modules_c` (marketing, sales, service). Needs the user to confirm.
- **Emails, phones, channels:** item structure and type values are now verified (section 2a-1 and the table rows).
- **Consent in REST:** `marketingSettings.marketingOptIn`, `marketingSettings.surveyOptIn`,
  `custom nieuwsbrief` (bool). `MaOptIn` on the workspace corresponds to `marketingOptIn`.
  Per-address flags: only `invalid` exists per email address (verified 2026-11-20); there is no per-address opt-out.

### 2a-1. REST verification 2026-11-20 (schemas, menus, counts)
Method: `metadata-catalog/contacts` and `.../contacts/customFields/c` with `Accept: application/schema+json`, menu resources `namedIDs/contacts/...` and `.../customFields/c/<field>` (the menu URL form `namedIDs/<object>/customFields/c/<field>` works), and aggregate ROQL (`GROUP BY` counts). No contact record was read. Raw files: `data/osc/rest-output/s/` and `v/` (gitignored). tst1 holds 7981 contacts.
- Standard properties verified: `title` string 80, `login` string 255, `externalReference` string 20, `disabled` bool, `name` object (not null), `emails` item (address 255, addressType, invalid), `phones` item (number 40, phoneType), `channelUsernames` item (channelType, userNumber 40, username 255), `marketingSettings` (`marketingOptIn`, `surveyOptIn`, bools, nullable), `cRMModules` (3 bools, not null), `contactType` menu, `organization`.
- Channel types (`channelTypes`, 15 items): 1 CSS e-mail, 2 MA e-mail, 3 Telefoon, 4 Fax, 5 Boeken, 6 CSS web, 7 MA web, 8 Chat, 9 E-mail, 10 Community, 11 Twitter, 12 YouTube, 13 RSS, 14 Facebook, 16 SRM Engage. Not on the workspace.
- Yes/No flags are **nullable** and tst1 data has three states (empty, Yes, No) for every flag. Counts are in the table rows.
- Custom fields **not on the workspace** (types verified): `custid` string 10, `contact_survey_text` string 1333, `deelname_event` bool, `kmmbedeelname` bool, `kmm_en` bool, `deelnemer_st` bool, `favoriete_drank` menu, `andere_suggesties` string 1333, `reden_geen_komst` menu, `rn_client` bool ("Is RightNow Mailbox?"), `events` menu, `secure_id` string 255, `force_update` integer, `cp_first_widget` menu, `support_contact_type` menu (326 Manager, 327 Medewerker), `form.test2` bool. Values of `favoriete_drank`, `reden_geen_komst`, `events`, `cp_first_widget` were not read. Proposal: drop all (data minimisation) until the owner names a field (question 12).

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

REST is done for this entity (section 2a-1) except two sub-schemas (see "Still open" below).

1. **Custom field types, lengths and menu values.** CLOSED by REST 2026-11-20 (section 2a-1). Flags are nullable.
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
- Next step: user review of the verified mapping and the open questions, then the ux-designer builds the mockup.

### Still open after REST (2026-11-20)
REST-answerable: lengths of `name.first`, `name.last` and the `address` sub-fields (`metadata-catalog/contacts/name` and `.../contacts/address` not read; SuiteCRM `first_name`/`last_name` varchar(100) and address fields are likely long enough, not verified).
Business questions added by the REST data:
12. **Fields not on the workspace.** 16 custom fields exist only in REST (section 2a-1). Confirm drop for all, or name those to keep (for example `support_contact_type` Manager/Medewerker).
13. **Function level.** The menu `functieniveau` has the values C, M, O. What do they mean (labels for the SuiteCRM list)?
14. **SPAM contacts.** 365 contacts have type SPAM (14) in tst1. Import them, skip them, or import without activity? Contact type values 7, 8, 9, 11 are unused: migrate the whole list of 14 or only the used values?
15. **Flags with three states.** Import empty as No (proposal), or keep a three-state dropdown?
