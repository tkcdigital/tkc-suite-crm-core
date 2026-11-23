<?php
// SUITE-69: TKC fields on Opportunities (main table, D2). Repair creates the columns.
// Dictionary key is the object name 'Opportunity' (C2, S11 pitfall: the module name compiles into nothing).

$tkcFields = [
    'tkc_closed_amount_c' => [
        'vname' => 'LBL_TKC_CLOSED_AMOUNT',
        'type' => 'currency',
        'len' => 26,
        'precision' => 6,
        'massupdate' => false,
    ],
    'tkc_date_closed_actual_c' => [
        'vname' => 'LBL_TKC_DATE_CLOSED_ACTUAL',
        'type' => 'date',
        'enable_range_search' => true,
        'options' => 'date_range_search_dom',
    ],
    'tkc_sales_type_c' => [
        'vname' => 'LBL_TKC_SALES_TYPE',
        'type' => 'enum',
        'options' => 'tkc_sales_type_list',
        'len' => 20,
    ],
    'tkc_country_c' => [
        'vname' => 'LBL_TKC_COUNTRY',
        'type' => 'enum',
        'options' => 'tkc_country_list',
        'len' => 10,
    ],
    // Non-unique index: OSC uniqueness is not verified (plan 4.4). A validator follows in E10.
    'tkc_reference_c' => [
        'vname' => 'LBL_TKC_REFERENCE',
        'type' => 'varchar',
        'len' => 12,
        'inline_edit' => false,
        'massupdate' => false,
    ],
    'tkc_quote_date_c' => [
        'vname' => 'LBL_TKC_QUOTE_DATE',
        'type' => 'date',
        'enable_range_search' => true,
        'options' => 'date_range_search_dom',
    ],
    'tkc_approach_c' => [
        'vname' => 'LBL_TKC_APPROACH',
        'type' => 'enum',
        'options' => 'tkc_approach_list',
        'len' => 20,
    ],
    'tkc_initial_contact_c' => [
        'vname' => 'LBL_TKC_INITIAL_CONTACT',
        'type' => 'date',
        'enable_range_search' => true,
        'options' => 'date_range_search_dom',
    ],
];

foreach ($tkcFields as $name => $def) {
    $dictionary['Opportunity']['fields'][$name] = array_merge(
        ['name' => $name, 'importable' => true, 'comment' => 'TKC SUITE-69'],
        $def
    );
}

// C4: one-field index, string key stops a duplicate entry.
$dictionary['Opportunity']['indices']['idx_opportunities_tkc_reference_c'] = [
    'name' => 'idx_opportunities_tkc_reference_c',
    'type' => 'index',
    'fields' => ['tkc_reference_c'],
];
