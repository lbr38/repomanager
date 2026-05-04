<?php
/**
 *  5.1.0 update
 */

$hostsDb = new \Models\Connection('hosts');

/**
 *  Add 'Cpu' column to hosts table
 */
if (!$hostsDb->columnExist('hosts', 'Cpu')) {
    $hostsDb->exec("ALTER TABLE hosts ADD COLUMN Cpu VARCHAR(255) DEFAULT 'unknown'");
}

/**
 *  Add 'Ram' column to hosts table
 */
if (!$hostsDb->columnExist('hosts', 'Ram')) {
    $hostsDb->exec("ALTER TABLE hosts ADD COLUMN Ram VARCHAR(255) DEFAULT 'unknown'");
}

/**
 *  Add 'Uptime' column to hosts table
 */
if (!$hostsDb->columnExist('hosts', 'Uptime')) {
    $hostsDb->exec("ALTER TABLE hosts ADD COLUMN Uptime VARCHAR(255) DEFAULT ''");
}

/**
 *  Clean some directories
 */
if (is_dir(LOGS_DIR . '/cve')) {
    if (!\Controllers\Filesystem\Directory::deleteRecursive(LOGS_DIR . '/cve')) {
        throw new Exception('Unable to delete directory ' . LOGS_DIR . '/cve');
    }
}

if (is_dir(LOGS_DIR . '/websocket')) {
    if (!\Controllers\Filesystem\Directory::deleteRecursive(LOGS_DIR . '/websocket')) {
        throw new Exception('Unable to delete directory ' . LOGS_DIR . '/websocket');
    }
}

/**
 *  Clean old repository files that could remain from previous versions
 *  Get all directories and files under /home/repo
 */
$files = glob(REPOS_DIR . '/*', GLOB_ERR);

foreach ($files as $file) {
    // Skip deb, rpm and gpgkeys directories
    if (in_array(basename($file), ['deb', 'rpm', 'gpgkeys'])) {
        continue;
    }

    // Delete directory
    if (is_dir($file)) {
        if (!\Controllers\Filesystem\Directory::deleteRecursive($file)) {
            throw new Exception('Unable to delete directory ' . $file);
        }

        continue;
    }

    // Delete file or symlink
    if (is_file($file) or is_link($file)) {
        if (!unlink($file)) {
            throw new Exception('Unable to delete ' . $file);
        }
    }
}

unset($hostsDb);
