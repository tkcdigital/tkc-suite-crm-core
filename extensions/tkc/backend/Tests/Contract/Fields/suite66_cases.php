<?php

// Field manifest of SUITE-66 (Cases), read by FieldManifestTest (C11).
// Entry format: see FieldManifestTest.
$enum = static fn(string $list, array $extra = []): array => ['type' => 'enum', 'len' => 30, 'options' => $list] + $extra;

return [
    [
        'module' => 'Cases',
        'fields' => [
            'tkc_case_ref_c' => ['type' => 'varchar', 'len' => 20, 'indexed' => true],
            'tkc_priority_c' => $enum('tkc_impact_list', ['audited' => true, 'default' => '']),
            'tkc_customer_priority_c' => ['type' => 'varchar', 'len' => 255],
            'tkc_tact_id_c' => ['type' => 'varchar', 'len' => 10],
            'tkc_product_c' => $enum('tkc_product_list', ['required' => false]),
            'tkc_category_c' => $enum('tkc_case_category_list', ['required' => false]),
            'tkc_queue_c' => $enum('tkc_queue_list', ['audited' => true, 'indexed' => true]),
            'tkc_expected_resolution_date_c' => ['type' => 'date'],
            'tkc_date_closed_c' => ['type' => 'datetime'],
            'tkc_case_origin_c' => ['type' => 'enum', 'len' => 20, 'options' => 'tkc_case_origin_list'],
            'tkc_event_remark_c' => ['type' => 'varchar', 'len' => 255],
            'tkc_event_remarks_c' => ['type' => 'text'],
            'tkc_tfs_project_id_c' => ['type' => 'varchar', 'len' => 10],
        ],
        'absent' => [
            // A10, A5 and the mail header are not built.
            'tkc_disposition_c', 'tkc_assigned_group_c', 'tkc_mail_header_c', 'tkc_collect_before_share_c',
        ],
        'lists' => [
            'tkc_impact_list' => ['', 'no_impact', 'some_impact', 'production_disrupted', 'immediate_action'],
            'tkc_product_list' => ['', 'oracle_service_cloud', 'egain'],
            'tkc_case_category_list' => ['', 'service_request', 'complaint', 'question', 'change_request'],
            'tkc_queue_list' => [
                '', 'support_1st_line', 'support_2nd_line', 'change_request', 'info_tkc_digital',
                'answer_feedback', 'info', 'rightnow_automail', 'finance',
                'archive', 'quotes', 'spam', 'consular_affairs',
            ],
            'tkc_case_origin_list' => ['', 'email', 'selfservice', 'staff', 'other'],
            'case_status_dom' => [
                'Open_New', 'Open_Assigned', 'Open_Updated', 'Open_OnHold', 'Open_WaitOracle',
                'Open_AwaitApproval', 'Open_Pending Input', 'Open_WaitFreshworks', 'Open_FeatureRequest',
                'Open_CAJira', 'Closed_Closed', 'Closed_Archived',
            ],
            'case_priority_dom' => ['', 'P1', 'P2', 'P3', 'tkc_P4'],
            'case_type_dom' => ['', 'tkc_support_request', 'tkc_general_communication', 'tkc_info_message', 'tkc_project_communication'],
        ],
        // A2: every status key keeps the Open_ or Closed_ prefix (AOP derives state from it).
        'keyPatterns' => ['case_status_dom' => '/^(Open|Closed)_/'],
        'values' => ['case_priority_default_key' => ''],
        'labels' => [
            'case_status_dom' => [
                'Open_New' => 'Unresolved', 'Open_Assigned' => 'In progress',
                'Open_Pending Input' => 'Waiting for customer (info needed)', 'Closed_Closed' => 'Solved',
            ],
            'case_priority_dom' => ['P1' => 'Priority 1', 'P2' => 'Priority 2', 'P3' => 'Priority 3', 'tkc_P4' => 'Priority 4'],
        ],
    ],
];
