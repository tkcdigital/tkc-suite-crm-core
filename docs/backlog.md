# SUITE — Backlog narrative

> Status truth: the `SUITE` planning project. This file holds the narrative: phases, sprint goals,
> decisions and open questions. Owner: product-owner.

## Phases
| # | Phase | Status |
|---|---|---|
| 1 | Claude Code project setup | done (see setup follow-ups) |
| 2 | Assessment — what can change (DB, settings, views) | to do |
| 2b | OSC field mapping per screen | to do (XML exports added 2026-11-20) |
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
- [ ] User: create `.claude/scripts/secrets.env` from the example with the real item titles/fields (the agent may not list vault items).
- [x] User: add OSC workspace XML exports to `designs/screens/<screen>/` (accounts, contacts, cases, opportunities).
- [x] User: Azure resource group `TKC-SUITE-CRM-RG` + service principal `tkc-suite-crm-manager-sp` (Contributor on the RG).
- [x] Commit the setup + the installer-generated `public/legacy/custom/` baseline on `tkc/main`.
- [ ] azure-engineer: Docker Compose dev environment (PHP 8.3 + Apache + MySQL 8) to replace `php -S` on PHP 8.5.
- [ ] architect: scaffold `extensions/tkc/` (from `extensions/defaultExt`).

## Decisions
- 2026-11-20 — Upgrade-safe customization only (extensions/tkc + public/legacy/custom); core patches by exception.
- 2026-11-20 — Branch model: `tkc/main` integration branch, `upstream` = salesagility/SuiteCRM-Core.
- 2026-11-20 — Real OSC data only in `data/` (gitignored); mockups/tests use synthetic data.
- 2026-11-20 — UI language: English (en_us) only for now; Dutch can follow later.

## Open questions
- Base currency: EUR (fresh install) — confirm.
- OSC environments to read from (test/reference site names) and who provides the read-only API user.
