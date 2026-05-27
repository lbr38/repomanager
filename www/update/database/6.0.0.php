<?php
/**
 *  5.x.x update
 */

// Drop some indexes if exists
try {
    $this->db->exec("DROP INDEX IF EXISTS repos_env_index");
    $this->db->exec("DROP INDEX IF EXISTS repos_env_id_snap_index");
} catch (Exception $e) {
    throw new Exception('could not delete old indexes from database: ' . $e->getMessage());
}


// Delete Pkg_translation column from repos_snap database
if ($this->db->columnExist('repos_snap', 'Pkg_translation')) {
    try {
        $this->db->exec("ALTER TABLE repos_snap DROP COLUMN Pkg_translation");
    } catch (Exception $e) {
        throw new Exception('could not delete Pkg_translation column from repos_snap table');
    }
}

// Add Description column to repos table
if (!$this->db->columnExist('repos', 'Description')) {
    try {
        $this->db->exec("ALTER TABLE repos ADD COLUMN Description VARCHAR(255)");
    } catch (Exception $e) {
        throw new Exception('could not add Description column to repos table');
    }
}

// Add Tags column to repos table
if (!$this->db->columnExist('repos', 'Tags')) {
    try {
        $this->db->exec("ALTER TABLE repos ADD COLUMN Tags VARCHAR(255)");
    } catch (Exception $e) {
        throw new Exception('could not add Tags column to repos table');
    }
}

// Delete Description column from repos_env table
if ($this->db->columnExist('repos_env', 'Description')) {
    try {
        $this->db->exec("ALTER TABLE repos_env DROP COLUMN Description");
    } catch (Exception $e) {
        throw new Exception('could not delete Description column from repos_env table');
    }
}
