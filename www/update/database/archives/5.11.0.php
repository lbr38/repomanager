<?php
/**
 *  5.11.0 update
 */

// Drop some indexes if exists
try {
    $this->db->exec("DROP INDEX IF EXISTS repos_snap_index");
    $this->db->exec("DROP INDEX IF EXISTS repos_snap_status_id_repo_index");
    $this->db->exec("DROP INDEX IF EXISTS repos_snap_id_repo_index");
} catch (Exception $e) {
    throw new Exception('could not delete old indexes from repos database: ' . $e->getMessage());
}

// Add 'Advanced_params' column to repos_snap table
if (!$this->db->columnExist('repos_snap', 'Advanced_params')) {
    $this->db->exec("ALTER TABLE repos_snap ADD COLUMN Advanced_params TEXT");
}

// Create new indexes
try {
    $this->db->exec("CREATE INDEX IF NOT EXISTS idx_repos_snap ON repos_snap (Date, Time, Signed, Arch, Type, Reconstruct, Status, Id_repo)");
    $this->db->exec("CREATE INDEX IF NOT EXISTS idx_repos_snap_status_id_repo ON repos_snap (Status, Id_repo)");
    $this->db->exec("CREATE INDEX IF NOT EXISTS idx_repos_snap_id_repo ON repos_snap (Id_repo)");
} catch (Exception $e) {
    throw new Exception('could not create new indexes for repos database: ' . $e->getMessage());
}

// Get all snapshots
try {
    $snapshots = [];
    $result = $this->db->query("SELECT * FROM repos_snap");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $snapshots[] = $row;
    }
} catch (Exception $e) {
    throw new Exception('could not fetch snapshots from repos database: ' . $e->getMessage());
}

// If snapshot has 'Pkg_included' or 'Pkg_excluded' columns, move their content to 'Advanced_params' column and remove them
foreach ($snapshots as $snapshot) {
    $advancedParams = [];

    if (!empty($snapshot['Pkg_included'])) {
        $advancedParams['packages']['include'] = explode(',', $snapshot['Pkg_included']);
    }

    if (!empty($snapshot['Pkg_excluded'])) {
        $advancedParams['packages']['exclude'] = explode(',', $snapshot['Pkg_excluded']);
    }

    // Encode advanced params as JSON and update snapshot with new content
    try {
        $advancedParams = json_encode($advancedParams, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    } catch (JsonException $e) {
        throw new Exception('could not encode advanced params as JSON: ' . $e->getMessage());
    }

    // Update snapshot with new 'Advanced_params' content
    try {
        $stmt = $this->db->prepare("UPDATE repos_snap SET Advanced_params = :advancedParams WHERE Id = :id");
        $stmt->bindValue(':advancedParams', $advancedParams, SQLITE3_TEXT);
        $stmt->bindValue(':id', $snapshot['Id'], SQLITE3_INTEGER);
        $stmt->execute();
    } catch (Exception $e) {
        throw new Exception('could not update snapshot with new advanced params: ' . $e->getMessage());
    }
}

// Finally, drop 'Pkg_included', 'Pkg_excluded' and 'DEB_DEFAULT_TRANSLATION' columns from database if they exist
try {
    if ($this->db->columnExist('repos_snap', 'Pkg_included')) {
        $this->db->exec("ALTER TABLE repos_snap DROP COLUMN Pkg_included");
    }

    if ($this->db->columnExist('repos_snap', 'Pkg_excluded')) {
        $this->db->exec("ALTER TABLE repos_snap DROP COLUMN Pkg_excluded");
    }

    if ($this->db->columnExist('settings', 'DEB_DEFAULT_TRANSLATION')) {
        $this->db->exec("ALTER TABLE settings DROP COLUMN DEB_DEFAULT_TRANSLATION");
    }
} catch (Exception $e) {
    throw new Exception('could not drop old columns from repos database: ' . $e->getMessage());
}
