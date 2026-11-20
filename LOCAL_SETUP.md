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
