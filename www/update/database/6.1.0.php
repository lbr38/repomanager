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
    // Add 'settings' column to hosts settings table if it doesn't exist
    if (!$hostsDb->columnExist('settings', 'settings')) {
        $hostsDb->exec("ALTER TABLE settings ADD COLUMN settings TEXT DEFAULT '{}'");
    }
} catch (Exception $e) {
    throw new Exception('could not add new settings columns to hosts settings table: ' . $e->getMessage());
}

// Migrate current compliance to the JSON settings column
try {
    $currentSettings = [];
    $settings = [];

    // Get current settings from the settings table
    $result = $hostsDb->query("SELECT * FROM settings");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $currentSettings = $row;
    }

    $settings['compliance']['thresholds']['count'] = $currentSettings['compliance_threshold_count'] ?? 1;
    $settings['compliance']['thresholds']['days'] = $currentSettings['compliance_threshold_days'] ?? 30;
    $settings['compliance']['thresholds']['security-update'] = $currentSettings['compliance_security_update'] ?? true;
    $settings['compliance']['thresholds']['reboot-required'] = $currentSettings['compliance_reboot_required'] ?? true;
    // Add two new sections for compliance excludes
    $settings['compliance']['exclusions']['hosts'] = [];
    $settings['compliance']['exclusions']['packages'] = [];

    // Update the settings table with the new JSON settings column
    $hostsDb->exec("UPDATE settings SET settings = '" . json_encode($settings) . "'");
} catch (Exception $e) {
    throw new Exception('could not migrate current compliance settings to JSON settings column: ' . $e->getMessage());
}
