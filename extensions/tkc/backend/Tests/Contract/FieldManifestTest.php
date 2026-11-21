<?php

namespace App\Extension\tkc\backend\Tests\Contract;

use App\Extension\tkc\backend\Metadata\Contract\LegacyMetadataLoader;
use App\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Compiled vardefs and dropdown lists must match the field manifests (C11, SUITE-63).
 * One manifest file per story in Fields/*.php. Each returns a list of entries:
 *   ['module' => 'Accounts',
 *    'fields' => [name => [type, len, options, default, audited, required, indexed, displayType]],
 *    'absent' => [field names that must not exist],
 *    'lists'  => [list name => ordered keys],
 *    'labels' => [list name => [key => label]]]
 * Only the attributes that a field entry names are checked. 'indexed' => true needs a one-field
 * index idx_<table>_<field>. "Field exists in the database" is proved by the second tkc:repair dry run.
 * Precondition: tkc:repair has run (Ext/ compiled). Same kernel pattern as ModuleMetadataContractTest.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class FieldManifestTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function manifestProvider(): array
    {
        $cases = [];
        $files = glob(__DIR__ . '/Fields/*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            foreach (require $file as $entry) {
                $cases[basename($file, '.php') . ' ' . $entry['module']] = [$entry];
            }
        }
        return $cases;
    }

    /**
     * @param array<string, mixed> $entry
     */
    #[DataProvider('manifestProvider')]
    public function testVardefsMatchManifest(array $entry): void
    {
        $root = dirname(__DIR__, 5);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new Dotenv())->load(...$files);

        $kernel = new Kernel('dev', false);
        $kernel->boot();
        /** @var LegacyMetadataLoader $loader */
        $loader = $kernel->getContainer()->get(LegacyMetadataLoader::class);
        $module = $entry['module'];
        $data = $loader->load($module);
        $fieldDefs = $data['fieldDefs'];
        $lists = $data['appListStrings'];
        $table = $fieldDefs['id']['table'] ?? strtolower($module);

        $this->assertNotEmpty($fieldDefs, "No vardefs for $module: run tkc:repair first.");

        foreach ($entry['fields'] ?? [] as $name => $expected) {
            $this->assertArrayHasKey($name, $fieldDefs, "$module.$name is missing.");
            $def = $fieldDefs[$name];
            foreach (['type', 'len', 'options', 'default', 'displayType'] as $attr) {
                if (array_key_exists($attr, $expected)) {
                    $this->assertEquals($expected[$attr], $def[$attr] ?? null, "$module.$name: $attr");
                }
            }
            foreach (['audited', 'required'] as $flag) {
                if (array_key_exists($flag, $expected)) {
                    $this->assertSame($expected[$flag], !empty($def[$flag]), "$module.$name: $flag");
                }
            }
            if (!empty($expected['indexed'])) {
                $index = $data['indices']["idx_{$table}_$name"] ?? null;
                $this->assertNotNull($index, "$module.$name: index idx_{$table}_$name is missing.");
                $this->assertSame([$name], $index['fields'], "$module.$name: the index must have this one field.");
            }
        }

        foreach ($entry['absent'] ?? [] as $name) {
            $this->assertArrayNotHasKey($name, $fieldDefs, "$module.$name must not exist.");
        }

        foreach ($entry['lists'] ?? [] as $list => $keys) {
            $this->assertArrayHasKey($list, $lists, "List $list is missing.");
            $this->assertSame($keys, array_map('strval', array_keys($lists[$list])), "List $list: keys or order differ.");
            if (in_array('', $keys, true)) {
                $this->assertSame('', $lists[$list][''], "List $list: the empty key needs an empty label.");
            }
        }
        foreach ($entry['labels'] ?? [] as $list => $labels) {
            foreach ($labels as $key => $label) {
                $this->assertSame($label, $lists[$list][$key] ?? null, "List $list: label of $key.");
            }
        }
    }
}
