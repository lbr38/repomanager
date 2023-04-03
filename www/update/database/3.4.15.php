<?php
/**
 *  3.4.15 database update
 */

/**
 *  Check that hosts database exists
 */
if (!is_file(HOSTS_DB)) {
    return;
}

/**
 *  Open hosts database
 */
$hostsDb = new \Models\Connection('hosts');

/**
 *  Quit if 'Reboot_required' column exists in hosts table
 */
if ($hostsDb->columnExist('hosts', 'Reboot_required') === true) {
    return;
}

/**
 *  Add new 'Reboot_required' column in hosts
 */
$hostsDb->exec("ALTER TABLE hosts ADD Reboot_required CHAR(5)");

/**
 *  Create a new hosts table
 */
$hostsDb->exec("CREATE TABLE IF NOT EXISTS hosts_new (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Ip VARCHAR(15) NOT NULL,
Hostname VARCHAR(255) NOT NULL,
Os VARCHAR(255),
Os_version VARCHAR(255),
Os_family VARCHAR(255),
Kernel VARCHAR(255),
Arch CHAR(10),
Type VARCHAR(255),
Profile VARCHAR(255),
Env VARCHAR(255),
AuthId VARCHAR(255),
Token VARCHAR(255),
Online_status CHAR(8),
Online_status_date DATE,
Online_status_time TIME,
Reboot_required CHAR(5),
Linupdate_version VARCHAR(255),
Status VARCHAR(8) NOT NULL)");


/**
 *  Copy all content from hosts to hosts_new:
 */
$hostsDb->exec("INSERT INTO hosts_new SELECT
Id,
Ip,
Hostname,
Os,
Os_version,
Os_family,
Kernel,
Arch,
Type,
Profile,
Env,
AuthId,
Token,
Online_status,
Online_status_date,
Online_status_time,
Reboot_required,
Linupdate_version,
Status FROM hosts");

/**
 *  Delete hosts and recreate it:
 */
$hostsDb->exec("DROP TABLE hosts");
$hostsDb->exec("CREATE TABLE IF NOT EXISTS hosts (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Ip VARCHAR(15) NOT NULL,
Hostname VARCHAR(255) NOT NULL,
Os VARCHAR(255),
Os_version VARCHAR(255),
Os_family VARCHAR(255),
Kernel VARCHAR(255),
Arch CHAR(10),
Type VARCHAR(255),
Profile VARCHAR(255),
Env VARCHAR(255),
AuthId VARCHAR(255),
Token VARCHAR(255),
Online_status CHAR(8),
Online_status_date DATE,
Online_status_time TIME,
Reboot_required CHAR(5),
Linupdate_version VARCHAR(255),
Status VARCHAR(8) NOT NULL)");

/**
 *  Copy all content from hosts_new to hosts:
 */
$hostsDb->exec("INSERT into hosts SELECT * FROM hosts_new");

/**
 *  Drop hosts_new:
 */
$hostsDb->exec("DROP TABLE hosts_new");

$hostsDb->exec("UPDATE hosts SET Reboot_required = 'false'");

/**
 *  Close hosts database
 */
$hostsDb->close();

unset($hostsDb);
