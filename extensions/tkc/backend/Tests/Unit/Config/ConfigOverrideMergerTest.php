<?php

namespace App\Extension\tkc\backend\Tests\Unit\Config;

use App\Extension\tkc\backend\Config\ConfigOverrideMerger;
use PHPUnit\Framework\TestCase;

class ConfigOverrideMergerTest extends TestCase
{
    private ConfigOverrideMerger $merger;
    private string $dir;

    protected function setUp(): void
    {
        $this->merger = new ConfigOverrideMerger();
        $this->dir = sys_get_temp_dir() . '/tkc-merger-' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
    }

    public function testMergeKeepsOtherKeysAndTemplateWins(): void
    {
        $merged = $this->merger->merge(
            ['a' => 1, 'x' => ['k' => 1, 'keep' => 2]],
            ['a' => 2, 'x' => ['k' => 9], 'new' => false]
        );
        $this->assertSame(['a' => 2, 'x' => ['k' => 9, 'keep' => 2], 'new' => false], $merged);
    }

    public function testDiffListsAddedAndChanged(): void
    {
        $diff = $this->merger->diff(['a' => 1, 'n' => ['b' => 1]], ['a' => 2, 'n' => ['b' => 1, 'c' => 3], 'z' => true]);
        $this->assertSame(["~ a: 1 -> 2", "+ n.c = 3", "+ z = true"], $diff);
    }

    public function testRenderParseRoundTripAndIdempotence(): void
    {
        $template = ['s' => "it's", 'b' => false, 'n' => ['c' => 3], 'l' => ['x', 'y']];
        $file = $this->dir . '/config_override.php';
        file_put_contents($file, $this->merger->render($this->merger->merge([], $template)));

        $current = $this->merger->parseOverrideFile($file);
        $this->assertSame($template, $current);
        $this->assertSame([], $this->merger->diff($current, $template));
        $this->assertSame(
            $this->merger->render($current),
            $this->merger->render($this->merger->merge($current, $template))
        );
    }

    public function testMissingOverrideFileIsEmpty(): void
    {
        $this->assertSame([], $this->merger->parseOverrideFile($this->dir . '/none.php'));
    }

    public function testParsesSyntheticFixtureAndMergeIsIdempotent(): void
    {
        $fixture = dirname(__DIR__, 2) . '/Fixtures/config_override_sample.php';
        $current = $this->merger->parseOverrideFile($fixture);
        $this->assertSame(['tkc_sample' => ['flag' => true, 'name' => 'synthetic']], $current);
        $this->assertSame([], $this->merger->diff($current, $current));
    }
}
