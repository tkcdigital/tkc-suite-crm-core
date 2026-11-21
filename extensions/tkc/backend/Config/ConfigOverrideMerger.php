<?php

namespace App\Extension\tkc\backend\Config;

/**
 * Merges a secret-free settings template into the legacy config_override.php.
 * Pure logic (no legacy bootstrap) so it can be unit tested.
 */
class ConfigOverrideMerger
{
    /**
     * Read the $sugar_config array that a config_override.php file defines.
     */
    public function parseOverrideFile(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $load = static function (string $path): array {
            $sugar_config = [];
            include $path;

            return is_array($sugar_config) ? $sugar_config : [];
        };

        return $load($file);
    }

    public function loadTemplate(string $file): array
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Template not found: ' . $file);
        }

        $template = (static fn(string $path) => include $path)($file);

        if (!is_array($template)) {
            throw new \RuntimeException('Template must return an array: ' . $file);
        }

        return $template;
    }

    /**
     * Template values win for the keys the template defines. Other keys stay.
     */
    public function merge(array $current, array $template): array
    {
        foreach ($template as $key => $value) {
            if (is_array($value) && !$this->isList($value) && isset($current[$key]) && is_array($current[$key])) {
                $current[$key] = $this->merge($current[$key], $value);
                continue;
            }
            $current[$key] = $value;
        }

        return $current;
    }

    /**
     * Describe the changes of a merge: lines like "+ key = value" and "~ key: old -> new".
     * Only keys that the template defines are listed.
     *
     * @return string[]
     */
    public function diff(array $current, array $template): array
    {
        $lines = [];
        $currentFlat = $this->flatten($current);

        foreach ($this->flatten($template) as $path => $value) {
            if (!array_key_exists($path, $currentFlat)) {
                $lines[] = '+ ' . $this->label($path) . ' = ' . $this->export($value);
            } elseif ($currentFlat[$path] !== $value) {
                $lines[] = '~ ' . $this->label($path) . ': ' . $this->export($currentFlat[$path]) . ' -> ' . $this->export($value);
            }
        }

        return $lines;
    }

    /**
     * Render the override file in the same style as the legacy Configurator.
     */
    public function render(array $config): string
    {
        $out = "<?php\n/***CONFIGURATOR***/\n";
        foreach ($this->flatten($config) as $path => $value) {
            $parts = explode("\0", $path);
            $out .= '$sugar_config' . implode('', array_map(
                static fn(string $p) => "['" . str_replace(['\\', "'"], ['\\\\', "\\'"], $p) . "']",
                $parts
            )) . ' = ' . var_export($value, true) . ";\n";
        }

        return $out . "/***CONFIGURATOR***/\n";
    }

    /**
     * Flatten to leaf values. Keys are joined with NUL internally, shown with a dot by diff().
     */
    private function flatten(array $config, string $prefix = ''): array
    {
        $flat = [];
        foreach ($config as $key => $value) {
            $path = $prefix === '' ? (string)$key : $prefix . "\0" . $key;
            if (is_array($value) && !$this->isList($value)) {
                $flat += $this->flatten($value, $path);
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    private function isList(array $value): bool
    {
        return $value === [] || array_is_list($value);
    }

    private function label(string $path): string
    {
        return str_replace("\0", '.', $path);
    }

    private function export(mixed $value): string
    {
        return str_replace(["\0", "\n"], ['.', ' '], var_export($value, true));
    }
}
