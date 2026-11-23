<?php
// SUITE-67: Contacts side of the relationship tkc_contacts_cases_primary (declared in the Cases vardef).
// It lets E8 actions and the import load the cases of a contact. No subpanel.

$dictionary['Contact']['fields']['tkc_contacts_cases_primary'] = [
    'name' => 'tkc_contacts_cases_primary',
    'type' => 'link',
    'relationship' => 'tkc_contacts_cases_primary',
    'module' => 'Cases',
    'bean_name' => 'aCase',
    'source' => 'non-db',
    'vname' => 'LBL_TKC_PRIMARY_CASES',
];
