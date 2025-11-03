<?php

namespace Models\Repo;

use Exception;
use \Controllers\Database\Log as DbLog;

class Deb extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the Id of a repository by its name, distribution and component/section
     */
    public function getIdByNameDistComponent(string $name, string $distribution, string $component) : int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Dist = :distribution AND Section = :component");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':distribution', $distribution, SQLITE3_TEXT);
            $stmt->bindValue(':component', $component, SQLITE3_TEXT);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Return repository environment description
     */
    public function getDescriptionByName(string $name, string $dist, string $component, string $env) : string|null
    {
        $description = null;

        try {
            $stmt = $this->db->prepare("SELECT repos_env.Description FROM repos_env
            INNER JOIN repos_snap
                ON repos_snap.Id = repos_env.Id_snap
            INNER JOIN repos
                ON repos.Id = repos_snap.Id_repo
            WHERE repos.Name = :name
            AND repos.Dist = :dist
            AND repos.Section = :component
            AND repos_env.Env = :env
            AND repos_snap.Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':component', $component);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $description = $row['Description'];
        }

        return $description;
    }

    /**
     *  Return environment Id from repo name
     */
    public function getEnvIdFromRepoName(string $name, string $dist, string $component, string $env) : array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT repos_env.Id
            FROM repos_env
            INNER JOIN repos_snap
                ON repos_snap.Id = repos_env.Id_snap
            INNER JOIN repos
                ON repos.Id = repos_snap.Id_repo
            WHERE repos.Name = :name
            AND repos.Dist = :dist
            AND repos.Section = :component
            AND repos_env.Env = :env");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':component', $component);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row['Id'];
        }

        return $data;
    }

    /**
     *  Return true if a repository with the specified name, distribution and component/section exists
     */
    public function exists(string $name, string $distribution, string $component) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Dist = :distribution AND Section = :component");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':distribution', $distribution, SQLITE3_TEXT);
            $stmt->bindValue(':component', $component, SQLITE3_TEXT);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if a snapshot exists at a specific date in database, from the repository name, version and date
     */
    public function existsSnapDate(string $name, string $dist, string $component, string $date) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT repos_snap.Id FROM repos
            INNER JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            WHERE repos.Name = :name
            AND repos.Dist = :dist
            AND repos.Section = :component            
            AND repos_snap.Date = :date
            AND repos_snap.Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':component', $component);
            $stmt->bindValue(':date', $date);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if a repository environment exists, based on its name and the repository name it points to
     */
    public function existsEnv(string $name, string $dist, string $component, string $env) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT repos.Id FROM repos
            INNER JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            INNER JOIN repos_env
                ON repos_env.Id_snap = repos_snap.Id
            WHERE repos.Name = :name
            AND repos.Dist = :dist
            AND repos.Section = :component
            AND repos_env.Env = :env
            AND repos_snap.Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':component', $component);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Add a new DEB repository
     */
    public function add(string $name, string $distribution, string $component, string $source = '') : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos ('Name', 'Dist', 'Section', 'Source', 'Package_type') VALUES (:name, :distribution, :component, :source, 'deb')");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':distribution', $distribution, SQLITE3_TEXT);
            $stmt->bindValue(':component', $component, SQLITE3_TEXT);
            $stmt->bindValue(':source', $source, SQLITE3_TEXT);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Return true if the repository exists and is active (has snapshots)
     */
    public function isActive(string $name, string $dist, string $component) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT repos.Id FROM repos
            INNER JOIN repos_snap
            ON repos_snap.Id_repo = repos.Id
            WHERE repos.Name = :name
            AND repos.Dist = :dist
            AND repos.Section = :component
            AND repos_snap.Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':component', $component);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }
}
