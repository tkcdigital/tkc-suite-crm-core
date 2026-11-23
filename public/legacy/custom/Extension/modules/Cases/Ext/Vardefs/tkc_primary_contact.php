<?php
// SUITE-67: primary contact of a case, a 1:N foreign key (C9). Mirrors core cases_created_contact.
// The core M:N relationship contacts_cases is not changed. Repair adds the column and the index.
// The field is required in the Angular edit and create views only. It is on no layout before E8.

$dictionary['Case']['fields']['tkc_primary_contact_id_c'] = [
    'name' => 'tkc_primary_contact_id_c',
    'vname' => 'LBL_TKC_PRIMARY_CONTACT_ID',
    'type' => 'id',
    'reportable' => true,
    'importable' => true,
    'audited' => true,
    'comment' => 'TKC SUITE-67: primary contact of the case',
];
$dictionary['Case']['fields']['tkc_primary_contact_c'] = [
    'name' => 'tkc_primary_contact_c',
    'vname' => 'LBL_TKC_PRIMARY_CONTACT',
    'type' => 'relate',
    'source' => 'non-db',
    'required' => true,
    'save' => true,
    'id_name' => 'tkc_primary_contact_id_c',
    'link' => 'tkc_contacts_cases_primary',
    'module' => 'Contacts',
    'table' => 'contacts',
    'rname' => 'name',
    'comment' => 'TKC SUITE-67: primary contact of the case (name)',
];
$dictionary['Case']['fields']['tkc_contacts_cases_primary'] = [
    'name' => 'tkc_contacts_cases_primary',
    'type' => 'link',
    'relationship' => 'tkc_contacts_cases_primary',
    'module' => 'Contacts',
    'bean_name' => 'Contact',
    'link_type' => 'one',
    'source' => 'non-db',
    'vname' => 'LBL_TKC_PRIMARY_CONTACT',
    'side' => 'right',
    'id_name' => 'tkc_primary_contact_id_c',
];
$dictionary['Case']['relationships']['tkc_contacts_cases_primary'] = [
    'lhs_module' => 'Contacts',
    'lhs_table' => 'contacts',
    'lhs_key' => 'id',
    'rhs_module' => 'Cases',
    'rhs_table' => 'cases',
    'rhs_key' => 'tkc_primary_contact_id_c',
    'relationship_type' => 'one-to-many',
];
$dictionary['Case']['indices']['idx_cases_tkc_primary_contact_id_c'] = [
    'name' => 'idx_cases_tkc_primary_contact_id_c',
    'type' => 'index',
    'fields' => ['tkc_primary_contact_id_c'],
];
