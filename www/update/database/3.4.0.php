<?php
/**
 *  3.4.0 database update
 */

/**
 *  Check if Allow_overwrite column exists in profile table
 */
if ($this->db->columnExist('profile', 'Linupdate_get_pkg_conf') === true) {
    return;
}

/**
 *  Create a new profile table
 */
$this->db->exec("CREATE TABLE IF NOT EXISTS profile_new (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Name VARCHAR(255) NOT NULL,
Package_exclude VARCHAR(255),
Package_exclude_major VARCHAR(255),
Service_restart VARCHAR(255),
Linupdate_get_pkg_conf CHAR(5),
Linupdate_get_repos_conf CHAR(5),
Notes VARCHAR(255))");

/**
 *  Copy all content from profile to profile_new:
 */
$this->db->exec("INSERT INTO profile_new SELECT
Id,
Name,
Package_exclude,
Package_exclude_major,
Service_restart,
Allow_overwrite,
Allow_repos_overwrite,
Notes FROM profile");

/**
 *  Delete profile and recreate it:
 */
$this->db->exec("DROP TABLE profile");
$this->db->exec("CREATE TABLE IF NOT EXISTS profile (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Name VARCHAR(255) NOT NULL,
Package_exclude VARCHAR(255),
Package_exclude_major VARCHAR(255),
Service_restart VARCHAR(255),
Linupdate_get_pkg_conf CHAR(5),
Linupdate_get_repos_conf CHAR(5),
Notes VARCHAR(255))");

/**
 *  Copy all content from profile_new to profile:
 */
$this->db->exec("INSERT into profile SELECT * FROM profile_new");

/**
 *  Drop profile_new:
 */
$this->db->exec("DROP TABLE profile_new;");

/**
 *  Convert values to boolean
 */
$this->db->exec("UPDATE profile SET Linupdate_get_pkg_conf = 'true' WHERE Linupdate_get_pkg_conf = 'yes'");
$this->db->exec("UPDATE profile SET Linupdate_get_pkg_conf = 'false' WHERE Linupdate_get_pkg_conf = 'no'");
$this->db->exec("UPDATE profile SET Linupdate_get_repos_conf = 'true' WHERE Linupdate_get_repos_conf = 'yes'");
$this->db->exec("UPDATE profile SET Linupdate_get_repos_conf = 'false' WHERE Linupdate_get_repos_conf = 'no'");
