<?php

namespace App\Extension\tkc\backend\Tests\Unit\Config;

use App\Extension\tkc\backend\Config\LegacyAssetVersion;
use PHPUnit\Framework\TestCase;

class LegacyAssetVersionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/tkc-assetver-' . uniqid();
        self::write($this->root, 'public/legacy/themes/suite8/css/Dawn/style.css', 'core');
        self::write($this->root, 'public/legacy/include/javascript/yui/build/base/base-min.css', 'yui');
        self::write($this->root, 'public/legacy/custom/themes/suite8/css/Dawn/style.css', 'custom');
        self::write($this->root, 'public/legacy/custom/themes/suite8/fonts/a.woff2', 'font');
        self::write($this->root, 'composer.lock', '{"packages":[{"name":"tinymce/tinymce","version":"8.5.1"}]}');
        self::write($this->root, 'public/legacy/suitecrm_version.php', "<?php \$suitecrm_version = '8.10.2';");
    }

    protected function tearDown(): void
    {
        self::remove($this->root);
    }

    public static function write(string $root, string $relative, string $content): void
    {
        $path = $root . '/' . $relative;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
    }

    public static function remove(string $path): void
    {
        if (is_dir($path)) {
            foreach (scandir($path) as $n) {
                if ($n !== '.' && $n !== '..') {
                    self::remove($path . '/' . $n);
                }
            }
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }

    public function testSameInputsGiveSameVersionWithValidFormat(): void
    {
        $sut = new LegacyAssetVersion();
        $a = $sut->compute($this->root);
        self::assertMatchesRegularExpression('/^tkc[0-9a-f]{12}$/', $a);
        self::assertSame($a, $sut->compute($this->root));
    }

    public function testOneByteChangeGivesNewVersion(): void
    {
        $sut = new LegacyAssetVersion();
        $before = $sut->compute($this->root);
        self::write($this->root, 'public/legacy/custom/themes/suite8/css/Dawn/style.css', 'customX');
        self::assertNotSame($before, $sut->compute($this->root));
    }

    public function testFontRenameGivesNewVersion(): void
    {
        $sut = new LegacyAssetVersion();
        $before = $sut->compute($this->root);
        $dir = $this->root . '/public/legacy/custom/themes/suite8/fonts/';
        rename($dir . 'a.woff2', $dir . 'b.woff2');
        self::assertNotSame($before, $sut->compute($this->root));
    }

    public function testMissingInputThrows(): void
    {
        unlink($this->root . '/public/legacy/include/javascript/yui/build/base/base-min.css');
        $this->expectException(\RuntimeException::class);
        (new LegacyAssetVersion())->compute($this->root);
    }

    public function testVendorUpdateGivesNewVersion(): void
    {
        $sut = new LegacyAssetVersion();
        $before = $sut->compute($this->root);
        self::write($this->root, 'composer.lock', '{"packages":[{"name":"tinymce/tinymce","version":"8.10.2"}]}');
        self::assertNotSame($before, $sut->compute($this->root));
    }

    public function testUpstreamReleaseGivesNewVersion(): void
    {
        $sut = new LegacyAssetVersion();
        $before = $sut->compute($this->root);
        self::write($this->root, 'public/legacy/suitecrm_version.php', "<?php \$suitecrm_version = '8.10.3';");
        self::assertNotSame($before, $sut->compute($this->root));
    }

    public function testMissingComposerLockThrows(): void
    {
        unlink($this->root . '/composer.lock');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing input: composer.lock');
        (new LegacyAssetVersion())->compute($this->root);
    }

    public function testMissingSuitecrmVersionThrows(): void
    {
        unlink($this->root . '/public/legacy/suitecrm_version.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing input: public/legacy/suitecrm_version.php');
        (new LegacyAssetVersion())->compute($this->root);
    }

    public function testMissingFontsThrow(): void
    {
        unlink($this->root . '/public/legacy/custom/themes/suite8/fonts/a.woff2');
        $this->expectException(\RuntimeException::class);
        (new LegacyAssetVersion())->compute($this->root);
    }
}
