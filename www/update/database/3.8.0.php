<?php
/**
 *  3.8.0 database update
 */

/**
 *  If 'PLANS_ENABLED' column exists in settings table, then remove it
 */
if ($this->db->columnExist('settings', 'PLANS_ENABLED') === true) {
    /**
     *  Remove 'PLANS_ENABLED' column from settings
     */
    $this->db->exec("ALTER TABLE settings DROP COLUMN PLANS_ENABLED");
    $this->db->exec("VACUUM");
}

/**
 *  Update repos_snap with Signed CHAR(5)
 */

/**
 *   Create repos_snap_new table
 */
$this->db->exec("CREATE TABLE repos_snap_new (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Date DATE NOT NULL,
Time TIME NOT NULL,
Signed CHAR(5) NOT NULL,
Arch VARCHAR(255),
Pkg_translation VARCHAR(255),
Type CHAR(6) NOT NULL,
Reconstruct CHAR(8),
Status CHAR(8) NOT NULL,
Id_repo INTEGER NOT NULL)");

/**
 *  Copy all content from repos_snap to repos_snap_new:
 */
$this->db->exec("INSERT INTO repos_snap_new SELECT * FROM repos_snap");

/**
 *  Delete repos_snap and recreate it:
 */
$this->db->exec("DROP TABLE repos_snap");

$this->db->exec("CREATE TABLE repos_snap (
Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
Date DATE NOT NULL,
Time TIME NOT NULL,
Signed CHAR(5) NOT NULL,
Arch VARCHAR(255),
Pkg_translation VARCHAR(255),
Type CHAR(6) NOT NULL,
Reconstruct CHAR(8),
Status CHAR(8) NOT NULL,
Id_repo INTEGER NOT NULL)");

/**
 *  Copy all content from repos_snap_new to repos_snap:
 */
$this->db->exec("INSERT INTO repos_snap SELECT * FROM repos_snap_new");

/**
 *  Drop repos_snap_new:
 */
$this->db->exec("DROP TABLE repos_snap_new");

/**
 *  Update Signed column in repos_snap,
 *  Replace 'yes' with 'true' and 'no' with 'false'
 */
$this->db->exec("UPDATE repos_snap SET Signed = 'true' WHERE Signed = 'yes'");
$this->db->exec("UPDATE repos_snap SET Signed = 'false' WHERE Signed = 'no'");

/**
 *  Migration operations table content to tasks table
 */
if ($this->db->tableExist('operations') === false) {
    return;
}

$result = $this->db->query("SELECT * FROM operations");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $operations[] = $row;
}

if (!empty($operations)) {
    foreach ($operations as $operation) {
        $rawParams = array();

        $rawParams['action'] = $operation['Action'];

        /**
         *  Retrieve repository informations depending on the operation type
         */

        // new
        if ($operation['Action'] == 'new') {
            $rawParams['action'] = 'create';
            $rawParams['repo-id'] = $operation['Id_repo_target'];
        }
        // update
        if ($operation['Action'] == 'update') {
            $rawParams['action']  = 'update';
            $rawParams['snap-id'] = $operation['Id_snap_target'];
        }
        // duplicate
        if ($operation['Action'] == 'duplicate') {
            $rawParams['action']  = 'duplicate';
            $rawParams['snap-id'] = $operation['Id_snap_source'];
            $rawParams['name'] = explode('|', $operation['Id_repo_target'])[0];
        }
        // env
        if ($operation['Action'] == 'env') {
            $rawParams['action']  = 'env';
            $rawParams['snap-id'] = $operation['Id_snap_target'];
            $rawParams['env'] = $operation['Id_env_target'];
        }
        // removeEnv
        if ($operation['Action'] == 'removeEnv') {
            $rawParams['action']  = 'removeEnv';
            $rawParams['snap-id'] = $operation['Id_snap_target'];
            $rawParams['env']     = $operation['Id_env_target'];
        }
        // rebuild
        if ($operation['Action'] == 'rebuild') {
            $rawParams['action']   = 'rebuild';
            $rawParams['snap-id']  = $operation['Id_snap_target'];
            if ($operation['GpgResign'] == 'yes') {
                $rawParams['gpg-sign'] = 'true';
            } else {
                $rawParams['gpg-sign'] = 'false';
            }
        }
        // delete
        if ($operation['Action'] == 'delete') {
            $rawParams['action']  = 'delete';
            $rawParams['snap-id'] = $operation['Id_snap_target'];
        }

        $rawParams['schedule']['scheduled'] = 'false';

        /**
         *  Insert into tasks table
         */
        $stmt = $this->db->prepare("INSERT INTO tasks (Type, Date, Time, Raw_params, Pid, Logfile, Duration, Status) VALUES (:type, :date, :time, :rawParams, :pid, :logfile, :duration, :status)");
        $stmt->bindValue(':type', 'immediate');
        $stmt->bindValue(':date', $operation['Date']);
        $stmt->bindValue(':time', $operation['Time']);
        $stmt->bindValue(':rawParams', json_encode($rawParams));
        $stmt->bindValue(':pid', $operation['Pid']);
        $stmt->bindValue(':logfile', $operation['Logfile']);
        $stmt->bindValue(':duration', $operation['Duration']);
        $stmt->bindValue(':status', $operation['Status']);
        $stmt->execute();
    }
}

/**
 *  Clean
 */
$this->db->exec("VACUUM");
