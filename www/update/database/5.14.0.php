<?php
/**
 *  5.14.0 update
 */
use \Controllers\Service\Unit\Main as ServiceUnit;

// Drop some indexes if exists
try {
    $this->db->exec("DROP INDEX IF EXISTS idx_repos_snap");
} catch (Exception $e) {
    throw new Exception('could not delete old indexes from main database: ' . $e->getMessage());
}

// Add 'Size' column to repos_snap table
if (!$this->db->columnExist('repos_snap', 'Size')) {
    $this->db->exec("ALTER TABLE repos_snap ADD COLUMN Size INTEGER");
}

// Add 'Size_human' column to repos_snap table
if (!$this->db->columnExist('repos_snap', 'Size_human')) {
    $this->db->exec("ALTER TABLE repos_snap ADD COLUMN Size_human VARCHAR(255)");
}

// Create new indexes
try {
    $this->db->exec("CREATE INDEX IF NOT EXISTS idx_repos_snap ON repos_snap (Date, Time, Signed, Arch, Type, Reconstruct, Size, Size_human, Status, Id_repo)");
} catch (Exception $e) {
    throw new Exception('could not create new indexes for main database: ' . $e->getMessage());
}

try {
    $serviceUnit = new ServiceUnit('snapshots-size-calculation');
    $serviceUnit->runUnit('snapshots-size-calculation');
} catch (Exception $e) {
    throw new Exception('Error while launching the calculation of snapshots size: ' . $e->getMessage());
}
