<?php

// Field manifest of SUITE-70 (source case of an opportunity), read by FieldManifestTest (C11).
// The relationship tkc_cases_opportunities is checked in SourceCaseRelationshipTest.
return [
    [
        'module' => 'Opportunities',
        'fields' => [
            // Audited: a change of the source case must show in the change log (SUITE-67 lesson).
            'tkc_source_case_id_c' => ['type' => 'id', 'audited' => true, 'indexed' => true],
            // No mass update and no import by name (SUITE-67 lessons). The import (E4) sets the id by OSC id.
            'tkc_source_case_c' => ['type' => 'relate', 'massupdate' => false, 'importable' => false],
            'tkc_cases_opportunities' => ['type' => 'link'],
        ],
    ],
    [
        'module' => 'Cases',
        'fields' => [
            'tkc_cases_opportunities' => ['type' => 'link'],
        ],
    ],
];
