<?php
/**
 *  6.3.0 update
 */

/**
 *  Remove tasks directory (and its pid subdirectory): a stopped task process is now found by its process title, PID files are no longer used
 */
if (!\Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/tasks')) {
    throw new Exception('could not delete ' . DATA_DIR . '/tasks directory');
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
 *  Add Description and Tags columns to hosts table: description and tags (comma-separated) of a host, edited by users
 */
foreach (['Description', 'Tags'] as $column) {
    if ($hostsDb->columnExist('hosts', $column)) {
        continue;
    }

    try {
        $hostsDb->exec("ALTER TABLE hosts ADD COLUMN " . $column . " VARCHAR(255)");
    } catch (Exception $e) {
        throw new Exception('could not add ' . $column . ' column to hosts table');
    }
}

unset($hostsDb);

/**
 *  Add Category and Details columns to history table
 *  Category of the action and details (JSON) of the action: changes (before/after values) and parameters
 */
if (!$this->db->columnExist('history', 'Details')) {
    try {
        $this->db->exec("ALTER TABLE history ADD COLUMN Details TEXT");
    } catch (Exception $e) {
        throw new Exception('could not add Details column to history table');
    }
}

if (!$this->db->columnExist('history', 'Category')) {
    try {
        $this->db->exec("ALTER TABLE history ADD COLUMN Category VARCHAR(255)");

        // Set the category of the existing history lines from their action
        $this->db->exec("UPDATE history SET Category = 'authentication' WHERE Action LIKE 'Authentication%'");
        $this->db->exec("UPDATE history SET Category = 'repository' WHERE Action LIKE 'Running task:%' OR Action LIKE 'Scheduling task:%' OR Action LIKE '%repository properties%'");
        $this->db->exec("UPDATE history SET Category = 'mirror' WHERE Action LIKE 'Editing %mirror properties'
                       OR (Action LIKE 'Running task:%' AND (Action LIKE '%New mirror%' OR Action LIKE '%Sync mirror%' OR Action LIKE '%exact mirror%'
                       OR Action LIKE '%Delete mirror%' OR Action LIKE '% on mirror %' OR Action LIKE '% from mirror %' OR Action LIKE '% of mirror %'))");
        $this->db->exec("UPDATE history SET Category = 'task' WHERE Action LIKE 'Stopped task%'");
        $this->db->exec("UPDATE history SET Category = 'group' WHERE Category IS NULL AND Action LIKE '%group%'");
        $this->db->exec("UPDATE history SET Category = 'profile' WHERE Category IS NULL AND Action LIKE '%host profile%'");
        $this->db->exec("UPDATE history SET Category = 'user' WHERE Action LIKE 'Created user%' OR Action LIKE 'Deleted user%' OR Action LIKE 'Reseted password%' OR Action LIKE 'Password modification%'");
    } catch (Exception $e) {
        throw new Exception('could not add Category column to history table');
    }
}

/**
 *  Add ARCH_REPO column to settings table: enable Arch Linux repositories
 *  Enabled by default as Arch Linux repositories were always available before this setting
 */
if (!$this->db->columnExist('settings', 'ARCH_REPO')) {
    try {
        $this->db->exec("ALTER TABLE settings ADD COLUMN ARCH_REPO CHAR(5)");
        $this->db->exec("UPDATE settings SET ARCH_REPO = 'true'");
    } catch (Exception $e) {
        throw new Exception('could not add ARCH_REPO column to settings table');
    }
}
