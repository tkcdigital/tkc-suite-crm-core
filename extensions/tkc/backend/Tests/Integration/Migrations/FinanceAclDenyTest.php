<?php

namespace App\Extension\tkc\backend\Tests\Integration\Migrations;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

/**
 * SUITE-64 guard. Read-only: the real rows of tkc_AccountFinance (acltype module, deleted = 0) must deny by default.
 * Exactly 8 actions, access -98, the other seven -99, no duplicate per action.
 * Failure messages show action names and values only. The helper is also tested on in-memory arrays,
 * so the failure path is proved without a change to the database.
 */
final class FinanceAclDenyTest extends TestCase
{
    private const ACTIONS = ['access', 'view', 'list', 'edit', 'delete', 'import', 'export', 'massupdate'];

    /**
     * @param array<int, array{name: string, aclaccess: int|string}> $rows
     * @return string[] violations
     */
    public static function violations(array $rows): array
    {
        $found = [];
        foreach ($rows as $r) {
            $found[$r['name']][] = (int)$r['aclaccess'];
        }
        $out = [];
        foreach (self::ACTIONS as $a) {
            $values = $found[$a] ?? [];
            if (count($values) !== 1) {
                $out[] = sprintf('%s: %d rows (%s), expected 1', $a, count($values), implode(',', $values));
                continue;
            }
            $expected = $a === 'access' ? -98 : -99;
            if ($values[0] !== $expected) {
                $out[] = sprintf('%s: %d, expected %d', $a, $values[0], $expected);
            }
        }
        foreach (array_diff(array_keys($found), self::ACTIONS) as $extra) {
            $out[] = sprintf('%s: unexpected action', $extra);
        }

        return $out;
    }

    public function testRealRowsDenyByDefault(): void
    {
        $root = dirname(__DIR__, 6);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new \Symfony\Component\Dotenv\Dotenv())->load(...$files);
        $conn = DriverManager::getConnection(['url' => $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL')]);
        $rows = $conn->fetchAllAssociative(
            'SELECT name, aclaccess FROM acl_actions WHERE category = ? AND acltype = ? AND deleted = 0',
            ['tkc_AccountFinance', 'module']
        );

        self::assertSame([], self::violations($rows), 'tkc_AccountFinance must deny by default (run the SeedFinanceAcl migration).');
    }

    public function testHelperAcceptsTheDenyRows(): void
    {
        self::assertSame([], self::violations($this->goodRows()));
    }

    public function testHelperReportsWrongMissingAndDuplicateRows(): void
    {
        $rows = $this->goodRows();
        $rows[1]['aclaccess'] = 90;             // view allow-all
        unset($rows[2]);                        // list missing
        $rows[] = ['name' => 'edit', 'aclaccess' => -99]; // duplicate edit
        self::assertSame(
            ['view: 90, expected -99', 'list: 0 rows (), expected 1', 'edit: 2 rows (-99,-99), expected 1'],
            self::violations($rows)
        );
    }

    /** @return array<int, array{name: string, aclaccess: int}> */
    private function goodRows(): array
    {
        return array_map(
            static fn(string $a): array => ['name' => $a, 'aclaccess' => $a === 'access' ? -98 : -99],
            self::ACTIONS
        );
    }
}
