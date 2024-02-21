<?php
/**
 *  3.7.9 database update
 */
$vacuum = false;

/**
 *  Remove column 'Linupdate_get_pkg_conf' if exists in profile table
 */
if ($this->db->columnExist('profile', 'Linupdate_get_pkg_conf') === true) {
    $this->db->exec("ALTER TABLE profile DROP COLUMN Linupdate_get_pkg_conf");
    $vacuum = true;
}

/**
 *  Remove column 'Linupdate_get_repos_conf' if exists in profile table
 */
if ($this->db->columnExist('profile', 'Linupdate_get_repos_conf') === true) {
    $this->db->exec("ALTER TABLE profile DROP COLUMN Linupdate_get_repos_conf");
    $vacuum = true;
}

/**
 *  Remove column 'Manage_client_conf' if exists in profile_settings table
 */
if ($this->db->columnExist('profile_settings', 'Manage_client_conf') === true) {
    $this->db->exec("ALTER TABLE profile_settings DROP COLUMN Manage_client_conf");
    $vacuum = true;
}

/**
 *  Remove column 'Manage_client_repos' if exists in profile_settings table
 */
if ($this->db->columnExist('profile_settings', 'Manage_client_repos') === true) {
    $this->db->exec("ALTER TABLE profile_settings DROP COLUMN Manage_client_repos");
    $vacuum = true;
}

/**
 *  Vacuum
 */
if ($vacuum === true) {
    $this->db->exec("VACUUM");
}
