<?php

namespace Models\Repo;

use Exception;
use Controllers\Database\Log as DbLog;

class Listing extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the list of repos, their snapshots and their environments
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
            repos.Description,
            repos.Tags,
            repos_env.Env,
            repos_snap.Date,
            repos_snap.Time,
            repos_snap.Signed,
            repos_snap.Arch,
            repos_snap.Type,
            repos_snap.Reconstruct,
            repos_snap.Size,
            repos_snap.Size_human
            FROM repos 
            LEFT JOIN repos_snap
                ON repos.Id = repos_snap.Id_repo
            LEFT JOIN repos_env 
                ON repos_snap.Id = repos_env.Id_snap
            WHERE repos_snap.Status = 'active'
            ORDER BY repos.Name ASC, repos.Releasever ASC, repos.Dist ASC, repos.Section ASC, repos_snap.Date DESC");
        } catch (Exception $e) {
            DbLog::error($e);
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
                repos.Description,
                repos.Tags,
                repos_env.Env,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Size,
                repos_snap.Size_human,
                repos_snap.Status
                FROM repos
                LEFT JOIN repos_snap
                    ON repos.Id = repos_snap.Id_repo
                LEFT JOIN repos_env 
                    ON repos_env.Id_snap = repos_snap.Id
                WHERE repos_snap.Status = 'active' AND repos.Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Releasever ASC, repos.Dist ASC, repos.Section ASC, repos_snap.Date DESC, repos_env.Env ASC");
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
                repos.Description,
                repos.Tags,
                repos_env.Env,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Size,
                repos_snap.Size_human,
                repos_snap.Status
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
                ORDER BY repos.Name ASC, repos.Releasever ASC, repos.Dist ASC, repos.Section ASC, repos_snap.Date DESC, repos_env.Env ASC");
                $stmt->bindValue(':groupname', $groupName);
                $result = $stmt->execute();
            }
        } catch (Exception $e) {
            DbLog::error($e);
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
                repos.Package_type,
                repos.Description,
                repos.Tags
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos_snap.Id_repo NOT NULL
                AND repos_snap.Status = 'active'
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC");
            }
        } catch (Exception $e) {
            DbLog::error($e);
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
            $stmt = $this->db->prepare("SELECT
                repos_snap.*,
                COALESCE(
                    (
                        SELECT GROUP_CONCAT(Env, ',')
                        FROM repos_env
                        WHERE Id_snap = repos_snap.Id
                        ORDER BY Env
                    ),
                    ''
                ) AS Environments,
                COALESCE(
                    (
                        SELECT GROUP_CONCAT(Id, ',')
                        FROM repos_env
                        WHERE Id_snap = repos_snap.Id
                        ORDER BY Env
                    ),
                    ''
                ) AS EnvironmentIds
            FROM repos_snap
            WHERE repos_snap.Id_repo = :repoId
            AND repos_snap.Status = 'active'
            ORDER BY repos_snap.Date DESC");
            $stmt->bindValue(':repoId', $repoId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the list of repositories matching the specified filters, with their latest active snapshot Id
     *  Repositories without any active snapshot are ignored
     *  An empty group Id / tags / package type means 'no filtering on this criteria'
     */
    public function listByTarget(string $groupId = '', array $tags = [], string $packageType = '') : array
    {
        $data = [];
        $conditions = [];
        $bindings = [];

        // Filter on package type
        if ($packageType !== '') {
            $conditions[] = 'repos.Package_type = :packageType';
            $bindings[':packageType'] = $packageType;
        }

        /**
         *  Filter on group
         *  Group Id 0 is a fictitious group ('Default') containing all the repositories that do not belong to any group
         *  The group Id is used instead of its name, so that renaming a group has no impact on the target
         */
        if ($groupId !== '') {
            if ($groupId === '0') {
                $conditions[] = 'repos.Id NOT IN (SELECT Id_repo FROM group_members)';
            } else {
                $conditions[] = 'repos.Id IN (SELECT group_members.Id_repo FROM group_members WHERE group_members.Id_group = :groupId)';
                $bindings[':groupId'] = $groupId;
            }
        }

        /**
         *  Filter on tags
         *  A repository must have ALL the specified tags to be selected
         *  Tags are stored as a comma-separated string, so the column is surrounded by commas to search for an exact tag
         */
        foreach (array_values($tags) as $index => $tag) {
            $placeholder = ':tag' . $index;
            $conditions[] = "(',' || IFNULL(repos.Tags, '') || ',') LIKE " . $placeholder;
            $bindings[$placeholder] = '%,' . $tag . ',%';
        }

        $where = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        try {
            // The latest active snapshot Id is retrieved with a subquery, then rows without snapshot are filtered out
            $stmt = $this->db->prepare("SELECT * FROM (
                SELECT
                repos.Id AS repoId,
                repos.Name,
                repos.Dist,
                repos.Section,
                repos.Releasever,
                repos.Package_type,
                repos.Tags,
                (
                    SELECT repos_snap.Id
                    FROM repos_snap
                    WHERE repos_snap.Id_repo = repos.Id
                    AND repos_snap.Status = 'active'
                    ORDER BY repos_snap.Date DESC, repos_snap.Time DESC, repos_snap.Id DESC
                    LIMIT 1
                ) AS snapId
                FROM repos" . $where . "
            ) WHERE snapId IS NOT NULL
            ORDER BY Name ASC, Releasever ASC, Dist ASC, Section ASC");

            foreach ($bindings as $placeholder => $value) {
                $stmt->bindValue($placeholder, $value);
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
            return $data;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the list of all distinct tags used by repositories
     */
    public function listTags(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT DISTINCT Tags FROM repos WHERE Tags IS NOT NULL AND Tags != ''");
        } catch (Exception $e) {
            DbLog::error($e);
            return $data;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            foreach (explode(',', $row['Tags']) as $tag) {
                $tag = trim($tag);

                if ($tag !== '' and !in_array($tag, $data)) {
                    $data[] = $tag;
                }
            }
        }

        sort($data);

        return $data;
    }
}
