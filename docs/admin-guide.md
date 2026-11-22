# Admin guide — configuration done in Admin / Studio

> Configuration in the app is not in git. Record every change here. Owner: document-agent.

| Date | Area (Admin → …) | Setting | Value | Story |
|---|---|---|---|---|
| 2026-11-20 | System Settings / Locale / Currencies (via `tkc:config:apply`, template `scripts/config/config_override.template.php`) | Default currency EUR (Euro, symbol EUR sign, 2 digits); date `d-m-Y`; time `H:i`; name format `f l`; decimal `,`; grouping `.`; export charset UTF-8; system name `TKC CRM` (placeholder); developerMode off | In the template, not yet applied to the local environment | SUITE-14 |
| 2026-11-21 | Email Settings, outbound account (dev only; set by `docker/dc.sh console tkc:mail:use-sink`) | SMTP server `mailpit`, port 1025, no authentication, no SSL, no user or password, from address `crm@example.com`, from name "TKC CRM (dev)" | Not in git. Run the command once for each new database. | SUITE-45 |

## TKC fields and lists: no Studio, no Dropdown Editor (C13, SUITE-63)
- Do not use Studio on a TKC field (`tkc_*_c`). Do not use the Dropdown Editor on a TKC list (`tkc_*_list`). Do not use it on a core list that TKC overrides, for example `account_type_dom`.
- Reason: a Dropdown Editor save writes `public/legacy/custom/include/language/en_us.lang.php`. Git tracks that file. It loads after the extension files. The save therefore keeps a full copy of the list and overrides the TKC file.
- To change a list: edit the file in `public/legacy/custom/Extension/application/Ext/Language/`. Then run `tkc:repair`.
- To change a field: edit the vardef in `public/legacy/custom/Extension/modules/<Module>/Ext/Vardefs/`. Then run `tkc:repair`. Studio fields would break the main-table rule (D2).
- Code review rejects any change in `public/legacy/custom/include/language/`.
- Overridden core lists are in `docs/architecture.md` § Overrides register.

## Outbound email in dev (SUITE-45)
- The command `docker/dc.sh console tkc:mail:use-sink` sets the outbound account. The setting is in the database, not in git. Run the command once for each new database.
- Manual fallback: open Admin > Email Settings. Enter the same values as in the table above.
- Test: send a test email from Email Settings to `test@example.com`. Open the Mailpit page (http://127.0.0.1:8025, or the port in `MAILPIT_UI_PORT`).
- Do not add personal SMTP accounts in dev. They bypass the sink. The import guard checks all outbound accounts.
- Do not run the command on a shared or Azure environment.

## Logo (login page and navbar, SUITE-59)
- The logo comes from git: `public/legacy/custom/themes/suite8/images/company_logo.svg`. The file is the TKC Digital logo in one colour (`currentColor`). The token `--tkc-logo` sets the colour (brand pink, SUITE-180). The primary colour does not set it. To change the logo colour, change `PINK_HEX`, `PINK_H` and `PINK` in `designs/system/generator/tokens_gen.py` (do not edit `tokens.json`, it is generated). Then run `python3 -I build_tokens.py && python3 -I assemble.py` in `designs/system/generator`, then `python3 -I scripts/theme/build_theme.py` and `scripts/build.sh`.
- Do not use the logo upload in Admin. It does not replace the theme image.
- To change the logo: replace the file, run `docker/dc.sh console cache:clear`, and rebuild if needed.
- Keep "Supercharged by SuiteCRM" visible.

## Sequence counters: `tkc:sequence set` (SUITE-62, SUITE-205)
- Run `docker/dc.sh console tkc:sequence set <name> <year> <value>` to see a dry run. The command writes nothing without `--execute`.
- The dry run shows the current value and the new value. It warns when the new value is lower than the current value.
- `--execute` refuses a lower value. This is a safety rule: a lower value lets numbers repeat.
- Use `--allow-lower` only in a test or a recovery. Before you use it:
  1. Stop all callers of this counter.
  2. Make sure that no record has a number above the new value.
- The counter can change between the dry run and `--execute`. Check the value again before you write.
- Details: `docs/architecture.md` § Sequences.

## Legacy asset version (SUITE-181)
- The config key `js_custom_version` in `public/legacy/config_override.php` sets the `?v=` value of the legacy CSS, JS and sprite URLs.
- The build sets it with `tkc:legacy:asset-version` (build step 11b). Never set it by hand. Never add it to `scripts/config/config_override.template.php`.
- The value is `tkc` and 12 hex characters. It changes when the legacy theme CSS or a theme font changes. It also changes after a vendor update (`composer.lock`) or an upstream release (`public/legacy/suitecrm_version.php`), so the browser loads new legacy JS (SUITE-183).
- No browser hard reload is needed after a theme change.
- On an Azure environment, run `tkc:legacy:asset-version` after `tkc:config:apply`. Reload php-fpm when `opcache.validate_timestamps=0`. A CDN must use the query string in its cache key.
- Details: `docs/architecture.md` § Legacy theme.

## Fonts (SUITE-189)
- The app uses Roboto (variable, weights 400 to 700) and IBM Plex Mono 400. Both are self-hosted in `public/legacy/custom/themes/suite8/fonts/`. The app loads no external font.
- The font choice is not an Admin setting. It comes from git (`designs/system/tokens.json` and `scripts/theme/build_theme.py`). Do not set a font in Admin or Studio.
- To replace a font file, give the new file a new file name (until SUITE-190). Then run `scripts/build.sh`.
- Licences: `Roboto-OFL.txt` and `OFL.txt` in the same directory. Keep them with the fonts.

## User number separators (SUITE-179)
- Run `tkc:config:apply` first. Then run `docker/dc.sh console tkc:users:repair-number-separators`. Use `--dry-run` to see the changes first.
- Reason: `tkc:config:apply` sets the system decimal separator `,` and grouping separator `.`. A user preference `dec_sep` = `.` (the installer sets it for the admin user) then makes both separators the same. The user edit view refuses Save with the message "The decimal separator cannot use the same character as the thousands separator."
- The command checks each user that is not deleted, in all statuses. It removes only the user separator keys that cause the conflict. The user then uses the system default. It does not change other users or other preferences.
- The output shows user IDs and key names only. The command skips a user whose preferences do not decode and reports it.
- A signed-in user must sign out and sign in again. The session holds the old preferences and writes them back to the database. A second run changes nothing.
