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

/**
 *  Add Public_ip column to hosts table: public IP sent by hosts behind a NAT
 */
try {
    $hostsDb = new \Models\Connection('hosts');
} catch (Exception $e) {
    throw new Exception('could not open hosts database: ' . $e->getMessage());
}

if (!$hostsDb->columnExist('hosts', 'Public_ip')) {
    try {
        $hostsDb->exec("ALTER TABLE hosts ADD COLUMN Public_ip VARCHAR(255)");
    } catch (Exception $e) {
        throw new Exception('could not add Public_ip column to hosts table');
    }
}

/**
 *  Add Category and Details columns to history table
 *  Category of the action and details (JSON) of the action: changes (before/after values) and parameters
 */
try {
    $mainDb = new \Models\Connection('main');
} catch (Exception $e) {
    throw new Exception('could not open main database: ' . $e->getMessage());
}

if (!$mainDb->columnExist('history', 'Details')) {
    try {
        $mainDb->exec("ALTER TABLE history ADD COLUMN Details TEXT");
    } catch (Exception $e) {
        throw new Exception('could not add Details column to history table');
    }
}

if (!$mainDb->columnExist('history', 'Category')) {
    try {
        $mainDb->exec("ALTER TABLE history ADD COLUMN Category VARCHAR(255)");

        // Set the category of the existing history lines from their action
        $mainDb->exec("UPDATE history SET Category = 'authentication' WHERE Action LIKE 'Authentication%'");
        $mainDb->exec("UPDATE history SET Category = 'repository' WHERE Action LIKE 'Running task:%' OR Action LIKE 'Scheduling task:%' OR Action LIKE '%repository properties%'");
        $mainDb->exec("UPDATE history SET Category = 'mirror' WHERE Action LIKE 'Editing %mirror properties'
                       OR (Action LIKE 'Running task:%' AND (Action LIKE '%New mirror%' OR Action LIKE '%Sync mirror%' OR Action LIKE '%exact mirror%'
                       OR Action LIKE '%Delete mirror%' OR Action LIKE '% on mirror %' OR Action LIKE '% from mirror %' OR Action LIKE '% of mirror %'))");
        $mainDb->exec("UPDATE history SET Category = 'task' WHERE Action LIKE 'Stopped task%'");
        $mainDb->exec("UPDATE history SET Category = 'group' WHERE Category IS NULL AND Action LIKE '%group%'");
        $mainDb->exec("UPDATE history SET Category = 'profile' WHERE Category IS NULL AND Action LIKE '%host profile%'");
        $mainDb->exec("UPDATE history SET Category = 'user' WHERE Action LIKE 'Created user%' OR Action LIKE 'Deleted user%' OR Action LIKE 'Reseted password%' OR Action LIKE 'Password modification%'");
    } catch (Exception $e) {
        throw new Exception('could not add Category column to history table');
    }
}

unset($mainDb);
