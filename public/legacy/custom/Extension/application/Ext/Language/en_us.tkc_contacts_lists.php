<?php
// SUITE-65: lists of the TKC fields on Contacts. Every list starts with the empty key (C5).
// Keys and labels contain no customer names (C13). The seed (E5-S5) reads these keys at run time.
$app_list_strings['tkc_contact_type_list'] = [
    '' => '',
    'tkbc_prospect' => 'TKBC prospect',
    'tkbc_customer' => 'TKBC customer',
    'tkbc_support' => 'TKBC support',
    'tkbc_sales' => 'TKBC sales',
    'ssc_prospect' => 'SSC prospect',
    'ssc_customer' => 'SSC customer',
    'ssc_support' => 'SSC support',
    'ssc_sales' => 'SSC sales',
    'partner_g_customer' => 'Partner G customer',
    'partner_h_customer' => 'Partner H customer',
    'partner_h_supplier' => 'Partner H supplier',
    'fs' => 'FS',
    'tkc_bodybuilder' => 'TKC bodybuilder',
    'spam' => 'Spam',
];
// Labels C, M, O stay until the owner gives the meaning (Q13).
$app_list_strings['tkc_function_level_list'] = [
    '' => '',
    'C' => 'C',
    'M' => 'M',
    'O' => 'O',
];
$app_list_strings['tkc_language_list'] = [
    '' => '',
    'nl' => 'Dutch',
    'en' => 'English',
];
