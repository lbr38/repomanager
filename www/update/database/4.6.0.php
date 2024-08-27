<?php
/**
 *  4.6.0 update
 */

/**
 *  Remove old files and directories
 */
if (file_exists(DATA_DIR . '/repomanager')) {
    unlink(DATA_DIR . '/repomanager');
}

if (file_exists(DATA_DIR . '/operations') and is_dir(DATA_DIR . '/operations')) {
    \Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/operations');
}

if (file_exists(DATA_DIR . '/configurations') and is_dir(DATA_DIR . '/configurations')) {
    \Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/configurations');
}

if (file_exists(DATA_DIR . '/backups') and is_dir(DATA_DIR . '/backups')) {
    \Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/backups');
}
