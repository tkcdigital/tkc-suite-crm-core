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
    // TKC SUITE-64 D2b: Finance has no subpanels, so the Angular store has no subpanel map and reloadSubpanels()
    // throws after a save. A module action replaces the core action with the same key: full copy of
    // config/services/module/recordview/actions.yaml default.actions.save, plus params.reloadSubpanels = false.
    // FinanceSaveActionTest guards the copy against drift.
    'recordActions' => [
        'actions' => [
            'save' => [
                'key' => 'save',
                'labelKey' => 'LBL_SAVE_BUTTON_LABEL',
                'priority' => 100,
                'params' => [
                    'expanded' => true,
                    'disableOnRun' => true,
                    'reloadSubpanels' => false,
                ],
                'modes' => ['edit'],
                'acl' => ['edit'],
            ],
        ],
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
