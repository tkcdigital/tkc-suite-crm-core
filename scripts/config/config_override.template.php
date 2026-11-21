<?php
/**
 * TKC system settings that must be identical in every environment (SUITE-14, D12).
 * SECRET-FREE: never add keys, passwords, tokens, hosts or credentials here.
 * Applied by: php bin/console tkc:config:apply [--dry-run]
 *
 * Values are $sugar_config keys. Nested arrays are merged key by key.
 */
return [
    // Currency
    'default_currency_iso4217' => 'EUR',
    'default_currency_name' => 'Euro',
    'default_currency_symbol' => '€',
    'default_currency_significant_digits' => 2,

    // Locale
    'default_language' => 'en_us',
    'default_date_format' => 'd-m-Y',
    'default_time_format' => 'H:i',
    'default_locale_name_format' => 'f l',
    'default_decimal_seperator' => ',',   // sic: SuiteCRM spelling
    'default_number_grouping_seperator' => '.',
    'default_export_charset' => 'UTF-8',

    // System
    'system_name' => 'TKC CRM',           // placeholder
    'developerMode' => false,
];
