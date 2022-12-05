<?php
/**
 *  v3.2.0-stable database update
 */

/**
 *  Check if Pool_id column exists in operations table
 */
if ($this->db->columnExist('operations', 'Pool_id') === true) {
    return;
}

/**
 *  If Pool_id column is not found then add it
 */
$this->db->exec("ALTER TABLE operations ADD Pool_id INTEGER");
/**
 *  Fill columns that were created:
 */
$this->db->exec("UPDATE operations SET Pool_id = '0000'");
/**
 *   Create a new operations table with NOT NULL constraint this time:
 */
$this->db->exec("CREATE TABLE IF NOT EXISTS operations_new (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Date DATE NOT NULL,
Time TIME NOT NULL,
Action VARCHAR(255) NOT NULL,
Type CHAR(6) NOT NULL,
Id_repo_source VARCHAR(255),
Id_snap_source INTEGER,
Id_env_source INTEGER,
Id_repo_target VARCHAR(255),
Id_snap_target INTEGER,
Id_env_target INTEGER,
Id_group INTEGER,
Id_plan INTEGER,
GpgCheck CHAR(3),
GpgResign CHAR(3),
Pid INTEGER NOT NULL,
Pool_id INTEGER NOT NULL,
Logfile VARCHAR(255) NOT NULL,
Duration INTEGER,
Status CHAR(7) NOT NULL)");
/**
 *  Copy all content from operations to operations_new:
 */
$this->db->exec("INSERT INTO operations_new SELECT
Id,
Date,
Time,
Action,
Type,
Id_repo_source,
Id_snap_source,
Id_env_source,
Id_repo_target, 
Id_snap_target,
Id_env_target,
Id_group,
Id_plan,
GpgCheck,
GpgResign,
Pid,
Pool_id,
Logfile,
Duration,
Status FROM operations");
/**
 *  Delete operations and recreate it:
 */
$this->db->exec("DROP TABLE operations");
$this->db->exec("CREATE TABLE IF NOT EXISTS operations (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Date DATE NOT NULL,
Time TIME NOT NULL,
Action VARCHAR(255) NOT NULL,
Type CHAR(6) NOT NULL,
Id_repo_source VARCHAR(255),
Id_snap_source INTEGER,
Id_env_source INTEGER,
Id_repo_target VARCHAR(255),
Id_snap_target INTEGER,
Id_env_target INTEGER,
Id_group INTEGER,
Id_plan INTEGER,
GpgCheck CHAR(3),
GpgResign CHAR(3),
Pid INTEGER NOT NULL,
Pool_id INTEGER NOT NULL,
Logfile VARCHAR(255) NOT NULL,
Duration INTEGER,
Status CHAR(7) NOT NULL)");
/**
 *  Copy all content from operations_new to operations:
 */
$this->db->exec("INSERT into operations SELECT * FROM operations_new");
/**
 *  Drop operations_new:
 */
$this->db->exec("DROP TABLE operations_new;");
