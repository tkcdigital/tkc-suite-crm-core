<?php
// SUITE-63 (C8): core vardef override. New accounts start as "Relation". Expected Repair SQL: none.
$dictionary['Account']['fields']['account_type']['default'] = 'tkc_relation';
