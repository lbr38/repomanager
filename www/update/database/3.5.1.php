<?php
/**
 *  3.5.1 database update
 */

/**
 *  Set some settings to default
 */
$this->db->exec("UPDATE settings SET
UPDATE_BACKUP_DIR = '/var/lib/repomanager/backups',
RPM_SIGN_METHOD = 'rpmsign',
STATS_LOG_PATH = '/var/log/nginx/repomanager_access.log'");
