<?php
/**
 *  4.9.0 update
 */

/**
 *  Add a new 'Details' column to the logs table
 */
if (!$this->db->columnExist('logs', 'Details') === true) {
    $this->db->exec("ALTER TABLE logs ADD COLUMN Details TEXT");
}

/**
 *  Add new 'Pkg_included' column to the repos_snap table
 */
if (!$this->db->columnExist('repos_snap', 'Pkg_included') === true) {
    $this->db->exec("ALTER TABLE repos_snap ADD COLUMN Pkg_included VARCHAR(255)");
}

/**
 *  Add new 'Pkg_excluded' column to the repos_snap table
 */
if (!$this->db->columnExist('repos_snap', 'Pkg_excluded') === true) {
    $this->db->exec("ALTER TABLE repos_snap ADD COLUMN Pkg_excluded VARCHAR(255)");
}
