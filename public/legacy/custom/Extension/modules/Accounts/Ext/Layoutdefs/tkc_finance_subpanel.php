<?php
// SUITE-64: Finance subpanel on Accounts. Create only: no select button (one row per account).
$layout_defs['Accounts']['subpanel_setup']['tkc_accounts_tkc_accountfinance'] = [
    'order' => 110,
    'module' => 'tkc_AccountFinance',
    'subpanel_name' => 'default',
    'sort_order' => 'asc',
    'sort_by' => 'id',
    'title_key' => 'LBL_TKC_ACCOUNTFINANCE_SUBPANEL_TITLE',
    'get_subpanel_data' => 'tkc_accounts_tkc_accountfinance',
    'top_buttons' => [['widget_class' => 'SubPanelTopButtonQuickCreate']],
];
