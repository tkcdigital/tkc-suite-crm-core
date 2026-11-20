# Architecture

> Owner: document-agent (content decided by the architect). Draft — completed in phase 5.

## Stack
SuiteCRM 8.10 — Symfony 6.4 backend (`core/backend`), Angular 18 frontend (`core/app`), legacy
SuiteCRM 7 layer (`public/legacy`), MySQL 8. Fork of salesagility/SuiteCRM-Core.

## Customization strategy
See `.claude/rules/suitecrm-customization.md`. Summary: configuration → metadata in
`public/legacy/custom/` → extension code in `extensions/tkc/` → core patch (exception).

## Extension layout
`extensions/tkc/` — `app/` (Angular), `backend/` (Symfony), `config/`. *(to scaffold)*

## Approved decisions (2026-11-20)
From `docs/assessment/customization-feasibility.md` §9:
- **D1** Extension `extensions/tkc/` scaffolded from `defaultExt` (key/remote `tkc`); `defaultExt` stays disabled.
- **D2** TKC fields are extension vardefs on the **main table** — no Studio fields, no `_cstm`.
- **D3** Naming: fields `<name>_c` (import key `osc_id_c`), lists `tkc_<name>_list`, labels `LBL_TKC_<NAME>`, modules `tkc_<Name>`, relationships `tkc_<lhs>_<rhs>`, files `tkc_<topic>.php`.
- **D4** One versioned detailviewdefs per redesigned module (detail = edit = create; edit differences via `displayLogic` modes).
- **D5** Symfony parameter changes as read-modify-write deltas in `extensions/tkc/config/*.php`.
- **D6** One token generator → Angular theme entry + legacy Dawn override.
- **D7** Dropdowns/labels via `custom/Extension/.../Language/`; never rename core list keys.
- **D8** OSC import = Symfony console command in `extensions/tkc/backend/` with notification/AOW/geocode guards.
- **D9** Docker dev environment (PHP 8.3 + MySQL 8) before phase 8.
- **D10** `tkc:repair` command (dry-run SQL, then execute) used in every deploy.
- **D11** New Module Builder modules `public/legacy/modules/tkc_*` count as Level 2.
- **D12** Secret-free settings template in `scripts/config/`, merged into `config_override.php` per environment.
- Doctrine migrations: data/config seeding only, never schema.

## Core patches
| Patch | File | Reason | Issue | Re-check on upgrade |
|---|---|---|---|---|
| — | | | | |

## Environments
Local: see `LOCAL_SETUP.md`. Azure: phase later — `docs/environments.md`.
