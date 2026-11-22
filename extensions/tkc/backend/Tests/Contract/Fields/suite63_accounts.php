<?php

// Field manifest of SUITE-63 (Accounts), read by FieldManifestTest (C11).
// Entry format: see FieldManifestTest. Keys of a field: type, len, options, default, audited,
// required, indexed, displayType.
$yesNo = ['type' => 'enum', 'len' => 10, 'options' => 'tkc_yes_no_list', 'displayType' => 'radio'];

$releaseKeys = static function (int $fromYear, int $toYear): array {
    $keys = [''];
    for ($year = $fromYear; $year <= $toYear; $year++) {
        foreach (['02', '05', '08', '11'] as $month) {
            $keys[] = $year . '_' . $month;
        }
    }
    return $keys;
};
// version: 2012_02 to 2017_02 (21 items). future version: 2012_02 to 2020_11 (36 items).
$version = array_slice($releaseKeys(2012, 2017), 0, 22);
$future = $releaseKeys(2012, 2020);

return [
    [
        'module' => 'Accounts',
        'fields' => [
            'tkc_has_rightnow_c' => $yesNo,
            'tkc_rightnow_version_c' => ['type' => 'enum', 'len' => 10, 'options' => 'tkc_rightnow_version_list'],
            'tkc_rightnow_future_version_c' => ['type' => 'enum', 'len' => 10, 'options' => 'tkc_rightnow_future_version_list'],
            'tkc_pod_c' => ['type' => 'enum', 'len' => 10, 'options' => 'tkc_pod_list'],
            'tkc_oracle_datacenter_c' => ['type' => 'enum', 'len' => 10, 'options' => 'tkc_oracle_datacenter_list'],
            'tkc_auto_upgrade_c' => $yesNo + ['default' => 'no'],
            'tkc_projects_on_portal_c' => $yesNo,
            'tkc_console_url_c' => ['type' => 'url', 'len' => 255],
            'tkc_tooling_c' => ['type' => 'text'],
            'tkc_contract_begin_c' => ['type' => 'date', 'audited' => true],
            'tkc_contract_end_c' => ['type' => 'date', 'audited' => true],
            'tkc_replicon_name_c' => ['type' => 'varchar', 'len' => 100],
            'tkc_sharepoint_name_c' => ['type' => 'varchar', 'len' => 40],
            'tkc_debtor_number_c' => ['type' => 'varchar', 'len' => 40, 'audited' => true, 'indexed' => true],
            'account_type' => ['default' => 'tkc_relation'],
        ],
        'absent' => [
            'tkc_crm_modules_c',
            // Finance fields live in tkc_AccountFinance (SUITE-64), not on Accounts.
            'tkc_rate_angels_c', 'tkc_rate_snor_c', 'tkc_rate_body_c',
            'tkc_expected_revenue_current_fy_c', 'tkc_expected_revenue_next_fy_c', 'tkc_other_financial_terms_c',
            'rate_angels', 'rate_snor', 'rate_body', 'expected_revenue_current_fy', 'expected_revenue_next_fy', 'other_financial_terms',
        ],
        'lists' => [
            'tkc_yes_no_list' => ['', 'yes', 'no'],
            'tkc_pod_list' => ['', 'ca_ap', 'wc', 'mw', 'pc', 'ps', 'gb', 'am', 'sy', 'jp'],
            'tkc_oracle_datacenter_list' => ['', 'gb', 'am', 'mw'],
            'tkc_rightnow_version_list' => $version,
            'tkc_rightnow_future_version_list' => $future,
            'account_type_dom' => ['tkc_lead', 'Prospect', 'Customer', 'tkc_supplier', 'tkc_relation', 'tkc_former_customer'],
        ],
        'labels' => ['account_type_dom' => ['Customer' => 'Customer']],
    ],
];
