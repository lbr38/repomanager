<?php

namespace Models\Repo;

use Exception;

class Rpm extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the Id of a repository by its name and release version
     */
    public function getIdByNameReleasever(string $name, string $releaseVersion) : int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Releasever = :releaseVersion");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':releaseVersion', $releaseVersion);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Return repository environment description
     */
    public function getDescriptionByName(string $name, string $releaseVersion, string $env) : string|null
    {
        $description = null;

        try {
            $stmt = $this->db->prepare("SELECT repos_env.Description FROM repos_env
            INNER JOIN repos_snap
                ON repos_snap.Id = repos_env.Id_snap
            INNER JOIN repos
                ON repos.Id = repos_snap.Id_repo
            WHERE repos.Name = :name
            AND repos.Releasever = :releaseVersion
            AND repos_env.Env = :env
            AND repos_snap.Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releaseVersion', $releaseVersion);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $description = $row['Description'];
        }

        return $description;
    }

    /**
     *  Return environment Id from repo name
     */
    public function getEnvIdFromRepoName(string $name, string $releaseVersion, string $env) : array
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
            AND repos.Releasever = :releaseVersion
            AND repos_env.Env = :env");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releaseVersion', $releaseVersion);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row['Id'];
        }

        return $data;
    }

    /**
     *  Return true if a repository with the specified name and release version exists
     */
    public function exists(string $name, string $releaseVersion) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Releasever = :releaseVersion");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':releaseVersion', $releaseVersion);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if a snapshot exists at a specific date in database, from the repository name, version and date
     */
    public function existsSnapDate(string $name, string $releaseVersion, string $date) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT repos_snap.Id FROM repos
            INNER JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            WHERE repos.Name = :name
            AND repos.Releasever = :releaseVersion 
            AND repos_snap.Date = :date
            AND repos_snap.Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releaseVersion', $releaseVersion);
            $stmt->bindValue(':date', $date);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if a repository environment exists, based on its name and the repository name it points to
     */
    public function existsEnv(string $name, string $releaseVersion, string $env) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT repos.Id FROM repos
            INNER JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            INNER JOIN repos_env
                ON repos_env.Id_snap = repos_snap.Id
            WHERE repos.Name = :name
            AND repos.Releasever = :releaseVersion
            AND repos_env.Env = :env
            AND repos_snap.Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releaseVersion', $releaseVersion);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Add a new RPM repository
     */
    public function add(string $name, string $releaseVersion, string $source = '') : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos ('Name', 'Releasever', 'Source', 'Package_type') VALUES (:name, :releaseVersion, :source, 'rpm')");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':releaseVersion', $releaseVersion);
            $stmt->bindValue(':source', $source, SQLITE3_TEXT);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Return true if the repository exists and is active (has snapshots)
     */
    public function isActive(string $name, string $releaseVersion) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT repos.Id FROM repos
            INNER JOIN repos_snap
            ON repos_snap.Id_repo = repos.Id
            WHERE repos.Name = :name
            AND repos.Releasever = :releasever
            AND repos_snap.Status = 'active'");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releaseVersion);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }
}
