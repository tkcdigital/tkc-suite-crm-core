<?php

namespace App\Extension\tkc\backend\Sequence;

/**
 * Counters for document numbers, one for each name and year (SUITE-62).
 *
 * Guarantee: every value that next() gives is unique for its name and year.
 * The series can have gaps. A save that fails after next() leaves a gap. Do not rely on a series without gaps.
 *
 * Use it from Symfony by autowiring Sequence. Use Sequence::legacy() in hooks and legacy code.
 */
final class Sequence
{
    private const NAME_PATTERN = '/^[a-z][a-z0-9_]{0,39}\z/';
    private const YEAR_MIN = 2000;
    private const YEAR_MAX = 2099;
    private const VALUE_MAX = 2147483646; // next() must still fit in a signed INT

    public function __construct(private readonly SequenceStore $store)
    {
    }

    /** A Sequence that uses the legacy database connection. The legacy layer must be loaded. */
    public static function legacy(): self
    {
        return new self(new LegacyDbSequenceStore());
    }

    /** Gives the last value plus one. */
    public function next(string $name, int $year): int
    {
        $this->check($name, $year);

        return $this->store->next($name, $year);
    }

    /** The last value that was given or set, or null when the counter does not exist. */
    public function current(string $name, int $year): ?int
    {
        $this->check($name, $year);

        return $this->store->current($name, $year);
    }

    /**
     * Sets the last value. The next call of next() gives $value + 1.
     * Without $allowLower the method refuses a value that is lower than the current value.
     */
    public function setLastValue(string $name, int $year, int $value, bool $allowLower = false): void
    {
        $this->check($name, $year);
        if ($value < 0 || $value > self::VALUE_MAX) {
            throw new \InvalidArgumentException('The value must be from 0 to ' . self::VALUE_MAX . '.');
        }
        $current = $this->store->current($name, $year);
        if (!$allowLower && $current !== null && $value < $current) {
            throw new \InvalidArgumentException(sprintf(
                'The value %d is lower than the current value %d of the counter %s %d.',
                $value,
                $current,
                $name,
                $year
            ));
        }
        $this->store->set($name, $year, $value);
    }

    private function check(string $name, int $year): void
    {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            throw new \InvalidArgumentException('The counter name must match ^[a-z][a-z0-9_]{0,39}$.');
        }
        if ($year < self::YEAR_MIN || $year > self::YEAR_MAX) {
            throw new \InvalidArgumentException('The year must be from ' . self::YEAR_MIN . ' to ' . self::YEAR_MAX . '.');
        }
    }
}
