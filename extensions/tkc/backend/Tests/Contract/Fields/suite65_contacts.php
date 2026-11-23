<?php

// Field manifest of SUITE-65 (Contacts), read by FieldManifestTest (C11).
// Entry format: see FieldManifestTest. osc_id_c is in suite71_osc_id.php.
$yesNo = ['type' => 'enum', 'len' => 10, 'options' => 'tkc_yes_no_list', 'displayType' => 'radio'];

return [
    [
        'module' => 'Contacts',
        'fields' => [
            'tkc_contact_type_c' => [
                'type' => 'enum', 'len' => 30, 'options' => 'tkc_contact_type_list', 'audited' => true, 'indexed' => true,
            ],
            'tkc_function_level_c' => ['type' => 'enum', 'len' => 10, 'options' => 'tkc_function_level_list'],
            'tkc_language_c' => ['type' => 'enum', 'len' => 10, 'options' => 'tkc_language_list'],
            'tkc_osc_usergroup_c' => $yesNo,
            'tkc_kmm_c' => $yesNo,
            'tkc_cafe_c' => $yesNo,
            'tkc_service_tomorrow_c' => $yesNo,
            'tkc_newsletter_c' => $yesNo,
            'tkc_freshworks_usergroup_c' => $yesNo,
        ],
        'absent' => [
            // Not built (SUITE-65 prescription).
            'tkc_crm_modules_c', 'tkc_tkc_cafe_c', 'lawful_basis_c', 'tkc_login_c', 'tkc_sla_c',
        ],
        'lists' => [
            'tkc_contact_type_list' => [
                '', 'tkbc_prospect', 'tkbc_customer', 'tkbc_support', 'tkbc_sales',
                'ssc_prospect', 'ssc_customer', 'ssc_support', 'ssc_sales',
                'partner_g_customer', 'partner_h_customer', 'partner_h_supplier',
                'fs', 'tkc_bodybuilder', 'spam',
            ],
            'tkc_function_level_list' => ['', 'C', 'M', 'O'],
            'tkc_language_list' => ['', 'nl', 'en'],
            'tkc_yes_no_list' => ['', 'yes', 'no'],
        ],
        'labels' => [
            'tkc_contact_type_list' => [
                'partner_g_customer' => 'Partner G customer',
                'spam' => 'Spam',
            ],
            'tkc_function_level_list' => ['C' => 'C', 'M' => 'M', 'O' => 'O'],
            'tkc_language_list' => ['nl' => 'Dutch', 'en' => 'English'],
        ],
    ],
];
