<?php

namespace App\Extension\tkc\backend\Sequence;

/**
 * Input checks for the counters (SUITE-62, SUITE-205 F3).
 * Sequence and LegacyDbSequenceStore both call this class. Legacy MySQL sessions are non-strict
 * and truncate a long name or clamp a number without an error, so PHP must check all input first.
 *
 * @internal Use Sequence.
 */
final class SequenceInput
{
    public const NAME_PATTERN = '/^[a-z][a-z0-9_]{0,39}\z/';
    public const YEAR_MIN = 2000;
    public const YEAR_MAX = 2099;
    public const VALUE_MAX = 2147483646; // next() must still fit in a signed INT

    /** Checks name and year. */
    public static function checkKey(string $name, int $year): void
    {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            throw new \InvalidArgumentException('The counter name must match ^[a-z][a-z0-9_]{0,39}$.');
        }
        if ($year < self::YEAR_MIN || $year > self::YEAR_MAX) {
            throw new \InvalidArgumentException('The year must be from ' . self::YEAR_MIN . ' to ' . self::YEAR_MAX . '.');
        }
    }

    /** Checks a value for set operations. */
    public static function checkValue(int $value): void
    {
        if ($value < 0 || $value > self::VALUE_MAX) {
            throw new \InvalidArgumentException('The value must be from 0 to ' . self::VALUE_MAX . '.');
        }
    }
}
