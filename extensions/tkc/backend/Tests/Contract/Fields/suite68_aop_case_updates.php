<?php

// Field manifest of SUITE-68 (AOP_Case_Updates, the case thread), read by FieldManifestTest (C11).
// Entry format: see FieldManifestTest.
return [
    [
        'module' => 'AOP_Case_Updates',
        'fields' => [
            'tkc_entry_type_c' => ['type' => 'enum', 'len' => 20, 'options' => 'tkc_entry_type_list', 'default' => 'staff'],
            'tkc_channel_c' => ['type' => 'enum', 'len' => 20, 'options' => 'tkc_channel_list', 'default' => ''],
            'osc_id_c' => ['type' => 'varchar', 'len' => 20, 'indexed' => true],
            // C8 override: the column becomes longtext, the field type stays text.
            'description' => ['type' => 'text', 'dbType' => 'longtext'],
        ],
        'absent' => [
            // The before-save rule (E9-S1), CC/BCC, attachments and the mail header are not built.
            'tkc_cc_c', 'tkc_bcc_c', 'tkc_mail_header_c',
        ],
        'lists' => [
            'tkc_entry_type_list' => ['note', 'staff', 'customer', 'customer_proxy'],
            'tkc_channel_list' => ['', 'email', 'phone', 'web', 'chat', 'fax', 'social', 'other'],
        ],
        'labels' => [
            'tkc_entry_type_list' => [
                'note' => 'Internal note', 'staff' => 'Agent reply', 'customer' => 'Customer',
                'customer_proxy' => 'Customer (logged by agent)',
            ],
        ],
    ],
];
