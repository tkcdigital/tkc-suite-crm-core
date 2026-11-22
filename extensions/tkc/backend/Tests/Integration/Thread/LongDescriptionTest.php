<?php

namespace App\Extension\tkc\backend\Tests\Integration\Thread;

use PHPUnit\Framework\TestCase;

/**
 * SUITE-68. Runs against the real database, after tkc:repair --execute changed
 * aop_case_updates.description to longtext. Uses synthetic text only.
 * The row has internal = 1 and no case or contact, so that AOP sends no mail.
 * The legacy layer is loaded in the test process.
 */
final class LongDescriptionTest extends TestCase
{
    private const LENGTH = 1048576;

    private string $id = '';

    protected function setUp(): void
    {
        self::bootLegacy();
    }

    protected function tearDown(): void
    {
        if ($this->id !== '') {
            // Test data cleanup only: the row was created by this test.
            $db = \DBManagerFactory::getInstance();
            $db->query('DELETE FROM aop_case_updates WHERE id = ' . $db->quoted($this->id));
        }
    }

    private static function bootLegacy(): void
    {
        chdir(dirname(__DIR__, 6) . '/public/legacy');
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }
        if (!class_exists(\DBManagerFactory::class, false)) {
            require_once 'include/entryPoint.php';
        }
        // Another test may have booted the legacy layer inside a function scope, so the bean lists
        // are not global. Load them again from the global scope.
        (static function (): void {
            global $beanList, $beanFiles, $moduleList, $modInvisList, $objectList;
            require 'include/modules.php';
        })();
        // Other tests can leave a partial vardef state: load the compiled vardefs again.
        unset($GLOBALS['dictionary']['AOP_Case_Updates']);
        \VardefManager::loadVardef('AOP_Case_Updates', 'AOP_Case_Updates', true);
    }

    public function testOneMegabyteDescriptionRoundTrips(): void
    {
        // Plain letters only: no space or newline at the end (trim) and no HTML (cleaning).
        $text = substr(str_repeat('abcdefghijklmnopqrstuvwxyz', (int)ceil(self::LENGTH / 26)), 0, self::LENGTH);
        self::assertSame(self::LENGTH, strlen($text));
        $hash = hash('sha256', $text);

        $bean = \BeanFactory::newBean('AOP_Case_Updates');
        // The bean must carry the TKC defaults and the longtext column type (checked before save).
        self::assertSame('staff', $bean->field_defs['tkc_entry_type_c']['default'] ?? null);
        self::assertSame('longtext', $bean->field_defs['description']['dbType'] ?? null);
        $bean->name = 'SUITE-68 synthetic long entry';
        $bean->internal = 1;
        $bean->description = $text;
        $this->id = $bean->save();
        self::assertNotEmpty($this->id);

        $loaded = \BeanFactory::getBean('AOP_Case_Updates', $this->id, ['use_cache' => false], true);
        self::assertNotFalse($loaded);
        self::assertSame(self::LENGTH, strlen($loaded->description));
        self::assertSame($hash, hash('sha256', $loaded->description));
    }
}
