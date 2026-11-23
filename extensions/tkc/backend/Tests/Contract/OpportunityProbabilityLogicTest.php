<?php

namespace App\Extension\tkc\backend\Tests\Contract;

use App\Extension\tkc\backend\Metadata\Contract\LegacyMetadataLoader;
use App\Kernel;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Probability follows the sales stage through the vardef logic of the field probability (SUITE-69, spike S11).
 * Each sales_stage_dom key has exactly one updateValue entry, and its targetValue is the sales_probability_dom value.
 * Precondition: tkc:repair has run (Ext/ compiled).
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class OpportunityProbabilityLogicTest extends TestCase
{
    /**
     * @return array{logic: array<string, mixed>, stages: array<string, mixed>, probabilities: array<string, mixed>}
     */
    private function load(): array
    {
        $root = dirname(__DIR__, 5);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new Dotenv())->load(...$files);

        $kernel = new Kernel('dev', false);
        $kernel->boot();
        /** @var LegacyMetadataLoader $loader */
        $loader = $kernel->getContainer()->get(LegacyMetadataLoader::class);
        $data = $loader->load('Opportunities');
        $lists = $data['appListStrings'];

        return [
            'logic' => $data['fieldDefs']['probability']['logic'] ?? [],
            'stages' => $lists['sales_stage_dom'],
            'probabilities' => $lists['sales_probability_dom'],
        ];
    }

    /**
     * @param array<string, mixed> $logic
     * @return array<string, list<string>> stage key => targetValues of the updateValue entries for that key
     */
    private function targetsByStage(array $logic): array
    {
        $targets = [];
        foreach ($logic as $entry) {
            $this->assertSame('updateValue', $entry['key'] ?? null);
            $active = $entry['params']['activeOnFields']['sales_stage'] ?? [];
            $this->assertCount(1, $active, 'One stage key per logic entry.');
            $this->assertSame(['sales_stage'], $entry['params']['fieldDependencies'] ?? null);
            $targets[(string)$active[0]][] = (string)$entry['params']['targetValue'];
        }
        return $targets;
    }

    public function testEachStageHasExactlyOneEntryWithTheListValue(): void
    {
        $data = $this->load();
        $targets = $this->targetsByStage($data['logic']);

        $this->assertCount(8, $data['stages'], 'sales_stage_dom has 8 keys.');
        $this->assertSame(array_keys($data['stages']), array_keys($data['probabilities']), 'The two lists have the same keys.');
        foreach ($data['stages'] as $key => $label) {
            $this->assertArrayHasKey((string)$key, $targets, "Stage $key has no logic entry.");
            $this->assertCount(1, $targets[(string)$key], "Stage $key must have exactly one logic entry.");
            $this->assertSame((string)$data['probabilities'][$key], $targets[(string)$key][0], "Stage $key: targetValue differs from sales_probability_dom.");
        }
    }

    public function testNoEntryForAKeyThatIsNotInTheList(): void
    {
        $data = $this->load();
        $targets = $this->targetsByStage($data['logic']);
        $this->assertSame([], array_values(array_diff(array_keys($targets), array_map('strval', array_keys($data['stages'])))));
        $this->assertCount(count($data['stages']), $data['logic'], 'One logic entry per stage, no other entry.');
    }
}
