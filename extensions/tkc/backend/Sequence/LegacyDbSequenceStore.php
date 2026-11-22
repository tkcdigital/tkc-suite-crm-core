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
 * setIfNotLower() uses three statements too (SUITE-205):
 * 1. INSERT ... ON DUPLICATE KEY UPDATE seq_name = seq_name creates the row at the value, or does nothing.
 * 2. UPDATE sets date_modified first, while seq_value still has the old value, then
 *    seq_value = GREATEST(LAST_INSERT_ID(seq_value), v). The row lock makes the read and the write one step.
 * 3. SELECT LAST_INSERT_ID() reads the value before the change.
 * This depends on LAST_INSERT_ID(expr) and on UPDATE assignments that run left to right (MySQL 8).
 * Do not use affected rows: they are 0 for an equal value, which is a success.
 *
 * The store checks name, year and value itself (SequenceInput) because it does not trust its caller.
 *
 * @internal Use Sequence.
 */
class LegacyDbSequenceStore implements SequenceStore
{
    private const TABLE = 'tkc_sequences';
    private const INT_MAX = 2147483647; // signed INT column maximum

    public function next(string $name, int $year): int
    {
        SequenceInput::checkKey($name, $year);
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
        SequenceInput::checkKey($name, $year);
        $db = $this->db();
        $value = $db->getOne('SELECT seq_value FROM ' . self::TABLE . ' WHERE ' . $this->where($db, $name, $year));

        return ($value === false || $value === null) ? null : (int)$value;
    }

    public function setIfNotLower(string $name, int $year, int $value): int
    {
        SequenceInput::checkKey($name, $year);
        SequenceInput::checkValue($value);
        $db = $this->db();
        $where = $this->where($db, $name, $year);

        $this->run($db, 'INSERT INTO ' . self::TABLE . ' (seq_name, seq_year, seq_value, date_modified) VALUES ('
            . $db->quoted($name) . ', ' . $year . ', ' . $value . ', UTC_TIMESTAMP())'
            . ' ON DUPLICATE KEY UPDATE seq_name = seq_name');
        $this->run($db, 'UPDATE ' . self::TABLE
            . ' SET date_modified = IF(seq_value <= ' . $value . ', UTC_TIMESTAMP(), date_modified),'
            . ' seq_value = GREATEST(LAST_INSERT_ID(seq_value), ' . $value . ') WHERE ' . $where);

        $before = $db->getOne('SELECT LAST_INSERT_ID()');
        if ($before === false || $before === null) {
            throw new \RuntimeException('The counter value cannot be read.');
        }

        return (int)$before;
    }

    /** No check. Numbers above $value can repeat. */
    public function forceSet(string $name, int $year, int $value): void
    {
        SequenceInput::checkKey($name, $year);
        SequenceInput::checkValue($value);
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
