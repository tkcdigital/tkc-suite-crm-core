<?php

namespace App\Extension\tkc\backend\Tests\Unit\Metadata;

use App\Extension\tkc\backend\Metadata\Contract\MetadataContractChecker;
use PHPUnit\Framework\TestCase;

class MetadataContractCheckerTest extends TestCase
{
    private const FIELDS = [
        'name' => ['name' => 'name', 'type' => 'name'],
        'tkc_channel_c' => ['name' => 'tkc_channel_c', 'type' => 'enum', 'options' => 'tkc_channel_list', 'default' => 'phone'],
    ];
    private const LISTS = ['tkc_channel_list' => ['phone' => 'Phone', 'web' => 'Web']];

    private function check(array $layouts, array $fields = self::FIELDS, array $lists = self::LISTS): array
    {
        return array_map('strval', (new MetadataContractChecker())->check('Synthetic', $layouts, $fields, $lists));
    }

    public function testCleanLayoutsHaveNoViolation(): void
    {
        $layouts = [
            'detailviewdefs' => ['panels' => ['main' => [[['name' => 'name'], 'tkc_channel_c', '', ['name' => '']]]]],
            'listviewdefs' => ['NAME' => ['default' => true]],
            'searchdefs' => ['basic_search' => ['name' => ['name' => 'name']], 'advanced_search' => ['tkc_channel_c']],
            'subpanels' => ['default' => ['name' => [], 'edit_button' => ['widget_class' => 'SubPanelEditButton']]],
        ];
        $this->assertSame([], $this->check($layouts));
    }

    public function testUnknownFieldInEachLayoutIsReported(): void
    {
        $layouts = [
            'detailviewdefs' => ['panels' => ['main' => [[['name' => 'ghost_c']]]]],
            'listviewdefs' => ['GHOST2_C' => []],
            'searchdefs' => ['basic_search' => ['ghost3_c' => ['name' => 'ghost3_c']]],
            'subpanels' => ['default' => ['ghost4_c' => []]],
        ];
        $violations = $this->check($layouts);
        $this->assertCount(4, $violations);
        foreach (['ghost_c', 'ghost2_c', 'ghost3_c', 'ghost4_c'] as $field) {
            $this->assertNotEmpty(array_filter($violations, static fn ($v) => str_contains($v, "\"$field\"")), $field);
        }
    }

    public function testSearchOnlyNameFromSearchFieldsIsAccepted(): void
    {
        $layouts = ['searchdefs' => ['advanced_search' => ['phone' => ['name' => 'phone'], 'ghost' => ['name' => 'ghost']]], 'searchfields' => ['phone']];
        $violations = $this->check($layouts);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('"ghost"', $violations[0]);
    }

    public function testUnknownDropdownKeyInActiveOnFieldsIsReported(): void
    {
        $layouts = ['detailviewdefs' => ['panels' => ['main' => [[
            ['name' => 'name', 'displayLogic' => ['k' => ['params' => ['activeOnFields' => ['tkc_channel_c' => ['phone', 'fax']]]]]],
        ]]]]];
        $violations = $this->check($layouts);
        $this->assertCount(1, $violations);
        $this->assertStringContainsString('value "fax" is not a key of list "tkc_channel_list"', $violations[0]);
    }

    public function testLogicInRecordActionsIsChecked(): void
    {
        $layouts = ['detailviewdefs' => ['recordActions' => ['actions' => ['a' => [
            'displayLogic' => ['k' => ['params' => ['activeOnFields' => ['ghost_c' => ['x']]]]],
        ]]]]];
        $this->assertCount(1, $this->check($layouts));
    }

    public function testTkcFieldWithMissingListOrBadDefaultIsReported(): void
    {
        $fields = [
            'tkc_a_c' => ['type' => 'enum', 'options' => 'tkc_missing_list'],
            'tkc_b_c' => ['type' => 'enum', 'options' => 'tkc_channel_list', 'default' => 'fax'],
            'core_x' => ['type' => 'enum', 'options' => 'core_missing_list'], // core field: not checked
        ];
        $violations = $this->check([], $fields);
        $this->assertCount(2, $violations);
    }
}
