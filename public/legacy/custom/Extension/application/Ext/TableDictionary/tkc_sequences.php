<?php
/**
 * SUITE-62: one counter for each name and year. Used by App\Extension\tkc\backend\Sequence\Sequence.
 * Schema comes from Quick Repair and Rebuild (tkc:repair). No id and no deleted column.
 * Do not name a column last_value or year: LAST_VALUE is reserved in MySQL 8.
 */
$dictionary['tkc_sequences'] = [
    'table' => 'tkc_sequences',
    'fields' => [
        [
            'name' => 'seq_name',
            'type' => 'varchar',
            'len' => 40,
            'required' => true,
            'isnull' => 'false',
        ],
        [
            'name' => 'seq_year',
            'type' => 'int',
            'required' => true,
            'isnull' => 'false',
        ],
        [
            'name' => 'seq_value',
            'type' => 'int',
            'required' => true,
            'isnull' => 'false',
            'default' => '0',
        ],
        [
            'name' => 'date_modified',
            'type' => 'datetime',
        ],
    ],
    'indices' => [
        [
            'name' => 'tkc_sequences_pk',
            'type' => 'primary',
            'fields' => ['seq_name', 'seq_year'],
        ],
    ],
];
