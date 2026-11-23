<?php
// SUITE-71: OSC import key of the Contacts record (idempotent re-import). Repair adds the column and the index.
// The index is not unique, because of soft deletes. The field is never in a layout.

$dictionary['Contact']['fields']['osc_id_c'] = [
    'name' => 'osc_id_c',
    'vname' => 'LBL_TKC_OSC_ID',
    'type' => 'varchar',
    'len' => 20,
    'importable' => true,
    'inline_edit' => false,
    'massupdate' => false,
    'audited' => false,
    'unified_search' => false,
    'duplicate_merge' => 'disabled',
    'comment' => 'TKC SUITE-71: OSC record ID, the import upsert key',
];
$dictionary['Contact']['indices']['idx_contacts_osc_id_c'] = [
    'name' => 'idx_contacts_osc_id_c',
    'type' => 'index',
    'fields' => ['osc_id_c'],
];
