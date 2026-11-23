<?php

namespace App\Extension\tkc\backend\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * SUITE-71: the D5 delta extensions/tkc/config/tkc_duplicate_ignore.php adds the TKC key fields to the
 * "default" list of the parameter module.recordview.duplicate_ignore (the Angular Duplicate action reads only that list).
 */
final class DuplicateIgnoreDeltaTest extends TestCase
{
    private const DELTA = __DIR__ . '/../../../../config/tkc_duplicate_ignore.php';
    private const CORE = __DIR__ . '/../../../../../../config/services/module/recordview/duplicate_ignore.yaml';
    private const PARAM = 'module.recordview.duplicate_ignore';
    private const KEYS = ['osc_id_c', 'tkc_reference_c', 'tkc_case_ref_c'];

    private function applyDelta(?array $param): array
    {
        $container = new ContainerBuilder();
        if ($param !== null) {
            $container->setParameter(self::PARAM, $param);
        }
        require self::DELTA;

        return $container->getParameter(self::PARAM);
    }

    public function testDefaultListHasTheKeyFields(): void
    {
        $core = Yaml::parseFile(self::CORE)['parameters'][self::PARAM];
        $result = $this->applyDelta($core);

        foreach (self::KEYS as $key) {
            self::assertContains($key, $result['default']);
        }
    }

    public function testExistingEntriesAndModuleListsAreKept(): void
    {
        $result = $this->applyDelta(['default' => ['id', 'osc_id_c'], 'Accounts' => ['x_c']]);

        self::assertContains('id', $result['default']);
        self::assertSame(['x_c'], $result['Accounts']);
        self::assertSame(1, count(array_keys($result['default'], 'osc_id_c', true)), 'no duplicates');
    }

    public function testMissingParameterIsCreated(): void
    {
        $result = $this->applyDelta(null);

        foreach (self::KEYS as $key) {
            self::assertContains($key, $result['default']);
        }
    }

    public function testRunningTwiceChangesNothing(): void
    {
        $once = $this->applyDelta(['default' => []]);

        self::assertSame($once, $this->applyDelta($once));
    }
}
