<?php
// SUITE-63: TKC fields on Accounts (main table, D2). Repair creates the columns.
// Dictionary key is the object name 'Account' (C2).

$tkcYesNo = [
    'type' => 'enum',
    'options' => 'tkc_yes_no_list',
    'len' => 10,
    'displayType' => 'radio',
    'displayDirection' => 'row',
    'importable' => true,
    'comment' => 'TKC SUITE-63',
];

$tkcFields = [
    'tkc_has_rightnow_c' => ['vname' => 'LBL_TKC_HAS_RIGHTNOW'] + $tkcYesNo,
    'tkc_rightnow_version_c' => [
        'vname' => 'LBL_TKC_RIGHTNOW_VERSION',
        'type' => 'enum',
        'options' => 'tkc_rightnow_version_list',
        'len' => 10,
    ],
    'tkc_rightnow_future_version_c' => [
        'vname' => 'LBL_TKC_RIGHTNOW_FUTURE_VERSION',
        'type' => 'enum',
        'options' => 'tkc_rightnow_future_version_list',
        'len' => 10,
    ],
    'tkc_pod_c' => [
        'vname' => 'LBL_TKC_POD',
        'type' => 'enum',
        'options' => 'tkc_pod_list',
        'len' => 10,
    ],
    'tkc_oracle_datacenter_c' => [
        'vname' => 'LBL_TKC_ORACLE_DATACENTER',
        'type' => 'enum',
        'options' => 'tkc_oracle_datacenter_list',
        'len' => 10,
    ],
    'tkc_auto_upgrade_c' => ['vname' => 'LBL_TKC_AUTO_UPGRADE', 'default' => 'no'] + $tkcYesNo,
    'tkc_projects_on_portal_c' => ['vname' => 'LBL_TKC_PROJECTS_ON_PORTAL'] + $tkcYesNo,
    'tkc_console_url_c' => [
        'vname' => 'LBL_TKC_CONSOLE_URL',
        'type' => 'url',
        'len' => 255,
    ],
    'tkc_tooling_c' => [
        'vname' => 'LBL_TKC_TOOLING',
        'type' => 'text',
        'rows' => 4,
        'cols' => 60,
    ],
    'tkc_contract_begin_c' => [
        'vname' => 'LBL_TKC_CONTRACT_BEGIN',
        'type' => 'date',
        'audited' => true,
        'enable_range_search' => true,
        'options' => 'date_range_search_dom',
    ],
    'tkc_contract_end_c' => [
        'vname' => 'LBL_TKC_CONTRACT_END',
        'type' => 'date',
        'audited' => true,
        'enable_range_search' => true,
        'options' => 'date_range_search_dom',
    ],
    'tkc_replicon_name_c' => [
        'vname' => 'LBL_TKC_REPLICON_NAME',
        'type' => 'varchar',
        'len' => 100,
    ],
    'tkc_sharepoint_name_c' => [
        'vname' => 'LBL_TKC_SHAREPOINT_NAME',
        'type' => 'varchar',
        'len' => 40,
    ],
    'tkc_debtor_number_c' => [
        'vname' => 'LBL_TKC_DEBTOR_NUMBER',
        'type' => 'varchar',
        'len' => 40,
        'audited' => true,
    ],
];

foreach ($tkcFields as $name => $def) {
    $dictionary['Account']['fields'][$name] = array_merge(
        ['name' => $name, 'importable' => true, 'comment' => 'TKC SUITE-63'],
        $def
    );
}

// C4: one-field index, string key stops a duplicate entry.
$dictionary['Account']['indices']['idx_accounts_tkc_debtor_number_c'] = [
    'name' => 'idx_accounts_tkc_debtor_number_c',
    'type' => 'index',
    'fields' => ['tkc_debtor_number_c'],
];
