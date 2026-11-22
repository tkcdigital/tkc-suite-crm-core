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
        // QA D1 (SUITE-62): in non-strict mode the column is clamped at 2147483647, but
        // LAST_INSERT_ID(seq_value + 1) still returns a larger, unstored value that can repeat.
        // All legacy connections are non-strict, also the connection of this PHPUnit process.
        // The test uses the console to cover the production path.
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

    public function testLegacyConnectionIsNonStrict(): void
    {
        // SUITE-205 F4: MysqliManager::connect() removes STRICT_TRANS_TABLES. The input checks in PHP depend on it.
        $mode = \DBManagerFactory::getInstance()->getOne('SELECT @@SESSION.sql_mode');
        self::assertIsString($mode);
        self::assertStringNotContainsString('STRICT_TRANS_TABLES', $mode);
    }

    public function testSetToTheCurrentValueViaConsoleSucceeds(): void
    {
        $this->sequence()->setLastValue($this->name, 2026, 30);
        [$code, $out] = $this->console('set', $this->name, '2026', '30', '--execute');
        self::assertSame(0, $code, $out);
        [$code, $out] = $this->console('next', $this->name, '2026');
        self::assertSame(0, $code);
        self::assertSame('31', trim($out));
    }

    public function testSetLowerViaConsoleIsRefusedAndKeepsTheCounter(): void
    {
        $this->sequence()->setLastValue($this->name, 2026, 120);
        [$code] = $this->console('set', $this->name, '2026', '100', '--execute');
        self::assertNotSame(0, $code);
        self::assertSame(120, $this->sequence()->current($this->name, 2026));
    }

    public function testDryRunWarnsWhenTheValueIsLower(): void
    {
        $this->sequence()->setLastValue($this->name, 2026, 120);
        [$code, $out] = $this->console('set', $this->name, '2026', '100');
        self::assertSame(0, $code);
        self::assertStringContainsString('Warning: 100 is lower than the current value 120. --execute refuses it without --allow-lower.', $out);
        self::assertStringNotContainsString('can be given again', $out);
        self::assertStringEndsWith('The counter can change before you run --execute.', trim($out));
        self::assertSame(120, $this->sequence()->current($this->name, 2026));
    }

    public function testDryRunWithAllowLowerNamesTheRepeatedNumbers(): void
    {
        $this->sequence()->setLastValue($this->name, 2026, 120);
        [$code, $out] = $this->console('set', $this->name, '2026', '100', '--allow-lower');
        self::assertSame(0, $code);
        self::assertStringContainsString('Warning: 100 is lower than the current value 120. The numbers 101 to 120 can be given again. Use --allow-lower only for a test or a recovery, after a pre-check.', $out);
        self::assertStringNotContainsString('refuses it', $out);
        self::assertStringEndsWith('The counter can change before you run --execute.', trim($out));
        self::assertSame(120, $this->sequence()->current($this->name, 2026));
    }

    public function testDryRunWithoutWarningWhenTheValueIsNotLower(): void
    {
        [$code, $out] = $this->console('set', $this->name, '2026', '5');
        self::assertSame(0, $code);
        self::assertStringContainsString('Dry run: ' . $this->name . ' 2026 would change from no counter to 5. Use --execute to write.', $out);
        self::assertStringNotContainsString('Warning', $out);
        self::assertStringEndsWith('The counter can change before you run --execute.', trim($out));
        self::assertNull($this->sequence()->current($this->name, 2026));
    }

    public function testExecuteAllowLowerPrintsTheWarningBeforeTheResult(): void
    {
        $this->sequence()->setLastValue($this->name, 2026, 120);
        [$code, $out] = $this->console('set', $this->name, '2026', '100', '--execute', '--allow-lower');
        self::assertSame(0, $code, $out);
        $warning = strpos($out, 'Warning: 100 is lower than the current value 120. The numbers 101 to 120 can be given again.');
        $result = strpos($out, 'last value set to 100');
        self::assertNotFalse($warning);
        self::assertNotFalse($result);
        self::assertLessThan($result, $warning);
        self::assertSame(100, $this->sequence()->current($this->name, 2026));
    }

    public function testSetLastValueNeverLowersTheCounterWhileNextRuns(): void
    {
        // SUITE-205 F1. Two console processes call next() 20000 times each. Meanwhile this test calls
        // setLastValue(current + 1..5) in this process and through the console. A set that lowers the
        // counter lets next() give a number twice. The test checks that no number repeats.
        $root = dirname(__DIR__, 6);
        $perProcess = 20000;
        $files = [];
        $procs = [];
        foreach ([0, 1] as $i) {
            $files[$i] = tempnam(sys_get_temp_dir(), 'tkcseq');
            $cmd = [PHP_BINARY, $root . '/bin/console', 'tkc:sequence', 'next', $this->name, '2026', '--count=' . $perProcess, '--env=prod'];
            $proc = proc_open($cmd, [1 => ['file', $files[$i], 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, $root);
            self::assertIsResource($proc);
            $procs[$i] = $proc;
        }
        $success = 0;
        $refused = 0;
        $consoleCalls = 0;
        $running = static function () use (&$procs): bool {
            foreach ($procs as $proc) {
                if (proc_get_status($proc)['running']) {
                    return true;
                }
            }
            return false;
        };
        // Wait until the counter exists, then race the two processes.
        for ($w = 0; $w < 600 && $this->sequence()->current($this->name, 2026) === null && $running(); $w++) {
            usleep(50000);
        }
        $s = Sequence::legacy();
        while ($running() || $consoleCalls < 3) {
            $c = $s->current($this->name, 2026) ?? 0;
            try {
                $s->setLastValue($this->name, 2026, $c + random_int(1, 5));
                $success++;
            } catch (\InvalidArgumentException) {
                $refused++;
            }
            if ($consoleCalls < 3 || ($running() && random_int(1, 20) === 1)) {
                $c = $s->current($this->name, 2026) ?? 0;
                [, $out] = $this->console('set', $this->name, '2026', (string)($c + random_int(1, 5)), '--execute');
                $consoleCalls++;
                if (str_contains($out, 'last value set to')) {
                    $success++;
                } else {
                    $refused++;
                }
            }
            if ($consoleCalls >= 3 && !$running()) {
                break;
            }
        }
        $values = [];
        foreach ($procs as $i => $proc) {
            self::assertSame(0, proc_close($proc), 'next process ' . $i . ' failed');
            $last = 0;
            $lines = file($files[$i], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            unlink($files[$i]);
            self::assertCount($perProcess, $lines);
            foreach ($lines as $line) {
                self::assertMatchesRegularExpression('/^\d+$/', $line);
                $v = (int)$line;
                self::assertGreaterThan($last, $v, 'The output of process ' . $i . ' must increase');
                $last = $v;
                $values[] = $v;
            }
        }
        fwrite(STDERR, sprintf("\n[SUITE-205 parallel] set successes=%d refusals=%d console sets=%d values=%d unique=%d\n",
            $success, $refused, $consoleCalls, count($values), count(array_unique($values))));
        self::assertCount(2 * $perProcess, $values);
        self::assertCount(2 * $perProcess, array_unique($values), 'Duplicate values: a set lowered the counter');
        self::assertGreaterThanOrEqual(1, $success);
        self::assertGreaterThanOrEqual(max($values), $this->sequence()->current($this->name, 2026));
    }
}
