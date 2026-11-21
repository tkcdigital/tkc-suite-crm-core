<?php

namespace App\Extension\tkc\backend\Metadata\Contract;

/**
 * Pure PHP check of the layout files of one module against its vardefs (SUITE-47).
 * No legacy, no database: the input is plain arrays.
 *
 * Layout input (key => value), every key is optional:
 *   detailviewdefs  the array under $viewdefs[<module>]['DetailView']
 *   listviewdefs    the array $listViewDefs[<module>] (upper case field keys)
 *   searchdefs      the array under $searchdefs[<module>]['layout'] (basic_search, advanced_search)
 *   subpanels       map of subpanel file name => $subpanel_layout['list_fields']
 *   searchfields    list of names from metadata/SearchFields.php (search-only names such as "phone")
 *
 * Rules: R1 every named field exists. R2 displayLogic/logic activeOnFields name an existing
 * field and (for list fields) existing keys. R3 a tkc_*_c / osc_id_c field with options points
 * to an existing list. R4 the default of such an enum field is a key of its list.
 */
class MetadataContractChecker
{
    private const LIST_TYPES = ['enum', 'multienum', 'radioenum', 'dynamicenum'];
    private const LOGIC_KEYS = ['displayLogic', 'logic'];

    /**
     * @param array<string, mixed> $layouts
     * @param array<string, array<string, mixed>> $fieldDefs
     * @param array<string, mixed> $appListStrings
     * @return list<Violation>
     */
    public function check(string $module, array $layouts, array $fieldDefs, array $appListStrings): array
    {
        $violations = [];
        $add = static function (string $file, string $path, string $message) use (&$violations, $module): void {
            $violations[] = new Violation($module, $file, $path, $message);
        };
        $requireField = function (string $file, string $path, string $name) use ($fieldDefs, $add): void {
            if ($name !== '' && !isset($fieldDefs[$name])) {
                $add($file, $path, sprintf('field "%s" has no vardef', $name));
            }
        };

        $detail = $layouts['detailviewdefs'] ?? null;
        if (is_array($detail)) {
            foreach ($detail['panels'] ?? [] as $panelName => $rows) {
                foreach ((array)$rows as $r => $row) {
                    foreach ((array)$row as $c => $cell) {
                        $name = $this->cellName($cell);
                        if ($name !== null) {
                            $requireField('detailviewdefs', "panels.$panelName.$r.$c", $name);
                        }
                    }
                }
            }
            $this->checkLogic($detail, 'detailviewdefs', '', $fieldDefs, $appListStrings, $add);
        }

        $list = $layouts['listviewdefs'] ?? null;
        if (is_array($list)) {
            foreach (array_keys($list) as $key) {
                $requireField('listviewdefs', (string)$key, strtolower((string)$key));
            }
        }

        $search = $layouts['searchdefs'] ?? null;
        $searchOnly = array_flip(array_map('strval', (array)($layouts['searchfields'] ?? [])));
        if (is_array($search)) {
            foreach (['basic_search', 'advanced_search'] as $section) {
                foreach ((array)($search[$section] ?? []) as $key => $cell) {
                    $name = $this->cellName($cell, $key);
                    if ($name !== null && !isset($searchOnly[$name])) {
                        $requireField('searchdefs', "$section.$key", $name);
                    }
                }
            }
        }

        foreach ((array)($layouts['subpanels'] ?? []) as $subpanel => $fields) {
            foreach ((array)$fields as $key => $def) {
                if (is_array($def) && isset($def['widget_class'])) {
                    continue; // button or widget column, not a vardef field
                }
                $requireField('subpanels/' . $subpanel, (string)$key, (string)$key);
            }
        }

        foreach ($fieldDefs as $name => $def) {
            if (!$this->isTkcField((string)$name)) {
                continue;
            }
            $options = $def['options'] ?? null;
            if (is_string($options) && $options !== '') {
                if (!isset($appListStrings[$options]) || !is_array($appListStrings[$options])) {
                    $add('vardefs', (string)$name, sprintf('options list "%s" does not exist', $options));
                    continue;
                }
                $default = $def['default'] ?? null;
                if ($default !== null && $default !== '' && !array_key_exists($default, $appListStrings[$options])) {
                    $add('vardefs', (string)$name, sprintf('default "%s" is not a key of list "%s"', $default, $options));
                }
            }
        }

        return $violations;
    }

    /**
     * Field name of a layout cell. Null when the cell names no field (blank, comment, custom code).
     */
    private function cellName(mixed $cell, string|int|null $key = null): ?string
    {
        if (is_string($cell)) {
            return $cell === '' ? null : $cell;
        }
        if (is_array($cell)) {
            if (isset($cell['name']) && is_string($cell['name']) && $cell['name'] !== '') {
                return $cell['name'];
            }
            return null;
        }
        if ($cell === null || $cell === false) {
            return null;
        }
        return null;
    }

    private function isTkcField(string $name): bool
    {
        return $name === 'osc_id_c' || (str_starts_with($name, 'tkc_') && str_ends_with($name, '_c'));
    }

    /**
     * Walks the whole array for displayLogic / logic entries (cells, recordActions, panels).
     */
    private function checkLogic(array $node, string $file, string $path, array $fieldDefs, array $appListStrings, callable $add): void
    {
        foreach ($node as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            $here = $path === '' ? (string)$key : $path . '.' . $key;
            if (is_string($key) && in_array($key, self::LOGIC_KEYS, true)) {
                foreach ($value as $logicName => $logic) {
                    $active = is_array($logic) ? ($logic['params']['activeOnFields'] ?? null) : null;
                    if (is_array($active)) {
                        $this->checkActiveOnFields($active, $file, $here . '.' . $logicName, $fieldDefs, $appListStrings, $add);
                    }
                }
                continue;
            }
            $this->checkLogic($value, $file, $here, $fieldDefs, $appListStrings, $add);
        }
    }

    private function checkActiveOnFields(array $active, string $file, string $path, array $fieldDefs, array $appListStrings, callable $add): void
    {
        foreach ($active as $field => $values) {
            if (is_int($field)) { // list form: names only
                $field = (string)$values;
                $values = [];
            }
            $field = (string)$field;
            if (!isset($fieldDefs[$field])) {
                $add($file, "$path.activeOnFields.$field", sprintf('field "%s" has no vardef', $field));
                continue;
            }
            $def = $fieldDefs[$field];
            if (!in_array($def['type'] ?? '', self::LIST_TYPES, true)) {
                continue;
            }
            $options = $def['options'] ?? null;
            if (!is_string($options) || !isset($appListStrings[$options]) || !is_array($appListStrings[$options])) {
                continue; // list problems are reported by R3 for TKC fields
            }
            foreach ((array)$values as $value) {
                if (!array_key_exists((string)$value, $appListStrings[$options])) {
                    $add($file, "$path.activeOnFields.$field", sprintf('value "%s" is not a key of list "%s"', $value, $options));
                }
            }
        }
    }
}
