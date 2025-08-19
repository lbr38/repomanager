<?php

namespace Models\Repo;

use DateTime;
use Exception;

class Repo extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open main database connection
         */
        $this->getConnection('main');
    }

    /**
     *  Retrieve all informations from a repo, snapshot and env in database
     */
    public function getAllById(string|null $repoId, string|null $snapId, string|null $envId) : array
    {
        $data = [];

        try {
            if (!empty($repoId) and !empty($snapId) and !empty($envId)) {
                $stmt = $this->db->prepare("SELECT
                repos.Id AS repoId,
                repos.Name,
                repos.Releasever,
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type,
                repos_snap.Id AS snapId,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Pkg_translation,
                repos_snap.Pkg_included,
                repos_snap.Pkg_excluded,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Status,
                repos_snap.Id_repo,
                repos_env.Id AS envId,
                repos_env.Env,
                repos_env.Description,
                repos_env.Id_snap
                FROM repos 
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                INNER JOIN repos_env
                    ON repos_env.Id_snap = repos_snap.Id
                WHERE repos.Id = :repoId
                AND repos_snap.Id = :snapId
                AND repos_env.Id = :envId");
                $stmt->bindValue(':repoId', $repoId);
                $stmt->bindValue(':snapId', $snapId);
                $stmt->bindValue(':envId', $envId);
            } elseif (!empty($repoId) and !empty($snapId)) {
                $stmt = $this->db->prepare("SELECT
                repos.Id AS repoId,
                repos.Name,
                repos.Releasever,
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type,
                repos_snap.Id AS snapId,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Pkg_translation,
                repos_snap.Pkg_included,
                repos_snap.Pkg_excluded,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Status,
                repos_snap.Id_repo
                FROM repos 
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos.Id = :repoId
                AND repos_snap.Id = :snapId");
                $stmt->bindValue(':repoId', $repoId);
                $stmt->bindValue(':snapId', $snapId);
            } elseif (!empty($repoId)) {
                $stmt = $this->db->prepare("SELECT *
                FROM repos
                WHERE repos.Id = :repoId");
                $stmt->bindValue(':repoId', $repoId);
            } elseif (!empty($snapId)) {
                $stmt = $this->db->prepare("SELECT
                repos.Id AS repoId,
                repos.Name,
                repos.Releasever,
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type,
                repos_snap.Id AS snapId,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Pkg_translation,
                repos_snap.Pkg_included,
                repos_snap.Pkg_excluded,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Status,
                repos_snap.Id_repo
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos_snap.Id = :snapId");
                $stmt->bindValue(':snapId', $snapId);
            } elseif (!empty($envId)) {
                $stmt = $this->db->prepare("SELECT
                repos.Id AS repoId,
                repos.Name,
                repos.Releasever,
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type,
                repos_snap.Id AS snapId,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Pkg_translation,
                repos_snap.Pkg_included,
                repos_snap.Pkg_excluded,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Status,
                repos_snap.Id_repo,
                repos_env.Id AS envId,
                repos_env.Env,
                repos_env.Description,
                repos_env.Id_snap
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                INNER JOIN repos_env
                    ON repos_env.Id_snap = repos_snap.Id
                WHERE repos_env.Id = :envId");
                $stmt->bindValue(':envId', $envId);
            }
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        // Throw an exception if no data found
        if ($this->db->isempty($result) === true) {
            throw new Exception("Error: cannot find repo with specified Id");
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return latest snapshot Id from repo Id
     */
    public function getLatestSnapId(int $repoId) : int|null
    {
        $snapId = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos_snap WHERE Id_repo = :repoId AND Status = 'active' ORDER BY Date DESC LIMIT 1");
            $stmt->bindValue(':repoId', $repoId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $snapId = $row['Id'];
        }

        return $snapId;
    }

    /**
     *  Return environment Id from repo name
     */
    public function getEnvIdFromRepoName(string $name, string|null $dist, string|null $section, string $env)
    {
        $data = array();

        try {
            /**
             *  Case RPM
             */
            if (empty($dist) and empty($section)) {
                $stmt = $this->db->prepare("SELECT repos_env.Id
                FROM repos_env
                INNER JOIN repos_snap
                    ON repos_snap.Id = repos_env.Id_snap
                INNER JOIN repos
                    ON repos.Id = repos_snap.Id_repo
                WHERE repos.Name = :name
                AND (repos.Dist IS NULL OR repos.Dist = '')
                AND (repos.Section IS NULL OR repos.Section = '')
                AND repos_env.Env = :env");
            /**
             *  Case DEB (dist and section are specified)
             */
            } else {
                $stmt = $this->db->prepare("SELECT repos_env.Id
                FROM repos_env
                INNER JOIN repos_snap
                    ON repos_snap.Id = repos_env.Id_snap
                INNER JOIN repos
                    ON repos.Id = repos_snap.Id_repo
                WHERE repos.Name = :name
                AND repos.Dist = :dist
                AND repos.Section = :section
                AND repos_env.Env = :env");
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    public function getEnvIdBySnapId(string $snapId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos_env WHERE Id_snap = :snapId");
            $stmt->bindValue(':snapId', $snapId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        $envId = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $envId[] = $row['Id'];
        }

        return $envId;
    }

    /**
     *  Retourne tous les Id de repos
     */
    public function getAllRepoId()
    {
        try {
            $result = $this->db->query("SELECT Id FROM repos");
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        $id = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id[] = $row;
        }

        return $id;
    }

    /**
     *  Retourne les snapshots d'un repos
     */
    public function getSnapByRepoId(string $repoId, string $status = null)
    {
        try {
            /**
             *  Si un status a été spécifié
             */
            if (!empty($status)) {
                $stmt = $this->db->prepare("SELECT * FROM repos_snap WHERE Id_repo = :repoId AND Status = :status");
                $stmt->bindValue(':status', $status);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM repos_snap WHERE Id_repo = :repoId");
            }
            $stmt->bindValue(':repoId', $repoId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        $snapshots = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $snapshots[] = $row;
        }

        return $snapshots;
    }

    /**
     *  Get repository environment description by the repo name
     */
    public function getDescriptionByName(string $name, string $dist = null, string $section = null, string $env)
    {
        try {
            if (empty($dist) and empty($section)) {
                $stmt = $this->db->prepare("SELECT repos_env.Description FROM repos_env
                INNER JOIN repos_snap
                    ON repos_snap.Id = repos_env.Id_snap
                INNER JOIN repos
                    ON repos.Id = repos_snap.Id_repo
                WHERE repos.Name = :name
                AND (repos.Dist IS NULL OR repos.Dist = '')
                AND (repos.Section IS NULL OR repos.Section = '')
                AND repos_env.Env = :env
                AND repos_snap.Status = 'active'");
            } else {
                $stmt = $this->db->prepare("SELECT repos_env.Description FROM repos_env
                INNER JOIN repos_snap
                    ON repos_snap.Id = repos_env.Id_snap
                INNER JOIN repos
                    ON repos.Id = repos_snap.Id_repo
                WHERE repos.Name = :name
                AND repos.Dist = :dist
                AND repos.Section = :section
                AND repos_env.Env = :env
                AND repos_snap.Status = 'active'");
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        /**
         *  Si aucune description n'existe ou si aucun environnement n'existe alors on renvoie une description vide
         */
        if ($this->db->isempty($result) === true) {
            return '';
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $description = $row['Description'];
        }

        return $description;
    }

    /**
     *  Retourne la date d'un snapshot en base de données, à partir de son Id
     */
    public function getSnapDateById(string $snapId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Date FROM repos_snap WHERE Id = :snapId");
            $stmt->bindValue(':snapId', $snapId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $date = $row['Date'];
        }

        return $date;
    }

    /**
     *  Get unused repos Id (repos that have no active snapshot and so are not visible from web UI)
     */
    public function getUnused() : array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT
            repos.Id,
            repos.Name,
            repos.Releasever,
            repos.Dist,
            repos.Section
            FROM repos
            WHERE repos.Id NOT IN (
            SELECT DISTINCT repos.Id FROM repos
            LEFT JOIN repos_snap
            ON repos_snap.Id_repo = repos.Id
            LEFT JOIN repos_env
            ON repos_env.Id_snap = repos_snap.Id
            WHERE repos_snap.Status = 'active')");
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Set environment description
     */
    public function envSetDescription(string $envId, string $description) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_env SET Description = :description WHERE Id = :envId");
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':envId', $envId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Modification de l'état de signature GPG
     */
    public function snapSetSigned(string $snapId, string $signed)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Signed = :signed WHERE Id = :snapId");
            $stmt->bindValue(':signed', $signed);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set snapshot date
     */
    public function snapSetDate(string $snapId, string $date)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Date = :date WHERE Id = :snapId");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set snapshot time
     */
    public function snapSetTime(string $snapId, string $time)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Time = :time WHERE Id = :snapId");
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set snapshot metadata rebuild state
     */
    public function snapSetRebuild(string $snapId, string $status = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Reconstruct = :status WHERE Id = :snapId");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set snapshot architectures
     */
    public function snapSetArch(string $snapId, string $arch)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Arch = :arch WHERE Id = :snapId");
            $stmt->bindValue(':arch', $arch);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set packages included
     */
    public function snapSetPackagesIncluded(int $snapId, string $packages)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Pkg_included = :pkgIncluded WHERE Id = :snapId");
            $stmt->bindValue(':pkgIncluded', $packages);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set packages excluded
     */
    public function snapSetPackagesExcluded(int $snapId, string $packages)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Pkg_excluded = :pkgExcluded WHERE Id = :snapId");
            $stmt->bindValue(':pkgExcluded', $packages);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Return true if a repo Id exists in database
     */
    public function existsId(string $id) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if a snapshot Id exists in database
     */
    public function existsSnapId(string $id) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos_snap WHERE Id = :id AND Status = 'active'");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie que l'Id d'environnement existe en base de données
     */
    public function existsEnvId(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos_env WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie qu'un environnement de repo existe
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function existsEnv(string $name, string $dist = null, string $section = null, string $env)
    {
        try {
            if (empty($dist) and empty($section)) {
                $stmt = $this->db->prepare("SELECT repos.Id
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                INNER JOIN repos_env
                    ON repos_env.Id_snap = repos_snap.Id
                WHERE repos.Name = :name
                AND (repos.Dist IS NULL OR repos.Dist = '')
                AND (repos.Section IS NULL OR repos.Section = '')
                AND repos_env.Env = :env
                AND repos_snap.Status = 'active'");
            } else {
                $stmt = $this->db->prepare("SELECT repos.Id
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                INNER JOIN repos_env
                    ON repos_env.Id_snap = repos_snap.Id
                WHERE repos.Name = :name
                AND repos.Dist = :dist
                AND repos.Section = :section
                AND repos_env.Env = :env
                AND repos_snap.Status = 'active'");
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if a snapshot exists at a specific date in database, from the repo name and the date
     */
    public function existsRepoSnapDate(string $date, string $name, string|null $dist, string|null $section)
    {
        try {
            if (empty($dist) and empty($section)) {
                $stmt = $this->db->prepare("SELECT repos_snap.Id
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos.Name = :name
                AND (repos.Dist IS NULL OR repos.Dist = '')
                AND (repos.Section IS NULL OR repos.Section = '')   
                AND repos_snap.Date = :date
                AND repos_snap.Status = 'active'");
            } else {
                $stmt = $this->db->prepare("SELECT repos_snap.Id
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos.Name = :name
                AND repos.Dist = :dist
                AND repos.Section = :section            
                AND repos_snap.Date = :date
                AND repos_snap.Status = 'active'");
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':date', $date);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if env exists, based on its name and the snapshot Id it points to
     */
    public function existsSnapIdEnv(string $snapId, string $env)
    {
        try {
            $stmt = $this->db->prepare("SELECT repos_snap.Id
            FROM repos_snap
            INNER JOIN repos_env
                ON repos_env.Id_snap = repos_snap.Id
            WHERE repos_snap.Id = :snapId
            AND repos_env.Env = :env");
            $stmt->bindValue(':snapId', $snapId);
            $stmt->bindValue(':env', $env);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie si un repo existe et est actif (contient des snapshots actifs)
     */
    public function isActive(string $name, string $dist = null, string $section = null)
    {
        try {
            if (empty($dist) and empty($section)) {
                $stmt = $this->db->prepare("SELECT repos.Id
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos.Name = :name
                AND (repos.Dist IS NULL OR repos.Dist = '')
                AND (repos.Section IS NULL OR repos.Section = '')
                AND repos_snap.Status = 'active'");
            } else {
                $stmt = $this->db->prepare("SELECT repos.Id
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos.Name = :name
                AND repos.Dist = :dist
                AND repos.Section = :section
                AND repos_snap.Status = 'active'");
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        /**
         *  Si le résultat est vide alors le repo n'existe pas où alors il ne contient aucun snapshot actif
         */
        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Retourne le nombre total de repos
     */
    public function count()
    {
        try {
            $result = $this->db->query("SELECT DISTINCT
            repos.Name,
            repos.Releasever,
            repos.Dist,
            repos.Section
            FROM repos 
            LEFT JOIN repos_snap
                ON repos.Id = repos_snap.Id_repo
            WHERE repos_snap.Status = 'active'");
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        return $this->db->count($result);
    }

    /**
     *  Add a repo snapshot in database
     */
    public function addSnap(string $date, string $time, string $gpgSignature, array $arch, array $includeTranslation, array $packagesIncluded, array $packagesExcluded, string $type, string $status, string $repoId)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos_snap ('Date', 'Time', 'Signed', 'Arch', 'Pkg_translation', 'Pkg_included', 'Pkg_excluded', 'Type', 'Status', 'Id_repo') VALUES (:date, :time, :signed, :arch, :includeTranslation, :packagesIncluded, :packagesExcluded, :type, :status, :repoId)");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':signed', $gpgSignature);
            $stmt->bindValue(':arch', implode(',', $arch));
            $stmt->bindValue(':includeTranslation', implode(',', $includeTranslation));
            $stmt->bindValue(':packagesIncluded', implode(',', $packagesIncluded));
            $stmt->bindValue(':packagesExcluded', implode(',', $packagesExcluded));
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Ajout du repo au groupe spécifié en base de données
     */
    public function addToGroup(string $repoId, string $groupId)
    {
        /**
         *  On vérifie d'abord que le repo n'est pas déjà membre du groupe
         *  Le raffraichissement du <select> peut provoquer deux fois l'ajout du repo dans le groupe, donc on fait cette vérification pour palier à ce bug
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM group_members WHERE Id_repo = :repoId AND Id_group = :groupId");
            $stmt->bindValue(':repoId', $repoId);
            $stmt->bindValue(':groupId', $groupId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        /**
         *  Si le repo est déjà présent on ne fait rien
         */
        if ($this->db->isempty($result) === false) {
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO group_members (Id_repo, Id_group) VALUES (:id_repo, :id_group)");
            $stmt->bindValue(':id_repo', $repoId);
            $stmt->bindValue(':id_group', $groupId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Retrait d'un repo du groupe spécifié
     */
    public function removeFromGroup(string $repoId, string $groupId = null)
    {
        try {
            /**
             *  Si on a précisé l'Id du groupe
             */
            if (!empty($groupId)) {
                $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_repo = :repoId AND Id_group = :groupId");
                $stmt->bindValue(':groupId', $groupId);
            } else {
                $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_repo = :repoId");
            }
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Remove an env in database
     */
    public function removeEnv(string $envId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM repos_env WHERE Id = :envId");
            $stmt->bindValue(':envId', $envId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        unset($stmt);
    }

    /**
     *  Update release version in database
     */
    public function updateReleasever(int $repoId, string $releasever)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos SET Releasever = :releasever WHERE Id = :repoId");
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        unset($stmt);
    }

    /**
     *  Update dist in database
     */
    public function updateDist(int $repoId, string $dist)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos SET Dist = :dist WHERE Id = :repoId");
            $stmt->bindValue(':dist', $dist);
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        unset($stmt);
    }

    /**
     *  Update section in database
     */
    public function updateSection(int $repoId, string $section)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos SET Section = :section WHERE Id = :repoId");
            $stmt->bindValue(':section', $section);
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        unset($stmt);
    }

    /**
     *  Update source repository in database
     */
    public function updateSource(int $repoId, string $source)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos SET Source = :source WHERE Id = :repoId");
            $stmt->bindValue(':source', $source);
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        unset($stmt);
    }
}
