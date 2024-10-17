<?php
/**
 *  4.12.0 update
 */

/**
 *  Add new 'Details' column to the sources table
 */
if (!$this->db->columnExist('sources', 'Details') === true) {
    $this->db->exec("ALTER TABLE sources ADD COLUMN Details TEXT");
}
