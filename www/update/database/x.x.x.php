<?php
/**
 *  x.x.x database update
 */

/**
 *  If 'PLANS_ENABLED' column exists in settings table, then remove it
 */
if ($this->db->columnExist('settings', 'PLANS_ENABLED') === true) {
    /**
     *  Remove 'PLANS_ENABLED' column from settings
     */
    $this->db->exec("ALTER TABLE settings DROP COLUMN PLANS_ENABLED");
    $this->db->exec("VACUUM");
}

/**
 *  Update repos_snap with Signed CHAR(5)
 */

/**
 *   Create repos_snap_new table
 */
$this->db->exec("CREATE TABLE repos_snap_new (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Date DATE NOT NULL,
Time TIME NOT NULL,
Signed CHAR(5) NOT NULL,
Arch VARCHAR(255),
Pkg_translation VARCHAR(255),
Type CHAR(6) NOT NULL,
Reconstruct CHAR(8),
Status CHAR(8) NOT NULL,
Id_repo INTEGER NOT NULL)");

/**
 *  Copy all content from repos_snap to repos_snap_new:
 */
$this->db->exec("INSERT INTO repos_snap_new SELECT * FROM repos_snap");

/**
 *  Delete repos_snap and recreate it:
 */
$this->db->exec("DROP TABLE repos_snap");

$this->db->exec("CREATE TABLE repos_snap (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Date DATE NOT NULL,
Time TIME NOT NULL,
Signed CHAR(5) NOT NULL,
Arch VARCHAR(255),
Pkg_translation VARCHAR(255),
Type CHAR(6) NOT NULL,
Reconstruct CHAR(8),
Status CHAR(8) NOT NULL,
Id_repo INTEGER NOT NULL)");

/**
 *  Copy all content from repos_snap_new to repos_snap:
 */
$this->db->exec("INSERT INTO repos_snap SELECT * FROM repos_snap_new");

/**
 *  Drop repos_snap_new:
 */
$this->db->exec("DROP TABLE repos_snap_new");

/**
 *  Update Signed column in repos_snap,
 *  Replace 'yes' with 'true' and 'no' with 'false'
 */
$this->db->exec("UPDATE repos_snap SET Signed = 'true' WHERE Signed = 'yes'");
$this->db->exec("UPDATE repos_snap SET Signed = 'false' WHERE Signed = 'no'");

/**
 *  Clean
 */
$this->db->exec("VACUUM");
