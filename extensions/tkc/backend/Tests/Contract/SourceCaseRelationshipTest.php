<?php

namespace App\Extension\tkc\backend\Tests\Contract;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

/**
 * SUITE-70: after Repair the relationships table has tkc_cases_opportunities (one-to-many) and
 * no join table exists for it (C9). Reads names and types only, no records.
 * Precondition: tkc:repair has run (it writes the relationships rows).
 */
class SourceCaseRelationshipTest extends TestCase
{
    private function connection(): Connection
    {
        $root = dirname(__DIR__, 5);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new Dotenv())->load(...$files);
        return DriverManager::getConnection(['url' => $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL')]);
    }

    public function testSourceCaseRelationshipIsOneToMany(): void
    {
        $types = $this->connection()->fetchAllKeyValue(
            'SELECT relationship_name, relationship_type FROM relationships WHERE deleted = 0 AND relationship_name = ?',
            ['tkc_cases_opportunities']
        );
        $this->assertArrayHasKey('tkc_cases_opportunities', $types, 'Relationship tkc_cases_opportunities is missing: run tkc:repair.');
        $this->assertSame('one-to-many', $types['tkc_cases_opportunities']);
    }

    public function testNoJoinTableExists(): void
    {
        $tables = $this->connection()->fetchFirstColumn("SHOW TABLES LIKE 'tkc\\_cases\\_opportunities%'");
        $this->assertSame([], $tables, 'The relationship is a foreign key (C9): no join table is allowed.');
    }
}
