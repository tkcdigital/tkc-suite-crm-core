<?php

namespace App\Extension\tkc\backend\Config;

/**
 * SUITE-181: content hash of the legacy theme assets. The value feeds the core key js_custom_version,
 * so a browser asks for a new URL when the legacy CSS or the theme fonts change.
 * SUITE-183: the hash also covers composer.lock (vendor assets such as TinyMCE) and suitecrm_version.php
 * (core legacy JS after an upstream release). Legacy JS is cached for 1 year (.htaccess), and core builds
 * "?v=" only from js_custom_version, so without these inputs a browser keeps an old vendor or core script.
 * Pure logic (no legacy bootstrap) so it can be unit tested.
 */
class LegacyAssetVersion
{
    private const CORE_CSS = 'public/legacy/themes/suite8/css/Dawn/style.css';
    private const YUI_CSS = 'public/legacy/include/javascript/yui/build/base/base-min.css';
    private const COMPOSER_LOCK = 'composer.lock';
    private const SUITECRM_VERSION = 'public/legacy/suitecrm_version.php';
    private const CUSTOM_CSS_DIR = 'public/legacy/custom/themes/suite8/css';
    private const CUSTOM_FONT_DIR = 'public/legacy/custom/themes/suite8/fonts';

    /**
     * @return string "tkc" and 12 hex characters (alphanumeric only: core puts the value raw in URLs)
     * @throws \RuntimeException when a required input is missing
     */
    public function compute(string $projectDir): string
    {
        $projectDir = rtrim($projectDir, '/');
        $files = [];

        foreach ([self::CORE_CSS, self::YUI_CSS, self::COMPOSER_LOCK, self::SUITECRM_VERSION] as $relative) {
            if (!is_file($projectDir . '/' . $relative)) {
                throw new \RuntimeException('Missing input: ' . $relative);
            }
            $files[] = $relative;
        }

        $css = $this->listFiles($projectDir, self::CUSTOM_CSS_DIR, true);
        if ($css === []) {
            throw new \RuntimeException('Missing input: ' . self::CUSTOM_CSS_DIR . '/**/*.css');
        }
        $fonts = $this->listFiles($projectDir, self::CUSTOM_FONT_DIR, false);
        if ($fonts === []) {
            throw new \RuntimeException('Missing input: ' . self::CUSTOM_FONT_DIR . '/*');
        }
        $files = array_merge($files, $css, $fonts);
        sort($files, SORT_STRING);

        $hash = hash_init('sha256');
        foreach ($files as $relative) {
            $content = file_get_contents($projectDir . '/' . $relative);
            if ($content === false) {
                throw new \RuntimeException('Cannot read input: ' . $relative);
            }
            hash_update($hash, $relative . "\0" . $content);
        }

        return 'tkc' . substr(hash_final($hash), 0, 12);
    }

    /**
     * @return string[] paths relative to the project directory
     */
    private function listFiles(string $projectDir, string $dir, bool $recursiveCss): array
    {
        $base = $projectDir . '/' . $dir;
        if (!is_dir($base)) {
            return [];
        }

        $found = [];
        if ($recursiveCss) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'css') {
                    $found[] = $dir . substr($file->getPathname(), strlen($base));
                }
            }
        } else {
            foreach (scandir($base) as $name) {
                if (is_file($base . '/' . $name)) {
                    $found[] = $dir . '/' . $name;
                }
            }
        }

        return $found;
    }
}
