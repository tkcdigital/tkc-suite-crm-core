<?php

namespace App\Extension\tkc\backend\Tests\Unit\Console;

use App\Extension\tkc\backend\Console\MigrationsMigrateCommand;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/** SUITE-64: the runner keeps only TKC versions (core migrations never run here). */
class MigrationsMigrateCommandTest extends TestCase
{
    public function testFilterKeepsOnlyTkcVersions(): void
    {
        $versions = [
            'App\Migrations\Version20200608135040',
            'App\Extension\tkc\Migrations\Version20261124120000',
            'App\Extension\other\Migrations\Version20261124130000',
            'App\Extension\tkc\Migrations\Version20261125120000',
        ];
        self::assertSame(
            ['App\Extension\tkc\Migrations\Version20261124120000', 'App\Extension\tkc\Migrations\Version20261125120000'],
            MigrationsMigrateCommand::filterTkc($versions)
        );
    }

    public function testNoTkcVersionsGivesMessageAndExitZero(): void
    {
        $tester = new CommandTester(new MigrationsMigrateCommand($this->factory(['App\Migrations\Version20200608135040'])));
        $code = $tester->execute([]);
        self::assertSame(0, $code);
        self::assertStringContainsString('No new TKC migrations.', $tester->getDisplay());
        self::assertStringContainsString('1 core migrations not run', $tester->getDisplay());
    }

    public function testDryRunListsAndRunsNothing(): void
    {
        $tester = new CommandTester(new MigrationsMigrateCommand(
            $this->factory(['App\Extension\tkc\Migrations\Version20261124120000'], false)
        ));
        $code = $tester->execute(['--dry-run' => true]);
        self::assertSame(0, $code);
        self::assertStringContainsString('Version20261124120000', $tester->getDisplay());
    }

    /** @param string[] $names */
    private function factory(array $names, bool $strict = true): DependencyFactory
    {
        $versions = array_map(static fn(string $n) => new Version($n), $names);
        $factory = $this->createMock(DependencyFactory::class);
        $factory->method('getMetadataStorage')->willReturn($this->createMock(\Doctrine\Migrations\Metadata\Storage\MetadataStorage::class));
        $finder = $this->createMock(\Doctrine\Migrations\Version\MigrationStatusCalculator::class);
        $finder->method('getNewMigrations')->willReturn(new AvailableMigrationsList(
            array_map(fn(Version $v) => new \Doctrine\Migrations\Metadata\AvailableMigration($v, $this->createMock(\Doctrine\Migrations\AbstractMigration::class)), $versions)
        ));
        $factory->method('getMigrationStatusCalculator')->willReturn($finder);
        if ($strict) {
            $factory->expects(self::never())->method('getMigrator');
        }
        return $factory;
    }
}
