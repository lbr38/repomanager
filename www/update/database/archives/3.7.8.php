<?php
/**
 *  3.7.8 database update
 */

/**
 *  Quit if column 'Pkg_source' does not exist in repos_snap table
 */
if ($this->db->columnExist('repos_snap', 'Pkg_source') === false) {
    return;
}

/**
 *  Remove deprecated repos_snap column 'Pkg_source' if exists
 */
$this->db->exec("ALTER TABLE repos_snap DROP COLUMN Pkg_source");
$this->db->exec("VACUUM");
