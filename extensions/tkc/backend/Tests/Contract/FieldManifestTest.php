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
 *    'fields' => [name => [type, len, options, default, dbType, audited, required, massupdate, importable, indexed, displayType]],
 *    'absent' => [field names that must not exist],
 *    'lists'  => [list name => ordered keys],
 *    'labels' => [list name => [key => label]],
 *    'keyPatterns' => [list name => regex that every key must match],
 *    'values' => [app string name => expected scalar value]]
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
            foreach (['type', 'len', 'options', 'default', 'displayType', 'dbType'] as $attr) {
                if (array_key_exists($attr, $expected)) {
                    $this->assertEquals($expected[$attr], $def[$attr] ?? null, "$module.$name: $attr");
                }
            }
            foreach (['audited', 'required'] as $flag) {
                if (array_key_exists($flag, $expected)) {
                    $this->assertSame($expected[$flag], !empty($def[$flag]), "$module.$name: $flag");
                }
            }
            // 'massupdate' => false: the field must set it explicitly (enum fields are mass-updatable by default).
            if (array_key_exists('massupdate', $expected)) {
                $this->assertSame($expected['massupdate'], $def['massupdate'] ?? true, "$module.$name: massupdate");
            }
            // 'importable' => false: the field must set it explicitly (core imports a field by default).
            if (array_key_exists('importable', $expected)) {
                $this->assertSame($expected['importable'], $def['importable'] ?? true, "$module.$name: importable");
            }
            if (!empty($expected['indexed'])) {
                $index = $data['indices']["idx_{$table}_$name"] ?? null;
                $this->assertNotNull($index, "$module.$name: index idx_{$table}_$name is missing.");
                $this->assertSame([$name], $index['fields'], "$module.$name: the index must have this one field.");
            }
        }

        // Each field label must resolve to a non-empty string (SUITE-204: stale language cache).
        foreach ($entry['fields'] ?? [] as $name => $expected) {
            $vname = $fieldDefs[$name]['vname'] ?? '';
            $this->assertNotSame('', $vname, "$module.$name has no vname.");
            $this->assertNotEmpty($data['modStrings'][$vname] ?? '', "$module.$name: label $vname does not resolve.");
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
        foreach ($entry['keyPatterns'] ?? [] as $list => $pattern) {
            $this->assertArrayHasKey($list, $lists, "List $list is missing.");
            foreach (array_keys($lists[$list]) as $key) {
                $this->assertMatchesRegularExpression($pattern, (string)$key, "List $list: key '$key' breaks the key rule.");
            }
        }
        foreach ($entry['values'] ?? [] as $name => $value) {
            $this->assertArrayHasKey($name, $lists, "App string $name is missing.");
            $this->assertSame($value, $lists[$name], "App string $name differs.");
        }
        foreach ($entry['labels'] ?? [] as $list => $labels) {
            foreach ($labels as $key => $label) {
                $this->assertSame($label, $lists[$list][$key] ?? null, "List $list: label of $key.");
            }
        }
    }

    /**
     * An audited module needs its <table>_audit table (SUITE-64: tkc:repair --execute never created it).
     * Reads information_schema only. The second tkc:repair dry run also lists a missing audit table.
     *
     * @param array<string, mixed> $entry
     */
    #[DataProvider('manifestProvider')]
    public function testAuditTableExistsForAuditedFields(array $entry): void
    {
        $audited = array_filter($entry['fields'] ?? [], static fn(array $f): bool => !empty($f['audited']));
        if ($audited === []) {
            $this->markTestSkipped('No audited field in this manifest entry.');
        }

        $root = dirname(__DIR__, 5);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new Dotenv())->load(...$files);
        $kernel = new Kernel('dev', false);
        $kernel->boot();
        /** @var LegacyMetadataLoader $loader */
        $loader = $kernel->getContainer()->get(LegacyMetadataLoader::class);
        $fieldDefs = $loader->load($entry['module'])['fieldDefs'];
        $table = $fieldDefs['id']['table'] ?? strtolower($entry['module']);

        $conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL')]);
        $found = $conn->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table . '_audit']
        );
        $this->assertSame(1, (int)$found, "{$entry['module']}: the table {$table}_audit is missing (audited fields: " . implode(', ', array_keys($audited)) . ').');
    }
}
