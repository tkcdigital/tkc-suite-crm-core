# TKC components

Status: v0.2 (SUITE-16, brand colour approved in SUITE-180). Source of the look: `design-system.html` (live, light/dark, all states).
Tokens: `tokens.json`. Every component uses **semantic** tokens only (`--tkc-<group>-<name>`). No raw values.

Legend: **A** = Angular shell theme (`core/app/shell/src/themes/suite8/css/…`, override in
`extensions/tkc/app/src/theme/_tkc-overrides.scss`). **L** = legacy Dawn theme
(`public/legacy/themes/suite8/css/Dawn/…`, override in `public/legacy/custom/themes/suite8/css/Dawn/style.css`).
`[custom]` = needs Angular or backend work beyond CSS (the architect judges the cost). Selector names are the
best-known area from reading the core SCSS file list; the frontend-engineer confirms the exact selector.

## 0. Conventions

- **Density.** `[data-density="compact"]` is the default on `pointer: fine`; `comfortable` on `pointer: coarse`.
  Components read `--tkc-density-control-height`, `-row-height`, `-cell-pad-x/-y`, `-field-gap`, `-panel-pad`,
  `-font-size`, `-btn-pad-x`, `-touch-target`. Comfortable controls are 44 px (WCAG 2.2 target size, 2.5.8 needs 24 px; we use 32 px compact).
- **Focus (all components).** `:focus-visible` shows `outline: 2px solid var(--tkc-focus-ring); outline-offset: 2px`.
  Compound controls (input groups, chips, composer) use `:focus-within` with a 2 px `--tkc-field-border-focus` ring.
  On dark navbar the ring is `--tkc-navbar-indicator`. Never `outline: none` without a replacement.
- **States.** default · hover · focus · active · disabled · error · read-only. Disabled is exempt from contrast, but stays legible (`--tkc-text-disabled` on `--tkc-field-bg-disabled`).
- **Status is never colour alone.** Pills have a text label and a dot; errors have an icon, text and a 2 px border.
- **Motion.** `--tkc-duration-fast` (120 ms) for colour/border, `-base` (200 ms) for toggles and menus, `-slow` (320 ms) for modal enter. Only `transform`, `opacity`, colour, border, shadow. All durations become 1 ms under `prefers-reduced-motion`.
- **Touch.** At `pointer: coarse` every control is 44 px high/wide; row actions open a bottom sheet.
- **Text.** Base 14 px (`--tkc-font-size-md`), min 12 px; numbers use tabular figures; IDs use `--tkc-font-family-mono`.

Key contrast ratios (light / dark, from the build check; 128 pair checks, all pass; full table in `design-system.html`):

| Pair | Light | Dark | Needs |
|---|---|---|---|
| `text-default` on `surface-default` | 15.6 | 17.3 | 4.5 |
| `text-muted` on `surface-default` | 5.3 | 9.1 | 4.5 |
| `text-link-default` on `surface-default` | 5.8 | 12.0 | 4.5 |
| `primary-on` on `primary-default` | 5.8 | 10.2 | 4.5 |
| `navbar-text-default` on `navbar-bg` | 10.9 | 18.1 | 4.5 |
| `navbar-text-muted` on `navbar-bg` | 9.1 | 10.1 | 4.5 |
| `field-border-default` on `surface-default` | 3.1 | 5.9 | 3 (UI) |
| `focus-ring` on `surface-default` | 4.1 | 12.0 | 3 (UI) |
| Status pill text on `*-subtle-bg` (all 6) | 6.9 to 11.0 | 10.7 to 12.8 | 4.5 |
| `danger-on-solid` on `danger-solid` | 6.5 | 9.3 | 4.5 |
| Thread meta on customer/agent/note bg | 7.2 min | 11.1 min | 4.5 |

Lowest text ratio in the whole set: 4.64 (light). Lowest UI ratio: 3.10 (light field border). Re-check after a brand-hue change (the style guide recalculates live).

---

## 1. Navbar and global search

