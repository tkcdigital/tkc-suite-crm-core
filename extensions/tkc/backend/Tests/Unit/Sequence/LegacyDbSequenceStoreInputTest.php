<?php

namespace App\Extension\tkc\backend\Tests\Unit\Sequence;

use App\Extension\tkc\backend\Sequence\LegacyDbSequenceStore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * SUITE-205 F3. The store checks its own input before it opens a database connection.
 * Legacy MySQL sessions are non-strict and would truncate a long name without an error.
 * These tests need no database: a refused call must not reach db().
 */
final class LegacyDbSequenceStoreInputTest extends TestCase
{
    /** @return array<string, array{0: callable(LegacyDbSequenceStore): mixed}> */
    public static function badCalls(): array
    {
        $long = str_repeat('a', 41);

        return [
            'next long name' => [fn ($s) => $s->next($long, 2026)],
            'current long name' => [fn ($s) => $s->current($long, 2026)],
            'setIfNotLower long name' => [fn ($s) => $s->setIfNotLower($long, 2026, 1)],
            'forceSet long name' => [fn ($s) => $s->forceSet($long, 2026, 1)],
            'next bad name' => [fn ($s) => $s->next("a'b", 2026)],
            'next bad year' => [fn ($s) => $s->next('quote', 1999)],
            'setIfNotLower bad year' => [fn ($s) => $s->setIfNotLower('quote', 2100, 1)],
            'setIfNotLower negative value' => [fn ($s) => $s->setIfNotLower('quote', 2026, -1)],
            'setIfNotLower value too high' => [fn ($s) => $s->setIfNotLower('quote', 2026, 2147483647)],
            'forceSet negative value' => [fn ($s) => $s->forceSet('quote', 2026, -1)],
            'forceSet value too high' => [fn ($s) => $s->forceSet('quote', 2026, 2147483647)],
        ];
    }

    #[DataProvider('badCalls')]
    public function testStoreRefusesBadInputBeforeTheDatabase(callable $call): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $call(new LegacyDbSequenceStore());
    }
}
