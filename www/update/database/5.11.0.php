<?php
/**
 *  5.11.0 update
 */

// Add 'Advanced_params' column to repos_snap table
if (!$this->db->columnExist('repos_snap', 'Advanced_params')) {
    $this->db->exec("ALTER TABLE repos_snap ADD COLUMN Advanced_params TEXT");
}
