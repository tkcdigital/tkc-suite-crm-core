<?php

namespace App\Extension\tkc\backend\Sequence;

/**
 * Storage for the counters of Sequence (SUITE-62). Implementations check their input with SequenceInput.
 *
 * @internal Use Sequence.
 */
interface SequenceStore
{
    /** Adds 1 to the counter (creates it at 1) and sends the new value. Atomic. */
    public function next(string $name, int $year): int;

    /** Sends the last value, or null when the counter does not exist. */
    public function current(string $name, int $year): ?int;

    /**
     * Atomic. Creates the counter at $value when it does not exist. Raises the counter to $value when it is
     * not higher. Changes nothing when it is higher. Sends the stored value before the call
     * ($value when the counter did not exist). The caller treats a result above $value as a refusal.
     */
    public function setIfNotLower(string $name, int $year, int $value): int;

    /** No check. Numbers above $value can repeat. Creates the counter when needed. */
    public function forceSet(string $name, int $year, int $value): void;
}
