<?php

namespace App\Extension\tkc\backend\Users;

/**
 * SUITE-179: reads and writes user preferences through the legacy UserPreference bean.
 * The legacy layer must be loaded (see RepairNumberSeparatorsCommand). No raw SQL.
 */
class LegacyPreferenceStore implements PreferenceStore
{
    public function systemSeparators(): array
    {
        global $sugar_config;

        return [
            (string)($sugar_config['default_decimal_seperator'] ?? '.'),
            (string)($sugar_config['default_number_grouping_seperator'] ?? ','),
        ];
    }

    public function activeUserIds(): array
    {
        $ids = [];
        $users = \BeanFactory::newBean('Users')->get_full_list('', 'users.deleted=0');
        foreach ($users ?: [] as $user) {
            $ids[] = (string)$user->id;
        }

        return $ids;
    }

    public function readGlobal(string $userId): ?array
    {
        $row = $this->row($userId);
        if ($row === null) {
            return null;
        }
        $decoded = base64_decode((string)$row->contents, true);
        $prefs = $decoded === false ? false : @unserialize($decoded, ['allowed_classes' => false]);
        if (!is_array($prefs)) {
            throw new \UnexpectedValueException('The stored preferences do not decode.');
        }

        return $prefs;
    }

    public function writeGlobal(string $userId, array $prefs): void
    {
        $row = $this->row($userId);
        if ($row === null) {
            throw new \UnexpectedValueException('The preference row is missing.');
        }
        $row->contents = base64_encode(serialize($prefs));
        $row->save();
    }

    private function row(string $userId): ?\UserPreference
    {
        $row = new \UserPreference();
        $found = $row->retrieve_by_string_fields(['assigned_user_id' => $userId, 'category' => 'global']);

        return $found === null ? null : $row;
    }
}
