<?php

namespace App\Extension\tkc\backend\Tests\Contract;

use App\Extension\tkc\backend\Metadata\Contract\LegacyMetadataLoader;
use App\Kernel;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

/**
 * tkc:repair must rebuild the module language cache and mark rebuild_all (SUITE-204).
 * Writes a stale synthetic cache file for Accounts, runs the dry run, and checks that the file is
 * rebuilt from Ext/Language. Precondition: the database is reachable (dry run writes one
 * cache_rebuild row, no schema change). Same kernel pattern as FieldManifestTest.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class RepairLanguageCacheTest extends TestCase
{
    private const STALE_KEY = 'LBL_TKC_SYNTHETIC_STALE_204';

    public function testRepairRebuildsStaleModuleLanguageCache(): void
    {
        $root = dirname(__DIR__, 5);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new Dotenv())->load(...$files);

        $cacheFile = "$root/public/legacy/cache/modules/Accounts/language/en_us.lang.php";
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($cacheFile, "<?php\n\$mod_strings = array('" . self::STALE_KEY . "' => 'stale');\n");

        try {
            // Real CLI path: the command needs a fresh legacy bootstrap and session, like bin/console.
            $process = new Process([PHP_BINARY, "$root/bin/console", 'tkc:repair', '--dry-run'], $root, ['APP_ENV' => 'dev']);
            $process->setTimeout(300);
            $process->run();
            $this->assertSame(Command::SUCCESS, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());

            // Collect every problem, so one run shows all that is wrong.
            $problems = [];
            if (str_contains((string)file_get_contents($cacheFile), self::STALE_KEY)) {
                $problems[] = 'The stale module language cache file was not rebuilt.';
            }

            $kernel = new Kernel('dev', false);
            $kernel->boot();
            /** @var LegacyMetadataLoader $loader */
            $loader = $kernel->getContainer()->get(LegacyMetadataLoader::class);
            $strings = $loader->load('Accounts')['modStrings'];
            if (array_key_exists(self::STALE_KEY, $strings)) {
                $problems[] = 'return_module_language still has the stale key.';
            }

            $expected = $this->extLabels($root);
            $this->assertNotEmpty($expected, 'No SUITE-63 Ext labels found for Accounts.');
            foreach ($expected as $key => $value) {
                if (($strings[$key] ?? null) !== $value) {
                    $problems[] = "Accounts label $key is missing or differs.";
                }
            }

            if ('rebuild_all' !== \DBManagerFactory::getInstance()->getOne("SELECT cache_key FROM cache_rebuild WHERE cache_key = 'rebuild_all'")) {
                $problems[] = 'cache_rebuild has no rebuild_all row.';
            }
            $this->assertSame([], $problems);
        } finally {
            // Never leave the synthetic stale file behind when an assertion fails.
            if (is_file($cacheFile) && str_contains((string)file_get_contents($cacheFile), self::STALE_KEY)) {
                unlink($cacheFile);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function extLabels(string $root): array
    {
        $labels = [];
        foreach (glob("$root/public/legacy/custom/Extension/modules/Accounts/Ext/Language/en_us.tkc*.php") ?: [] as $file) {
            $load = static function (string $__file): array {
                $mod_strings = [];
                include $__file;
                return $mod_strings;
            };
            $labels = array_merge($labels, $load($file));
        }
        return $labels;
    }
}
