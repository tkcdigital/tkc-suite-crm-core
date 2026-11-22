<?php

namespace App\Extension\tkc\backend\Tests\Integration\Sequence;

use App\Extension\tkc\backend\Sequence\LegacyDbSequenceStore;
use App\Extension\tkc\backend\Sequence\Sequence;
use PHPUnit\Framework\TestCase;

/**
 * SUITE-62. Runs against the real database, after tkc:repair --execute created tkc_sequences.
 * Uses synthetic counter names with the prefix tkc_test_. tearDown deletes only those rows.
 * The legacy layer is loaded in the test process (see bootLegacy).
 * The parallel check starts real bin/console processes.
 */
final class SequenceCommandTest extends TestCase
{
    private string $name;
    /** @var string[] */
    private array $names = [];

    protected function setUp(): void
    {
        self::bootLegacy();
        $this->name = $this->newName();
    }

    protected function tearDown(): void
    {
        $db = \DBManagerFactory::getInstance();
        foreach ($this->names as $name) {
            // Test data cleanup only: the names were created by this test.
            $db->query('DELETE FROM tkc_sequences WHERE seq_name = ' . $db->quoted($name));
        }
    }

    private function newName(): string
    {
        $name = 'tkc_test_' . bin2hex(random_bytes(6));
        $this->names[] = $name;

        return $name;
    }

    private static function bootLegacy(): void
    {
        if (defined('sugarEntry') && class_exists(\DBManagerFactory::class, false)) {
            return;
        }
        // Loads the legacy layer directly, like a legacy entry point (no Symfony kernel in this process).
        chdir(dirname(__DIR__, 6) . '/public/legacy');
        define('sugarEntry', true);
        require_once 'include/entryPoint.php';
    }

    private function sequence(): Sequence
    {
        return new Sequence(new LegacyDbSequenceStore());
    }

    public function testFirstCallCreatesTheRowAndValuesCountUp(): void
    {
        $s = $this->sequence();
        self::assertNull($s->current($this->name, 2026));
        self::assertSame(1, $s->next($this->name, 2026));
        self::assertSame(2, $s->next($this->name, 2026));
        self::assertSame(3, $s->next($this->name, 2026));
        self::assertSame(3, $s->current($this->name, 2026));
    }

    public function testYearsAreIndependent(): void
    {
        $s = $this->sequence();
        $s->next($this->name, 2026);
        $s->next($this->name, 2026);
        self::assertSame(1, $s->next($this->name, 2027));
        self::assertSame(3, $s->next($this->name, 2026));
    }

    public function testSetLastValueThenNext(): void
    {
        $s = $this->sequence();
        $s->setLastValue($this->name, 2026, 41);
        self::assertSame(42, $s->next($this->name, 2026));
        try {
            $s->setLastValue($this->name, 2026, 10);
            self::fail('A lower value must be refused');
        } catch (\InvalidArgumentException) {
            self::assertSame(42, $s->current($this->name, 2026));
        }
        $s->setLastValue($this->name, 2026, 10, true);
        self::assertSame(11, $s->next($this->name, 2026));
    }

    public function testNextRefusesWhenTheCounterIsAtTheIntMaximum(): void
    {
        // QA D1 (SUITE-62): the console runs MySQL in non-strict mode. There the column is clamped at
        // 2147483647, but LAST_INSERT_ID(seq_value + 1) still returns a larger, unstored value that can repeat.
        // The PHPUnit connection is strict and fails anyway, so this test uses the real console path.
        $this->sequence()->setLastValue($this->name, 2026, 2147483646);
        [$code1, $out1] = $this->console('next', $this->name, '2026');
        self::assertSame(0, $code1);
        self::assertSame('2147483647', trim($out1));
        [$code2, $out2] = $this->console('next', $this->name, '2026');
        self::assertNotSame(0, $code2, 'next() must refuse at the INT maximum, got: ' . trim($out2));
        self::assertDoesNotMatchRegularExpression('/^\d+$/m', $out2, 'No value may be printed at the INT maximum');
        self::assertSame(2147483647, $this->sequence()->current($this->name, 2026));
    }

    /** @return array{0:int,1:string} exit code and stdout of one tkc:sequence call (prod env, as in production). */
    private function console(string ...$args): array
    {
        $root = dirname(__DIR__, 6);
        $cmd = array_merge([PHP_BINARY, $root . '/bin/console', 'tkc:sequence'], $args, ['--env=prod']);
        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
        self::assertIsResource($proc);
        $out = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($proc), (string)$out];
    }

    public function testSequenceLegacyUsesTheDatabase(): void
    {
        self::assertSame(1, Sequence::legacy()->next($this->name, 2026));
    }

    public function testTwoParallelProcessesGetDistinctValues(): void
    {
        $root = dirname(__DIR__, 6);
        $count = 200;
        $procs = [];
        foreach ([0, 1] as $i) {
            $cmd = [PHP_BINARY, $root . '/bin/console', 'tkc:sequence', 'next', $this->name, '2026', '--count=' . $count, '--env=prod'];
            $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
            self::assertIsResource($proc);
            $procs[$i] = [$proc, $pipes];
        }
        $values = [];
        foreach ($procs as [$proc, $pipes]) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($proc), 'Command failed: ' . $err);
            foreach (preg_split('/\R/', trim($out)) as $line) {
                if (preg_match('/^\d+$/', $line)) {
                    $values[] = (int)$line;
                }
            }
        }
        self::assertCount(2 * $count, $values);
        self::assertCount(2 * $count, array_unique($values), 'Duplicate values');
        self::assertSame(2 * $count, max($values));
        self::assertSame(1, min($values));
    }
}
