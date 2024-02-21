<?php
/**
 *  v3.1.0-stable database update
 */

/**
 *  Check if Arch column exists in repos_snap table
 */
if ($this->db->columnExist('repos_snap', 'Arch') === true) {
    return;
}

/**
 *  If Arch column is not found then it means that v3.1.0-stable has not been installed yet,
 *  Proceed to create Arch and Pkg_translation
 */
$this->db->exec("ALTER TABLE repos_snap ADD Arch VARCHAR(255)");
$this->db->exec("ALTER TABLE repos_snap ADD Pkg_translation VARCHAR(255)");
/**
 *  Fill columns that were created:
 */
$this->db->exec("UPDATE repos_snap SET Arch = 'x86_64' WHERE Id_repo IN (SELECT Id FROM repos WHERE Package_type = 'rpm')");
$this->db->exec("UPDATE repos_snap SET Arch = 'amd64' WHERE Id_repo IN (SELECT Id FROM repos WHERE Package_type = 'deb')");
/**
 *   Create a new repos_snap table with NOT NULL constraint this time:
 */
$this->db->exec("CREATE TABLE repos_snap_new (Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, Date DATE NOT NULL, Time TIME NOT NULL, Signed CHAR(3) NOT NULL, Arch VARCHAR(55) NOT NULL, Pkg_translation VARCHAR(255), Type CHAR(6) NOT NULL, Reconstruct CHAR(8), Status CHAR(8) NOT NULL, Id_repo INTEGER NOT NULL);");
/**
 *  Copy all content from repos_snap to repos_snap_new:
 */
$this->db->exec("INSERT INTO repos_snap_new SELECT Id, Date, Time, Signed, Arch, Pkg_translation, Type, Reconstruct, Status, Id_repo FROM repos_snap");
/**
 *  Delete repos_snap and recreate it:
 */
$this->db->exec("DROP TABLE repos_snap");
$this->db->exec("CREATE TABLE repos_snap (Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, Date DATE NOT NULL, Time TIME NOT NULL, Signed CHAR(3) NOT NULL, Arch VARCHAR(55) NOT NULL, Pkg_translation VARCHAR(255), Type CHAR(6) NOT NULL, Reconstruct CHAR(8), Status CHAR(8) NOT NULL, Id_repo INTEGER NOT NULL)");
/**
 *  Copy all content from repos_snap_new to repos_snap:
 */
$this->db->exec("INSERT into repos_snap SELECT * FROM repos_snap_new");
/**
 *  Drop repos_snap_new:
 */
$this->db->exec("DROP TABLE repos_snap_new;");
