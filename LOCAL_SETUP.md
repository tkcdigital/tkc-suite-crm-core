# Local Development Setup — SuiteCRM 8 (Docker)

Docker Compose is the only supported dev setup: PHP 8.3 + Apache (`mod_rewrite`) and MySQL 8.0.
It matches the Azure runtime. Details: [`docker/README.md`](docker/README.md).

| Item | Value |
|---|---|
| App | http://localhost:8081 |
| MySQL | 127.0.0.1:3307 (container `db`, MySQL 8.0.46) |
| Admin | user `admin`; password in `docker/.env`, variable `SITE_ADMIN_PASSWORD` (gitignored) |

## Prerequisites
Docker Desktop. For the Angular build on the host: Node.js with Corepack (Yarn 4.10.3).

## First setup
```bash
docker/init.sh                       # creates docker/.env with random dev-only passwords
docker/dc.sh up -d --build
docker/dc.sh exec app composer install        # only if vendor/ is missing
yarn install && yarn merge-angular-json && yarn build    # host; fills public/dist (Step "Angular build")
set -a; . docker/.env; set +a
docker/dc.sh exec -T -u www-data app php bin/console suitecrm:app:install -n \
  -U "$DB_USER" -P "$DB_PASSWORD" -H db -Z 3306 -N "$DB_NAME" \
  -u "$SITE_ADMIN_USER" -p "$SITE_ADMIN_PASSWORD" -S http://localhost:8081/ -d no -W true
docker/dc.sh console scrm:copy-legacy-assets
docker/dc.sh exec -T app vendor/bin/pscss --style=compressed \
  public/legacy/themes/suite8/css/Dawn/style.scss > public/legacy/themes/suite8/css/Dawn/style.css
docker/dc.sh console tkc:config:apply --dry-run && docker/dc.sh console tkc:config:apply
docker/dc.sh console tkc:repair --dry-run     # review the SQL; run without --dry-run only after review
docker/dc.sh console cache:clear
```
Use `-d no` for demo data. The value `false` is NOT "no" and installs demo data.
`docker/dc.sh console <args>` runs `php bin/console <args>` as `www-data`, so cache files stay writable for Apache.
Do not run console commands as root (`exec app php ...` without `-u www-data`): root-owned cache files give HTTP 500.

## Daily use
```bash
docker/dc.sh up -d | down | logs -f app | sh
```
`docker/dc.sh down -v` deletes the database and the container volumes (destructive; then re-run the install).

## After a change
- Metadata, vardef or language change: `docker/dc.sh console tkc:repair` (review first), then `docker/dc.sh console cache:clear`.
- Angular change: `yarn merge-angular-json && yarn build` on the host (writes `public/dist`, served from the bind mount).
- Legacy theme SCSS change: re-run the `pscss` command above.

## Build chain
`scripts/build.sh` runs the whole build in order and stops at the first error. Run it after `docker/dc.sh up -d` and the installer.
```bash
scripts/build.sh [--yes] [--skip-core] [--skip-frontend] [--dry-run-only]
```
| Step | Action |
|---|---|
| 0 | Preflight: Docker, running `app` container, `docker/.env`, `public/legacy/config.php`, node, yarn, python3, curl |
| 1-5 | `yarn install --immutable`, `yarn merge-angular-json`, `yarn build:core`, `yarn build:shell`, `yarn build:extension tkc` (host) |
| 6 | Compile the core Dawn CSS with `pscss` (output is gitignored) |
| 7 | `scrm:copy-legacy-assets` |
| 8 | `tkc:repair --dry-run`: shows the SQL |
| 9 | Asks "Run tkc:repair --execute? [y/N]" (always, also when there is no SQL) |
| 10-12 | `tkc:repair --execute`, `tkc:config:apply`, `cache:clear` |
| 13 | Smoke test: `GET http://localhost:8081/` must give HTTP 200 (`APP_PORT` overrides the port) |

Flags: `--yes` skips the confirmation (only for reviewed deploys: it runs SQL unread); `--skip-core` skips step 3;
`--skip-frontend` skips steps 1-5; `--dry-run-only` stops after step 8.
Exit codes: `0` success; `1` a step failed (the script prints `FAILED at step n: <name>`);
`2` no confirmation possible (no TTY and no `--yes`) or an unknown flag.
`docker/dc.sh console` adds `-T` by itself when stdin is not a TTY, so the script also works in CI.

## Mail sink
All outgoing email goes to Mailpit. Nothing leaves the dev machine.
1. `docker/dc.sh up -d` starts the `mailpit` container. The web page is http://127.0.0.1:8025.
2. Point the SuiteCRM system outbound account at the sink (once per database, safe to repeat):
   `docker/dc.sh console tkc:mail:use-sink --dry-run`, then `docker/dc.sh console tkc:mail:use-sink`.
   A second run prints "No change".
3. Test: Admin > Email Settings > send a test email to `test@example.com`. The message shows in Mailpit.
4. API check: `curl -s http://127.0.0.1:8025/api/v1/messages`.

Rules: the SMTP port 1025 is not published to the host. Mailpit keeps no data after a restart.
The sink host name is the env value `TKC_MAIL_SINK_HOSTS` (default `mailpit`, parameter `tkc.mail.sink_hosts`).
The command is for DEV only. Do not run it on a shared or Azure environment.
Fallback: set the SMTP server in Admin > Email Settings by hand (host `mailpit`, port 1025, no authentication).

## Tests

## Theme

## Angular build
The build runs on the host, not in the container. It only needs Node and Yarn 4 (Corepack:
`npm install -g corepack --force && corepack enable`); it does not need PHP or MySQL.
The container serves the result from the bind mount.

## Legacy host setup (retired)

Retired on 2026-11-20 (SUITE-12): Docker is the only dev setup. The old host setup (`php -S` on 8080,
Homebrew MySQL, `.env.local` with `DATABASE_URL` to 127.0.0.1:3306, `composer install --ignore-platform-reqs`)
is not supported. Stop the `php -S` server and the Homebrew MySQL (`brew services stop mysql`).
Backups of the old host config are in `data/backup/host-setup-2026-11-20/` (gitignored, contain credentials).

## Official docs
[SuiteCRM 8 Installation Guide](https://docs.suitecrm.com/8.x/admin/installation-guide/) and
[Compatibility Matrix](https://docs.suitecrm.com/8.x/admin/compatibility-matrix/).
