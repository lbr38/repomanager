<?php
/**
 *  6.3.0 update
 */

/**
 *  Remove tasks PID directory: a stopped task process is now found by its process title, PID files are no longer used
 *  Remove the parent tasks directory too as it held nothing else
 */
if (is_dir(DATA_DIR . '/tasks/pid')) {
    if (!\Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/tasks/pid')) {
        throw new Exception('could not delete ' . DATA_DIR . '/tasks/pid directory');
    }
}

if (is_dir(DATA_DIR . '/tasks') and count(scandir(DATA_DIR . '/tasks')) == 2) {
    if (!rmdir(DATA_DIR . '/tasks')) {
        throw new Exception('could not delete ' . DATA_DIR . '/tasks directory');
    }
}
