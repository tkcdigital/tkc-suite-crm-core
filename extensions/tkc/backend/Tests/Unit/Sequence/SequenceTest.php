<?php

namespace App\Extension\tkc\backend\Tests\Unit\Sequence;

use App\Extension\tkc\backend\Sequence\Sequence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SequenceTest extends TestCase
{
    private Sequence $sequence;

    protected function setUp(): void
    {
        $this->sequence = new Sequence(new InMemorySequenceStore());
    }

    public function testFirstCallGivesOneThenTwo(): void
    {
        self::assertSame(1, $this->sequence->next('quote', 2026));
        self::assertSame(2, $this->sequence->next('quote', 2026));
        self::assertSame(3, $this->sequence->next('quote', 2026));
    }

    public function testCurrentIsNullBeforeFirstCall(): void
    {
        self::assertNull($this->sequence->current('quote', 2026));
        $this->sequence->next('quote', 2026);
        self::assertSame(1, $this->sequence->current('quote', 2026));
    }

    public function testYearsAndNamesAreSeparateCounters(): void
    {
        $this->sequence->next('quote', 2026);
        $this->sequence->next('quote', 2026);
        self::assertSame(1, $this->sequence->next('quote', 2027));
        self::assertSame(1, $this->sequence->next('case', 2026));
        self::assertSame(3, $this->sequence->next('quote', 2026));
    }

    public function testSetLastValueThenNextGivesValuePlusOne(): void
    {
        $this->sequence->setLastValue('quote', 2026, 41);
        self::assertSame(41, $this->sequence->current('quote', 2026));
        self::assertSame(42, $this->sequence->next('quote', 2026));
    }

    public function testSetLastValueAcceptsEqualValue(): void
    {
        $this->sequence->setLastValue('quote', 2026, 10);
        $this->sequence->setLastValue('quote', 2026, 10);
        self::assertSame(11, $this->sequence->next('quote', 2026));
    }

    public function testLowerValueIsRefusedByDefault(): void
    {
        $this->sequence->setLastValue('quote', 2026, 50);
        $this->expectException(\InvalidArgumentException::class);
        try {
            $this->sequence->setLastValue('quote', 2026, 49);
        } finally {
            self::assertSame(50, $this->sequence->current('quote', 2026));
        }
    }

    public function testLowerValueIsAllowedOnRequest(): void
    {
        $this->sequence->setLastValue('quote', 2026, 50);
        $this->sequence->setLastValue('quote', 2026, 5, true);
        self::assertSame(6, $this->sequence->next('quote', 2026));
    }

    public function testNegativeValueIsRefusedEvenWhenLowerIsAllowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sequence->setLastValue('quote', 2026, -1, true);
    }

    #[DataProvider('badNames')]
    public function testBadNameThrows(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sequence->next($name, 2026);
    }

    public static function badNames(): array
    {
        return [
            'empty' => [''],
            'upper case' => ['Quote'],
            'starts with digit' => ['1quote'],
            'starts with underscore' => ['_quote'],
            'space' => ['my quote'],
            'quote char' => ["a'b"],
            'sql' => ["a'; DROP TABLE x;--"],
            'too long' => [str_repeat('a', 41)],
            'newline at end' => ["quote\n"],
        ];
    }

    public function testNameOfFortyCharactersIsValid(): void
    {
        self::assertSame(1, $this->sequence->next(str_repeat('a', 40), 2026));
    }

    #[DataProvider('badYears')]
    public function testBadYearThrows(int $year): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sequence->next('quote', $year);
    }

    public static function badYears(): array
    {
        return [[1999], [2100], [0], [-2026]];
    }

    public function testYearBoundsAreValid(): void
    {
        self::assertSame(1, $this->sequence->next('quote', 2000));
        self::assertSame(1, $this->sequence->next('quote', 2099));
    }

    public function testValidationAppliesToCurrentAndSet(): void
    {
        foreach ([
            fn () => $this->sequence->current('Bad', 2026),
            fn () => $this->sequence->current('quote', 1999),
            fn () => $this->sequence->setLastValue('Bad', 2026, 1),
            fn () => $this->sequence->setLastValue('quote', 2100, 1),
        ] as $call) {
            try {
                $call();
                self::fail('Expected InvalidArgumentException');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }
}
