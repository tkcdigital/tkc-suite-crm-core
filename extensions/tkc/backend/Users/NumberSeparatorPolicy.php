<?php

namespace App\Extension\tkc\backend\Users;

/**
 * SUITE-179: finds user-level number separators that make the effective decimal and grouping
 * separator the same. The user edit view then refuses Save. Pure logic, no legacy access.
 * The fallback copies include/utils.php: an empty() user value (also "0") uses the system default.
 */
class NumberSeparatorPolicy
{
    public const DEC_KEY = 'dec_sep';
    public const GRP_KEY = 'num_grp_sep';

    /**
     * @param array<string,mixed> $globalPrefs the user's "global" preferences
     * @return list<string> preference keys to remove (empty when there is no conflict)
     * @throws \InvalidArgumentException when the system separators are the same
     */
    public function keysToClear(array $globalPrefs, string $sysDec, string $sysGrp): array
    {
        if ($sysDec === $sysGrp) {
            throw new \InvalidArgumentException('The system decimal and grouping separators are the same.');
        }

        $userDec = $globalPrefs[self::DEC_KEY] ?? null;
        $userGrp = $globalPrefs[self::GRP_KEY] ?? null;
        $effDec = empty($userDec) ? $sysDec : (string)$userDec;
        $effGrp = empty($userGrp) ? $sysGrp : (string)$userGrp;
        if ($effDec !== $effGrp) {
            return [];
        }

        $keys = [];
        if (!empty($userDec)) {
            $keys[] = self::DEC_KEY;
        }
        if (!empty($userGrp)) {
            $keys[] = self::GRP_KEY;
        }

        return $keys;
    }
}
