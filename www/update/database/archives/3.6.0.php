<?php
/**
 *  3.6.0 database update
 */

/**
 *  Drop repos_list_settings table
 */
$this->db->exec('DROP TABLE IF EXISTS repos_list_settings');

/**
 *  Quit if 'Releasever' column exists in repos table
 */
if ($this->db->columnExist('repos', 'Releasever') === true) {
    return;
}

/**
 *  Add new 'Releasever' column in repos
 */
$this->db->exec("ALTER TABLE repos ADD Releasever VARCHAR(255)");

if (empty(RELEASEVER)) {
    $releasever = 8;
} else {
    $releasever = RELEASEVER;
}

/**
 *  Set value for Releasever column
 */
$this->db->exec("UPDATE repos SET Releasever = '$releasever' WHERE Package_type = 'rpm'");
