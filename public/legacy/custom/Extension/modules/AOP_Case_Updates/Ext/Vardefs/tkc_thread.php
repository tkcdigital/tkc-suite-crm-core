<?php
// SUITE-68: TKC thread fields on AOP_Case_Updates (main table, D2). Repair creates the columns.
// Dictionary key is the object name 'AOP_Case_Updates' (C2).

$dictionary['AOP_Case_Updates']['fields']['tkc_entry_type_c'] = [
    'name' => 'tkc_entry_type_c',
    'vname' => 'LBL_TKC_ENTRY_TYPE',
    'type' => 'enum',
    'options' => 'tkc_entry_type_list',
    'len' => 20,
    'default' => 'staff',
    'massupdate' => false,
    'comment' => 'TKC SUITE-68',
];
$dictionary['AOP_Case_Updates']['fields']['tkc_channel_c'] = [
    'name' => 'tkc_channel_c',
    'vname' => 'LBL_TKC_CHANNEL',
    'type' => 'enum',
    'options' => 'tkc_channel_list',
    'len' => 20,
    'default' => '',
    'massupdate' => false,
    'comment' => 'TKC SUITE-68',
];
