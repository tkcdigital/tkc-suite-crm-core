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

## Core patches
| Patch | File | Reason | Issue | Re-check on upgrade |
|---|---|---|---|---|
| — | | | | |

## Environments
Local: see `LOCAL_SETUP.md`. Azure: phase later — `docs/environments.md`.
