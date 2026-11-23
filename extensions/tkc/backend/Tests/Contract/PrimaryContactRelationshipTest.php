<?php

namespace App\Extension\tkc\backend\Tests\Contract;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

/**
 * SUITE-67: after Repair the relationships table has tkc_contacts_cases_primary (one-to-many)
 * and the core M:N relationship contacts_cases is still there. Reads name and type only, no records.
 * Precondition: tkc:repair has run (it writes the relationships rows).
 */
class PrimaryContactRelationshipTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function relationshipTypes(): array
    {
        $root = dirname(__DIR__, 5);
        $files = array_filter(["$root/.env", "$root/.env.local"], 'is_file');
        (new Dotenv())->load(...$files);
        $conn = DriverManager::getConnection(['url' => $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL')]);
        return $conn->fetchAllKeyValue(
            'SELECT relationship_name, relationship_type FROM relationships WHERE deleted = 0 AND relationship_name IN (?, ?)',
            ['tkc_contacts_cases_primary', 'contacts_cases']
        );
    }

    public function testPrimaryContactRelationshipIsOneToMany(): void
    {
        $types = $this->relationshipTypes();
        $this->assertArrayHasKey('tkc_contacts_cases_primary', $types, 'Relationship tkc_contacts_cases_primary is missing: run tkc:repair.');
        $this->assertSame('one-to-many', $types['tkc_contacts_cases_primary']);
    }

    public function testCoreContactsCasesRelationshipStillExists(): void
    {
        $this->assertArrayHasKey('contacts_cases', $this->relationshipTypes(), 'Core relationship contacts_cases is missing.');
    }
}
