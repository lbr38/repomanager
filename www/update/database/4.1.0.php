
<?php
/**
 *  4.1.0 database update
 */

/**
 *  Add column 'RPM_MISSING_SIGNATURE' if not exists and set default value
 */
if ($this->db->columnExist('settings', 'RPM_MISSING_SIGNATURE') !== true) {
    $this->db->exec("ALTER TABLE settings ADD RPM_MISSING_SIGNATURE VARCHAR(255)");
    $this->db->exec("UPDATE settings SET RPM_MISSING_SIGNATURE = 'error'");
}

/**
 *  Add column 'RPM_INVALID_SIGNATURE' if not exists and set default value
 */
if ($this->db->columnExist('settings', 'RPM_INVALID_SIGNATURE') !== true) {
    $this->db->exec("ALTER TABLE settings ADD RPM_INVALID_SIGNATURE VARCHAR(255)");
    $this->db->exec("UPDATE settings SET RPM_INVALID_SIGNATURE = 'error'");
}

/**
 *  Add column 'DEB_INVALID_SIGNATURE' if not exists and set default value
 */
if ($this->db->columnExist('settings', 'DEB_INVALID_SIGNATURE') !== true) {
    $this->db->exec("ALTER TABLE settings ADD DEB_INVALID_SIGNATURE VARCHAR(255)");
    $this->db->exec("UPDATE settings SET DEB_INVALID_SIGNATURE = 'error'");
}
