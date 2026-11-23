<?php
// SUITE-70: opportunities whose source case is the open case. E8 sets the final order.
$layout_defs['Cases']['subpanel_setup']['tkc_cases_opportunities'] = [
    'order' => 120,
    'module' => 'Opportunities',
    'subpanel_name' => 'default',
    'sort_order' => 'desc',
    'sort_by' => 'date_entered',
    'title_key' => 'LBL_TKC_OPPORTUNITIES_SUBPANEL',
    'get_subpanel_data' => 'tkc_cases_opportunities',
    'top_buttons' => [
        ['widget_class' => 'SubPanelTopButtonQuickCreate'],
        ['widget_class' => 'SubPanelTopSelectButton', 'mode' => 'MultiSelect'],
    ],
];
