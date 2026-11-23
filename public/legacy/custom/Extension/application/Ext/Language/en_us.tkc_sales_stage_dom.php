<?php
// SUITE-69 (C7): core list overrides for Opportunities. Each assignment replaces the whole core list.
// Never rename a core key (D7). New keys are tkc_<key> (A13). Overrides register: this file (2 lists).
//
// sales_stage_dom: 8 keys. Hidden core keys: Value Proposition, Id. Decision Makers, Perception Analysis,
// Proposal/Price Quote. Core "Create opportunity" on a quote writes Proposal/Price Quote and reads its
// probability (modules/AOS_Quotes/createOpportunity.php:49-50): follow-up SUITE-202. The My Opportunities
// dashlet filters on core stage keys: follow-up SUITE-201.
$app_list_strings['sales_stage_dom'] = [
    'Prospecting' => '0% - Lead',
    'Qualification' => '5% - Qualify',
    'Needs Analysis' => '10% - Analysis',
    'tkc_quote_multi' => '25% - Quote, several vendors',
    'tkc_quote_single' => '50% - Quote, single vendor',
    'Negotiation/Review' => '80% - Verbal commitment',
    'Closed Won' => '100% - Won',
    'Closed Lost' => 'Lost',
];

// Same 8 keys as sales_stage_dom. The vardef logic in tkc_probability_logic.php repeats these values.
$app_list_strings['sales_probability_dom'] = [
    'Prospecting' => '0',
    'Qualification' => '5',
    'Needs Analysis' => '10',
    'tkc_quote_multi' => '25',
    'tkc_quote_single' => '50',
    'Negotiation/Review' => '80',
    'Closed Won' => '100',
    'Closed Lost' => '0',
];
