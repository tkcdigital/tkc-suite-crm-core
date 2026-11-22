<?php

namespace App\Extension\tkc\backend\Tests\Integration\Migrations;

use App\Extension\tkc\Migrations\Version20261124120000SeedFinanceAcl;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * SUITE-64. Runs against the real database. Works on category tkc_AccountFinance only and
 * removes those rows before and after each test (test data cleanup). Logs counts only.
 */
final class SeedFinanceAclTest extends TestCase
{
    private const CATEGORY = 'tkc_AccountFinance';
    private const ACTIONS = ['access', 'view', 'list', 'edit', 'delete', 'import', 'export', 'massupdate'];

    private Connection $conn;

    protected function setUp(): void
    {
        $this->conn = self::connect();
        $this->clean();
    }

    protected function tearDown(): void
    {
        $this->clean();
    }

    private function clean(): void
    {
        $this->conn->executeStatement('DELETE FROM acl_actions WHERE category = ?', [self::CATEGORY]);
    }

    private static function connect(): Connection
    {
        // bin/console loads .env; PHPUnit does not. The URL comes from the environment only.
        $root = dirname(__DIR__, 6);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new \Symfony\Component\Dotenv\Dotenv())->load(...$files);
        $url = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL');

        return DriverManager::getConnection(['url' => $url]);
    }

    private function runUp(): void
    {
        $m = new Version20261124120000SeedFinanceAcl($this->conn, new NullLogger());
        $m->up(new \Doctrine\DBAL\Schema\Schema());
        foreach ($m->getSql() as $q) {
            $this->conn->executeStatement($q->getStatement(), $q->getParameters(), $q->getTypes());
        }
    }

    /** @return array<string, int[]> */
    private function rows(): array
    {
        $out = [];
        foreach ($this->conn->fetchAllAssociative(
            'SELECT name, aclaccess FROM acl_actions WHERE category = ? AND acltype = ? AND deleted = 0',
            [self::CATEGORY, 'module']
        ) as $r) {
            $out[$r['name']][] = (int)$r['aclaccess'];
        }

        return $out;
    }

    private function assertDeny(): void
    {
        $rows = $this->rows();
        self::assertSame(self::ACTIONS, array_values(array_intersect(self::ACTIONS, array_keys($rows))));
        foreach (self::ACTIONS as $a) {
            self::assertSame([$a === 'access' ? -98 : -99], $rows[$a], "action $a");
        }
    }

    public function testNoRowsGivesEightDenyRows(): void
    {
        $this->runUp();
        $this->assertDeny();
    }

    public function testExistingAllowAllRowsAreSetToDenyWithoutDuplicates(): void
    {
        foreach (self::ACTIONS as $a) {
            $this->conn->insert('acl_actions', [
                'id' => bin2hex(random_bytes(16)), 'date_entered' => '2026-01-01 00:00:00', 'date_modified' => '2026-01-01 00:00:00',
                'modified_user_id' => '1', 'created_by' => '1', 'name' => $a, 'category' => self::CATEGORY,
                'acltype' => 'module', 'aclaccess' => $a === 'access' ? 89 : 90, 'deleted' => 0,
            ]);
        }
        $this->runUp();
        $this->assertDeny();
    }

    public function testSecondRunChangesNothing(): void
    {
        $this->runUp();
        $first = $this->conn->fetchAllAssociative('SELECT id, aclaccess, date_modified FROM acl_actions WHERE category = ? ORDER BY id', [self::CATEGORY]);
        $this->runUp();
        $second = $this->conn->fetchAllAssociative('SELECT id, aclaccess, date_modified FROM acl_actions WHERE category = ? ORDER BY id', [self::CATEGORY]);
        self::assertSame($first, $second);
        self::assertCount(8, $second);
    }

    public function testDownIsIrreversible(): void
    {
        $this->expectException(\Doctrine\Migrations\Exception\IrreversibleMigration::class);
        (new Version20261124120000SeedFinanceAcl($this->conn, new NullLogger()))->down(new \Doctrine\DBAL\Schema\Schema());
    }
}
