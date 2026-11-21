# Docker dev environment

PHP 8.3 + Apache (`mod_rewrite`, `AllowOverride All`, DocumentRoot `public/`) and MySQL 8.0 (pinned `mysql:8.0.46`).
It matches the composer platform (PHP 8.3) and the later Azure runtime.

| Item | Value |
|---|---|
| App | http://localhost:8081 (host port 8081, bound to 127.0.0.1) |
| MySQL | 127.0.0.1:3307 (container port 3306) |
| Image | `docker/php/Dockerfile`, `php:8.3.37-apache-bookworm` |
| PHP settings | `docker/php/php.ini` (512M memory, 64M upload, `E_ALL & ~E_DEPRECATED`, errors to log only) |
| Extensions | pdo_mysql, mysqli, gd, intl, zip, curl, mbstring, ldap, imap, soap, bcmath, exif, opcache, pcntl |

Docker is the only dev setup (SUITE-12). Stop any old `php -S` server and Homebrew MySQL.
IMAP builds on PHP 8.3 (PHP 8.4 removed it from core; check this again before an upgrade).

## Secrets
`docker/.env` is gitignored. It holds throw-away DEV passwords only. Never put real or Azure secrets in it.
`docker/init.sh` creates it from `docker/.env.example` with random passwords.
Use `docker/dc.sh` (it adds `--env-file docker/.env`; the repo root `.env` belongs to Symfony).

## First start
See [`LOCAL_SETUP.md`](../LOCAL_SETUP.md) for the full command list (init, build, install, assets, config, repair).

## Files
| File | Purpose |
|---|---|
| `compose.yaml` (repo root) | services `app`, `db` and `mailpit` (dev mail sink), named volumes, `DATABASE_URL` (serverVersion 8.0.46) |
| `docker/php/Dockerfile`, `php.ini` | image; volume mount points owned by `www-data` |
| `docker/mysql/my.cnf` | utf8mb4, strict mode |
| `docker/init.sh` | creates `docker/.env` (random dev-only passwords) |
| `docker/dc.sh` | compose wrapper; `console` and `sh` run as `www-data` |

## Install notes
- The installer writes `public/legacy/config.php`, `config_si.php`, `config_override.php` and `.env.local`
  into the bind mount (all gitignored). There are no overlay files: Docker is the only setup.
- `DATABASE_URL` in `compose.yaml` beats `.env.local`, so the DB host and server version stay correct.
- `var/`, `cache/`, `logs/`, `public/legacy/cache` and `public/legacy/upload` are named volumes (fast, off the bind mount).
- Run the installer and all console commands as `www-data` (`docker/dc.sh console ...`). Root-owned cache gives HTTP 500.
- Demo data: pass `-d no`. `-d false` installs demo data.
- The installer prints "root has been added to the allowed_cron_users list" only when run as root. Run as `www-data` to avoid it.

## Daily use
```bash
docker/dc.sh up -d | down | logs -f app | sh
docker/dc.sh down -v      # also deletes the DB and container volumes (destructive)
```

## Mail sink (Mailpit)
- Service `mailpit` (`axllent/mailpit:v1.21`) catches all outgoing email. Web page: http://127.0.0.1:8025
  (`MAILPIT_UI_PORT`). The page has no login, so it listens on 127.0.0.1 only.
- SMTP port 1025 is not published. Only the `app` container reaches it (host name `mailpit`).
- No volume: a restart deletes all held mail. Held mail can contain personal data.
- Point SuiteCRM at the sink once per database: `docker/dc.sh console tkc:mail:use-sink [--dry-run]`.
- The sink host name is one config value: env `TKC_MAIL_SINK_HOSTS` (default `mailpit`) -> parameter `tkc.mail.sink_hosts`.
- Host ports: `APP_PORT` (8081), `DB_PORT` (3307), `MAILPIT_UI_PORT` (8025). Set them for a second instance with `-p <name>`.
- This compose file and the command are for DEV only. They never go to Azure.
