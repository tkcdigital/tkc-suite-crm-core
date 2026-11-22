<?php
// SUITE-64: link from Accounts to the Finance rows. The relationship is declared in the module vardef.
$dictionary['Account']['fields']['tkc_accounts_tkc_accountfinance'] = [
    'name' => 'tkc_accounts_tkc_accountfinance',
    'type' => 'link',
    'relationship' => 'tkc_accounts_tkc_accountfinance',
    'source' => 'non-db',
    'module' => 'tkc_AccountFinance',
    'bean_name' => 'tkc_AccountFinance',
    'vname' => 'LBL_TKC_ACCOUNTFINANCE_SUBPANEL_TITLE',
];
