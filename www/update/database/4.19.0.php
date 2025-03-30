<?php
/**
 *  4.19.0 update
 */

/**
 *  Add 'OIDC_ENABLED' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_ENABLED')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_ENABLED BOOLEAN DEFAULT 'false'");
}

/**
 *  Add 'SSO_OIDC_ONLY' column to settings table
 */
if (!$this->db->columnExist('settings', 'SSO_OIDC_ONLY')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN SSO_OIDC_ONLY BOOLEAN DEFAULT 'false'");
}

/**
 *  Add 'OIDC_PROVIDER_URL' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_PROVIDER_URL')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_PROVIDER_URL VARCHAR(255)");
}

/**
 *  Add 'OIDC_AUTHORIZATION_ENDPOINT' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_AUTHORIZATION_ENDPOINT')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_AUTHORIZATION_ENDPOINT VARCHAR(255)");
}

/**
 *  Add 'OIDC_TOKEN_ENDPOINT' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_TOKEN_ENDPOINT')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_TOKEN_ENDPOINT VARCHAR(255)");
}

/**
 *  Add 'OIDC_USERINFO_ENDPOINT' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_USERINFO_ENDPOINT')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_USERINFO_ENDPOINT VARCHAR(255)");
}

/**
 *  Add 'OIDC_SCOPES' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_SCOPES')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_SCOPES VARCHAR(255) DEFAULT 'groups,email,profile'");
}

/**
 *  Add 'OIDC_CLIENT_ID' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_CLIENT_ID')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_CLIENT_ID VARCHAR(255)");
}

/**
 *  Add 'OIDC_CLIENT_SECRET' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_CLIENT_SECRET')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_CLIENT_SECRET VARCHAR(255)");
}

/**
 *  Add 'OIDC_USERNAME' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_USERNAME')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_USERNAME VARCHAR(255) DEFAULT 'preferred_username'");
}

/**
 *  Add 'OIDC_FIRST_NAME' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_FIRST_NAME')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_FIRST_NAME VARCHAR(255) DEFAULT 'given_name'");
}

/**
 *  Add 'OIDC_LAST_NAME' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_LAST_NAME')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_LAST_NAME VARCHAR(255) DEFAULT 'family_name'");
}

/**
 *  Add 'OIDC_EMAIL' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_EMAIL')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_EMAIL VARCHAR(255) DEFAULT 'email'");
}

/**
 *  Add 'OIDC_GROUPS' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_GROUPS')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_GROUPS VARCHAR(255) DEFAULT 'groups'");
}

/**
 *  Add 'OIDC_GROUP_ADMINISTRATOR' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_GROUP_ADMINISTRATOR')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_GROUP_ADMINISTRATOR VARCHAR(255) DEFAULT 'administrator'");
}

/**
 *  Add 'OIDC_GROUP_SUPER_ADMINISTRATOR' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_GROUP_SUPER_ADMINISTRATOR')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_GROUP_SUPER_ADMINISTRATOR VARCHAR(255) DEFAULT 'super-administrator'");
}

/**
 *  Add 'Username' column to history table
 */
if (!$this->db->columnExist('history', 'Username')) {
    $this->db->exec("ALTER TABLE history ADD COLUMN Username VARCHAR(255)");
}

/**
 *  Add 'Ip' column to history table
 */
if (!$this->db->columnExist('history', 'Ip')) {
    $this->db->exec("ALTER TABLE history ADD COLUMN Ip VARCHAR(255)");
}

/**
 *  Add 'Ip_forwarded' column to history table
 */
if (!$this->db->columnExist('history', 'Ip_forwarded')) {
    $this->db->exec("ALTER TABLE history ADD COLUMN Ip_forwarded VARCHAR(255)");
}

/**
 *  Add 'User_agent' column to history table
 */
if (!$this->db->columnExist('history', 'User_agent')) {
    $this->db->exec("ALTER TABLE history ADD COLUMN User_agent VARCHAR(255)");
}

/**
 *  Clean deleted users from database
 */
$this->db->exec("DELETE FROM users WHERE State = 'deleted'");
