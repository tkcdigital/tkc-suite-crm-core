<?php

namespace App\Extension\tkc\backend\Tests\Unit\Users;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use App\Extension\tkc\backend\Users\Console\RepairNumberSeparatorsCommand;
use App\Extension\tkc\backend\Users\NumberSeparatorPolicy;
use App\Extension\tkc\backend\Users\PreferenceStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * SUITE-179: dry run writes nothing, a second run changes nothing, only conflicting users change.
 */
class RepairNumberSeparatorsCommandTest extends TestCase
{
    private function store(): PreferenceStore
    {
        return new class implements PreferenceStore {
            public array $rows = [
                'u-admin' => ['dec_sep' => '.', 'theme' => 'x'],
                'u-ok' => ['dec_sep' => ',', 'num_grp_sep' => '.'],
                'u-none' => [],
                'u-bad' => 'broken',
            ];
            public int $writes = 0;

            public function systemSeparators(): array
            {
                return [',', '.'];
            }

            public function activeUserIds(): array
            {
                return array_merge(array_keys($this->rows), ['u-norow']);
            }

            public function readGlobal(string $userId): ?array
            {
                if ($userId === 'u-norow') {
                    return null;
                }
                if (!is_array($this->rows[$userId])) {
                    throw new \UnexpectedValueException('x');
                }
                return $this->rows[$userId];
            }

            public function writeGlobal(string $userId, array $prefs): void
            {
                $this->writes++;
                $this->rows[$userId] = $prefs;
            }
        };
    }

    private function tester(PreferenceStore $store): CommandTester
    {
        return new CommandTester(new RepairNumberSeparatorsCommand(
            $this->createMock(DefaultLegacyHandler::class),
            new NumberSeparatorPolicy(),
            $store
        ));
    }

    public function testDryRunWritesNothing(): void
    {
        $store = $this->store();
        $tester = $this->tester($store);
        self::assertSame(Command::SUCCESS, $tester->execute(['--dry-run' => true]));
        self::assertSame(0, $store->writes);
        self::assertStringContainsString('1 change(s) not written', $tester->getDisplay());
        self::assertStringContainsString('u-admin: clear dec_sep', $tester->getDisplay());
    }

    public function testRunChangesOnlyTheConflictingUserAndRerunChangesNothing(): void
    {
        $store = $this->store();
        $tester = $this->tester($store);
        $tester->execute([]);
        self::assertSame(1, $store->writes);
        self::assertSame(['theme' => 'x'], $store->rows['u-admin']);
        self::assertSame(['dec_sep' => ',', 'num_grp_sep' => '.'], $store->rows['u-ok']);
        self::assertStringContainsString('1 skipped', $tester->getDisplay());
        self::assertStringContainsString('next sign-in', $tester->getDisplay());

        $second = $this->tester($store);
        $second->execute([]);
        self::assertSame(1, $store->writes);
        self::assertStringContainsString('0 change(s)', $second->getDisplay());
    }
}
