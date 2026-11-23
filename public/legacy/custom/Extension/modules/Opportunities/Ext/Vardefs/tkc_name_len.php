<?php
// SUITE-69 (C8): core vardef override. OSC opportunity names are longer than the core 50 characters.
// Only the length changes. Overrides register: this file.
$dictionary['Opportunity']['fields']['name']['len'] = '255';
