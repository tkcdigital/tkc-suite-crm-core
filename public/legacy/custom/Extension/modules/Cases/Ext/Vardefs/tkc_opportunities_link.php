<?php
// SUITE-70: Cases side of the relationship tkc_cases_opportunities (declared in the Opportunities vardef).

$dictionary['Case']['fields']['tkc_cases_opportunities'] = [
    'name' => 'tkc_cases_opportunities',
    'type' => 'link',
    'relationship' => 'tkc_cases_opportunities',
    'module' => 'Opportunities',
    'bean_name' => 'Opportunity',
    'source' => 'non-db',
    'vname' => 'LBL_TKC_OPPORTUNITIES_SUBPANEL',
];
