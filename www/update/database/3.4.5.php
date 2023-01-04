<?php
/**
 *  3.4.5 database update
 */

/**
 *  Check that planifications table exist
 */
if ($this->db->tableExist('planifications') === false) {
    return;
}

/**
 *  Check if OnlySyncDifference column exists in planifications table
 */
if ($this->db->columnExist('planifications', 'OnlySyncDifference') === true) {
    return;
}

/**
 *  Add new 'OnlySyncDifference' column in planifications
 */
$this->db->exec("ALTER TABLE planifications ADD OnlySyncDifference CHAR(3)");

/**
 *  Create a new planifications table
 */
$this->db->exec("CREATE TABLE IF NOT EXISTS planifications_new (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Type CHAR(7) NOT NULL,
Frequency CHAR(15),
Day CHAR(70),
Date DATE,
Time TIME,
Action VARCHAR(255) NOT NULL,
Id_snap INTEGER,
Id_group INTEGER,
Target_env VARCHAR(255),
Gpgcheck CHAR(3),
Gpgresign CHAR(3),
OnlySyncDifference CHAR(3),	
Reminder VARCHAR(255),
Notification_error CHAR(3),
Notification_success CHAR(3),
Mail_recipient VARCHAR(255),
Status CHAR(10) NOT NULL,
Error VARCHAR(255),
Logfile VARCHAR(255))");


/**
 *  Copy all content from planifications to planifications_new:
 */
$this->db->exec("INSERT INTO planifications_new SELECT
Id,
Type,
Frequency,
Day,
Date,
Time,
Action,
Id_snap,
Id_group,
Target_env,
Gpgcheck,
Gpgresign,
OnlySyncDifference,
Reminder,
Notification_error,
Notification_success,
Mail_recipient,
Status,
Error,
Logfile FROM planifications");

/**
 *  Delete planifications and recreate it:
 */
$this->db->exec("DROP TABLE planifications");
$this->db->exec("CREATE TABLE IF NOT EXISTS planifications (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Type CHAR(7) NOT NULL,
Frequency CHAR(15),
Day CHAR(70),
Date DATE,
Time TIME,
Action VARCHAR(255) NOT NULL,
Id_snap INTEGER,
Id_group INTEGER,
Target_env VARCHAR(255),
Gpgcheck CHAR(3),
Gpgresign CHAR(3),
OnlySyncDifference CHAR(3),
Reminder VARCHAR(255),
Notification_error CHAR(3),
Notification_success CHAR(3),
Mail_recipient VARCHAR(255),
Status CHAR(10) NOT NULL,
Error VARCHAR(255),
Logfile VARCHAR(255))");

/**
 *  Copy all content from planifications_new to planifications:
 */
$this->db->exec("INSERT into planifications SELECT * FROM planifications_new");

/**
 *  Drop planifications_new:
 */
$this->db->exec("DROP TABLE planifications_new");

$this->db->exec("UPDATE planifications SET OnlySyncDifference = 'no'");
