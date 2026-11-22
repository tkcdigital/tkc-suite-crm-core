<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$listViewDefs['tkc_AccountFinance'] = [
    'ACCOUNT_NAME' => [
        'type' => 'relate',
        'label' => 'LBL_ACCOUNT_NAME',
        'id' => 'ACCOUNT_ID',
        'module' => 'Accounts',
        'link' => true,
        'width' => '25%',
        'default' => true,
    ],
    'RATE_ANGELS' => ['label' => 'LBL_RATE_ANGELS', 'width' => '15%', 'default' => true],
    'RATE_SNOR' => ['label' => 'LBL_RATE_SNOR', 'width' => '15%', 'default' => true],
    'RATE_BODY' => ['label' => 'LBL_RATE_BODY', 'width' => '15%', 'default' => true],
];
