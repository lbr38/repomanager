<?php
/**
 *  3.4.16 database update
 */

/**
 *  Check that users table exist
 */
if ($this->db->tableExist('users') === false) {
    return;
}


/**
 *  Check if Api_key column exists in users table
 */
if ($this->db->columnExist('users', 'Api_key') === true) {
    return;
}

/**
 *  Add new 'Api_key' column in users
 */
$this->db->exec("ALTER TABLE users ADD Api_key CHAR(32)");

/**
 *  Create a new users table
 */
$this->db->exec("CREATE TABLE IF NOT EXISTS users_new (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Username VARCHAR(255) NOT NULL,
Password CHAR(60),
Api_key CHAR(32),
First_name VARCHAR(50),
Last_name VARCHAR(50),
Email VARCHAR(100),
Role INTEGER NOT NULL,
Type CHAR(5) NOT NULL,
State CHAR(7) NOT NULL)");


/**
 *  Copy all content from users to users_new:
 */
$this->db->exec("INSERT INTO users_new SELECT
Id,
Username,
Password,
Api_key,
First_name,
Last_name,
Email,
Role,
Type,
State FROM users");

/**
 *  Delete users and recreate it:
 */
$this->db->exec("DROP TABLE users");
$this->db->exec("CREATE TABLE IF NOT EXISTS users (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Username VARCHAR(255) NOT NULL,
Password CHAR(60),
Api_key CHAR(32),
First_name VARCHAR(50),
Last_name VARCHAR(50),
Email VARCHAR(100),
Role INTEGER NOT NULL,
Type CHAR(5) NOT NULL,
State CHAR(7) NOT NULL)");

/**
 *  Copy all content from users_new to users:
 */
$this->db->exec("INSERT into users SELECT * FROM users_new");

/**
 *  Drop users_new:
 */
$this->db->exec("DROP TABLE users_new");
