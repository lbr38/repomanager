<?php

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
