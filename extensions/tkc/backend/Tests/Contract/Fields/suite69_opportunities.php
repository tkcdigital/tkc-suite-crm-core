<?php

// Field manifest of SUITE-69 (Opportunities), read by FieldManifestTest (C11).
// Entry format: see FieldManifestTest.
return [
    [
        'module' => 'Opportunities',
        'fields' => [
            'tkc_closed_amount_c' => ['type' => 'currency'],
            'tkc_date_closed_actual_c' => ['type' => 'date'],
            'tkc_sales_type_c' => ['type' => 'enum', 'len' => 20, 'options' => 'tkc_sales_type_list'],
            'tkc_country_c' => ['type' => 'enum', 'len' => 10, 'options' => 'tkc_country_list'],
            'tkc_reference_c' => ['type' => 'varchar', 'len' => 12, 'indexed' => true],
            'tkc_quote_date_c' => ['type' => 'date'],
            'tkc_approach_c' => ['type' => 'enum', 'len' => 20, 'options' => 'tkc_approach_list'],
            'tkc_initial_contact_c' => ['type' => 'date'],
            // C8 override: only the length changes.
            'name' => ['type' => 'name', 'len' => 255],
        ],
        'absent' => [
            // Not built (SUITE-69 prescription).
            'waarschijnlijkheid', 'tkc_waarschijnlijkheid_c', 'type_medewerker', 'tkc_type_medewerker_c',
            'VERKOOP_ID', 'verkoop_id', 'tkc_verkoop_id_c',
        ],
        'lists' => [
            'tkc_sales_type_list' => ['', 'cross_sell', 'up_sell'],
            'tkc_country_list' => ['', 'nl', 'de', 'be', 'other', 'dk', 'se', 'no', 'lu'],
            'tkc_approach_list' => ['', 'website', 'phone', 'social', 'network'],
            // C7: 8 keys, core keys kept where they stay, A13 keys tkc_*. Hidden core keys are left out.
            'sales_stage_dom' => [
                'Prospecting', 'Qualification', 'Needs Analysis', 'tkc_quote_multi', 'tkc_quote_single',
                'Negotiation/Review', 'Closed Won', 'Closed Lost',
            ],
            'sales_probability_dom' => [
                'Prospecting', 'Qualification', 'Needs Analysis', 'tkc_quote_multi', 'tkc_quote_single',
                'Negotiation/Review', 'Closed Won', 'Closed Lost',
            ],
        ],
        'labels' => [
            'sales_stage_dom' => [
                'Prospecting' => '0% - Lead',
                'Qualification' => '5% - Qualify',
                'Needs Analysis' => '10% - Analysis',
                'tkc_quote_multi' => '25% - Quote, several vendors',
                'tkc_quote_single' => '50% - Quote, single vendor',
                'Negotiation/Review' => '80% - Verbal commitment',
                'Closed Won' => '100% - Won',
                'Closed Lost' => 'Lost',
            ],
            // Probability per stage (the values are strings).
            'sales_probability_dom' => [
                'Prospecting' => '0', 'Qualification' => '5', 'Needs Analysis' => '10',
                'tkc_quote_multi' => '25', 'tkc_quote_single' => '50', 'Negotiation/Review' => '80',
                'Closed Won' => '100', 'Closed Lost' => '0',
            ],
        ],
    ],
];
