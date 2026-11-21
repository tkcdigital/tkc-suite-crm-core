# Admin guide — configuration done in Admin / Studio

> Configuration in the app is not in git. Record every change here. Owner: document-agent.

| Date | Area (Admin → …) | Setting | Value | Story |
|---|---|---|---|---|
| 2026-11-20 | System Settings / Locale / Currencies (via `tkc:config:apply`, template `scripts/config/config_override.template.php`) | Default currency EUR (Euro, symbol EUR sign, 2 digits); date `d-m-Y`; time `H:i`; name format `f l`; decimal `,`; grouping `.`; export charset UTF-8; system name `TKC CRM` (placeholder); developerMode off | In the template, not yet applied to the local environment | SUITE-14 |
| 2026-11-21 | Email Settings, outbound account (dev only; set by `docker/dc.sh console tkc:mail:use-sink`) | SMTP server `mailpit`, port 1025, no authentication, no SSL, no user or password, from address `crm@example.com`, from name "TKC CRM (dev)" | Not in git. Run the command once for each new database. | SUITE-45 |

## Outbound email in dev (SUITE-45)
- The command `docker/dc.sh console tkc:mail:use-sink` sets the outbound account. The setting is in the database, not in git. Run the command once for each new database.
- Manual fallback: open Admin > Email Settings. Enter the same values as in the table above.
- Test: send a test email from Email Settings to `test@example.com`. Open the Mailpit page (http://127.0.0.1:8025, or the port in `MAILPIT_UI_PORT`).
- Do not add personal SMTP accounts in dev. They bypass the sink. The import guard checks all outbound accounts.
- Do not run the command on a shared or Azure environment.

## Logo (login page and navbar, SUITE-59)
- The logo comes from git: `public/legacy/custom/themes/suite8/images/company_logo.svg`. The file is a placeholder until the owner supplies the real logo.
- Do not use the logo upload in Admin. It does not replace the theme image.
- To change the logo: replace the file, run `docker/dc.sh console cache:clear`, and rebuild if needed.
- Keep "Supercharged by SuiteCRM" visible.

## User number separators (SUITE-179)
- Run `tkc:config:apply` first. Then run `docker/dc.sh console tkc:users:repair-number-separators`. Use `--dry-run` to see the changes first.
- Reason: `tkc:config:apply` sets the system decimal separator `,` and grouping separator `.`. A user preference `dec_sep` = `.` (the installer sets it for the admin user) then makes both separators the same. The user edit view refuses Save with the message "The decimal separator cannot use the same character as the thousands separator."
- The command checks each user that is not deleted, in all statuses. It removes only the user separator keys that cause the conflict. The user then uses the system default. It does not change other users or other preferences.
- The output shows user IDs and key names only. The command skips a user whose preferences do not decode and reports it.
- A signed-in user sees the change after the next sign-in. A second run changes nothing.
