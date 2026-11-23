<?php
// SUITE-70: source case of an opportunity, a 1:N foreign key (C9, no join table). Case 1 : N Opportunities.
// Repair adds the column and the index. The Cases side (link and subpanel) is in the Cases extension files.

$dictionary['Opportunity']['fields']['tkc_source_case_id_c'] = [
    'name' => 'tkc_source_case_id_c',
    'vname' => 'LBL_TKC_SOURCE_CASE_ID',
    'type' => 'id',
    'reportable' => true,
    'importable' => true,
    'audited' => true,
    'comment' => 'TKC SUITE-70: source case of the opportunity',
];
$dictionary['Opportunity']['fields']['tkc_source_case_c'] = [
    'name' => 'tkc_source_case_c',
    'vname' => 'LBL_TKC_SOURCE_CASE',
    'type' => 'relate',
    'source' => 'non-db',
    // A bulk change would link many opportunities to one case.
    'massupdate' => false,
    // The legacy Import wizard matches a relate field by name. The import (E4) sets the id by OSC id.
    'importable' => false,
    'save' => true,
    'id_name' => 'tkc_source_case_id_c',
    'link' => 'tkc_cases_opportunities',
    'module' => 'Cases',
    'table' => 'cases',
    'rname' => 'name',
    'comment' => 'TKC SUITE-70: source case of the opportunity (name)',
];
$dictionary['Opportunity']['fields']['tkc_cases_opportunities'] = [
    'name' => 'tkc_cases_opportunities',
    'type' => 'link',
    'relationship' => 'tkc_cases_opportunities',
    'module' => 'Cases',
    'bean_name' => 'aCase',
    'link_type' => 'one',
    'source' => 'non-db',
    'vname' => 'LBL_TKC_SOURCE_CASE',
    'side' => 'right',
    'id_name' => 'tkc_source_case_id_c',
];
$dictionary['Opportunity']['relationships']['tkc_cases_opportunities'] = [
    'lhs_module' => 'Cases',
    'lhs_table' => 'cases',
    'lhs_key' => 'id',
    'rhs_module' => 'Opportunities',
    'rhs_table' => 'opportunities',
    'rhs_key' => 'tkc_source_case_id_c',
    'relationship_type' => 'one-to-many',
];
$dictionary['Opportunity']['indices']['idx_opportunities_tkc_source_case_id_c'] = [
    'name' => 'idx_opportunities_tkc_source_case_id_c',
    'type' => 'index',
    'fields' => ['tkc_source_case_id_c'],
];
