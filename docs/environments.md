# Environments

> Owner: document-agent (content from the azure-engineer).

| Environment | Where | URL | How to run / deploy |
|---|---|---|---|
| Local dev | developer Mac (PHP built-in server, MySQL via Homebrew) | http://localhost:8080 | `LOCAL_SETUP.md`, `/run-suitecrm` |
| Azure (later phase) | resource group `TKC-SUITE-CRM-RG` | — | to be defined by the azure-engineer |

## Azure access
- Service principal: `tkc-suite-crm-manager-sp`, role **Contributor** on `TKC-SUITE-CRM-RG` only.
- Credentials: Proton Pass vault `TKC-SUITE` (see `.claude/rules/secrets.md`); used only by the azure-engineer.
- Every deployment to a shared environment needs the user's approval and the security-officer gate.
