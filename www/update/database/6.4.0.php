<?php
/**
 *  6.4.0 update
 */

/**
 *  Add ALPINE_REPO column to settings table: enable Alpine Linux repositories (mirrors only)
 *  Enabled by default, as the other package types on a new installation
 */
if (!$this->db->columnExist('settings', 'ALPINE_REPO')) {
    try {
        $this->db->exec("ALTER TABLE settings ADD COLUMN ALPINE_REPO CHAR(5)");
        $this->db->exec("UPDATE settings SET ALPINE_REPO = 'true'");
    } catch (Exception $e) {
        throw new Exception('could not add ALPINE_REPO column to settings table');
    }
}

/**
 *  Add OPENBSD_REPO column to settings table: enable OpenBSD repositories (mirrors only)
 *  Enabled by default, as the other package types on a new installation
 */
if (!$this->db->columnExist('settings', 'OPENBSD_REPO')) {
    try {
        $this->db->exec("ALTER TABLE settings ADD COLUMN OPENBSD_REPO CHAR(5)");
        $this->db->exec("UPDATE settings SET OPENBSD_REPO = 'true'");
    } catch (Exception $e) {
        throw new Exception('could not add OPENBSD_REPO column to settings table');
    }
}
