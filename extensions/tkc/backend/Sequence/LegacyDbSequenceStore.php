<?php

namespace App\Extension\tkc\backend\Sequence;

/**
 * SequenceStore on the legacy database connection (SUITE-62). No constructor arguments:
 * the connection comes from DBManagerFactory when needed, so the container can autowire this class.
 *
 * next() uses three statements on one connection and no explicit transaction:
 * 1. INSERT ... ON DUPLICATE KEY UPDATE creates the row. The primary key absorbs a race of two first calls.
 * 2. UPDATE ... SET seq_value = LAST_INSERT_ID(seq_value + 1) is atomic and takes the InnoDB row lock.
 * 3. SELECT LAST_INSERT_ID() reads the value of this connection.
 * Inside a legacy transaction on the same connection the row lock stays until the commit.
 *
 * The caller must check name and year (Sequence does this). The name is quoted again here.
 */
class LegacyDbSequenceStore implements SequenceStore
{
    private const TABLE = 'tkc_sequences';
    private const INT_MAX = 2147483647; // signed INT column maximum

    public function next(string $name, int $year): int
    {
        $db = $this->db();
        $where = $this->where($db, $name, $year);

        $this->run($db, 'INSERT INTO ' . self::TABLE . ' (seq_name, seq_year, seq_value, date_modified) VALUES ('
            . $db->quoted($name) . ', ' . $year . ', 0, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE seq_name = seq_name');

        // The guard keeps the value inside a signed INT. Without it, a non-strict MySQL session clamps the column
        // at 2147483647 while LAST_INSERT_ID() returns the larger, unstored value, which can repeat (SUITE-62 QA D1).
        $result = $this->run($db, 'UPDATE ' . self::TABLE
            . ' SET seq_value = LAST_INSERT_ID(seq_value + 1), date_modified = UTC_TIMESTAMP() WHERE ' . $where
            . ' AND seq_value < ' . self::INT_MAX);
        if ($db->getAffectedRowCount($result) !== 1) {
            throw new \RuntimeException('The counter was not updated (it may be at its maximum ' . self::INT_MAX . ').');
        }

        $value = $db->getOne('SELECT LAST_INSERT_ID()');
        if ($value === false || $value === null || (int)$value < 1) {
            throw new \RuntimeException('The counter value cannot be read.');
        }

        return (int)$value;
    }

    public function current(string $name, int $year): ?int
    {
        $db = $this->db();
        $value = $db->getOne('SELECT seq_value FROM ' . self::TABLE . ' WHERE ' . $this->where($db, $name, $year));

        return ($value === false || $value === null) ? null : (int)$value;
    }

    public function set(string $name, int $year, int $value): void
    {
        $db = $this->db();
        $this->run($db, 'INSERT INTO ' . self::TABLE . ' (seq_name, seq_year, seq_value, date_modified) VALUES ('
            . $db->quoted($name) . ', ' . $year . ', ' . $value . ', UTC_TIMESTAMP())'
            . ' ON DUPLICATE KEY UPDATE seq_value = ' . $value . ', date_modified = UTC_TIMESTAMP()');
    }

    private function db(): \DBManager
    {
        return \DBManagerFactory::getInstance();
    }

    private function where(\DBManager $db, string $name, int $year): string
    {
        return 'seq_name = ' . $db->quoted($name) . ' AND seq_year = ' . $year;
    }

    private function run(\DBManager $db, string $sql): mixed
    {
        $result = $db->query($sql);
        if ($result === false) {
            throw new \RuntimeException('The counter query failed.');
        }

        return $result;
    }
}
