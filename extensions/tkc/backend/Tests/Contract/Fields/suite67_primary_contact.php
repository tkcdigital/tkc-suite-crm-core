<?php

// Field manifest of SUITE-67 (primary contact of a case), read by FieldManifestTest (C11).
// The relationship tkc_contacts_cases_primary is checked in PrimaryContactRelationshipTest.
return [
    [
        'module' => 'Cases',
        'fields' => [
            'tkc_primary_contact_id_c' => ['type' => 'id', 'audited' => true, 'required' => false, 'indexed' => true],
            // No mass update: a bulk change would make one person the primary contact of many cases (QA finding).
            // No import by name: the legacy wizard takes the first contact with that name (security C1).
            'tkc_primary_contact_c' => ['type' => 'relate', 'required' => true, 'massupdate' => false, 'importable' => false],
            'tkc_contacts_cases_primary' => ['type' => 'link'],
        ],
    ],
    [
        'module' => 'Contacts',
        'fields' => [
            'tkc_contacts_cases_primary' => ['type' => 'link'],
        ],
    ],
];
