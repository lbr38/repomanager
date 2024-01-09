<?php
/**
 *  3.7.12 database update
 */

/**
 *  If 'RPM_SIGN_IGNORE_MISSING_SIGNATURE' column does not exist in settings table, then add it
 */
if ($this->db->columnExist('settings', 'RPM_SIGN_IGNORE_MISSING_SIGNATURE') === false) {
    /**
     *  Add new 'RPM_SIGN_IGNORE_MISSING_SIGNATURE' column in settings
     */
    $this->db->exec("ALTER TABLE settings ADD RPM_SIGN_IGNORE_MISSING_SIGNATURE CHAR(5)");

    /**
     *  Set value for RPM_SIGN_IGNORE_MISSING_SIGNATURE column
     */
    $this->db->exec("UPDATE settings SET RPM_SIGN_IGNORE_MISSING_SIGNATURE = 'false'");
}

/**
 *  If 'PLANS_UPDATE_REPO' column exists in settings table, then remove it
 */
if ($this->db->columnExist('settings', 'PLANS_UPDATE_REPO') === true) {
    /**
     *  Remove 'PLANS_UPDATE_REPO' column from settings
     */
    $this->db->exec("ALTER TABLE settings DROP COLUMN PLANS_UPDATE_REPO");
    $this->db->exec("VACUUM");
}
