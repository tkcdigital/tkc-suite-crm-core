# TKC console commands: repair, config and legacy asset version

These commands run in `php bin/console`. Use `-d error_reporting="E_ALL & ~E_DEPRECATED"` on PHP 8.5.
Code: `extensions/tkc/backend/Console/` and `extensions/tkc/backend/Config/`.

## tkc:repair (SUITE-13, D10)

CLI version of Admin > Repair > Quick Repair and Rebuild.

```
php bin/console tkc:repair --dry-run            # default: show the SQL, change nothing in the database
php bin/console tkc:repair --execute            # run the SQL
php bin/console tkc:repair --module=Cases       # limit the SQL to one module
php bin/console cache:clear                     # run after the repair
```

Steps: clear the legacy caches, rebuild the `Ext/` files, build the repairDatabase SQL.
`--execute` also creates missing audit tables. Always run `--dry-run` first and read the SQL.
The legacy HTML output is dropped. The exit code is non-zero on an error (unknown module, exception).
Dry run writes files (`custom/**/Ext/`, `cache/`). It does not write to the database.
Run the command as the web user, so the files stay writable.

## tkc:config:apply (SUITE-14, D12)

Merges `scripts/config/config_override.template.php` into `public/legacy/config_override.php`.

```
php bin/console tkc:config:apply --dry-run      # show the diff: "+" new key, "~" changed key
php bin/console tkc:config:apply                # write the file
```

- The merge is idempotent. The second run prints "No change".
- The template wins for its keys. Other keys in the override file stay.
- The file is written in the Configurator style (`/***CONFIGURATOR***/` block).
- The template holds no secrets. Never add keys, passwords, tokens or hosts.
- Options `--template=` and `--target=` select other files (for tests).
- Not in the template: jjwg geocoding (stored in the database, not in `config_override.php`;
  set it in Admin > Maps) and AOP settings (the AOP admin page holds a Joomla access key).
  AOP stays unset, so the SuiteCRM defaults apply.
- The template does not set `js_custom_version`. `tkc:legacy:asset-version` sets it.

## tkc:legacy:asset-version (SUITE-181)

Sets `js_custom_version` in `public/legacy/config_override.php`. The legacy UI uses this value in the `?v=` part of its CSS, JS and sprite URLs.
Core hashes the key, not the file content. The command makes the key follow the content, so a browser loads a changed theme or font.

```
php bin/console tkc:legacy:asset-version --dry-run   # show the value, change nothing
php bin/console tkc:legacy:asset-version             # write the file
```

- The value is `tkc` and 12 hex characters of a sha256 hash.
- The hash covers these inputs, sorted by path (path and content):
  - core `public/legacy/themes/suite8/css/Dawn/style.css`
  - `public/legacy/include/javascript/yui/build/base/base-min.css`
  - every `*.css` file under `public/legacy/custom/themes/suite8/css/`
  - every file in `public/legacy/custom/themes/suite8/fonts/`
- The output has the line `js_custom_version = <value>` and one result: "No change", "Would apply" or "Applied". It prints no other key and no file content.
- The merge uses `ConfigOverrideMerger`. Other keys in the file stay. The file is written atomically.
- Exit code 1: an input is missing, or the file cannot be written.
- Option `--target=` selects another override file (for tests).
- Run it after `tkc:config:apply` and before `cache:clear`. `scripts/build.sh` does this in step 11b.
- Azure: run it in each environment after `tkc:config:apply`. Reload php-fpm when `opcache.validate_timestamps=0`. A CDN must use the query string in its cache key.
- Code: `extensions/tkc/backend/Config/LegacyAssetVersion.php` and `extensions/tkc/backend/Console/LegacyAssetVersionCommand.php`.
