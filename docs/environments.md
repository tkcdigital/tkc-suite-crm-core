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

## Azure conditions (SUITE-183)
The security-officer accepted some dependency advisories on these conditions. See `docs/architecture.md` § Accepted dependency advisories. Each Azure environment must meet all of them.
- Set `APP_ENV=prod`.
- Do not set `GRAPHQL_SHOW_DOCS` to true.
- Do not set `AZURE_BLOB_INSTANCES` (SUITE-193 covers Azure upload storage).
- Do not activate an inbound mailbox of any type (group, personal, bounce handling, email-to-case) in an environment with real data, until SUITE-192 closes. Users can create personal mailboxes themselves. Bounce-handling mailboxes are also `inbound_email` rows.
- Check at the OSC real-data import gate (SUITE-156) and at each Azure deploy (SUITE-164): `inbound_email` has no row with `deleted=0` and `status='Active'` (aggregate count only), and the scheduler jobs `function::pollMonitoredInboxes`, `function::pollMonitoredInboxesForBouncedCampaignEmails` and `function::pollMonitoredInboxesAOP` are Inactive.
- The runtime image contains no Node runtime and no `node_modules` (build output only).
- The web document root is `public/`, so `composer.lock` (an input of `tkc:legacy:asset-version`) is not served.
- Run no dev server (`ng serve`, Karma) on Azure or on a public CI agent.
- The runtime image must contain `composer.lock` and `public/legacy/suitecrm_version.php`. The command `tkc:legacy:asset-version` reads both.
- The deploy runs `tkc:legacy:asset-version` after `scrm:copy-legacy-assets`.

## Azure deploy sequence (SUITE-64, SUITE-164)
1. `tkc:repair --execute`
2. `tkc:migrations:migrate`
3. `tkc:config:apply`
4. Clear the cache.
5. Check the Finance ACL rows (SUITE-164): `access` 89 and seven actions -99 for `tkc_AccountFinance`.

- Run the sequence as one job for each deploy, not for each replica.
- Never run `doctrine:migrations:migrate` or `doctrine:migrations:version --add --all`. See `docs/architecture.md` § TKC data migrations.
