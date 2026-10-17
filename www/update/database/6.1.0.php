<?php
/**
 *  6.1.0 update
 */

// Open hosts database
try {
    $hostsDb = new \Models\Connection('hosts');
} catch (Exception $e) {
    throw new Exception('could not open hosts database: ' . $e->getMessage());
}

try {
    // Delete 'Pid' column from tasks table
    if ($this->db->columnExist('tasks', 'Pid')) {
        $this->db->exec("ALTER TABLE tasks DROP COLUMN Pid");
    }

    // Delete 'Logfile' column from tasks table
    if ($this->db->columnExist('tasks', 'Logfile')) {
        $this->db->exec("ALTER TABLE tasks DROP COLUMN Logfile");
    }
} catch (Exception $e) {
    throw new Exception('could not drop columns from tasks table: ' . $e->getMessage());
}

try {
    // Add 'compliance_exclude_packages' column to hosts settings table
    if (!$hostsDb->columnExist('settings', 'compliance_exclude_packages')) {
        $hostsDb->exec("ALTER TABLE settings ADD COLUMN compliance_exclude_packages TEXT DEFAULT '[]'");
    }
} catch (Exception $e) {
    throw new Exception('could not add compliance_exclude_packages column to hosts settings table: ' . $e->getMessage());
}
