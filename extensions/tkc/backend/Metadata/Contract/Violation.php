<?php

namespace App\Extension\tkc\backend\Metadata\Contract;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * One broken rule found by the metadata contract test (SUITE-47).
 * Not a service: value object with scalar arguments.
 */
#[Exclude]
final class Violation
{
    public function __construct(
        public readonly string $module,
        public readonly string $file,
        public readonly string $path,
        public readonly string $message
    ) {
    }

    public function __toString(): string
    {
        return sprintf('%s | %s | %s | %s', $this->module, $this->file, $this->path, $this->message);
    }
}
