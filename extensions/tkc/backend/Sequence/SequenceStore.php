<?php

namespace App\Extension\tkc\backend\Sequence;

/**
 * Storage for the counters of Sequence (SUITE-62). The arguments are already validated.
 */
interface SequenceStore
{
    /** Adds 1 to the counter (creates it at 1) and sends the new value. Atomic. */
    public function next(string $name, int $year): int;

    /** Sends the last value, or null when the counter does not exist. */
    public function current(string $name, int $year): ?int;

    /** Sets the last value. Creates the counter when needed. */
    public function set(string $name, int $year, int $value): void;
}
