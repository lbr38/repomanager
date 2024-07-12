<?php
/**
 *  4.4.0 database update
 */

/**
 *  Remove old tasks pool directory
 */
if (is_dir(DATA_DIR . '/tasks/pool')) {
    \Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/tasks/pool');
}
