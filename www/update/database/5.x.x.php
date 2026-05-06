<?php
/**
 *  5.x.x update
 */

// Delete Pkg_translation column from repos_snap database
if (!$this->db->columnExist('repos_snap', 'Pkg_translation')) {
    // Delete index if it exists
    $this->db->exec("DROP INDEX IF EXISTS repos_snap_index");

    try {
        $this->db->exec("ALTER TABLE repos_snap DROP COLUMN Pkg_translation");
    } catch (Exception $e) {
        throw new Exception('could not delete Pkg_translation column from repos_snap table');
    }

    // Recreate index without Pkg_translation column
    $this->db->exec("CREATE INDEX IF NOT EXISTS repos_snap_index ON repos_snap (Date, Time, Signed, Arch, Pkg_included, Pkg_excluded, Type, Reconstruct, Status, Id_repo)");
}
