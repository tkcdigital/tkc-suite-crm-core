# SUITE — Backlog narrative

> Status truth: the `SUITE` planning project. This file holds the narrative: phases, sprint goals,
> decisions and open questions. Owner: product-owner.

## Phases
| # | Phase | Status |
|---|---|---|
| 1 | Claude Code project setup | done (see setup follow-ups) |
| 2 | Assessment — what can change (DB, settings, views) | done — approved 2026-11-20 (`docs/assessment/`) |
| 2b | OSC field mapping per screen | drafts done; REST verification next |
| 3 | Design system | to do |
| 4 | Screen designs (mockups for evaluation) | to do |
| 5 | Plan (`docs/architecture.md`, this file) | to do |
| 6 | Epics / stories / tasks in SUITE | to do |
| 7 | Sprints | to do |
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
- [ ] architect: scaffold `extensions/tkc/` (from `extensions/defaultExt`).

## Decisions
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

## Open questions
- Base currency: EUR (fresh install) — confirm.
- OSC environments to read from (test/reference site names) and who provides the read-only API user.
