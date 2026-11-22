<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$searchdefs['tkc_AccountFinance'] = [
    'templateMeta' => [
        'maxColumns' => '3',
        'maxColumnsBasic' => '4',
        'widths' => ['label' => '10', 'field' => '30'],
    ],
    'layout' => [
        'basic_search' => [['name' => 'account_name', 'label' => 'LBL_ACCOUNT_NAME']],
        'advanced_search' => [['name' => 'account_name', 'label' => 'LBL_ACCOUNT_NAME']],
    ],
];
