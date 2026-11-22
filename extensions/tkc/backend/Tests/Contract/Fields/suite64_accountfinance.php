<?php

// Field manifest of SUITE-64 (tkc_AccountFinance), read by FieldManifestTest (C11).
$text = ['type' => 'text', 'audited' => true];

return [
    [
        'module' => 'tkc_AccountFinance',
        'fields' => [
            'rate_angels' => $text,
            'rate_snor' => $text,
            'rate_body' => $text,
            'expected_revenue_current_fy' => $text,
            'expected_revenue_next_fy' => $text,
            'other_financial_terms' => $text,
            'account_id' => ['type' => 'id', 'required' => true, 'indexed' => true],
            'account_name' => ['type' => 'relate'],
        ],
        'absent' => [],
    ],
];
