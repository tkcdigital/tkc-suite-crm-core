# Customization feasibility assessment — TKC SuiteCRM 8.10

> Planning issue: SUITE-2 · Phase 2 (Assessment) · Author: architect · Date: 2026-11-20 · Status: **Approved 2026-11-20** (D1–D12 approved by the owner)
> Scope: what we can change in SuiteCRM 8.10 (fork of SuiteCRM-Core) to replace Oracle Service Cloud (OSC), how we change it, and at what cost and risk.
> Method: every claim comes from code in this repository. Each claim has a path. "Not verified" means the claim was not confirmed in code.
> Related: OSC field mappings in [`docs/osc-mapping/`](../osc-mapping/) (DRAFT, REST not yet available).

## 1. Summary

### Easy (configuration or metadata, Level 1–2)
- New fields of all common types, dropdowns, relationships, labels.
- List columns, filters, subpanels, panels and tabs on the record view.
- Required, read-only and conditional visibility rules. The Angular UI supports `displayLogic` and field `logic` in metadata.
- Record buttons in the Angular UI. These are metadata (`recordActions`), and an optional backend process handler can run the action.
- Dropdown values, menus and module visibility.
- Accounts, Contacts, Cases and Opportunities render fully in the new Angular UI (`config/services/module/module_routing.yaml`). The four "fields + redesign" screens therefore use the metadata-driven path.

### Hard (extension code, Level 3–4, or functional gaps)
- **A global restyle of the Angular UI.** The core theme is compile-time SCSS. It has no CSS custom properties: there are 0 `var(--` hits in `core/app/shell/src/themes/suite8`. A token-based theme needs an extension mechanism, and we must prove that mechanism in a spike (§9, S1).
- **Two UIs, two themes.** Home dashboard, Emails, Quotes detail, Calendar, Studio, most Admin pages and global search results render in the legacy layer inside an iframe. Each design token must go to two theme targets.
- **OSC concepts that SuiteCRM does not have:** SLA engine, queues, per-profile workspaces, and product/category hierarchy on Incidents. Each one needs custom design.
- **Data import.** Bean-based import fires logic hooks that can email customers (§8.2).
- **Custom modules.** Module Builder deploys into `public/legacy/modules/`, which is outside `custom/`. This conflicts with the current rule (§9, D11).

### Not possible without a core patch (or not supported in 8.10)
| Item | Why (code reference) |
|---|---|
| Change the core Angular SCSS variables in place | Variables live in `core/app/shell/src/themes/suite8/css/abstracts/_variables.scss`. They compile into the shell bundle (`core/angular/base-angular.json` → `styles`). Work-around candidates: §9 S1. |
| A separate **edit** layout in the Angular record view | `RecordViewDefinitionHandler::addPanelDefinitions()` builds panels only from the detail view defs. `editviewdefs` adds only `required` and `readOnly` (`core/backend/ViewDefinitions/LegacyHandler/RecordViewDefinitionHandler.php:546-623, 786-807`). Work-around: `displayLogic` with `modes`. |
| Role-based or per-profile layouts (OSC workspaces per profile) | The view handlers load one layout per module (`RecordViewDefinitionHandler::getDetailViewDefs()`, `:270-289`). No role key found. |
| Smarty `customCode` in the Angular record view | `customCode` occurs only in `core/backend/ViewDefinitions/LegacyHandler/RecordView/RecordViewReadOnlyMapper.php`, where it marks the field read-only. It does not render. |
| An Angular Home dashboard | `home` is `index/list/record: false` (`module_routing.yaml:3-6`). No Angular dashboard view exists. A new one is Level 3 and size L, not a configuration change. |
| A structural change to the login page | The login component (`core/app/core/src/lib/views/login/components/login/`) has no component registry. Only the logo image and CSS can change. |
| Remove the "Supercharged by SuiteCRM" notice | This is a licence condition: AGPLv3 §7(b), stated in every core file header. It is not a technical limit. |

## 2. Customization levels in this codebase

