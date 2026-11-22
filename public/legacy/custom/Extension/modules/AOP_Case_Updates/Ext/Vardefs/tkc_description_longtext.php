<?php
// SUITE-68 (C8): thread entries can be 1 MB. The field type stays text, only the column becomes longtext.
// Expected Repair SQL: ALTER TABLE aop_case_updates modify column description longtext.
$dictionary['AOP_Case_Updates']['fields']['description']['dbType'] = 'longtext';
