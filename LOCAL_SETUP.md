# Local Development Setup — SuiteCRM 8

## Prerequisites

| Tool | Required | Status |
|------|----------|--------|
| PHP | 8.3 (composer platform; 8.5 works with the flags below) | ⚠️ 8.5.3 installed |
| Composer | any | ✅ installed |
| Node.js | any | ✅ installed |
| Yarn | 4.10.3 (via Corepack) | ❌ see Step 4 |
| MySQL / MariaDB | required | ❌ needs installing |
| PHP extensions | pdo_mysql, gd, intl, curl, zip, openssl, iconv, ctype, json, mbstring | ✅ all present |

---

## Step 1: Install MySQL

```bash
brew install mysql
brew services start mysql
mysql_secure_installation
```

Then create a database and user:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE suitecrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'suitecrm'@'localhost' IDENTIFIED BY 'yourpassword';
GRANT ALL PRIVILEGES ON suitecrm.* TO 'suitecrm'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Step 2: Configure `.env.local`

Create a local env override file (this file is not committed to git):

```bash
cp .env .env.local
```

Edit `.env.local` and set the `DATABASE_URL`:

```dotenv
DATABASE_URL="mysql://suitecrm:yourpassword@127.0.0.1:3306/suitecrm?serverVersion=8.0.32&charset=utf8mb4"
```

> Replace `yourpassword` with the password you set in Step 1.

---

## Step 3: Install PHP Dependencies

```bash
composer install --ignore-platform-reqs
```

> `--ignore-platform-reqs` is needed because `composer.json` pins the platform to PHP 8.3,
> but the installed version is 8.5.x. The app will still work at runtime.

---

## Step 4: Install JS Dependencies & Build Frontend

This project uses **Yarn 4.x**, managed via [Corepack](https://yarnpkg.com/corepack) (bundled with Node.js ≥ 16.9). The global `yarn` 1.x will not work.

### 4a. Enable Corepack

```bash
npm install -g corepack --force
corepack enable
```

Verify the correct version is active (should print `4.10.3`):

```bash
yarn --version
```

### 4b. Install dependencies

```bash
yarn install
```

### 4c. Generate `angular.json`

The project uses a custom Yarn plugin to merge extension configs into a single `angular.json`. This file is **not committed** and must be generated before every build:

```bash
yarn merge-angular-json
```

### 4d. Build the frontend

```bash
yarn build
```

> The production build compiles both the `core` and `shell` Angular apps and outputs to `public/dist/`. It takes a few minutes.

---

## Step 5: Set File Permissions

Create any missing directories first, then set permissions:

```bash
mkdir -p var public/legacy/cache
chmod -R 755 var/ logs/ public/legacy/cache/ public/legacy/upload/
```

---

## Step 6: Run the Web Installer

Start PHP's built-in server with multiple workers (required — the installer makes self-referencing cURL calls that deadlock on a single-threaded server):

```bash
PHP_CLI_SERVER_WORKERS=8 php -d error_reporting="E_ALL & ~E_DEPRECATED" -d display_errors=0 -d log_errors=1 -S localhost:8080 -t public/
```

Open **http://localhost:8080/install.php** in your browser and follow the installer wizard. It will:
- Run database migrations
- Create `config.php` and `config_si.php` for the legacy layer
- Set up the admin user

---

## Step 6b: Legacy UI assets (not in git)

The legacy screens (Home dashboard, Admin, Emails, etc.) need two generated assets:

```bash
# Copy TinyMCE and other legacy JS vendors into public/legacy/vendor
php bin/console scrm:copy-legacy-assets

# Compile the legacy suite8 theme (Dawn is the default sub-theme; Noon fails to compile upstream)
vendor/bin/pscss --style=compressed public/legacy/themes/suite8/css/Dawn/style.scss > public/legacy/themes/suite8/css/Dawn/style.css
```

> PHP 8.5 emits deprecation notices for SuiteCRM code. If they are printed to the page they break
> sessions/API calls ("Error occurred while retrieving records"), hence the `-d` flags on the server command.

---

## Step 7: Post-Install Cache Clear

After the installer completes:

```bash
php bin/console cache:clear
```

---

## Returning to Development (after initial setup)

```bash
# Start MySQL (if not running)
brew services start mysql

# Start the dev server (multi-worker to avoid self-referencing cURL deadlocks)
PHP_CLI_SERVER_WORKERS=8 php -d error_reporting="E_ALL & ~E_DEPRECATED" -d display_errors=0 -d log_errors=1 -S localhost:8080 -t public/
```

---

## Tips & Common Issues

- **Apache/Nginx** is preferred over PHP's built-in server for full functionality — the legacy layer relies on `mod_rewrite`. Install via `brew install httpd`.
- **Platform mismatch**: Always use `--ignore-platform-reqs` with Composer commands locally.
- **Writable directories**: If you see permission errors, re-run the `chmod` command in Step 5.
- **Database connection errors**: Double-check the `DATABASE_URL` in `.env.local` matches your MySQL credentials.
- **Official docs**: [SuiteCRM 8 Installation Guide](https://docs.suitecrm.com/8.x/admin/installation-guide/) and [Compatibility Matrix](https://docs.suitecrm.com/8.x/admin/compatibility-matrix/).
