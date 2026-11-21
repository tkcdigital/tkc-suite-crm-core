<?php

use Symfony\Component\DependencyInjection\Container;

if (!isset($container)) {
    return;
}

/** @var Container $container */
$extensions = $container->getParameter('extensions') ?? [];

$extensions['tkc'] = [
    'remoteEntry' => '../extensions/tkc/remoteEntry.js',
    'remoteName' => 'tkc',
    'enabled' => true,
    'extension_name' => 'TKC Extension',
    'extension_uri' =>  'https://github.com/tkcdigital/tkc-suite-crm-core',
    'description' => 'TKC customisations: design system theme, views and fields',
    'version' =>  '1.0.0',
    'author' =>  'TKC',
    'author_uri' =>  'https://github.com/tkcdigital',
    'license' =>  'GPL3'
];

$container->setParameter('extensions', $extensions);
