<?php
// SUITE-65: TKC fields on Contacts (main table, D2). Repair creates the columns.
// Dictionary key is the object name 'Contact' (C2).

$tkcYesNo = [
    'type' => 'enum',
    'options' => 'tkc_yes_no_list',
    'len' => 10,
    'displayType' => 'radio',
    'displayDirection' => 'row',
    'importable' => true,
    'comment' => 'TKC SUITE-65',
];

$tkcFields = [
    'tkc_contact_type_c' => [
        'vname' => 'LBL_TKC_CONTACT_TYPE',
        'type' => 'enum',
        'options' => 'tkc_contact_type_list',
        'len' => 30,
        'audited' => true,
    ],
    'tkc_function_level_c' => [
        'vname' => 'LBL_TKC_FUNCTION_LEVEL',
        'type' => 'enum',
        'options' => 'tkc_function_level_list',
        'len' => 10,
    ],
    'tkc_language_c' => [
        'vname' => 'LBL_TKC_LANGUAGE',
        'type' => 'enum',
        'options' => 'tkc_language_list',
        'len' => 10,
    ],
    'tkc_osc_usergroup_c' => ['vname' => 'LBL_TKC_OSC_USERGROUP'] + $tkcYesNo,
    'tkc_kmm_c' => ['vname' => 'LBL_TKC_KMM'] + $tkcYesNo,
    'tkc_cafe_c' => ['vname' => 'LBL_TKC_CAFE'] + $tkcYesNo,
    'tkc_service_tomorrow_c' => ['vname' => 'LBL_TKC_SERVICE_TOMORROW'] + $tkcYesNo,
    'tkc_newsletter_c' => ['vname' => 'LBL_TKC_NEWSLETTER'] + $tkcYesNo,
    'tkc_freshworks_usergroup_c' => ['vname' => 'LBL_TKC_FRESHWORKS_USERGROUP'] + $tkcYesNo,
];

foreach ($tkcFields as $name => $def) {
    $dictionary['Contact']['fields'][$name] = array_merge(
        ['name' => $name, 'importable' => true, 'comment' => 'TKC SUITE-65'],
        $def
    );
}

// C4: one-field index for the contact type (the SPAM filter reads it, E7).
$dictionary['Contact']['indices']['idx_contacts_tkc_contact_type_c'] = [
    'name' => 'idx_contacts_tkc_contact_type_c',
    'type' => 'index',
    'fields' => ['tkc_contact_type_c'],
];
