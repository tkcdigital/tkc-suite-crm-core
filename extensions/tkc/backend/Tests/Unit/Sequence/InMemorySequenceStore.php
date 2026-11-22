<?php

namespace App\Extension\tkc\backend\Tests\Unit\Sequence;

use App\Extension\tkc\backend\Sequence\SequenceStore;

/** Test double for SUITE-62. Keeps the counters in an array. */
final class InMemorySequenceStore implements SequenceStore
{
    /** @var array<string, int> */
    public array $values = [];

    public function next(string $name, int $year): int
    {
        return $this->values["$name|$year"] = ($this->values["$name|$year"] ?? 0) + 1;
    }

    public function current(string $name, int $year): ?int
    {
        return $this->values["$name|$year"] ?? null;
    }

    /** Called once at the start of a write, then cleared. Simulates a parallel next(). */
    public ?\Closure $beforeWrite = null;

    private function hook(): void
    {
        $hook = $this->beforeWrite;
        $this->beforeWrite = null;
        if ($hook !== null) {
            $hook();
        }
    }

    public function set(string $name, int $year, int $value): void
    {
        $this->hook();
        $this->values["$name|$year"] = $value;
    }
}
