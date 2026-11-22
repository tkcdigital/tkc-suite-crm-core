<?php

namespace App\Extension\tkc\backend\Sequence;

/**
 * Counters for document numbers, one for each name and year (SUITE-62).
 *
 * Guarantee: every value that next() gives is unique for its name and year.
 * The series can have gaps. A save that fails after next() leaves a gap. Do not rely on a series without gaps.
 *
 * Use it from Symfony by autowiring Sequence. Use Sequence::legacy() in hooks and legacy code.
 * Do not call SequenceStore or LegacyDbSequenceStore directly.
 */
final class Sequence
{
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
     *
     * Without $allowLower this is one atomic step in the store. When the stored value is above $value
     * (also when a parallel next() got there first), nothing changes and the method throws LowerValueException.
     * An equal value is a success. With $allowLower the write is unchecked and numbers above $value can repeat.
     *
     * @throws LowerValueException when the stored value is above $value
     */
    public function setLastValue(string $name, int $year, int $value, bool $allowLower = false): void
    {
        $this->check($name, $year);
        SequenceInput::checkValue($value);
        if ($allowLower) {
            $this->store->forceSet($name, $year, $value);
            return;
        }
        $before = $this->store->setIfNotLower($name, $year, $value);
        if ($before > $value) {
            throw new LowerValueException($before, $value, $name, $year);
        }
    }

    private function check(string $name, int $year): void
    {
        SequenceInput::checkKey($name, $year);
    }
}
