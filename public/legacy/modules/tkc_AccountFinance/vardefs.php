<?php
// SUITE-64: vardefs of tkc_AccountFinance. The relationship is declared here (C9, plan 11.6): no custom/metadata file.
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$textField = static function (string $name, string $vname): array {
    return [
        'name' => $name,
        'vname' => $vname,
        'type' => 'text',
        'audited' => true,
        'massupdate' => false,
        'importable' => true,
        'comment' => 'TKC SUITE-64',
    ];
};

$dictionary['tkc_AccountFinance'] = [
    'table' => 'tkc_accountfinance',
    'audited' => true,
    'unified_search' => false,
    'full_text_search' => false,
    'unified_search_default_enabled' => false,
    'duplicate_merge' => false,
    'optimistic_locking' => true,
    'fields' => [
        'rate_angels' => $textField('rate_angels', 'LBL_RATE_ANGELS'),
        'rate_snor' => $textField('rate_snor', 'LBL_RATE_SNOR'),
        'rate_body' => $textField('rate_body', 'LBL_RATE_BODY'),
        'expected_revenue_current_fy' => $textField('expected_revenue_current_fy', 'LBL_EXPECTED_REVENUE_CURRENT_FY'),
        'expected_revenue_next_fy' => $textField('expected_revenue_next_fy', 'LBL_EXPECTED_REVENUE_NEXT_FY'),
        'other_financial_terms' => $textField('other_financial_terms', 'LBL_OTHER_FINANCIAL_TERMS'),
        'account_id' => [
            'name' => 'account_id',
            'vname' => 'LBL_ACCOUNT_ID',
            'type' => 'id',
            'required' => true,
            'reportable' => false,
            'comment' => 'TKC SUITE-64',
        ],
        'account_name' => [
            'name' => 'account_name',
            'vname' => 'LBL_ACCOUNT_NAME',
            'type' => 'relate',
            'source' => 'non-db',
            'link' => 'tkc_accounts_tkc_accountfinance',
            'id_name' => 'account_id',
            'module' => 'Accounts',
            'rname' => 'name',
            'table' => 'accounts',
            'massupdate' => false,
            'required' => true,
        ],
        'tkc_accounts_tkc_accountfinance' => [
            'name' => 'tkc_accounts_tkc_accountfinance',
            'type' => 'link',
            'relationship' => 'tkc_accounts_tkc_accountfinance',
            'source' => 'non-db',
            'module' => 'Accounts',
            'bean_name' => 'Account',
            'vname' => 'LBL_ACCOUNT_NAME',
            'link_type' => 'one',
            'side' => 'right',
            'id_name' => 'account_id',
        ],
    ],
    'indices' => [
        'idx_tkc_accountfinance_account_id' => [
            'name' => 'idx_tkc_accountfinance_account_id',
            'type' => 'index',
            'fields' => ['account_id'],
        ],
    ],
    'relationships' => [
        'tkc_accounts_tkc_accountfinance' => [
            'lhs_module' => 'Accounts',
            'lhs_table' => 'accounts',
            'lhs_key' => 'id',
            'rhs_module' => 'tkc_AccountFinance',
            'rhs_table' => 'tkc_accountfinance',
            'rhs_key' => 'account_id',
            'relationship_type' => 'one-to-many',
        ],
    ],
];

if (!class_exists('VardefManager')) {
    require_once 'include/SugarObjects/VardefManager.php';
}
VardefManager::createVardef('tkc_AccountFinance', 'tkc_AccountFinance', ['basic']);
