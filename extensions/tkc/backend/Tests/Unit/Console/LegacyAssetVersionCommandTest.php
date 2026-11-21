<?php

namespace App\Extension\tkc\backend\Tests\Unit\Console;

use App\Extension\tkc\backend\Config\ConfigOverrideMerger;
use App\Extension\tkc\backend\Config\LegacyAssetVersion;
use App\Extension\tkc\backend\Console\LegacyAssetVersionCommand;
use App\Extension\tkc\backend\Tests\Unit\Config\LegacyAssetVersionTest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LegacyAssetVersionCommandTest extends TestCase
{
    private string $root;
    private string $target;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/tkc-assetcmd-' . uniqid();
        $w = fn(string $p, string $c) => LegacyAssetVersionTest::write($this->root, $p, $c);
        $w('public/legacy/themes/suite8/css/Dawn/style.css', 'core');
        $w('public/legacy/include/javascript/yui/build/base/base-min.css', 'yui');
        $w('public/legacy/custom/themes/suite8/css/Dawn/style.css', 'custom');
        $w('public/legacy/custom/themes/suite8/fonts/a.woff2', 'font');
        $w('composer.lock', '{}');
        $w('public/legacy/suitecrm_version.php', '<?php');
        $this->target = $this->root . '/override.php';
        file_put_contents($this->target, "<?php\n\$sugar_config['other_key'] = 'keep';\n");
    }

    protected function tearDown(): void
    {
        LegacyAssetVersionTest::remove($this->root);
    }

    private function tester(): CommandTester
    {
        return new CommandTester(new LegacyAssetVersionCommand($this->root, new ConfigOverrideMerger(), new LegacyAssetVersion()));
    }

    public function testAppliesThenNoChangeAndKeepsOtherKeys(): void
    {
        $t = $this->tester();
        self::assertSame(0, $t->execute(['--target' => $this->target]));
        self::assertStringContainsString('Applied', $t->getDisplay());
        self::assertMatchesRegularExpression('/js_custom_version = tkc[0-9a-f]{12}/', $t->getDisplay());

        $config = (new ConfigOverrideMerger())->parseOverrideFile($this->target);
        self::assertSame('keep', $config['other_key']);
        self::assertMatchesRegularExpression('/^tkc[0-9a-f]{12}$/', $config['js_custom_version']);

        self::assertSame(0, $t->execute(['--target' => $this->target]));
        self::assertStringContainsString('No change', $t->getDisplay());
        self::assertStringNotContainsString('keep', $t->getDisplay());
    }

    public function testContentChangeAppliesAgain(): void
    {
        $t = $this->tester();
        $t->execute(['--target' => $this->target]);
        LegacyAssetVersionTest::write($this->root, 'public/legacy/custom/themes/suite8/css/Dawn/style.css', 'custom2');
        $t->execute(['--target' => $this->target]);
        self::assertStringContainsString('Applied', $t->getDisplay());
    }

    public function testDryRunWritesNothing(): void
    {
        $before = file_get_contents($this->target);
        $t = $this->tester();
        self::assertSame(0, $t->execute(['--target' => $this->target, '--dry-run' => true]));
        self::assertStringContainsString('Would apply', $t->getDisplay());
        self::assertSame($before, file_get_contents($this->target));
    }

    public function testMissingInputExitsWithOneAndWritesNothing(): void
    {
        unlink($this->root . '/public/legacy/themes/suite8/css/Dawn/style.css');
        $before = file_get_contents($this->target);
        $t = $this->tester();
        self::assertSame(1, $t->execute(['--target' => $this->target]));
        self::assertSame($before, file_get_contents($this->target));
    }
}
