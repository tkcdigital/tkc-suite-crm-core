# Environments

> Owner: document-agent (content from the azure-engineer).

| Environment | Where | URL | How to run / deploy |
|---|---|---|---|
| Local dev (Docker, only dev setup) | Compose: PHP 8.3 + Apache, MySQL 8.0.46 | http://localhost:8081 (DB 127.0.0.1:3307) | `LOCAL_SETUP.md`, `docker/README.md`, `docker/dc.sh up -d`; stop: `docker/dc.sh down`; reset: `docker/dc.sh down -v` then reinstall |
| Azure (later phase) | resource group `TKC-SUITE-CRM-RG` | — | to be defined by the azure-engineer |

## Local dev services and ports
| Service | Host port | Parameter | Default |
|---|---|---|---|
| App (Apache) | `APP_PORT` | `APP_PORT` | 8081 |
| MySQL (`db`) | `DB_PORT` | `DB_PORT` | 3307 |
| Mailpit web UI (`mailpit`, SUITE-45) | `MAILPIT_UI_PORT` | `MAILPIT_UI_PORT` | 8025 |

- The system outbound account sends to Mailpit. Personal SMTP accounts bypass the sink; the import guard (SUITE-79) checks all accounts. The web UI listens on 127.0.0.1 only. SMTP port 1025 is not published. Mailpit keeps no data after a restart. See `docs/architecture.md` § Mail sink.
- If a default port is in use, set the parameter in the environment before `docker/dc.sh up -d`. Example: `MAILPIT_UI_PORT=8026`.
- Second instance (X7): run a second compose project with `-p <name>` and different values for the three port parameters. Each project has its own database volume.
- The compose file is for dev only and never goes to Azure. The command `tkc:mail:use-sink` ships with the code, but it runs only where `TKC_DEV_MAIL_SINK=1` (set only in the dev `compose.yaml`).

## Azure access
- Service principal: `tkc-suite-crm-manager-sp`, role **Contributor** on `TKC-SUITE-CRM-RG` only.
- Credentials: Proton Pass vault `TKC-SUITE` (see `.claude/rules/secrets.md`); used only by the azure-engineer.
- Every deployment to a shared environment needs the user's approval and the security-officer gate.