- **Purpose.** Module navigation, global search, notifications, user menu.
- **Anatomy.** Logo mark + name (the logo uses `--tkc-logo`, the brand pink; see Logo colour rule below) · module items (active item has a 3 px bottom indicator) · "More" overflow · search pill · bell with unread dot · avatar menu.
- **States.** Item: default, hover (`navbar-item-hover`), active/`aria-current` (`navbar-item-active` + `navbar-indicator`), focus (indicator-coloured ring, inset). Search: default, focus (indicator border), filled. Bell: dot shown when unread.
- **Tokens.** `navbar-bg`, `navbar-text-default`, `navbar-text-muted`, `navbar-item-hover`, `navbar-item-active`, `navbar-indicator`, `navbar-search-bg`, `navbar-search-text`, `navbar-search-placeholder`, `layout-navbar-height`, `z-navbar`.
- **Maps to.** A: `.navbar` and `app-navbar` (`_sidebar.scss` does not apply; navbar rules live in the shell theme's navbar partials; the `--tkc-navbar-bg` proof is in `docs/architecture.md` S1). L: `#toolbar`, `.navbar`, `#globalLinks` in Dawn (`style.css` navbar area).
- **Phone.** Module list collapses into one menu button; search and bell stay. Touch targets 44 px.
- **Dark.** Navbar becomes `neutral-990`, so it merges into the canvas; the 3 px indicator carries the brand.
- **Notes.** Global search *results page* is legacy (`home: all false`): restyle through L only.

### Logo colour rule (SUITE-180)
- `--tkc-logo` (semantic, refers to `--tkc-color-brand-pink`, `#e95295`, `oklch(0.660 0.195 356.5)`, fixed hue) is set as `color`/`fill` of `.logo` only, in the navbar and on the login page.
- Never use `--tkc-logo` or `--tkc-color-brand-pink` for text, borders, focus rings, links or buttons. Pink text on white is 3.44:1 and fails AA text contrast.
- Contrast (3:1 graphic): logo on `navbar-bg` 3.33:1 light, 5.88:1 dark; logo on `surface-raised` 3.44:1 light, 4.52:1 dark.

## 2. Buttons

| Variant | Use | Background / text / border |
|---|---|---|
| Primary | One per view (Save, Create) | `primary-default` / `primary-on` / none; hover `primary-hover`; active `primary-active` |
| Secondary | Default action (Edit, Cancel, Filters) | `header-action-bg` / `header-action-text` / `field-border-default`; hover `header-action-hover` |
| Ghost | Tertiary, inline (Add filter, Add email, subpanel Create) | transparent / `text-link-default`; hover `primary-subtle-bg` |
| Danger | Confirm of destructive action | `danger-solid` / `danger-on-solid` |
| Danger quiet | Destructive entry point (Delete in toolbar) | transparent / `header-action-danger-text` / `danger-subtle-border` |
| Icon | Square, icon only, needs `aria-label` | as secondary or ghost; width = control height |
| Small | Subpanel, bulk bar | height 28 px (compact only; 44 px on touch via density override) |

- **States.** default, hover, focus (ring), active, disabled (`field-bg-disabled` / `text-disabled`, `cursor: not-allowed`). Loading: label replaced by spinner, button stays the same width, `aria-busy`.
- **Tokens.** Above plus `radius-md`, `font-weight-medium`, `density-control-height`, `density-btn-pad-x`, `duration-fast`.
- **Maps to.** A: `_button.scss`, `_button-primary.scss`, `_button-secondary.scss`, `_button-danger.scss`, `_button-main*.scss`, `_button-group.scss`, `scrm-button`. L: `input.button`, `.button`, `.btn`, `a.button`, `.buttonLikeLink` (Dawn).
- **Rule.** Label = verb + object where there is room ("Create case"). Primary on a dark surface uses the dark-mode `primary-default` (lighter) with dark text.

## 3. Header record actions and overflow menu

- **Purpose.** Actions on a record, in a fixed order.
- **Anatomy.** Record header: monogram tile · eyebrow (module + reference, mono) · title · meta row (status pill, priority pill, queue pill, owner, updated) · actions area. Actions: [status bar, Cases only] · Edit · More (overflow menu). Edit mode: Cancel · Save (primary); Save and close, Save and continue move into More.
- **Status bar `[custom]`.** Segmented one-click buttons for the OSC status shortcuts (Waiting, Updated, Unresolved, Solved). The current status is `aria-pressed` (`primary-subtle-*`). Visibility follows OSC rule 13 (display logic per status). The 8 other OSC function buttons ("route to Finance", "solve as CX", "Spam", …) go into the More menu as items, not as separate buttons. Needs a custom Angular action group with `displayLogic`, and the backend actions in `docs/osc-mapping/incident.md` §3.
- **Menu.** `header-action-menu-bg`, `shadow-3`, `radius-md`, items `density-control-height` high, danger item last after a divider with `header-action-danger-text`. Keyboard: Enter/Space opens, arrows move, Esc closes and returns focus to the trigger.
- **Limits.** Desktop above 1024 px: max 4 visible actions; phone: only the primary visible.
- **Tokens.** `surface-default`, `border-default`, `primary-subtle-*`, `header-action-*`, `text-muted`, `radius-lg`, `z-dropdown`.
- **Maps to.** A: `scrm-record-header`, `scrm-action-group-menu` (`_action-group-menu.scss`, `_dropdown*.scss`, `_tiered-menu.scss`), `recordActions` in `detailviewdefs.php` (kept by Studio, see S3). L: `.moduleTitle`, `.action_buttons`, `.buttons` (Dawn), only for legacy record pages (Quotes, Calls, Meetings, Admin).

## 4. Form fields

Common anatomy: label above (13 px, `field-label`, required asterisk in `field-border-error`) · control · help or error line.
Control height `density-control-height`, `radius-md`, 1 px `field-border-default`, background `field-bg-default`.
Common states: default · hover (`field-border-hover`) · focus (`field-border-focus` + 2 px ring) · error (2 px `field-border-error`, `field-bg-error`, icon + message, `aria-invalid`, `aria-describedby`) · read-only (`field-bg-readonly`, dashed border) · disabled.
Common tokens: `field-*`, `text-placeholder`, `text-muted`, `radius-md`, `density-*`, `duration-fast`.
Common maps to: A `_input.scss`, `_field-layout.scss`, `scrm-field`, `scrm-field-modal`. L `input[type=text]`, `select`, `textarea`, `.dateTime`, `.yui-ac-input` (Dawn).

| Field | Notes |
|---|---|
| Text, textarea | Textarea min 5.5 rem, vertical resize. Long values wrap (`overflow-wrap: anywhere`) in detail mode. |
| Dropdown (enum) | Native-look select with chevron (`_select.scss`). The open list: `.tkc-listbox` (raised surface, `shadow-3`, hover `primary-subtle-bg`, check mark on selected, group labels in caps). Use for OSC menus, product, category, priority. |
| Multi-select chips | Chips in the input box (`primary-subtle-*`, remove button 20 px, `aria-label` "Remove X"). A: `_multiselect.scss`, `_chips.scss`. |
| Yes / No | **Segmented radio** (two visible options, one click, `radiogroup`) for OSC Yes/No fields that were dropdowns. Selected = `primary-default`. The **toggle** (`field-toggle-off` track, 40x24, `role="switch"`) is only for settings that apply instantly. A: `_radio.scss`, `_checkbox.scss`; the Yes/No field is an `enum` with `displayType: radio` on `tkc_yes_no_list` (A3); the empty first key shows "Not set". Keyboard focus, `radiogroup` and "Not set" come from the SUITE-186 radio override; the segmented look is CSS in SUITE-103 (no new component logic). |
| Checkbox, radio | 18 px box, 2 px `field-border-default`; checked = `primary-default`. Hit area = the whole label row (44 px high on touch). |
| Date | Input + calendar button as input group; format from user preference; `inputmode="numeric"`. |
| Date and time | Date input + time input (max 6 rem) + calendar button. |
| Currency | Input group: left addon with ISO code (`surface-sunken`), right-aligned tabular numbers. Currency symbol is display only; the dropdown for currency stays in the addon for edit. |
| Relate / lookup | Input group: combobox input + clear (x) + select (magnifier) buttons. Opens the relate modal (`_relatemodal-mobile.scss`) or a typeahead listbox. A: `_relate-field.scss`. |
| Email list, phone list | Repeating rows: value · type select · primary radio · remove button. "Add email" is a ghost small button. Opt-out/invalid flags (email only) are checkboxes in a second line `[custom]`. On phone the type select wraps under the value. A: core `email` field type edit template (`[custom]` CSS only if no markup change). |
| Detail (view) value | `.tkc-value`: plain text, same height and grid cell as the edit control, so detail and edit do not jump. Links use `text-link-default`. Empty = "None" in `text-muted`. |

## 5. Record view layout: tabs, panels, field grid

One layout for detail, edit and create (decision D4 and S3). Edit differences come from cell `displayLogic`, never from a second layout.

- **Tabs.** Underline tabs on `surface-default`; selected = `text-link-default` + 3 px `primary-default` bottom border; counts in a `tkc-count` badge. Horizontally scrollable on phone. States: default, hover (`surface-sunken`), focus (inset ring), selected, disabled. A: `_tabs.scss`, `_button-tab.scss`. `tabDefs` in `detailviewdefs.php`; do not use panel-level `displayLogic` (S3: Studio drops it).
- **Panel.** Card (`surface-default`, `border-default`, `radius-lg`), header row 44 px on `surface-sunken` with chevron, title (14 px semibold) and optional hint. Collapsible (`aria-expanded`); the collapsed state is stored per user. A: `_widget.scss`, `_record-content-skeleton.scss`, `scrm-panel`.
- **Field grid.** CSS grid `repeat(auto-fill, minmax(15rem, 1fr))`, gap `density-field-gap` by 20 px. Cell classes: normal, `span-2`, `span-all` (subject, description). Collapses to one column below 750 px. Maps to `panels[].rows[].cols[]` in `detailviewdefs.php`; the layout builder uses 1 to 4 columns; **rows with a 3rd/4th column are the grid's wide mode**.
- **Sidebar.** Right column 19 rem with summary and widgets (`sidebarWidgets`). Stacks below content under 1024 px. A: `_sidebar.scss`, `_sidebar-statistic.scss`, `_grid-widget.scss`.
- **Tokens.** `surface-*`, `border-default`, `radius-lg`, `density-panel-pad`, `density-field-gap`, `layout-field-col-min`, `text-*`.
- **L.** `.tabForm`, `.edit.view`, `.detail.view`, `#detailpanel_*` (Dawn) for Quotes detail and Admin pages.

## 6. List view

- **Anatomy.** Toolbar (filter-by-text · saved filter dropdown · Filters button with active count · column chooser · primary Create) · active filter chips row (with Clear all, Save as filter) · bulk bar (appears when rows are selected) · table · footer (range, pagination, rows per page).
- **Table.** Header row on `table-header-bg`, 13 px semibold, sticky, sort button with arrow (sorted column in `text-link-default`; `aria-sort`). Rows: `table-row-default`, hover `table-row-hover`, selected `table-row-selected`, 1 px `table-row-border`. Row height `density-row-height` (32 / 48 px). First data column is the link to the record; reference numbers in mono. Numbers right aligned with tabular figures. Long cells truncate with ellipsis (`max-width` 18 rem) and show the full value in `title`.
- **Selection.** Header checkbox (indeterminate when partial), row checkbox, 44 px hit area on touch. Bulk bar uses `primary-subtle-*`, role `status`, text "N selected", actions: Assign, Change status, Export, Delete (danger quiet), "Select all N".
- **Pagination.** Prev / numbered / Next buttons (`aria-current="page"` = `primary-default`), rows-per-page select. A: `_record-pagination.scss`.
- **Filters.** Quick filter text field; Filters opens the filter panel (drawer, `z-drawer`); saved filters in a dropdown. A: `_listview-filter.scss`, `_light-filter.scss`, `_button-quickfilter-mobile.scss`, `_searchbar.scss`.
- **Maps to.** A: `scrm-list-container`, `_light-table.scss`, `_minimal-table.scss`, `_checkbox.scss`. L: `table.list.view`, `.oddListRowS1/.evenListRowS1`, `.listViewPaginationTdS1` (Dawn) for Emails list, Home and search results.
- **Phone.** The table scrolls horizontally inside its own container; the first two columns stay sticky `[custom]` if the architect accepts it (otherwise no sticky columns).
- **[custom].** Priority and status as pills in cells (see §8).

## 7. Subpanels

- **Anatomy.** Card with header (chevron title button, record count, Create, Select, filter icon) · mini table (no sticky header) · footer with range and "View all". Collapsed header has no bottom border. Row actions in a "…" button (`aria-label` names the record).
- **States.** Expanded, collapsed (`aria-expanded`), empty (`tkc-empty--compact` with a one-line message), loading (skeleton rows).
- **Tokens.** `surface-default`, `surface-sunken` (header), `border-default`, `radius-lg`, table tokens, `tkc-count` (`neutral-subtle-*`).
- **Maps to.** A: `_subpanel.scss`, `_subpanel-activities.scss`, `_subpanel-history.scss`, `_subpanel-mobile.scss`, `_widget-table.scss`. L: `.subpanel`, `.subpanelTabs`, `#subpanel_*` (Dawn). Order and visibility come from the module's subpanel definitions and the user's layout.

## 8. Badges and status pills

- **Anatomy.** Pill, 12 px semibold, `radius-pill`, 1 px border, dot (omit with `tkc-pill--plain` where the text alone carries the meaning, e.g. priority in a list).
- **Variants.** `success`, `warning`, `danger`, `info`, `accent` (violet), `neutral` = `<group>-subtle-bg / -text / -border`; `outline` for queue (no status meaning); `solid-danger` for Urgent.
- **Meaning is shared across entities.**

| Entity | Value → variant |
|---|---|
| Case status | Unresolved → info · Waiting → warning · Updated → accent · Solved → success · Closed → neutral · Spam → danger |
| Priority | High → danger · Medium → warning · Low → neutral · Urgent → solid-danger |
| Queue | outline (neutral, plain) |
| Opportunity stage | Lead → neutral · Qualification → info · Proposal → accent · Negotiation → warning · Won → success · Lost → danger |

- **Count badge.** `tkc-count` = `neutral-subtle-*`, used in tabs and subpanel headers.
- **Maps to.** A: enum field display in list and detail; `_chips.scss`, `_shapes.scss`. **[custom]** a small field display component (or CSS keyed on `[data-value]`) that maps a list key to a variant; the key-to-variant table lives in the extension config, so the OSC value lists can change without a UI change. L: not needed (legacy list shows plain text); optional Dawn span class.

## 9. Alerts and toasts

- **Inline alert.** `<group>-subtle-*`, 4 px left bar in `<group>-solid`, icon, bold title + text, optional dismiss (32 px hit area). Roles: `status` (success, info) or `alert` (warning, danger).
- **Toast.** `surface-raised`, `shadow-3`, 4 px left bar, icon, one line, optional action (Undo, Retry). Bottom right (top on phone), 5 s, pause on hover/focus, stack max 3, `z-toast`. Errors do not auto-dismiss.
- **Maps to.** A: `_message.scss`, `_message-mobile.scss`, `_notifications.scss`, `scrm-message`. L: `.error`, `.yui-panel`, `#ajaxStatus`, `.alert-*` (Dawn).

## 10. Modals and tooltips

- **Modal.** `surface-raised`, `radius-xl`, `shadow-4`, backdrop `--tkc-backdrop` at 55 %. Header (title + close), body, footer on `surface-sunken` with actions right-aligned (primary last; for destructive confirms the safe action is first and focused). Width 32 rem (confirm), 48 rem (relate/lookup, field chooser), full-screen sheet on phone. Focus trap, Esc closes, focus returns to the trigger, `aria-modal`, `aria-labelledby`.
- **Tooltip.** `surface-overlay` / `text-on-overlay`, 12 px, `shadow-2`, shown on hover and focus, `z-tooltip`.
- **Maps to.** A: `_classic-view-modal.scss`, `_confirmation-list-modal.scss`, `_field-grid-modal.scss`, `_relatemodal-mobile.scss`, `_popover.scss`, `_insert-link-popup.scss`. L: `.yui-panel`, `#popup_*` (Dawn).

## 11. Case thread (messages) `[custom]`

- **Purpose.** Chronological conversation on a case: customer entries, agent replies, internal notes, attachments (OSC "RichIncidentThread").
- **Composer (top).** Entry-type segmented control (Reply · Customer entry · Internal note) · channel select (Email, Phone, …) · standard-text quick buttons (Greeting, Closing, Outage, More info needed) · textarea · footer (Attach, CC/BCC, Save draft, Send). Selecting Internal note changes the composer to the amber dashed style and the primary label to "Add note", so a note cannot be sent as a reply by mistake.
- **Entry.** Avatar (initials; lock icon for notes) · card with 4 px left accent: head (kind label with icon, author, channel pill, time right) · body · attachment chips (file icon, name, size).

| Entry | Background / border / accent | Extra signal |
|---|---|---|
| Customer | `thread-customer-bg / -border / -accent` | user icon, label "Customer", channel pill |
| Agent | `thread-agent-bg / -border / -accent` | reply icon, label "Agent reply" |
| Internal note | `thread-internal-bg / -border / -accent`, dashed | lock icon, "Not visible to the customer" |

- **States.** Entry default; collapsed (long text, "Show more" ghost button, `aria-expanded`); draft (dashed outline + "Draft" pill); failed send (danger alert under the entry with Retry). Sort newest first by default, toggle in the thread header; filter chips by type (All, Customer, Agent, Notes).
- **Tokens.** `thread-*`, `thread-meta-text`, `surface-default`, `border-default`, `text-*`, `radius-md`, `space-*`.
- **Maps to.** A: `_record-thread.scss`, `_case-updates.scss`, `_squire-editor.scss`, `_uploaded-file.scss`, `_drafts.scss`, the Case Updates subpanel and `AOP_Case_Updates`. **Cost.** The entry-type and channel fields, the composer switch, quick-text buttons and CC/BCC are Angular work and backend fields (`tkc_entry_type_c`, `tkc_channel_c`, see `docs/osc-mapping/incident.md` §2.1). Restyling only the core update list (no new behaviour) is CSS only and keeps the type via the existing `internal` flag (agent vs note).

## 12. Empty states and loading

- **Empty.** Circular brand-tint icon, title (16 px), one or two lines saying what to do, 0 to 2 buttons (secondary first, primary last). Compact variant for subpanels and dashlets: one line, no icon.
- **Loading.** Skeleton blocks (`surface-sunken` to `border-default` shimmer; static under reduced motion). Text states end with an ellipsis ("Saving…").
- **Maps to.** A: `_record-content-skeleton.scss`, `_history-sidebar-skeleton-loading.scss`, `_loading-spinner.scss`, `_inline-loading-spinner.scss`, `_box-loading.scss`. L: none (restyle `#ajaxStatus` only).

## 13. Dashlets (legacy Home)

- **Anatomy.** Card (`surface-default`, `radius-lg`), header 44 px on `surface-sunken` (title, refresh, options "…"), body 16 px padding. Content: KPI numbers (28 px bold), bars (`primary-default` on `surface-sunken` track, value right), mini lists (use the list table in compact density), empty compact state.
- **Chart colours.** Use `primary-default`, then `info-solid`, `accent-solid`, `warning-solid`, `success-solid`; always add labels or patterns (not colour alone).
- **Maps to.** L (Home is legacy, `home: all false`): `.dashletPanel`, `.moduleTitle`, `table.dashletPanel`, `.dashletToolbar`, `#pagecontent .dashlet` (Dawn). A: `_widget.scss`, `_charts.scss` only if a later Angular dashboard exists. **Limit.** The Home layout (columns, drag and drop) is legacy markup; we restyle, we do not re-layout.

## 14. Login

- **Anatomy.** Centred card (24 rem, `surface-raised`, `radius-xl`, `shadow-3`) on a canvas with two soft brand-tint radial gradients. TKC Digital logo (`--tkc-logo`) + name, "Sign in", User name, Password (show/hide `[custom]` optional), primary full-width Sign in (44 px), divider "or", SSO secondary button (only if SSO is enabled), "Forgot your password?" link.
- **States.** Field errors inline; failed login = `tkc-alert--danger` above the form with `role="alert"`; locked/expired = warning alert. Inputs: `autocomplete="username"` and `current-password`, never block paste.
- **Maps to.** A: login view (`app-initializer.ts:111-187`: login, 2FA, logged-out are Angular). Background and card via the TKC theme. Logo: the TKC Digital logo (SUITE-59), coloured with `--tkc-logo` (not `primary-default`). No placeholder tile.

## 15. Admin and other legacy pages (no new component)

Admin settings, Quotes detail, Emails and Studio are legacy (see `docs/assessment/customization-feasibility.md` §4.1). They get the same tokens through the Dawn override: page background `surface-canvas`, forms `field-*`, buttons §2, tables §6, tabs §5, alerts §9. No layout change. Because the Angular shell loads them in an iframe, the Dawn override must set the same font family, base size (14 px), link colour and focus ring, otherwise the seam shows.

## 16. Token → CSS contract (for the generator, D6)

- Naming: the CSS name is `--tkc-` + the token path joined by `-`, group name dropped (`primitive.color.brand.700` → `--tkc-color-brand-700`; `semantic.light.text.muted` → `--tkc-text-muted`). Each leaf in `tokens.json` also has a `css` field with the exact name.
- Brand colours use `oklch(L C var(--tkc-brand-hue))`. `hex` is the resolved sRGB value for engines without oklch (Dawn on old browsers) and for contrast checks.
- Output blocks, in this order (the same as `design-system.html`): primitives on `:root`; semantic light on `:root`; semantic dark under `@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) }`; semantic dark again on `:root[data-theme="dark"]`; density on `:root`, `:root[data-density="comfortable"]`, and `@media (pointer: coarse) { :root:not([data-density="compact"]) }`; reduced-motion block.
- Semantic values reference primitives (`{primitive.color.brand.700}`); emit them as `var(--tkc-color-brand-700)` so a brand-hue change flows through.
- Dawn needs the same variables plus selector rules that map Dawn selectors to `var(--tkc-*)`; component tables above list the Dawn selectors.
