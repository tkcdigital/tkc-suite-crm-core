<?php

namespace App\Extension\tkc\backend\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * SUITE-183: the D5 delta extensions/tkc/config/tkc_tinymce.php removes the TinyMCE "media" plugin
 * from the Angular edit settings and leaves every other "ui" key unchanged.
 */
final class TinymceDeltaTest extends TestCase
{
    private const DELTA = __DIR__ . '/../../../../config/tkc_tinymce.php';
    private const CORE_UI = __DIR__ . '/../../../../../../config/services/ui/ui.yaml';

    private function applyDelta(array $ui): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('ui', $ui);
        require self::DELTA;

        return $container->getParameter('ui');
    }

    public function testCoreUiHasNoMediaPluginAfterTheDelta(): void
    {
        $core = Yaml::parseFile(self::CORE_UI)['parameters']['ui'];
        self::assertStringContainsString('media', implode(' ', $core['tinymce']['edit']['plugins']), 'precondition: core loads media');

        $ui = $this->applyDelta($core);
        $plugins = $ui['tinymce']['edit']['plugins'];

        self::assertCount(3, $plugins);
        foreach ($plugins as $entry) {
            self::assertNotContains('media', preg_split('/\s+/', $entry));
        }
        self::assertSame('insertdatetime table paste code help wordcount', $plugins[2]);
        self::assertSame($core['tinymce']['edit']['plugins'][0], $plugins[0]);

        $coreWithoutPlugins = $core;
        unset($coreWithoutPlugins['tinymce']['edit']['plugins'], $ui['tinymce']['edit']['plugins']);
        self::assertSame($coreWithoutPlugins, $ui, 'all other ui keys stay the same');
    }

    public function testShortListIsPaddedToReplaceEveryDefault(): void
    {
        $ui = $this->applyDelta(['tinymce' => ['edit' => ['plugins' => ['link media table']]]]);

        self::assertSame(['link table', '', ''], $ui['tinymce']['edit']['plugins']);
    }

    public function testPluginNamesThatContainMediaAreKept(): void
    {
        $ui = $this->applyDelta(['tinymce' => ['edit' => ['plugins' => ['mediaembed media x', 'a', 'b']]]]);

        self::assertSame('mediaembed x', $ui['tinymce']['edit']['plugins'][0]);
    }
}
