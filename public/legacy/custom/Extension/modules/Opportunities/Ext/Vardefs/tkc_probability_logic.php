<?php
// SUITE-69 (C8, spike S11): core vardef override. The probability follows the sales stage in the edit view
// (field logic updateValue, no hook). One entry per sales_stage_dom key. targetValue = the sales_probability_dom value.
// Accepted gap: a stage change outside the Angular edit view does not recompute an existing probability.
// Overrides register: this file. Keep it in line with en_us.tkc_sales_stage_dom.php.
$tkcProbability = [
    'Prospecting' => '0',
    'Qualification' => '5',
    'Needs Analysis' => '10',
    'tkc_quote_multi' => '25',
    'tkc_quote_single' => '50',
    'Negotiation/Review' => '80',
    'Closed Won' => '100',
    'Closed Lost' => '0',
];

$logic = [];
foreach ($tkcProbability as $stage => $target) {
    $id = 'tkc_prob_' . trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($stage)), '_');
    $logic[$id] = [
        'key' => 'updateValue',
        'modes' => ['edit', 'create'],
        'params' => [
            'fieldDependencies' => ['sales_stage'],
            'activeOnFields' => ['sales_stage' => [$stage]],
            'targetValue' => $target,
        ],
    ];
}
$dictionary['Opportunity']['fields']['probability']['logic'] = $logic;
