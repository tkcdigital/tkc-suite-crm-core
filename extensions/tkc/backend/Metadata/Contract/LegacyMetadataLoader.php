<?php

namespace App\Extension\tkc\backend\Metadata\Contract;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Loads the metadata of one module from the legacy layer (SUITE-47). Reads files and vardefs
 * only: no record read, no database write. Needs a compiled Ext/ (run tkc:repair first).
 * Public so the contract test can fetch it from the dev kernel.
 */
#[Autoconfigure(public: true)]
class LegacyMetadataLoader
{
    public function __construct(private readonly DefaultLegacyHandler $legacyHandler)
    {
    }

    /**
     * @return array{layouts: array<string, mixed>, fieldDefs: array<string, array<string, mixed>>, indices: array<string, array<string, mixed>>, appListStrings: array<string, mixed>}
     */
    public function load(string $module): array
    {
        $buffering = ob_get_level();
        ob_start(); // the legacy code echoes HTML; drop it
        try {
            return $this->read($module);
        } finally {
            while (ob_get_level() > $buffering) {
                ob_end_clean();
            }
        }
    }

    private function read(string $module): array
    {
        // Not init(): it starts a PHP session, which only works inside bin/console. This loader also
        // runs from PHPUnit and needs no session. Do what init() does besides that: sugarEntry, cwd.
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }
        // The legacy entry point dies on a PHP_SELF with odd characters (PHPUnit child processes
        // run from a temp file). Use a safe value while legacy loads, then restore.
        $previousSelf = $_SERVER['PHP_SELF'] ?? null;
        $_SERVER['PHP_SELF'] = 'index.php';
        $previousDir = getcwd();
        chdir($this->legacyHandler->getProjectDir() . '/public/legacy');
        try {
            $this->legacyHandler->runLegacyEntryPoint();

            global $beanList;

            if (!isset($beanList[$module])) {
                throw new \RuntimeException('Unknown module: ' . $module);
            }
            $bean = \BeanFactory::newBean($module);
            if (!$bean instanceof \SugarBean) {
                throw new \RuntimeException('No bean for module: ' . $module);
            }

            return [
                'layouts' => $this->loadLayouts($module),
                'fieldDefs' => $bean->field_defs,
                'indices' => $GLOBALS['dictionary'][$bean->getObjectName()]['indices'] ?? [],
                'appListStrings' => return_app_list_strings_language('en_us'),
            ];
        } finally {
            if ($previousSelf === null) {
                unset($_SERVER['PHP_SELF']);
            } else {
                $_SERVER['PHP_SELF'] = $previousSelf;
            }
            if ($previousDir !== false) {
                chdir($previousDir);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLayouts(string $module): array
    {
        $layouts = [];

        $viewdefs = $this->includeVar($this->resolve($module, 'detailviewdefs.php'), 'viewdefs');
        if (isset($viewdefs[$module]['DetailView'])) {
            $layouts['detailviewdefs'] = $viewdefs[$module]['DetailView'];
        }

        $listViewDefs = $this->includeVar($this->resolve($module, 'listviewdefs.php'), 'listViewDefs');
        if (isset($listViewDefs[$module])) {
            $layouts['listviewdefs'] = $listViewDefs[$module];
        }

        $searchdefs = $this->includeVar($this->resolve($module, 'searchdefs.php'), 'searchdefs');
        if (isset($searchdefs[$module]['layout'])) {
            $layouts['searchdefs'] = $searchdefs[$module]['layout'];
        }

        $searchFields = $this->includeVar($this->resolve($module, 'SearchFields.php'), 'searchFields');
        $layouts['searchfields'] = array_keys($searchFields[$module] ?? []);

        $subpanels = [];
        $files = array_merge(
            glob("modules/$module/metadata/subpanels/*.php") ?: [],
            glob("custom/modules/$module/metadata/subpanels/*.php") ?: []
        );
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $custom = "custom/modules/$module/metadata/subpanels/$name.php";
            $layout = $this->includeVar(is_file($custom) ? $custom : $file, 'subpanel_layout');
            if (isset($layout['list_fields'])) {
                $subpanels[$name] = $layout['list_fields'];
            }
        }
        $layouts['subpanels'] = $subpanels;

        return $layouts;
    }

    /**
     * Custom file first, like SuiteCRM (custom/modules/<M>/metadata, then modules/<M>/metadata).
     */
    private function resolve(string $module, string $file): ?string
    {
        foreach (["custom/modules/$module/metadata/$file", "modules/$module/metadata/$file"] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Includes a layout file in closure scope so it cannot touch our variables.
     */
    private function includeVar(?string $path, string $var): array
    {
        if ($path === null) {
            return [];
        }
        $load = static function (string $__path, string $__var): array {
            $$__var = [];
            include $__path;
            return (array)$$__var;
        };
        return $load($path, $var);
    }
}
