# Admin guide — configuration done in Admin / Studio

> Configuration in the app is not in git. Record every change here. Owner: document-agent.

| Date | Area (Admin → …) | Setting | Value | Story |
|---|---|---|---|---|
| 2026-11-20 | System Settings / Locale / Currencies (via `tkc:config:apply`, template `scripts/config/config_override.template.php`) | Default currency EUR (Euro, symbol EUR sign, 2 digits); date `d-m-Y`; time `H:i`; name format `f l`; decimal `,`; grouping `.`; export charset UTF-8; system name `TKC CRM` (placeholder); developerMode off | In the template, not yet applied to the local environment | SUITE-14 |
