<?php

namespace App\Extension\tkc\backend\Tests\Unit\AccountFinance;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * SUITE-64 D2b. tkc_AccountFinance has no subpanels, so the Angular store has no subpanel map and
 * reloadSubpanels() throws after a save. The detail view metadata turns that reload off for the save action.
 * A module action replaces the core action with the same key, so the entry must be a full copy of the core one.
 * The drift guard fails when the core entry changes in an upgrade merge.
 */
final class FinanceSaveActionTest extends TestCase
{
    private static function root(): string
    {
        return dirname(__DIR__, 6);
    }

    /** @return array<string, mixed> */
    private static function financeSave(): array
    {
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }
        $viewdefs = [];
        require self::root() . '/public/legacy/modules/tkc_AccountFinance/metadata/detailviewdefs.php';

        return $viewdefs['tkc_AccountFinance']['DetailView']['recordActions']['actions']['save'] ?? [];
    }

    public function testReloadSubpanelsIsBooleanFalse(): void
    {
        $save = self::financeSave();
        self::assertArrayHasKey('reloadSubpanels', $save['params'] ?? []);
        self::assertFalse($save['params']['reloadSubpanels']);
    }

    public function testEntryIsAFullCopyOfTheCoreSaveEntryPlusReloadSubpanels(): void
    {
        $yaml = Yaml::parseFile(self::root() . '/config/services/module/recordview/actions.yaml');
        $core = $yaml['parameters']['module.recordview.actions']['default']['actions']['save'];
        $tkc = self::financeSave();

        $tkcWithoutAddition = $tkc;
        unset($tkcWithoutAddition['params']['reloadSubpanels']);
        self::assertEquals($core, $tkcWithoutAddition, 'The Finance save entry must equal the core entry, except params.reloadSubpanels.');
    }
}
