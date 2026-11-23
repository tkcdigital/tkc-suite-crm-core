<?php

// SUITE-71: D5 parameter delta (read-modify-write of config/services/module/recordview/duplicate_ignore.yaml).
// The Angular Duplicate action must not copy the OSC import key or the human reference fields. A copy would give
// two records the same import key and break idempotent re-imports (osc_id_c), or the same quote or case number
// (tkc_reference_c, tkc_case_ref_c; SUITE-69 security C2c). Only the "default" list works: the per-module list
// is added as one nested array in record-duplicate.service.ts:55 and does nothing.
// Remove the workaround when upstream fixes that line. SUITE-66 and SUITE-69 fields are listed here as well.

use Symfony\Component\DependencyInjection\Container;

if (!isset($container)) {
    return;
}

/** @var Container $container */
$name = 'module.recordview.duplicate_ignore';
$ignore = $container->hasParameter($name) ? ($container->getParameter($name) ?? []) : [];
$default = array_values(array_filter((array) ($ignore['default'] ?? []), static fn ($entry): bool => $entry !== null));

foreach (['osc_id_c', 'tkc_reference_c', 'tkc_case_ref_c'] as $field) {
    if (!in_array($field, $default, true)) {
        $default[] = $field;
    }
}

$ignore['default'] = $default;
$container->setParameter($name, $ignore);
