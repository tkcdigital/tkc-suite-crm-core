<?php
// SUITE-68: OSC import key of the thread entry (idempotent re-import). Repair adds the column and the index.

$dictionary['AOP_Case_Updates']['fields']['osc_id_c'] = [
    'name' => 'osc_id_c',
    'vname' => 'LBL_OSC_ID',
    'type' => 'varchar',
    'len' => 20,
    'inline_edit' => false,
    'massupdate' => false,
    'comment' => 'TKC SUITE-68: OSC thread entry ID',
];
$dictionary['AOP_Case_Updates']['indices']['idx_aop_case_updates_osc_id_c'] = [
    'name' => 'idx_aop_case_updates_osc_id_c',
    'type' => 'index',
    'fields' => ['osc_id_c'],
];
