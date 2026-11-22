<?php

declare(strict_types=1);

namespace App\Extension\tkc\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

/**
 * SUITE-64: module ACL actions of tkc_AccountFinance, deny by default. Data only, DBAL only.
 *
 * Upsert, not insert-only: ACLAction::addActions(), SugarBean::create_tables() and Repair Roles insert
 * allow-all rows first (access 89, other actions 90). A seed that skips existing rows would leave Finance open.
 * Existing rows (deleted = 0) are set to deny. Missing rows are inserted. A second run changes nothing.
 * Why access is 89 and not -98 (ACL_ALLOW_DISABLED): ModuleRegistryHandler::applyUserActionFilter() drops a module
 * with access below 89 from the frontend module list and mod strings, and it has no admin exception. With -98 an admin
 * gets no labels and no subpanel metadata. The data stays denied by the seven -99 actions (view, list, edit, delete,
 * import, export, massupdate). -98 is only for a role override.
 * Role grants are not here (E13-S1). Logs counts only.
 */
final class Version20261124120000SeedFinanceAcl extends AbstractMigration
{
    private const CATEGORY = 'tkc_AccountFinance';
    private const ACL_ALLOW_ENABLED = 89;   // access action: module enabled (registry visible)
    private const ACL_ALLOW_NONE = -99;     // every other action: no access
    /** Names of $ACLActions['module']['actions'] in modules/ACLActions/actiondefs.php. */
    private const ACTIONS = ['access', 'view', 'list', 'edit', 'delete', 'import', 'export', 'massupdate'];

    public function getDescription(): string
    {
        return 'Seed deny-by-default module ACL actions for tkc_AccountFinance (SUITE-64)';
    }

    public function up(Schema $schema): void
    {
        $inserted = 0;
        $updated = 0;
        $now = gmdate('Y-m-d H:i:s');

        foreach (self::ACTIONS as $action) {
            $value = $action === 'access' ? self::ACL_ALLOW_ENABLED : self::ACL_ALLOW_NONE;
            $where = 'name = ? AND category = ? AND acltype = ? AND deleted = 0';
            $key = [$action, self::CATEGORY, 'module'];

            $exists = (int)$this->connection->fetchOne("SELECT COUNT(*) FROM acl_actions WHERE $where", $key);
            if ($exists === 0) {
                $this->connection->insert('acl_actions', [
                    'id' => self::uuid(),
                    'date_entered' => $now,
                    'date_modified' => $now,
                    'modified_user_id' => '1',
                    'created_by' => '1',
                    'name' => $action,
                    'category' => self::CATEGORY,
                    'acltype' => 'module',
                    'aclaccess' => $value,
                    'deleted' => 0,
                ]);
                ++$inserted;
                continue;
            }

            // Only rows that differ: a second run changes nothing (date_modified stays).
            $updated += (int)$this->connection->executeStatement(
                "UPDATE acl_actions SET aclaccess = ?, date_modified = ?, modified_user_id = '1' WHERE $where AND aclaccess <> ?",
                [$value, $now, ...$key, $value]
            );
        }

        $this->write(sprintf('SeedFinanceAcl: %d inserted, %d updated.', $inserted, $updated));
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('SeedFinanceAcl cannot be reversed: it would remove the deny rows.');
    }

    private static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