| Level | Mechanism in this repo | Paths | Build / repair steps | In git? |
|---|---|---|---|---|
| **1 — Admin / Studio** | Legacy Admin and Studio (ModuleBuilder) in the legacy iframe. The Admin index is Angular (`core/app/shell/src/app/app-initializer.ts:148-167`); sub-pages are legacy. | The DB (`config`, `fields_meta_data`, `acl_roles`, `aow_workflow`…) and files that Studio writes into `public/legacy/custom/` | none, or Quick Repair | DB settings: **no** → record them in `docs/admin-guide.md`. Studio files in `custom/`: yes. |
| **2 — Metadata in `public/legacy/custom/`** | Legacy extension framework (vardefs, language, layoutdefs, relationships, logic-hook registration) plus full viewdef overrides | `custom/Extension/modules/<M>/Ext/{Vardefs,Language,Layoutdefs,LogicHooks}/`, `custom/Extension/application/Ext/{Language,TableDictionary}/`, `custom/modules/<M>/metadata/*.php` | Admin → Repair → **Quick Repair and Rebuild** (all modules runs `repairDatabase.php` → `DBManager::repairTable` + `DynamicField::repairCustomFields`), then `php bin/console cache:clear` | Yes. The compiled `custom/modules/*/Ext/` and `custom/application/Ext/` are ignored (`public/legacy/.gitignore`), so **every environment must run Repair after deploy**. |
| **3 — Extension code in `extensions/tkc/`** | Symfony: classes under `App\Extension\` are autowired and autoconfigured (`config/core_services.yaml:204-206`, `composer.json` autoload `"App\\Extension\\": "extensions/"`). Tagged interfaces auto-register: process handlers, view-config mappers, record validators, schedulers, statistics providers (`core_services.yaml:103-190`). Config: `extensions/*/config/**` loads **after** core `config/services/**` (`config/services.yaml:31-58`). Angular: a module-federation remote (`extensions/defaultExt/app/webpack.config.js`) that `ExtensionLoader` loads before routes register (`core/app/core/src/lib/services/extensions/extension-loader.service.ts`; `app-initializer.ts:78-79`). Migrations: `extensions/<ext>/Migrations`, namespace `App\Extension\<ext>\Migrations` (`core/backend/DependecyInjection/Migrations/ExtensionMigrationsExtension.php`). | `extensions/tkc/{app,backend,config,Migrations}` | `yarn merge-angular-json` (`core/yarn/plugins/merge-angular-json.js`, deep-merges `core/angular/base-angular.json` + `extensions/**/app/angular.json`), then `yarn build:extension tkc` (`package.json:13`), then `php bin/console cache:clear`. The cache warmer copies `extensions/*/public` → `public/extensions/` (`core_services.yaml:237-241`, `ExtensionAssetCopy.php:56-67`). | Yes |
| **4 — Legacy custom code** | Logic hooks, custom controllers and views, legacy theme CSS, legacy dashlets | `custom/Extension/modules/<M>/Ext/LogicHooks/`, `custom/modules/<M>/{views,controller.php}`, `custom/themes/suite8/css/Dawn/style.css`, `custom/include/` | Quick Repair; the theme CSS is appended at runtime (`include/SugarTheme/SugarTheme.php:1039-1041`) | Yes |
| **5 — Core patch** | Edit in `core/**` or `public/legacy/**` outside `custom/` | — | full rebuild | Yes, marked `// TKC-PATCH <id>` and listed in `docs/architecture.md` § Core patches |

Notes:
- No Symfony console command for Quick Repair exists in core. The `AsCommand` list in `core/backend` has only install, upgrade, version, asset and scheduler commands. The rule text "`php bin/console` repair equivalents" refers to something that does not exist yet. See D10.
- `yarn merge-angular-json` exists as a Yarn plugin (`.yarnrc.yml`). The root `angular.json` is generated and gitignored.

## 3. Change-type matrix

Effort: **S** < 1 day, **M** 1–3 days, **L** > 3 days (per item, after the scaffold exists). Upgrade risk: **L**ow / **M**edium / **H**igh.

| # | Change type | Level | How (paths / mechanism) | Effort | Upgrade risk | Notes |
|---|---|---|---|---|---|---|
| 1 | New field: varchar, int, decimal, bool, text | 2 | `custom/Extension/modules/<M>/Ext/Vardefs/tkc_<field>.php` + `…/Ext/Language/en_us.tkc_<field>.php` → Quick Repair | S | L | Main-table field (D2). In Angular, `int`, `float`, `phone`, `url` have no `.edit` component. They fall back to `varchar.edit` (`core/app/core/src/lib/fields/base-field.registry.ts:96`; map in `base-fields.manifest.ts:263-365`). Validators exist for int, float, phone and email (`services/record/validation/validators/`). |
| 2 | New field: dropdown (enum) | 2 | Vardef `type => enum`, `options => tkc_<list>_list`. List in `custom/Extension/application/Ext/Language/en_us.tkc_<list>.php` | S | L | Angular `enum`, `enum-radio`, `enum-chips`, `enum-dropdown` (`base-fields.manifest.ts:316-332`) |
| 3 | New field: multienum | 2 | Same as #2 with `type => multienum` | S | L | Angular `multienum.*` includes filter (`:336-339`). The value is stored as one text column, which limits reporting and indexing. |
| 4 | New field: relate (lookup) | 2 | Vardef id field + `relate` field (`id_name`, `module`). Add a `link` for a real relationship (#13). | S–M | L | Angular `relate.*` (`:305-308`) |
| 5 | New field: currency | 2 | `type => currency` + `currency_id` | S | L | Angular `currency.*` (`:297-299`) and logic `field-logic/currency-conversion/`. Base currency is an open question in the backlog (EUR). |
| 6 | New field: date / datetime | 2 | `type => date` or `datetimecombo` | S | L | `datetimecombo` maps to `datetime` (`config/services/record/fields/fields_metadata_map.yaml:3-5`) |
| 7 | Required / read-only | 2 | Vardef `required`, or editviewdefs `displayParams.required/readOnly`, which merge into the Angular record view (`RecordViewDefinitionHandler.php:786-807`) | S | L | |
| 8 | Conditional visibility / required / value (OSC workspace "Rules": Required, Hidden, Show, Value) | 2 | Cell `displayLogic` in viewdefs (example: `public/legacy/modules/Contacts/metadata/detailviewdefs.php:225-253`). Vardef `logic` (example: `modules/Documents/vardefs.php:59-79`) with actions in `core/app/core/src/lib/fields/field-logic/` (required, readonly, updateValue, updateValueBackend, setEmpty). Panel `displayLogic` (`components/panel-logic/panel-logic.manager.ts:57-65`). | M | M | The metadata format belongs to SuiteCRM 8. `displayType` is marked `@DEPRECATED` (`field-logic/display-type/field-logic-display-type.action.ts:38-40`), so expect format drift between minor versions. OSC "Focus" and "Execute" have no metadata equal: use a record action or skip them. |
| 9 | Custom validation | 3 | Front end: `ValidationManager.registerFieldSaveValidator()` (`services/record/validation/validation.manager.ts:135-180`). Back end: a service that implements `RecordValidatorInterface` (tag `record.validators.record`, `core_services.yaml:163`). Async: viewdefs `asyncValidators` (`RecordViewDefinitionHandler.php:776-779`). | M | L | Put every rule in the back end too. Client-only validation is not a control. |
| 10 | Dependent dropdown (parent → child; for example the OSC Product → Category) | 2 + 3 | The Angular enum supports `dynamic` + `parentenum` (`fields/base/base-enum.component.ts:277-295`). The `dynamicenum → enum dynamic:true` map is **commented out** upstream ("to be enabled in future", `fields_metadata_map.yaml:6-10`). We must enable it in `extensions/tkc/config/` or set `dynamic`/`parentenum` in the vardef. | M | M | **Spike S2.** For a deep or data-driven hierarchy, use a relate to a new `tkc_` module instead. |
| 11 | Dropdown values (core lists) | 1 / 2 | Prefer `custom/Extension/application/Ext/Language/en_us.tkc_<list>.php`. Studio's dropdown editor also works (it writes into `custom/include/language/`, which is versioned). | S | L | Do not rename **keys** of core lists that code reads, for example Case `state`/`status` (AOP updates status, `CaseUpdatesHook.php:292`) and `sales_stage_dom`. Add or relabel keys only. |
| 12 | Field label rename | 2 | `custom/Extension/modules/<M>/Ext/Language/en_us.tkc_labels.php` | S | L | |
| 13 | New relationship | 1 / 2 | Studio, or hand-written: `custom/Extension/application/Ext/TableDictionary/`, `custom/metadata/tkc_<rel>MetaData.php`, link vardefs, `Ext/Layoutdefs` → Quick Repair | M | L | Name it `tkc_<lhs>_<rhs>`. Commit every file that Studio writes. |
| 14 | New custom module | 1 (Module Builder) | Module Builder deploys into `public/legacy/modules/<key>_<Name>/` + `custom/Extension/application/Ext/Include/`. A visible module that is not in `module_routing` renders in **Angular** by default (`core/backend/Navbar/LegacyHandler/NavbarHandler.php:615-639`). | M–L | M | The location is outside `custom/`, but the directory is new and does not overlap upstream. It needs a rule exception (D11). The legacy layer loads modules only from `public/legacy/modules/`. |
| 15 | List view columns | 2 (or 3) | `custom/modules/<M>/metadata/listviewdefs.php` (full override). Alternative: a `ViewConfigMapperInterface` for view `list` (`ListViewDefinitionHandler.php:233`). | S | M | A full-copy override freezes upstream changes to that file. |
| 16 | Filters / search fields | 2 | `custom/modules/<M>/metadata/searchdefs.php`, `SearchFields.php`. Angular reads them through `FilterDefinitionProvider` (`ListViewDefinitionHandler.php:260`). | S | L | |
| 17 | Detail / edit layout (panels, tabs, columns) | 2 (or 3) | `custom/modules/<M>/metadata/detailviewdefs.php`: `panels`, `templateMeta.tabDefs[*].newTab`, `maxColumns`, `colClasses` (`RecordViewDefinitionHandler.php:426-432`). Delta alternative: `ViewConfigMapperInterface`, view `record` (`:288`, `ViewConfigMappers.php:84-93`). | M | M | One layout serves detail, edit and create (§1). The copy **must keep** the SuiteCRM 8 keys of the upstream file: `topWidget`, `sidebarWidgets`, `recordActions` (example: Accounts `detailviewdefs.php:103-171`). Spike S3 (2026-11-20): a Studio save keeps `topWidget`, `sidebarWidgets`, `recordActions`, other widgets and cell `displayLogic`/`logic`, but **drops panel-level `tabDefs[*].displayLogic**` (`GridLayoutMetaDataParser.php:515-535`). Studio is allowed with a diff check (see `docs/architecture.md`). |
| 18 | Record header, actions, buttons (OSC ribbon / quick-access toolbar) | 2 + 3 | Viewdefs `recordActions.actions` / `.exclude`, merged with the global defaults `module.recordview.actions` (`config/services/module/recordview/actions.yaml`; `core/backend/Data/Service/RecordActionDefinitionProvider.php:54-67`). An `asyncProcess: true` action calls a backend `ProcessHandlerInterface` with type `record-<key>`. Pattern: `core/backend/Process/Service/RecordActions/AbortEmailMarketing.php:45,79-119`. Put the class in `extensions/tkc/backend/…`; autoconfigure tags it (`core_services.yaml:104-105`). Front-end-only handler: `RecordActionManager.addHandler()` (`services/actions/base-action-manager.service.ts:67-75`). Header component swap: `RecordHeaderRegistry`. Widgets: `topWidget`, `sidebarWidgets`, `headerWidgets`, `bottomWidgets` (`RecordViewDefinitionHandler.php:223-226`). | M | L–M | Each process handler **must** implement `getRequiredACLs()` correctly. This is a security-officer check item. |
| 19 | Mass (bulk) actions | 2 + 3 | Listviewdefs `bulkActions` + `module.listview.bulk_action` (`config/services/module/listview/bulk_actions.yaml`) + a process handler (examples: `core/backend/Process/Service/BulkActions/*.php`) | M | L | |
| 20 | Line / table actions in lists | 3 (config) | `config/services/module/listview/{line_actions,table_actions}.yaml`, overridden as a delta in `extensions/tkc/config/*.php` (D5) | S | M | |
| 21 | Subpanels | 2 | `custom/Extension/modules/<M>/Ext/Layoutdefs/`, `custom/modules/<M>/metadata/subpanels/`. Angular reads them through `SubPanelDefinitionHandler`. Top buttons and line actions: `config/services/module/subpanelview/*.yaml` | S–M | L | |
| 22 | Dashboards / dashlets | 4 (legacy) / 3 | The Home dashboard is legacy (`module_routing.yaml:3-6`): legacy dashlets in `custom/modules/<M>/Dashlets/`. On Angular record views: chart, statistics and history widgets (Accounts example `:103-152`) and new statistics through `StatisticsProviderInterface` (`core_services.yaml:124-125`). | M (record widgets) / L (Angular home) | M | An Angular home page is new product work, not configuration. |
| 23 | Workflows (AOW) | 1 | Admin → Workflow. The list is Angular and the editor is legacy (`module_routing.yaml:215-218`). The hook is registered in `custom/Extension/application/Ext/LogicHooks/AOW_WorkFlow_Hook.php`. | S per workflow | L | Stored in the DB, not in git. Record each workflow in `docs/admin-guide.md`, or seed it with a script (§7). |
| 24 | Logic hooks | 4 | `custom/Extension/modules/<M>/Ext/LogicHooks/tkc_<name>.php` + class in `custom/modules/<M>/` or `custom/include/TKC/` | S–M | L | Hooks fire for Angular saves too: `RecordHandler` calls `$bean->save()` (`core/backend/Data/LegacyHandler/RecordHandler.php:386`). Do not edit the installer-generated `custom/modules/<M>/logic_hooks.php`. |
| 25 | Custom field type / component (Angular) | 3 | Inject `FieldRegistry` in `ExtensionModule` and call `register(module, type, mode, Component)` or `registerField(module, field, mode, Component)` (`fields/base-field.registry.ts:52-58`). Other overridable registries: `NavbarRegistry`, `SidebarWidgetRegistry`, `RecordHeaderRegistry`, `ChartRegistry` (`common/components/registry/base-component.registry.ts`). | M | M | The extension compiles against `core` dist as a shared singleton (`extensions/defaultExt/app/webpack.config.js:137-141`). Each upstream upgrade needs a rebuild and can break the build. |
| 26 | Global theme, Angular UI (colours, type, spacing) | 3 (spike) / 5 | The core SCSS has no CSS variables. Options: (a) an extension `app/angular.json` adds a TKC SCSS entry to the **shell** `styles` through the json-merger deep merge (`merge-angular-json.js:52-58`; base styles at `core/angular/base-angular.json:28-30`). (b) The `ExtensionModule` loads a global stylesheet at runtime. (c) Patch `_variables.scss` (Level 5). | M–L | M | **Spike S1.** Most core variables have no `!default` (`_variables.scss:140-144`). A TKC entry file can import core abstracts, override variables, then import the component partials, but this tracks the import list of `style.scss`. |
| 27 | Legacy UI theme (iframe screens) | 4 | `public/legacy/custom/themes/suite8/css/Dawn/style.css`. `SugarTheme::getCSSURL()` **appends** it after the core sub-theme CSS (`include/SugarTheme/SugarTheme.php:1036-1041`). The core is SCSS with ~731 variables (`themes/suite8/css/Dawn/variables.scss`). | M | L | Write a targeted override file. Do not recompile the full theme into `custom/`, because the file is appended, not replaced, and the CSS would be doubled. |
| 28 | Login page / branding | 1 / 2 | Logo: image key `company_logo` (`components/logo/logo.component.html:28`), resolved through `themes.image_paths`, which includes `legacy/custom/themes/<theme>/images` (`config/services/themes/image_paths.yaml`). System name: `system_name` (`app-initializer.ts:76-77`). | S (logo) / L (layout) | L | No login registry. Layout changes are CSS only. Keep the AGPL "Supercharged by SuiteCRM" notice. Not verified: the path where Admin's logo upload writes. |
| 29 | Navigation / menus | 1 / 3 | Admin → Display Modules (`custom/include/tabConfig.php`, versioned). Navbar overrides: `config/services/navigation/navbar_overrides.yaml` (delta in the extension). Component swaps: navbar registries (`components/navbar/*.registry.ts`). Quick actions: `config/services/ui/quick_actions.yaml`. | S–M | L | |
| 30 | Roles / ACL / security groups | 1 | Admin → Roles; Security Suite. `acl-roles` is list-only Angular; `security-groups` is fully legacy (`module_routing.yaml:223-230`). | M | L | Stored in the DB. Script them for repeatable environments (§7). Not verified: field-level ACL. The role model is module/action level. |
| 31 | Email / case threads (OSC Incident thread) | 2 (+4) | AOP Case Updates render as the `record-thread` sidebar widget (`modules/Cases/metadata/detailviewdefs.php:100-199`). Items carry `internal` and `source` classes. Inbound email → case update: `CaseUpdatesHook::saveEmailUpdate()` (`modules/AOP_Case_Updates/CaseUpdatesHook.php:225`). Styles: `_record-thread.scss`, `_case-updates.scss`. | M | M | The OSC thread types (customer, agent, internal note) map to the `internal` flag + author. Attachment line items are already in the thread layout. |
| 32 | SLA / queues | 1 + 3 | **No SLA engine** in the code. Building blocks: the Business Hours module (`module_routing.yaml:91-94`), AOW time-based conditions, a Symfony scheduler (`SchedulerInterface`, tag `app.scheduler`, `core_services.yaml:139-140`). Queues: AOP distribution (`AOPAssignManager.php:48-96`), security groups, or a `queue_c` dropdown + list filters. | L | M | This is a **functional gap** against OSC. It needs a product-owner decision before design. |
| 33 | Search / global search | 1 / 2 | Global search goes to legacy `home/search` or `home/unified-search` (`services/navigation/global-search/global-search.service.ts:46-52`). Home is legacy, so the results page is legacy. Searchable fields: vardef `unified_search` + Admin search settings. | S | L | Only the legacy theme styles the results page. |
| 34 | Data import | 3 | See §6.4. Options: legacy Import wizard (manual, CSV); V8 REST (`public/legacy/Api/V8`, `config/services/legacy/api_paths.yaml`); a **TKC Symfony console command** that uses `LegacyHandler` + beans (recommended); direct SQL (not recommended). | L | L | Idempotent upsert on `osc_id_c`. Suppress notifications (§8.2). |
| 35 | REST / GraphQL API | 1 / 3 | V8 JSON:API (OAuth2) for integrations. The core API Platform GraphQL/REST under `/api` feeds the Angular UI. API Platform maps only `%kernel.project_dir%/core` (`config/packages/api_platform.yaml:26-27`). A TKC resource needs an extra mapping path from `extensions/tkc/config/packages/` (**not verified**: whether the paths merge). | M | M | Prefer a process handler (#18) over a new API resource. |

## 4. Which UI renders what (8.10)

Source: `config/services/module/module_routing.yaml`, consumed by `app-initializer.ts:189-362`. A false or missing flag falls through to `ClassicViewUiComponent`, which renders the legacy page in an iframe (`app-initializer.ts:364-414`; `route-converter.service.ts:200-210`). Visible modules that are not in the file default to full Angular (`NavbarHandler.php:622-633`).

| Area | List | Record (detail/edit) | Notes |
|---|---|---|---|
| Accounts, Contacts, Cases, Opportunities, Leads | Angular | Angular | Our four redesign screens: fully metadata-driven |
| Quotes (AOS_Quotes) | Angular | **Legacy** | `quotes: record: false` (`:175-178`). The line-item editor is legacy. |
| Emails | **Legacy** | **Legacy** | `emails: all false` (`:255-258`). An Angular record-modal compose definition exists (`modules/Emails/metadata/modalcomposeviewdefs.php`). Not verified: which entry points open it. |
| Home dashboard + global search results | **Legacy** | — | `home: all false` (`:3-6`) |
| Calendar, Maps, Security Groups | **Legacy** | **Legacy** | `:183-186, 223-226, 259-262` |
| Calls, Meetings, Project, Contracts, Invoices, Products, Reports, Workflow, Users, Employees, Roles, Schedulers, PDF/Email templates, Case Updates/Events | Angular | **Legacy** | `record: false` |
| Administration | Angular index (`AdminPanelComponent`, `app-initializer.ts:148-167`) + 11 Angular lists | Legacy (except Email Queue, Migration Tasks) | Page by page in §4.1 (spike S5) |
| Studio / Module Builder, Import wizard | Legacy | Legacy | Not in `module_routing` and not visible modules |
| Login, 2FA, logged-out | Angular | — | `app-initializer.ts:111-187` |
| New `tkc_*` custom modules | Angular | Angular | Default for visible modules |

**Effect on the design system**
1. Every token must be produced for **two** targets: the Angular shell theme (§3 #26) and the legacy Dawn override (§3 #27). One generator from `designs/system/tokens.json` must write both outputs in the same story (`design-system.md`).
2. Quotes detail, Emails, Home and global search can only be **restyled** (CSS). A "modernized view" for these screens is a legacy-theme job, not an Angular job.
3. The iframe boundary is visible: the legacy page has its own fonts, spacing and focus styles. Only consistent tokens hide the seam. Accessibility (WCAG 2.2 AA focus states) must be checked in both UIs.

### 4.1 Administration pages (spike S5, 2026-11-20 — traced in code, not checked in a browser)
How it works: the Admin panel (`adminpaneldefs.php`, via `AdminPanelDefinitionHandler.php:107`) converts each legacy URL to an Angular route (`RouteConverter.php:204-219`). A link is Angular only if `module_routing` has the matching flag; `administration/<Action>` falls through to the legacy iframe (`app-initializer.ts:364-404`). Admin-only modules that are not in `$moduleList` get no Angular routes (`NavbarHandler.php:619-633`).

| Section | Angular list (record legacy unless noted) | Legacy iframe |
|---|---|---|
| Users & Authentication | User Management, Role Management | Password Management, OAuth2 Clients, OAuth Keys, Security Group Management, Security Suite Settings |
| System | Scheduler | System Settings, Currencies, Languages, Locale, PDF Settings, Search Settings, Elasticsearch, Themes |
| Module Settings | — | Activity Streams, Business Hours, AOP Settings, Calendar Sync, Module Menu Filters, Connectors, Display Modules and Subpanels, History Subpanel, Releases, AOS Settings |
| Email | Inbound Email, Outbound Email, External OAuth Connections, External OAuth Providers, **Email Queue (list + record Angular)** | Email Settings, Campaign Email Settings |
| Admin Tools | **Manual Migration Tasks (list + record Angular)** | Repair, Backups, Diagnostic, Import Wizard, Module Loader |
| Developer Tools | Workflow Manager (editor legacy) | Studio, Rename Modules, Module Builder, Dropdown Editor |
| Google Suite | Address Cache | Maps Settings, Geocoded Counts, Geocoding Test, Geocode Addresses |

Result: 11 of 49 Admin links open an Angular list; 38 (about 78 %) open legacy pages, and almost all detail/edit/settings pages are legacy. **Theme scope for Admin = mainly the legacy Dawn override**; the Angular theme covers only the Admin cards and the standard list view. Recommendation: restyle Admin with tokens only — no Angular rebuild of Admin screens.

## 5. Per-screen assessment

Field counts come from the DRAFT mappings in `docs/osc-mapping/` (types and menu values are not yet verified via REST).

| Screen | OSC source | Fields core / new / drop | UI | Expected change types (§3 #) | Level | Risk | Effort |
|---|---|---|---|---|---|---|---|
| **accounts** | Organization ([mapping](../osc-mapping/organization.md)) | 6 / 22 / 4 | Angular list + record | New fields (1–6), dropdowns (2, 11; `account_type` relabel or new field), layout (17), list/filter (15, 16), ribbon → actions (18), subpanels (21). Hierarchy exists: `parent_name` "Member of". **6 OSC workspaces routed by relation type and Sales profile** → one layout with conditional panels (8) or custom view (25). Organization SLA: gap (32). Custom OSC objects (projects, support/licence contracts): scope decision. | 2, some 3 | M–H | M–L |
| **contacts** | Contact ([mapping](../osc-mapping/contact.md)) | 11 / 11 / 5 | Angular list + record | Mostly TKC Yes/No flags and dropdowns (1, 2), channel IDs / extra phones (1), multiple emails via the core email widget, consent fields, conditional logic (8), layout (17), actions (18). No workspace rules in the export. | 2, some 3 | M | M |
| **cases** | Incident ([mapping](../osc-mapping/incident.md)) | 12 / 24 / 7 | Angular list + record; thread in sidebar | Many fields; Product/Category/Disposition hierarchy (10, spike S2, or relate to a `tkc_` module); status/severity dropdowns with care for core keys (11); **48 workspace rules** → 8 / 18 / drop; **17 function buttons** that set several fields and save → 18 + process handlers; SPAM button writes to Contact (cross-module action); thread restyle + entry type/channel (31); queue/SLA (32); case number format (`osc_ref_no_c`); import with hooks that send email (§8.2) | 2 + 3 (+4) | **H** | **L** |
| **opportunities** | Opportunity ([mapping](../osc-mapping/opportunity.md)) | 12 / 10 / 2 | Angular list + record | Status menu with probability built in → `sales_stage_dom` relabel/add keys (11); second amount and actual close date (1, 5, 6); new Opportunity↔Cases relationship (13); "Auto Offerte Nr" button → process handler (18); core required fields differ from OSC (7) | 2 + 3 | L–M | M |
| **quotes** | — | — | Angular list; legacy record | Cosmetic: list columns (15) + legacy theme (27) for the detail and line items | 2 + 4 | L | S–M |
| **emails** | — | — | Legacy | Cosmetic: legacy theme (27) only. The email field on Angular records follows the Angular theme (`fields/email`). | 4 | L | S–M |
| **home-dashboard** | — | — | Legacy | Cosmetic: legacy theme (27), `suitep-base/dashboard*.scss` targets. An Angular dashboard is out of scope (§1). | 4 | L | M |

## 6. Database

### 6.1 How schema changes happen
- Vardefs (core + `custom/Extension/.../Vardefs`) → Quick Repair and Rebuild → `DBManager::repairTable()` → `repairTableParams()`. This compares columns and indices and **adds or alters** main-table columns (`public/legacy/include/database/DBManager.php:742-890`). Review the SQL that Repair shows before you execute it.
- `_cstm` columns: `DynamicField::repairCustomFields()` **adds missing columns only**. The code comment says: "We aren't checking for data types, just that the column exists" (`modules/DynamicFields/DynamicField.php:963-997`). It creates **no** indices besides the `id_c` primary key (`:873-916`).
- `_cstm` handling depends on the dictionary flag `custom_fields`. That flag becomes `true` **only** when `fields_meta_data` has rows for the module (`DynamicField.php:246-283`). The installer already created `jjwg_maps_*_c` Studio fields on Accounts, Cases, Contacts, Opportunities and others (`public/legacy/custom/Extension/modules/*/Ext/Vardefs/_override_sugarfield_jjwg_maps_*`), so `_cstm` tables exist there.

### 6.2 Recommendation: extension vardefs on the main table (not `_cstm`, not Studio fields)
| Option | Where the definition lives | Type change on Repair | Index support | Reproducible from git | Verdict |
|---|---|---|---|---|---|
| A. Studio custom field | `fields_meta_data` (DB) + `<table>_cstm` | no | no | **no** | Reject for TKC fields |
| B. Extension vardef, `source => custom_fields` | file + `_cstm` | **no** (add only) | **no** | yes, but it depends on the `custom_fields` flag from option A rows | Reject |
| C. Extension vardef, main table (no `source`) | file + main table | **yes** (MISMATCH → ALTER) | **yes** (`$dictionary[<Obj>]['indices']`) | yes | **Recommended** |

Reasons for C: one source of truth in git, real type and length management, indices for `osc_id_c` and filter fields, and no extra JOIN on every list query.
Cost of C: the columns sit in tables that the core owns. A future upstream column with the same name would collide. The `_c` suffix prevents this, because the core never uses `_c` on main tables. Not verified: that Repair never drops unknown columns. Only add and alter paths were seen in `repairTableParams()`.

### 6.3 Indexes and the import key
- Every imported module gets `osc_id_c` (varchar 36 or longer, depending on the OSC ID format) as a main-table field + a **non-unique** index `idx_<table>_osc_id_c`, declared in `custom/Extension/modules/<M>/Ext/Vardefs/tkc_osc_id.php`.
- Do not use a unique index. Soft-deleted rows (`deleted = 1`) would block a re-import. The import code enforces uniqueness (`WHERE osc_id_c = ? AND deleted = 0`).
- Add indices only for fields that are filtered or sorted in list views. Each index costs on insert during bulk import.

### 6.4 Doctrine migrations
- The core uses them for upgrade steps (`core/backend/Migrations/Version*.php`, run by `Install/Service/Upgrade/Steps/RunMigrations.php`). Example: a data-only, idempotent seed (`Version20260416120000.php`).
- Extensions are supported: `extensions/tkc/Migrations/`, namespace `App\Extension\tkc\Migrations` (`ExtensionMigrationsExtension.php:54-87`).
- **Decision:** do **not** use migrations for schema. Vardefs + Repair stay the only schema source. Two sources would drift, and Repair would try to "fix" what a migration did. **Do** use extension migrations for idempotent **data and config seeding** that must be the same in every environment: `config` table rows, scheduler rows, default roles or security groups, AOW workflow records. Each one must check before it inserts, as `Version20260416120000::seedTask()` does.

## 7. Settings

| Setting store | Examples | In git? | Action |
|---|---|---|---|
| `public/legacy/config.php` | DB connection, `site_url`, installer values | no, holds secrets | Generated per environment. Never read by agents (`secrets.md`). |
| `public/legacy/config_override.php` | Values saved from Admin: system settings, AOP (`$sugar_config['aop']`, `AOPAssignManager.php:66-96`), locale, currency display | no | Record in `docs/admin-guide.md`. Make repeatable with a versioned, secret-free template that a TKC CLI command merges (§9 D12). |
| `.env` / `.env.local` | `DATABASE_URL`, `APP_SECRET`, `SITE_URL` | `.env.local` no | Proton Pass → `pass-cli run` |
| DB tables | `config` (category/name/value), currencies, users, roles, security groups, AOW workflows, inbound/outbound email accounts, schedulers, AOP settings | no | Record in `docs/admin-guide.md`. Seed the reproducible ones through extension migrations or seed scripts (§6.4). Mail accounts and OAuth keys contain secrets: configure them by hand per environment. |
| Symfony parameters | `module_routing`, record/list actions, navbar overrides, UI limits (`config/services/**`) | yes, via `extensions/tkc/config/` | Override as a **delta** in PHP (D5). Do not copy the whole YAML. |
| Studio output files | `custom/modules/<M>/metadata/*.php`, `custom/include/language/*`, `custom/include/tabConfig.php` | yes | Commit them in the same story. |

**Must go into `docs/admin-guide.md`:** every Admin or Studio action that writes to the DB or `config_override.php`: system name, currency, locale, AOP settings, email accounts, schedulers, display modules, roles, security groups, AOW workflows, search settings, and PDF templates.

## 8. Limits and risks

### 8.1 Functional limits against OSC
| OSC feature | SuiteCRM 8.10 | Effect |
|---|---|---|
| Workspaces per profile (Organization: 6 workspaces routed by relation type + Sales profile) | One layout per module (§1) | All roles see the same layout. Use `displayLogic` and ACL to hide content, not per-role layouts. |
| Workspace rules (Focus, Execute, Invoke, Run, Fire, ConfigureMenuItems in `tkc-incidents.xml`) | Required/readonly/value/visibility are possible (§3 #8). Focus, menu configuration and named rule chains are not. | The osc-analyst must classify each rule as supported, record action, or drop. |
| SLA, milestones, queues | No engine (§3 #32) | Product decision needed |
| Product/Category hierarchy on Incident | Not in core Cases | `tkc_` module or a dependent dropdown (S2) |
| Ribbon, quick-access toolbar | `recordActions` (header buttons, overflow menu) | A button with server logic needs a process handler (L3) |
| Embedded browser controls (Replicon, SharePoint, agent info proxy) | No equivalent | Links built from fields; many external sites block embedding |

### 8.2 Data-import risks (security-officer gate)
- `CaseUpdatesHook::creationNotify()` emails the contact when a Case is related to a Contact. It skips only when `$_REQUEST['module'] === 'Import'` (`modules/AOP_Case_Updates/CaseUpdatesHook.php:434-451`). `closureNotify` fires `after_save` on closed cases (`custom/modules/Cases/logic_hooks.php:14,18`). **A script import can email real customers.** The import must run with outbound email disabled and with AOP notifications off, or with an explicit guard.
- The AOW workflow hook runs on every save (`custom/Extension/application/Ext/LogicHooks/AOW_WorkFlow_Hook.php`). This slows the import and can trigger workflows.
- `jjwg_maps` geocode hooks run `before_save` on Accounts, Contacts, Cases and others (`custom/modules/Cases/logic_hooks.php:12,15`). If the Maps geocoding scheduler is active, addresses (PII) can go to an external geocoding service. Not verified: the scheduler state. **Check it before any real-data import.**
- Import code logs counts and IDs only (`data-privacy.md`).

### 8.3 Upgrade risks
| Risk | Mitigation |
|---|---|
| A full-copy viewdef in `custom/modules/<M>/metadata/` hides all upstream changes to that file | Keep a list of overridden viewdefs in `docs/architecture.md`. At each upstream merge, diff the upstream file against our copy. Use a `ViewConfigMapper` delta where a full copy is not needed. |
| A Symfony parameter redefinition **replaces** the whole array | Use the PHP read-modify-write pattern from `extensions/defaultExt/config/extension.php:10-25` (D5) |
| The Angular extension is built against the `core` dist (shared singleton) | Rebuild and smoke-test the extension on every upstream merge. Keep the extension small and registry-based. |
| The json-merger change to `angular.json` (theme option a) depends on the structure of `base-angular.json` | Re-check at each merge. Spike S1 decides the option. |
| Studio and Module Builder write generated files | Commit them. Module Builder modules live outside `custom/` (D11). |
| Compiled `Ext/` files are not in git | Every deploy must run Repair (D10) |
| SuiteCRM 8 metadata keys change (`displayType` deprecated) | Pin a test per screen that loads the record metadata. |

### 8.4 PHP version
- `composer.json` requires `php >= 8.3` with platform `8.3`. The legacy pre-check says min `8.1.0`, recommended `8.2.0` (`public/legacy/php_version.php:8,13`). The effective floor is **8.3**, because vendor is locked to it. `LOCAL_SETUP.md` ("pins the platform to PHP 8.1.0") is out of date.
- Local PHP 8.5 is outside the composer platform. Deprecation output breaks API responses (`LOCAL_SETUP.md` Step 6b note).
- Not verified in code: the upstream maximum version. Use **PHP 8.3** in Docker for development and Azure.

### 8.5 Performance
- Each Angular metadata or record request bootstraps the legacy layer (`LegacyHandler::init/close`, for example `RecordViewDefinitionHandler.php:166-178`). Wide layouts and many widgets add cost.
- `_cstm` fields add a JOIN per query. Option C (§6.2) avoids this.
- A bean-based import is slow: hooks, AOW, geocode. Use batch commits, hook guards and indices on `osc_id_c`.

## 9. Recommendations

### Decisions (approved by the owner 2026-11-20)
| ID | Decision | Reason |
|---|---|---|
| **D1** | Scaffold `extensions/tkc/` from `extensions/defaultExt/`: `config/extension.php` with key `tkc`, `remoteName: 'tkc'`, `remoteEntry: '../extensions/tkc/remoteEntry.js'`, `enabled: true`; `app/` with all `defaultExt` paths renamed in `angular.json` and `webpack.config.js`; `backend/` with namespace `App\Extension\tkc\backend\…`; `Migrations/` only when needed. Keep `defaultExt` disabled. | One home for Level 3 code. Builds with `yarn merge-angular-json && yarn build:extension tkc`. |
| **D2** | Schema: extension vardefs on the **main table**. No Studio-created fields and no `_cstm` for TKC fields. | §6.2 |
| **D3** | Naming: fields `<name>_c` (import key `osc_id_c`); dropdown lists `tkc_<name>_list`; labels `LBL_TKC_<NAME>`; modules `tkc_<Name>`; relationships `tkc_<lhs>_<rhs>`; extension files `tkc_<topic>.php`. Clarify the rule line "TKC-specific fields/modules: prefix `tkc_`" so it is not read as `tkc_<name>_c` for every field. | Avoid collisions with core and Studio names |
| **D4** | Layouts: one versioned `custom/modules/<M>/metadata/detailviewdefs.php` per redesigned module. It keeps the upstream SuiteCRM 8 keys. Edit-only differences use `displayLogic` modes. Studio is allowed in development to draft; the output file is committed and reviewed. | §3 #17, §8.3 |
| **D5** | Symfony parameter changes: `extensions/tkc/config/*.php` that reads the current parameter, changes only TKC keys, and writes it back. Never copy full YAML parameter arrays. | Parameters replace whole arrays. The delta survives upgrades. |
| **D6** | Theme: `designs/system/tokens.json` → one generator → (1) the Angular TKC theme entry (S1 result), (2) `public/legacy/custom/themes/suite8/css/Dawn/style.css`. Both outputs come from the same commit. | §4 |
| **D7** | Dropdowns and labels go in `custom/Extension/.../Language/` files. Do not rename keys of core lists. | §3 #11 |
| **D8** | Import: a Symfony console command in `extensions/tkc/backend/` (legacy bootstrap + beans), wrapped by `scripts/seed/`. Idempotent upsert on `osc_id_c`, batched, notification and AOW guards on, logs counts and IDs only. No Import wizard and no direct SQL for real data. | §3 #34, §8.2 |
| **D9** | Docker dev environment on PHP 8.3 + MySQL 8 before phase 8 | §8.4 |
| **D10** | Build `tkc:repair`, a Symfony command that wraps the legacy Quick Repair. Run it first in dry-run mode (show the SQL), then in execute mode. Use it in every deploy. | No core repair command exists. `Ext/` is not in git. |
| **D11** | Rule amendment: new Module Builder module directories `public/legacy/modules/tkc_*` (new files only) count as Level 2, not as a core patch | §3 #14 |
| **D12** | `scripts/config/` holds a secret-free, versioned settings template that a TKC command merges into `config_override.php` per environment. Admin-only items stay in `docs/admin-guide.md`. | §7 |

### Spikes before phase 3/4 (each one is a planning task, Level 3, read-out to the architect)
| ID | Question | Output |
|---|---|---|
| S1 | Can `extensions/tkc/app/angular.json` add or replace a shell `styles` entry through `merge-angular-json`, so that core SCSS compiles with TKC variables? If not: does an extension-loaded runtime stylesheet override the shell styles reliably? | The chosen Angular theme mechanism. If neither works: a Level-5 proposal. |
| S2 | Does a dependent dropdown (`dynamic` + `parentenum`) work in Angular edit, detail and filter for Cases? | Go or no-go for the Product/Category design |
| S3 | Does a Studio layout save keep `recordActions`, `sidebarWidgets` and cell `displayLogic`? | **Done 2026-11-20:** all kept except panel `tabDefs` `displayLogic` → Studio allowed with check; put logic on cells, not panels. |
| S4 | Can an extension add an API Platform mapping path? | Needed only if a TKC API resource becomes necessary |
| S5 | Which Admin sub-pages render in Angular versus legacy? | **Done 2026-11-20:** §4.1 — Admin is mostly legacy; restyle via the Dawn override with tokens only. |

### Prerequisites for phase 3 (design system) and phase 4 (mockups)
1. S1 is done, so that tokens can be designed against a proven Angular delivery path.
2. Mockups mark each element with its UI (Angular or legacy) and `[custom]` where it is outside metadata. For Quotes, Emails and Home, mockups show restyle only (§4).
3. Record-view mockups use **one** layout for detail and edit (§1).
4. The osc-analyst classifies every OSC workspace rule and ribbon button as metadata logic, record action, or drop (§8.1).
5. The product owner decides on SLA, queues and the Incident product/category model (§8.1) before the Cases design.
6. The security officer confirms the import guards (§8.2) before any real-data run.
