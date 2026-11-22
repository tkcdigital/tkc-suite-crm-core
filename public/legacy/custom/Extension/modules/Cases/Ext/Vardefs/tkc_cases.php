<?php
// SUITE-66: TKC fields on Cases (main table, D2). Repair creates the columns.
// Dictionary key is the object name 'Case' (C2).

$tkcFields = [
    'tkc_case_ref_c' => [
        'vname' => 'LBL_TKC_CASE_REF',
        'type' => 'varchar',
        'len' => 20,
        'inline_edit' => false,
        'massupdate' => false,
    ],
    // OSC "TKC priority" is an impact scale. The core field priority stays the priority (plan 11.5).
    'tkc_priority_c' => [
        'vname' => 'LBL_TKC_PRIORITY',
        'type' => 'enum',
        'options' => 'tkc_impact_list',
        'len' => 30,
        'default' => '',
        'audited' => true,
    ],
    'tkc_customer_priority_c' => [
        'vname' => 'LBL_TKC_CUSTOMER_PRIORITY',
        'type' => 'varchar',
        'len' => 255,
    ],
    'tkc_tact_id_c' => [
        'vname' => 'LBL_TKC_TACT_ID',
        'type' => 'varchar',
        'len' => 10,
    ],
    'tkc_product_c' => [
        'vname' => 'LBL_TKC_PRODUCT',
        'type' => 'enum',
        'options' => 'tkc_product_list',
        'len' => 30,
        'required' => false,
    ],
    'tkc_category_c' => [
        'vname' => 'LBL_TKC_CATEGORY',
        'type' => 'enum',
        'options' => 'tkc_case_category_list',
        'len' => 30,
        'required' => false,
    ],
    'tkc_queue_c' => [
        'vname' => 'LBL_TKC_QUEUE',
        'type' => 'enum',
        'options' => 'tkc_queue_list',
        'len' => 30,
        'audited' => true,
    ],
    'tkc_expected_resolution_date_c' => [
        'vname' => 'LBL_TKC_EXPECTED_RESOLUTION_DATE',
        'type' => 'date',
        'enable_range_search' => true,
        'options' => 'date_range_search_dom',
    ],
    'tkc_date_closed_c' => [
        'vname' => 'LBL_TKC_DATE_CLOSED',
        'type' => 'datetime',
        'massupdate' => false,
        'enable_range_search' => true,
        'options' => 'date_range_search_dom',
    ],
    'tkc_case_origin_c' => [
        'vname' => 'LBL_TKC_CASE_ORIGIN',
        'type' => 'enum',
        'options' => 'tkc_case_origin_list',
        'len' => 20,
    ],
    'tkc_event_remark_c' => [
        'vname' => 'LBL_TKC_EVENT_REMARK',
        'type' => 'varchar',
        'len' => 255,
    ],
    'tkc_event_remarks_c' => [
        'vname' => 'LBL_TKC_EVENT_REMARKS',
        'type' => 'text',
        'rows' => 4,
        'cols' => 60,
    ],
    'tkc_tfs_project_id_c' => [
        'vname' => 'LBL_TKC_TFS_PROJECT_ID',
        'type' => 'varchar',
        'len' => 10,
    ],
];

foreach ($tkcFields as $name => $def) {
    $dictionary['Case']['fields'][$name] = array_merge(
        ['name' => $name, 'importable' => true, 'comment' => 'TKC SUITE-66'],
        $def
    );
}

// C4: one-field indices, string keys stop a duplicate entry.
foreach (['tkc_case_ref_c', 'tkc_queue_c'] as $name) {
    $dictionary['Case']['indices']["idx_cases_$name"] = [
        'name' => "idx_cases_$name",
        'type' => 'index',
        'fields' => [$name],
    ];
}
