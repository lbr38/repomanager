<?php
/**
 *  4.24.0 update
 */

/**
 *  Add 'REPO_DEDUPLICATION' column to settings table
 */
if (!$this->db->columnExist('settings', 'REPO_DEDUPLICATION')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN REPO_DEDUPLICATION CHAR(5) DEFAULT 'true'");
}

/**
 *  Create indexes for the repos table
 */
$this->db->exec("CREATE INDEX IF NOT EXISTS repos_ALL_index ON repos (Name, Releasever, Dist, Section, Source, Package_type)");

/**
 *  Create indexes for the repos_snap table
 */
$this->db->exec("CREATE INDEX IF NOT EXISTS repos_snap_status_id_repo_index ON repos_snap (Status, Id_repo)");

/**
 *  Create indexes for the repos_env table
 */
$this->db->exec("CREATE INDEX IF NOT EXISTS repos_env_id_snap_index ON repos_env (Id_snap)");

/**
 *  Create indexes for the group_members table
 */
$this->db->exec("CREATE INDEX IF NOT EXISTS group_members_id_repo_index ON group_members (Id_repo)");
$this->db->exec("CREATE INDEX IF NOT EXISTS group_members_id_group_index ON group_members (Id_group)");
