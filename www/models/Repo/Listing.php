<?php

namespace Models\Repo;

use Exception;

class Listing extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the list of repos, their snapshots and their environments
     *  Does not display repos that have no active environments
     */
    public function list() : array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT
            repos.Id AS repoId,
            repos_snap.Id AS snapId,
            repos_env.Id AS envId,
            repos.Name,
            repos.Dist,
            repos.Section,
            repos.Releasever,
            repos.Source,
            repos.Package_type,
            repos_env.Env,
            repos_snap.Date,
            repos_snap.Time,
            repos_snap.Signed,
            repos_snap.Arch,
            repos_snap.Pkg_translation,
            repos_snap.Type,
            repos_env.Description
            FROM repos 
            LEFT JOIN repos_snap
                ON repos.Id = repos_snap.Id_repo
            LEFT JOIN repos_env 
                ON repos_snap.Id = repos_env.Id_snap
            WHERE repos_snap.Status = 'active'
            ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_env.Env ASC");
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the list of repos by group name
     */
    public function listByGroup(string $groupName) : array
    {
        $data = [];

        // If the group is 'Default' (a fictitious group), then we display all repos that do not belong to any group
        try {
            if ($groupName == 'Default') {
                $result = $this->db->query("SELECT DISTINCT
                repos.Id AS repoId,
                repos_snap.Id AS snapId,
                repos_env.Id AS envId,
                repos.Name,
                repos.Dist,
                repos.Section,
                repos.Releasever,
                repos.Source,
                repos.Package_type,
                repos_env.Env,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Pkg_translation,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Status,
                repos_env.Description
                FROM repos
                LEFT JOIN repos_snap
                    ON repos.Id = repos_snap.Id_repo
                LEFT JOIN repos_env 
                    ON repos_env.Id_snap = repos_snap.Id
                WHERE repos_snap.Status = 'active' AND repos.Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_snap.Date DESC");
            } else {
                $stmt = $this->db->prepare("SELECT DISTINCT
                repos.Id AS repoId,
                repos_snap.Id AS snapId,
                repos_env.Id AS envId,
                repos.Name,
                repos.Dist,
                repos.Section,
                repos.Releasever,
                repos.Source,
                repos.Package_type,
                repos_env.Env,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Pkg_translation,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Status,
                repos_env.Description
                FROM repos
                LEFT JOIN repos_snap
                    ON repos.Id = repos_snap.Id_repo
                LEFT JOIN repos_env 
                    ON repos_env.Id_snap = repos_snap.Id
                LEFT JOIN group_members
                    ON repos.Id = group_members.Id_repo
                LEFT JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name = :groupname
                AND repos_snap.Status = 'active'
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_snap.Date DESC");
                $stmt->bindValue(':groupname', $groupName);
                $result = $stmt->execute();
            }
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return an array of all repo names, with or without associated snapshots and environments
     *  If 'true' parameter is passed then the function will return only the names of the repos that have an active snapshot attached
     *  If 'false' parameter is passed then the function will return all repo names with or without attached snapshot
     */
    public function listNameOnly(bool $withActiveSnapshots)
    {
        $data = [];

        try {
            if (!$withActiveSnapshots) {
                $result = $this->db->query("SELECT DISTINCT *
                FROM repos
                ORDER BY Name ASC, Dist ASC, Section ASC");
            }

            if ($withActiveSnapshots) {
                $result = $this->db->query("SELECT DISTINCT
                repos.Id,
                repos.Name,
                repos.Releasever,
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos_snap.Id_repo NOT NULL
                AND repos_snap.Status = 'active'
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC");
            }
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the list of snapshots for a repository
     */
    public function listSnapshots(int $repoId) : array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM repos_snap WHERE Id_repo = :repoId AND Status = 'active' ORDER BY Date DESC");
            $stmt->bindValue(':repoId', $repoId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }
}
