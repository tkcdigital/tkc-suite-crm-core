<?php

// Field manifest of SUITE-71 (OSC import key osc_id_c on the four imported core modules), read by FieldManifestTest (C11).
// AOP_Case_Updates is in suite68_aop_case_updates.php. AOS_Contracts is in SUITE-144.
$entries = [];
foreach (['Accounts', 'Contacts', 'Cases', 'Opportunities'] as $module) {
    $entries[] = [
        'module' => $module,
        'fields' => [
            'osc_id_c' => ['type' => 'varchar', 'len' => 20, 'audited' => false, 'required' => false, 'indexed' => true],
        ],
    ];
}

return $entries;
