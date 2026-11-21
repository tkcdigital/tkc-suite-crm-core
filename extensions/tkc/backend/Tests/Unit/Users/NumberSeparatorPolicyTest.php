<?php

namespace App\Extension\tkc\backend\Tests\Unit\Users;

use App\Extension\tkc\backend\Users\NumberSeparatorPolicy;
use PHPUnit\Framework\TestCase;

/**
 * SUITE-179: a user separator that equals the effective other separator blocks the user edit Save.
 */
class NumberSeparatorPolicyTest extends TestCase
{
    public function testAdminCaseClearsTheConflictingDecimalSeparator(): void
    {
        self::assertSame(['dec_sep'], (new NumberSeparatorPolicy())->keysToClear(['dec_sep' => '.'], ',', '.'));
    }

    public function testNoConflictClearsNothing(): void
    {
        $prefs = ['dec_sep' => ',', 'num_grp_sep' => '.'];
        self::assertSame([], (new NumberSeparatorPolicy())->keysToClear($prefs, ',', '.'));
        self::assertSame([], (new NumberSeparatorPolicy())->keysToClear([], ',', '.'));
        self::assertSame([], (new NumberSeparatorPolicy())->keysToClear(['dec_sep' => '.', 'num_grp_sep' => ','], ',', '.'));
    }

    public function testOnlyGroupingSeparatorConflicts(): void
    {
        self::assertSame(['num_grp_sep'], (new NumberSeparatorPolicy())->keysToClear(['num_grp_sep' => ','], ',', '.'));
    }

    public function testEmptyAndZeroFallBackToTheSystemValue(): void
    {
        $policy = new NumberSeparatorPolicy();
        foreach (['', '0', null] as $empty) {
            self::assertSame([], $policy->keysToClear(['dec_sep' => $empty, 'num_grp_sep' => $empty], ',', '.'), var_export($empty, true));
        }
        // An empty user decimal falls back to ",", so a user grouping "," conflicts.
        self::assertSame(['num_grp_sep'], $policy->keysToClear(['dec_sep' => '', 'num_grp_sep' => ','], ',', '.'));
    }

    public function testBothUserValuesEqualClearsBoth(): void
    {
        self::assertSame(['dec_sep', 'num_grp_sep'], (new NumberSeparatorPolicy())->keysToClear(['dec_sep' => ';', 'num_grp_sep' => ';'], ',', '.'));
    }

    public function testSystemConflictThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new NumberSeparatorPolicy())->keysToClear([], '.', '.');
    }
}
