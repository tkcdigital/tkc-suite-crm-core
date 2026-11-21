<?php

namespace App\Extension\tkc\backend\Tests\Contract;

use App\Extension\tkc\backend\Metadata\Contract\LegacyMetadataLoader;
use App\Extension\tkc\backend\Metadata\Contract\MetadataContractChecker;
use App\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Layout files of a module must match its vardefs and dropdown lists (SUITE-47).
 * Reads metadata only. Precondition: tkc:repair has run (Ext/ compiled).
 *
 * Kernel: a plain TestCase boots App\Kernel in environment "dev" by hand. The "test" environment
 * does not boot (SUITE-176, core config/packages/test/framework.yaml uses the removed storage_id),
 * and KernelTestCase::bootKernel needs it (test.service_container). Do not patch core config.
 * LegacyMetadataLoader is public (#[Autoconfigure]) because dev has no test container.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ModuleMetadataContractTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function moduleProvider(): array
    {
        $modules = [];
        foreach (require __DIR__ . '/modules.php' as $module) {
            $modules[$module] = [$module];
        }
        return $modules;
    }

    #[DataProvider('moduleProvider')]
    public function testLayoutsMatchVardefs(string $module): void
    {
        // bin/console loads .env; PHPUnit does not. Real environment variables win over the files.
        $root = dirname(__DIR__, 5);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new Dotenv())->load(...$files);

        $kernel = new Kernel('dev', false);
        $kernel->boot();
        /** @var LegacyMetadataLoader $loader */
        $loader = $kernel->getContainer()->get(LegacyMetadataLoader::class);
        $data = $loader->load($module);

        $this->assertNotEmpty($data['fieldDefs'], "No vardefs for $module: run tkc:repair first.");
        $violations = (new MetadataContractChecker())->check($module, $data['layouts'], $data['fieldDefs'], $data['appListStrings']);

        $allowed = (require __DIR__ . '/known-core-violations.php')[$module] ?? [];
        $violations = array_values(array_filter($violations, static function ($v) use ($allowed): bool {
            foreach ($allowed as $a) {
                if ($a['file'] === $v->file && $a['path'] === $v->path) {
                    return false;
                }
            }
            return true;
        }));

        $this->assertSame([], array_map('strval', $violations), "Metadata contract violations in $module");
    }
}
