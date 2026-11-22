<?php

// Allowlist of violations that come from SuiteCRM core files, not from TKC changes (SUITE-47).
// Keep empty if possible. One entry per violation:
//   'Module' => [['file' => ..., 'path' => ..., 'reason' => 'why core is wrong and we do not fix it']]
return [
    'Cases' => [
        [
            'file' => 'subpanels/ForEmails',
            'path' => 'reply_to_status',
            'reason' => 'Core Cases subpanel ForEmails lists reply_to_status, which is an Emails field. Core defect, not changed by TKC. Visible since SUITE-66 added Cases to modules.php.',
        ],
    ],
    'AOP_Case_Updates' => [
        [
            'file' => 'detailviewdefs',
            'path' => 'panels.default.2.1',
            'reason' => 'Core detailviewdefs of AOP_Case_Updates shows aop_case_updates_contacts_1_name, which has no vardef (the relationship is not defined in the module). Core defect, not changed by TKC. Visible since SUITE-68 added AOP_Case_Updates to modules.php.',
        ],
    ],
];
