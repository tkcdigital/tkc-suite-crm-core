<?php
// SUITE-66 (C7): core list overrides for Cases. Each assignment replaces the whole core list.
// Keys that are left out are hidden. Never rename a core key (D7). Overrides register: this file (4 lists).
//
// case_status_dom: 12 keys in OSC order. A2: every key keeps the Open_ or Closed_ prefix, because AOP
// derives the state from it (CaseUpdatesHook.php:308). Hidden core keys: Closed_Rejected, Closed_Duplicate.
// Core search filter "open only" and the My Cases dashlet still use the core keys (follow-ups SUITE-200, SUITE-201).
$app_list_strings['case_status_dom'] = [
    'Open_New' => 'Unresolved',
    'Open_Assigned' => 'In progress',
    'Open_Updated' => 'Updated',
    'Open_OnHold' => 'On hold',
    'Open_WaitOracle' => 'Waiting for Oracle',
    'Open_AwaitApproval' => 'Ready, awaiting approval of the requester',
    'Open_Pending Input' => 'Waiting for customer (info needed)',
    'Open_WaitFreshworks' => 'Waiting for Freshworks',
    'Open_FeatureRequest' => 'Feature request',
    'Open_CAJira' => 'CA Jira',
    'Closed_Closed' => 'Solved',
    'Closed_Archived' => 'Archived',
];

// Priority: A13 key tkc_P4. The empty first key keeps the default empty in both UIs.
// Core reads case_priority_default_key only in modules/Cases/CasesQuickCreate.php:61 (legacy quick create).
$app_list_strings['case_priority_dom'] = [
    '' => '',
    'P1' => 'Priority 1',
    'P2' => 'Priority 2',
    'P3' => 'Priority 3',
    'tkc_P4' => 'Priority 4',
];
$app_list_strings['case_priority_default_key'] = '';

// Type: hidden core keys Administration, Product, User.
$app_list_strings['case_type_dom'] = [
    '' => '',
    'tkc_support_request' => 'Support request',
    'tkc_general_communication' => 'General communication',
    'tkc_info_message' => 'Info message',
    'tkc_project_communication' => 'Project communication',
];
