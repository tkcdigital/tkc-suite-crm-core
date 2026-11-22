<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$subpanel_layout = [
    'top_buttons' => [['widget_class' => 'SubPanelTopCreateButton']],
    'where' => '',
    'list_fields' => [
        'rate_angels' => ['vname' => 'LBL_RATE_ANGELS', 'width' => '20%', 'default' => true],
        'rate_snor' => ['vname' => 'LBL_RATE_SNOR', 'width' => '20%', 'default' => true],
        'rate_body' => ['vname' => 'LBL_RATE_BODY', 'width' => '20%', 'default' => true],
        'edit_button' => [
            'vname' => 'LBL_EDIT_BUTTON',
            'widget_class' => 'SubPanelEditButton',
            'module' => 'tkc_AccountFinance',
            'width' => '4%',
            'default' => true,
        ],
    ],
];
