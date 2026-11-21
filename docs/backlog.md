# SUITE — Backlog narrative

> Status truth: the `SUITE` planning project. This file holds the narrative: phases, sprint goals,
> decisions and open questions. Owner: product-owner.

## Phases
| # | Phase | Status |
|---|---|---|
| 1 | Claude Code project setup | done (see setup follow-ups) |
| 2 | Assessment — what can change (DB, settings, views) | done — approved 2026-11-20 (`docs/assessment/`) |
| 2b | OSC field mapping per screen | drafts done; REST verification next |
| 3 | Design system | done — v0.1 approved 2026-11-20 (`designs/system/`) |
| 4 | Screen designs (mockups for evaluation) | in review — 7 mockups published 2026-11-20 (SUITE-17) |
| 5 | Plan | done — `docs/plan/implementation-plan.md` approved 2026-11-20 (A1–A20 defaults accepted) |
| 6 | Epics / stories / tasks in SUITE | draft — `docs/plan/backlog-draft.md` (not yet in the planning tool) |
| 7 | Sprints | draft — sprint plan in `docs/plan/backlog-draft.md` |
| 8 | Run the sprints | to do |
| 9 | Seed SuiteCRM with OSC data (anonymized sample first) | to do |

## Screens in scope
| Screen | OSC counterpart | Scope |
|---|---|---|
| accounts | Organization | fields + redesign |
| contacts | Contact | fields + redesign |
| cases | Incident | fields + redesign |
| opportunities | Opportunity | fields + redesign |
| quotes | — | cosmetic |
| emails | — (part of Incident threads in OSC) | cosmetic |
| home-dashboard | — | cosmetic |

## Setup follow-ups (from phase 1)
- [x] User: create the `SUITE` project in the planning tool (2026-11-20).
- [x] User: Proton Pass vault `TKC-SUITE` + agent token (Keychain `pass-cli-suite`); agent session verified (Viewer on TKC-SUITE).
- [x] User: create `.claude/scripts/secrets.env` (2026-11-20).
- [x] User: add OSC workspace XML exports to `designs/screens/<screen>/` (accounts, contacts, cases, opportunities).
- [x] User: Azure resource group `TKC-SUITE-CRM-RG` + service principal `tkc-suite-crm-manager-sp` (Contributor on the RG).
- [x] Commit the setup + the installer-generated `public/legacy/custom/` baseline on `tkc/main`.
- [x] azure-engineer: Docker Compose dev environment (PHP 8.3 + Apache + MySQL 8.0) — the only dev setup since 2026-11-20 (`php -S` retired).
- [x] architect: scaffold `extensions/tkc/` (SUITE-9; browser check still open).

## Decisions
- 2026-11-20 — Implementation plan approved: all assumption defaults A1–A20 accepted (12 case statuses + P1–P4; three-state Yes/No; required primary contact on cases; queue only; Finance fields in a separate `tkc_AccountFinance` module with module ACL; quote number `OFTYY-NNNN` on Opportunity, no quote import; `tkc_<name>_c` naming; one case reference `YYMMDD-NNNNNN`; extras (pipeline board, totals, My day) deferred; attachments not in the first import; owner runs the bulk OSC extract). Roles: TKC Sales + TKC Service + admin flag.
- 2026-11-20 — Assessment decisions D1–D12 approved (main-table vardefs, delta config, `tkc:repair`, import command with notification guards, Docker PHP 8.3, …) — see `docs/assessment/customization-feasibility.md` §9.
- 2026-11-20 — OSC screenshots with customer data stay in `data/osc/inputs/` only; the Incident export is redacted in git.
- 2026-11-20 — Upgrade-safe customization only (extensions/tkc + public/legacy/custom); core patches by exception.
- 2026-11-20 — Branch model: `tkc/main` integration branch, `upstream` = salesagility/SuiteCRM-Core.
- 2026-11-20 — Real OSC data only in `data/` (gitignored); mockups/tests use synthetic data.
- 2026-11-20 — UI language: English (en_us) only for now; Dutch can follow later.

