<?php
/**
 *  3.7.6 database update
 */
$vacumm = 0;

/**
 *  Remove deprecated settings columns
 *  DROP COLUMN requires sqlite >= 3.35
 */
if ($this->db->columnExist('settings', 'REPOS_DIR') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN REPOS_DIR");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'WWW_DIR') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN WWW_DIR");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'WWW_USER') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN WWW_USER");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'WWW_HOSTNAME') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN WWW_HOSTNAME");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'UPDATE_AUTO') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN UPDATE_AUTO");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'UPDATE_BRANCH') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN UPDATE_BRANCH");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'UPDATE_BACKUP') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN UPDATE_BACKUP");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'UPDATE_BACKUP_DIR') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN UPDATE_BACKUP_DIR");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'RPM_SIGN_METHOD') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN RPM_SIGN_METHOD");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'RPM_INCLUDE_SOURCE') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN RPM_INCLUDE_SOURCE");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'DEB_INCLUDE_SOURCE') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN DEB_INCLUDE_SOURCE");
    $vacumm++;
}

if ($this->db->columnExist('settings', 'STATS_LOG_PATH') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN STATS_LOG_PATH");
    $vacumm++;
}

if ($vacumm > 0) {
    $this->db->exec("VACUUM");
}
