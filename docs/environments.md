# Environments

> Owner: document-agent (content from the azure-engineer).

| Environment | Where | URL | How to run / deploy |
|---|---|---|---|
| Local dev (Docker, only dev setup) | Compose: PHP 8.3 + Apache, MySQL 8.0.46 | http://localhost:8081 (DB 127.0.0.1:3307) | `LOCAL_SETUP.md`, `docker/README.md`, `docker/dc.sh up -d`; stop: `docker/dc.sh down`; reset: `docker/dc.sh down -v` then reinstall |
| Azure (later phase) | resource group `TKC-SUITE-CRM-RG` | — | to be defined by the azure-engineer |

## Azure access
- Service principal: `tkc-suite-crm-manager-sp`, role **Contributor** on `TKC-SUITE-CRM-RG` only.
- Credentials: Proton Pass vault `TKC-SUITE` (see `.claude/rules/secrets.md`); used only by the azure-engineer.
- Every deployment to a shared environment needs the user's approval and the security-officer gate.
