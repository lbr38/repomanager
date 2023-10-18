<?php
/**
 *  3.7.4 database update
 */

/**
 *  Add mirroring timeout columns in settings table
 */

/**
 *  Ignore if column already exist
 */
if ($this->db->columnExist('settings', 'MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT') === true) {
    return;
}

/**
 *  Add new column in settings
 */
$this->db->exec("ALTER TABLE settings ADD MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT INTEGER");

/**
 *  Set value for new column
 */
$this->db->exec("UPDATE settings SET MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT = '300'");
