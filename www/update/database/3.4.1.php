<?php
/**
 *  3.4.1 database update
 */

/**
 *  Open hosts database
 */
$this->getConnection('hosts');

/**
 *  Check if Allow_overwrite column exists in profile table
 */
if ($this->db->columnExist('hosts', 'Linupdate_version') === true) {
    return;
}

/**
 *  Add Linupdate_version column
 */
$this->db->exec("ALTER TABLE hosts ADD Linupdate_version VARCHAR(255)");
