<?php
/**
 *  3.4.3 database update
 */

if (!file_exists(STATS_DB)) {
    return;
}

/**
 *  Open stats database
 */
$this->getConnection('stats');

/**
 *  Destroy and recreate indexes
 */
$this->db->exec("DROP INDEX IF EXISTS request_index");
$this->db->exec("CREATE INDEX IF NOT EXISTS access_index ON access (Date, Time, Request)");
$this->db->exec("CREATE INDEX IF NOT EXISTS stats_index ON stats (Date, Time, Size, Packages_count, Id_env)");
