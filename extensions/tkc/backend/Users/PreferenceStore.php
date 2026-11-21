<?php

namespace App\Extension\tkc\backend\Users;

/**
 * SUITE-179: access to the "global" user preferences. A fake replaces it in tests.
 */
interface PreferenceStore
{
    /** @return array{0:string,1:string} system decimal and grouping separator */
    public function systemSeparators(): array;

    /** @return list<string> IDs of the users that are not deleted (all statuses) */
    public function activeUserIds(): array;

    /**
     * @return array<string,mixed>|null the global preferences, null when the user has no row
     * @throws \UnexpectedValueException when the stored value does not decode
     */
    public function readGlobal(string $userId): ?array;

    /** @param array<string,mixed> $prefs */
    public function writeGlobal(string $userId, array $prefs): void;
}
