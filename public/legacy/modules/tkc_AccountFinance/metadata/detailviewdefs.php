<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$viewdefs['tkc_AccountFinance']['DetailView'] = [
    'templateMeta' => [
        'form' => ['buttons' => ['EDIT', 'DELETE']],
        'maxColumns' => '2',
        'widths' => [
            ['label' => '10', 'field' => '30'],
            ['label' => '10', 'field' => '30'],
        ],
        'useTabs' => false,
        'tabDefs' => ['DEFAULT' => ['newTab' => false, 'panelDefault' => 'expanded']],
    ],
    'panels' => [
        'default' => [
            [['name' => 'account_name', 'label' => 'LBL_ACCOUNT_NAME'], ''],
            ['rate_angels', 'rate_snor'],
            ['rate_body', ''],
            ['expected_revenue_current_fy', 'expected_revenue_next_fy'],
            ['other_financial_terms', ''],
        ],
    ],
];
