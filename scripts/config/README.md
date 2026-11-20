# TKC console commands: repair and config

Both commands run in `php bin/console`. Use `-d error_reporting="E_ALL & ~E_DEPRECATED"` on PHP 8.5.
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
