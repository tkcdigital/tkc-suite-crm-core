<?php

// SUITE-183: D5 parameter delta (read-modify-write of the core "ui" parameter, config/services/ui/ui.yaml).
// Removes the TinyMCE "media" plugin from the Angular edit field. Reason: npm tinymce 7.9.3 has a high
// media-plugin XSS advisory (GHSA-mf2p-h6hf-fcwm) and no 7.x fix. Remove this delta when upstream ships a fixed TinyMCE.
// The edit component merges its own defaults with this list by lodash merge (index by index). Keep at least
// 3 entries, so that every default entry (one of them holds "media") is replaced. TinyMCE accepts spaces and
// commas as separators, so both count as a plugin-name boundary (security review F4).

use Symfony\Component\DependencyInjection\Container;

if (!isset($container)) {
    return;
}

/** @var Container $container */
$ui = $container->getParameter('ui') ?? [];
$plugins = $ui['tinymce']['edit']['plugins'] ?? [];

$plugins = array_map(
    static fn (string $entry): string => trim((string) preg_replace(['/\s*,\s*,+\s*/', '/\s+/'], [',', ' '], (string) preg_replace('/(^|[\s,])media(?=[\s,]|$)/', '$1', $entry)), " ,"),
    $plugins
);
while (count($plugins) < 3) {
    $plugins[] = '';
}

$ui['tinymce']['edit']['plugins'] = array_values($plugins);
$container->setParameter('ui', $ui);
