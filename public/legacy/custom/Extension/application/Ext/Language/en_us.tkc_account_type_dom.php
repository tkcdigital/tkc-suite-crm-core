<?php
// SUITE-63 (C7): core list override, replaces the whole core list. Keys that are left out are hidden.
// Never rename a core key (D7). Overrides register: account_type_dom.
$app_list_strings['account_type_dom'] = [
    'tkc_lead' => 'Lead',
    'Prospect' => 'Prospect',
    'Customer' => 'Customer',
    'tkc_supplier' => 'Supplier',
    'tkc_relation' => 'Relation',
    'tkc_former_customer' => 'Former customer',
];
