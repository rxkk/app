<?php

namespace Rxkk\App\Facade;

use Rxkk\Lib\MySQL as BaseMySQL;

class MySQL {
    /**
     * @param string $sql  Any SQL query: SELECT, INSERT, UPDATE, DELETE, SHOW, DESCRIBE, etc.
     *
     * @MCP Execute any SQL query against the database.
     *      For SELECT/SHOW/DESCRIBE returns array of rows.
     *      For INSERT returns {insert_id, affected_rows}.
     *      For UPDATE/DELETE returns {affected_rows}.
     *      To explore schema use: SHOW TABLES, DESCRIBE <table>, SHOW CREATE TABLE <table>.
     * @MCP-CONFIRM
     */
    public static function query(string $sql): mixed {
        $mysqli = BaseMySQL::getSingleton()->connect;

        $result = $mysqli->query($sql);

        if ($result === false) {
            throw new \RuntimeException('SQL error: ' . $mysqli->error . ' | Query: ' . $sql);
        }

        // Non-SELECT (INSERT, UPDATE, DELETE, CREATE, etc.)
        if ($result === true) {
            $data = ['affected_rows' => $mysqli->affected_rows];
            if ($mysqli->insert_id > 0) {
                $data['insert_id'] = $mysqli->insert_id;
            }
            return $data;
        }

        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
