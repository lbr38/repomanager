<?php
/**
 *  4.20.1 update
 */

/**
 *  Add 'OIDC_HTTP_PROXY' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_HTTP_PROXY')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_HTTP_PROXY VARCHAR(255)");
}

/**
 *  Add 'OIDC_CERT_PATH' column to settings table
 */
if (!$this->db->columnExist('settings', 'OIDC_CERT_PATH')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN OIDC_CERT_PATH VARCHAR(255)");
}