## Product decisions (owner, 2026-11-20)
| Topic | Decision | Effect |
|---|---|---|
| SLA (Organization, Incident) | **Dropped.** No SLA engine, no SLA fields. | OSC SLA controls, response interval and SLA dates are `drop` in the mappings. |
| Queues | **Kept.** | Cases get a queue field + list filters; assignment by queue (design in phase 3/4). |
| Incident Product | Flat list: **Oracle Service Cloud**, **eGain** | Simple dropdown; no hierarchy. Spike S2 (dependent dropdown) is not needed for Product/Category. |
| Incident Category | Flat list: **Service Request**, **Complaint**, **Question**, **Change Request** | Simple dropdown. |
| Incident Disposition | Not decided | Open question. |
| Contracts (`Support$SupportContract`, `Support$LicentieContract`) | **In scope** | Map to SuiteCRM Contracts (AOS_Contracts) or a `tkc_` module — architect decides after REST field lists. |
| Projects (`Support$Project`) | **Out of scope** | Not migrated; no Projects grid on Accounts/Cases. |
| UI language | English (en_us) | — |

## Sprint 0 progress (2026-11-21)
- Implemented on branch `sprint/0`: SUITE-44 (build chain), 45 (mail sink), 46 (tests), 47 (metadata contract test, Accounts first), 48, 49, 56 (theme generator), 57 (Angular theme), 58 (Angular overrides), 59 (login logo), 60 (legacy theme).
- Documents are synced (step 5). Details are in `docs/architecture.md`, `docs/admin-guide.md`, `docs/environments.md` and `LOCAL_SETUP.md`.
- Also implemented: SUITE-175 (`lcobucci/clock` 3.5.0, owner chose option B: keep the PHP 8.3 platform), SUITE-179 (command `tkc:users:repair-number-separators`), the TKC Digital logo for SUITE-59.
- QA browser checks ran on 2026-11-21. The fixes for the QA findings are in SUITE-57, 58 and 60.
- New issues: SUITE-179 (bug: user separators), SUITE-180 (brand pink for the logo), SUITE-181 (legacy CSS URL version does not change with content).
- Gates still open:
  - PO verification and security sign-off for SUITE-44, 45, 57 to 60, 175 and 179.
  - SUITE-179 AC3: the owner signs out and in, then saves a password change.
  - SUITE-176 (bug): the Symfony `test` env does not boot.
  - SUITE-180 and SUITE-181.
- Spikes SUITE-51 to SUITE-55 are done (2026-11-21). Spike code stays on `spike/*` branches and is never merged. Results:
  - S7 (SUITE-51): go. AOS_Contracts renders in Angular with a D5 delta. Renewal reminder is not supported, so the layout leaves the field off.
  - S8 (SUITE-52): go. Thread fields, `dynamicClass` and `bottomWidgets` work. The thread is a full-width block, not a tab.
  - S9 (SUITE-53): action `displayLogic` on a field value works (go). A panel does not hide when all cells hide (no-go, accept the empty header).
  - S10 (SUITE-54): partial. Keyboard access needs an S theme fix (SUITE-186). The default list filter is a saved filter seeded per user (SUITE-106).
  - S11 (SUITE-55): go with metadata logic on `probability` (SUITE-69). No hook.
  - Downstream stories SUITE-103, 106, 108, 110, 114, 126, 128, 129, 130, 137 and 144 are corrected. New story: SUITE-186 (Sprint 1, before SUITE-103).
  - SUITE-50 AC3 (the `docs/architecture.md` spike results) is open until the document-agent finishes it.
- Decisions needed from the owner:
  - SUITE-144: contract panels. Option A: one panel "Contract details" with cells shown by contract type (recommended). Option B: three panels with one empty header.
  - SUITE-129: the thread is a block under the panels, not a Messages tab. The UX designer confirms this against the Cases mockup. If not, the PO raises an M story for a real tab.
- New follow-up issues: SUITE-182 (label association, accessibility), SUITE-183 (dependency advisories, high priority), SUITE-184 (build hardening).

## Open questions
- Base currency: EUR (fresh install) — confirm.
- OSC environments to read from (test/reference site names) and who provides the read-only API user.
- Design spec gaps found in SUITE-58 (ux-designer decides):
  - Toast left bar: `components.md` §9 says 4 px. The token says 3 px.
  - Body text size: the theme uses `--tkc-font-size-md`. The spec names `--tkc-density-font-size`.
  - Bulk-action bar: core always shows it. `components.md` §6 says only when rows are selected.
  - Toast on phones: the spec has no breakpoint. The theme puts the toast at the top below 47.9375rem.
