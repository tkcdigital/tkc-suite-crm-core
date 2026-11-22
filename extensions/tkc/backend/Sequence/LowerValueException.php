<?php

namespace App\Extension\tkc\backend\Sequence;

/**
 * setLastValue() refused a value that is lower than the stored value (SUITE-205).
 * $current is the stored value at the moment of the check. The counter was not changed.
 * An import can catch this exception and treat it as "the counter is already ahead".
 */
final class LowerValueException extends \InvalidArgumentException
{
    public function __construct(
        public readonly int $current,
        int $value,
        string $name,
        int $year
    ) {
        parent::__construct(sprintf(
            'The value %d is lower than the current value %d of the counter %s %d.',
            $value,
            $current,
            $name,
            $year
        ));
    }
}
