<?php
/**
 *  4.7.1 update
 */

/**
 *  Remove old files and directories
 */
if (file_exists(DATA_DIR . '/logs/stats') and is_dir(DATA_DIR . '/logs/stats')) {
    \Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/logs/stats');
}

if (file_exists(DATA_DIR . '/logs/service') and is_dir(DATA_DIR . '/logs/service')) {
    \Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/logs/service');
}
