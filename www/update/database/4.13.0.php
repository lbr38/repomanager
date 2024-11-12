<?php
/**
 *  4.13.0 update
 */

/**
 *  Add new 'Color' column to the env table
 */
if (!$this->db->columnExist('env', 'Color') === true) {
    $this->db->exec("ALTER TABLE env ADD COLUMN Color VARCHAR(255)");

    /**
     *  Add color for each environment
     */
    $this->db->exec("UPDATE env SET Color = '#ffffff'");

    /**
     *  Add default red color for the last environment
     */
    $this->db->exec("UPDATE env SET Color = '#F32F63' WHERE Name = '" . LAST_ENV . "'");
}
