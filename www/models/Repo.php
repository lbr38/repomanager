<?php

namespace Models;

use DateTime;
use Exception;

class Repo extends Model
{
    public function __construct(array $variables = [])
    {

        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
    }

    /**
     *  Remplace la fonction commentée ci-dessus
     */
    public function getAllById(string $repoId = null, string $snapId = null, string $envId = null)
    {
        try {
            if (!empty($repoId) and !empty($snapId) and !empty($envId)) {
                $stmt = $this->db->prepare("SELECT
                repos.Id AS repoId,
                repos.Name,
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type,
                repos_snap.Id AS snapId,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
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
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type,
                repos_snap.Id AS snapId,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
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
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type,
                repos_snap.Id AS snapId,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
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
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type,
                repos_snap.Id AS snapId,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
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
            \Controllers\Common::dbError($e);
        }

        /**
         *  Si rien n'a été trouvé en BDD avec l'ID fourni alors on quitte
         */
        if ($this->db->isempty($result) === true) {
            throw new Exception("Erreur : impossible de trouver le repo correspondant aux Id spécifiés");
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        /**
         *  Retourne un array contenant toutes les données trouvées concernant le repo / snapshot / env
         */
        return $data;
    }

    /**
     *  Retourne l'Id du repo en base de données à partir de son nom
     */
    public function getIdByName(string $name, string $dist = null, string $section = null)
    {
        try {
            /**
             *  Cas où on a seulement spécifié le nom du repo
             */
            if (empty($dist) or empty($section)) {
                $stmt = $this->db->prepare("SELECT Id from repos WHERE Name = :name AND Dist IS NULL AND Section IS NULL");

            /**
             *  Cas où la distribution et la section ont été spécifié
             */
            } else {
                $stmt = $this->db->prepare("SELECT Id from repos WHERE Name = :name and Dist = :dist and Section = :section");
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    public function getEnvIdFromRepoName(string $name, string $dist = null, string $section = null, string $env)
    {
        try {
            if (empty($dist) and empty($section)) {
                $stmt = $this->db->prepare("SELECT repos_env.Id
                FROM repos_env
                INNER JOIN repos_snap
                    ON repos_snap.Id = repos_env.Id_snap
                INNER JOIN repos
                    ON repos.Id = repos_snap.Id_repo
                WHERE repos.Name = :name
                AND repos_env.Env = :env");
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
            \Controllers\Common::dbError($e);
        }

        $id = '';

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    public function getEnvIdBySnapId(string $snapId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos_env WHERE Id_snap = :snapId");
            $stmt->bindValue(':snapId', $snapId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
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
            \Controllers\Common::dbError($e);
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
            \Controllers\Common::dbError($e);
        }

        $snapshots = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $snapshots[] = $row;
        }

        return $snapshots;
    }

    /**
     *  Retourne l'Id du snapshot le + récent du repo
     */
    public function getLastSnapshotId(string $repoId)
    {
        try {
            $stmt = $this->db->prepare("SELECT
            repos_snap.Id AS snapId,
            repos_snap.Date
            FROM repos_snap
            INNER JOIN repos
                ON repos.Id = repos_snap.Id_repo
            WHERE repos.Id = :repoId
            AND repos_snap.Status = 'active'
            ORDER BY repos_snap.Date DESC
            LIMIT 1;");
            $stmt->bindValue(':repoId', $repoId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $id = '';

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['snapId'];
        }

        return $id;
    }

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
                AND repos_env.Env = :env");
            } else {
                $stmt = $this->db->prepare("SELECT repos_env.Description FROM repos_env
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
            \Controllers\Common::dbError($e);
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
     *  Retourne les repos membres d'un groupe à partir de son Id
     */
    public function getReposGroupMembers(string $groupId)
    {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT
            repos.Id AS repoId,
            repos.Name,
            repos.Dist,
            repos.Section,
            repos.Source,
            repos.Package_type
            FROM group_members 
            INNER JOIN repos
                ON repos.Id = group_members.Id_repo
            INNER JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            WHERE repos_snap.Status = 'active' 
            AND Id_group = :idgroup");
            $stmt->bindValue(':idgroup', $groupId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $row;
        }

        return $repos;
    }

    /**
     *  Retourne les repos qui ne sont membres d'aucun groupe
     */
    public function getReposNotMembersOfAnyGroup()
    {
        $result = $this->db->query("SELECT DISTINCT
        repos.Id AS repoId,
        repos.Name,
        repos.Dist,
        repos.Section,
        repos.Source,
        repos.Package_type
        FROM repos
        INNER JOIN repos_snap
            ON repos_snap.Id_repo = repos.Id
        WHERE repos_snap.Status = 'active' AND repos.Id NOT IN (SELECT Id_repo FROM group_members)");

        $repos = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $row;
        }

        return $repos;
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
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $date = $row['Date'];
        }

        return $date;
    }

    /**
     *  Récupère l'url source complete avec la racine du dépot (Debian uniquement)
     */
    public function getFullSource(string $sourceName)
    {
        $fullUrl = '';

        /**
         *  Récupère l'url complète
         */
        try {
            $stmt = $this->db->prepare("SELECT Url FROM sources WHERE Name = :name");
            $stmt->bindValue(':name', $sourceName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $fullUrl = $row['Url'];
        }

        return $fullUrl;
    }

    /**
     *  Liste les snapshots de repos inutilisés en fonction de l'Id de repo et du paramètre de retention spécifié
     */
    public function getUnunsedSnapshot(string $repoId, string $retention)
    {
        try {
            $stmt = $this->db->prepare("SELECT
            repos_snap.Id AS snapId,
            repos_snap.Date
            FROM repos
            LEFT JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            LEFT JOIN repos_env
                ON repos_env.Id_snap = repos_snap.Id
            WHERE repos_snap.Id_repo = :repoId
            AND repos_env.Id_snap IS NULL
            AND repos_snap.Status = 'active'
            ORDER BY Date DESC LIMIT -1 OFFSET :retention");
            $stmt->bindValue(':repoId', $repoId);
            $stmt->bindValue(':retention', $retention);

            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $data = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Modification de la description
    */
    public function envSetDescription(string $envId, string $description)
    {
        /**
         *  Vérification des caractères de la description
         */
        if (\Controllers\Common::isAlphanumDash($description, array(' ', '(', ')', '@', ',', '.', '\'', 'é', 'è', 'ê', 'à', 'ç', 'ù', 'ô', 'ï', '"')) === false) {
            throw new Exception("La description contient des caractères invalides");
        }

        try {
            $stmt = $this->db->prepare("UPDATE repos_env SET Description = :description WHERE Id = :envId");
            $stmt->bindValue(':description', \Controllers\Common::validateData($description));
            $stmt->bindValue(':envId', $envId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);

        \Controllers\Common::clearCache();
    }

    /**
     *  Modification de l'état de signature GPG
     */
    public function snapSetSigned(string $snapId, string $signed)
    {
        /**
         *  signed peut être égal à 'yes' ou 'no'
         */
        if ($signed != "yes" and $signed != "no") {
            return;
        }

        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Signed = :signed WHERE Id = :snapId");
            $stmt->bindValue(':signed', $signed);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
    }

    /**
     *  Modification de la date d'un snapshot en base de données
     */
    public function snapSetDate(string $snapId, string $date)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Date = :date WHERE Id = :snapId");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
    }

    /**
     *  Modification de l'heure d'un snapshot en base de données
     */
    public function snapSetTime(string $snapId, string $time)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Time = :time WHERE Id = :snapId");
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
    }

    /**
     *  Modification de l'état de reconstruction des métadonnées du snapshot
     */
    public function snapSetReconstruct(string $snapId, string $status = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Reconstruct = :status WHERE Id = :snapId");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
    }

    /**
     *  Retourne true si une opération est en cours sur l'Id de snapshot spécifié
     */
    public function snapOpIsRunning(string $snapId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM operations WHERE (Id_snap_source = :snapId OR Id_snap_target = :snapId) AND Status = 'running'");
            $stmt->bindValue(':snapId', $snapId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Modification de l'état du snapshot
     */
    public function snapSetStatus(string $snapId, string $status)
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Status = :status WHERE Id = :snapId");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
    }

    /**
     *  Retourne true si l'Id de repo existe en base de données
     */
    public function existsId(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie que l'Id du snapshot existe en base de données
     */
    public function existsSnapId(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos_snap WHERE Id = :id AND Status = 'active'");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
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
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie que le repo existe à partir de son nom
     *  Retourne true si existe
     *  Retourne false si n'existe pas
     */
    public function exists(string $name, string $dist = '', string $section = '')
    {
        try {
            if (!empty($dist) and !empty($section)) {
                $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name = :name AND Dist = :dist AND Section = :section");
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM repos WHERE Name = :name AND Dist IS NULL AND Section IS NULL");
            }
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
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
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie si un snapshot de repo existe à une date spécifique en base de données, à partir du nom du repo et de la date recherchée
     */
    public function existsRepoSnapDate(string $date, string $name, string $dist = null, string $section = null)
    {
        try {
            if (empty($dist) and empty($section)) {
                $stmt = $this->db->prepare("SELECT repos_snap.Id
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos.Name = :name           
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
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie si un environnement existe à partir de son nom et de l'Id de snapshot vers lequel il pointe
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
            \Controllers\Common::dbError($e);
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
                AND repos.Dist IS NULL
                AND repos.Section IS NULL
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
            \Controllers\Common::dbError($e);
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
     *  Retourne la liste des repos actifs, càd ayant au moins 1 snapshot actif, et leur environnement si il y en a
     */
    public function list()
    {
        try {
            $result = $this->db->query("SELECT
            repos.Id AS repoId,
            repos_snap.Id AS snapId,
            repos_env.Id AS envId,
            repos.Name,
            repos.Dist,
            repos.Section,
            repos.Source,
            repos.Package_type,
            repos_env.Env,
            repos_snap.Date,
            repos_snap.Time,
            repos_snap.Signed,
            repos_snap.Type,
            repos_env.Description
            FROM repos 
            LEFT JOIN repos_snap
                ON repos.Id = repos_snap.Id_repo
            LEFT JOIN repos_env 
                ON repos_snap.Id = repos_env.Id_snap
            WHERE repos_snap.Status = 'active'
            ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_env.Env ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $datas;
        }

        return $repos;
    }

    /**
     *  Retourne la liste des repos, leurs snapshots et leur environnements
     *  N'affiche pas les repos qui n'ont aucun environnement actif
     */
    public function listWithEnv()
    {
        try {
            $result = $this->db->query("SELECT
            repos.Id AS repoId,
            repos_snap.Id AS snapId,
            repos_env.Id AS envId,
            repos.Name,
            repos.Dist,
            repos.Section,
            repos.Source,
            repos.Package_type,
            repos_env.Env,
            repos_snap.Date,
            repos_snap.Time,
            repos_snap.Signed,
            repos_snap.Type,
            repos_env.Description
            FROM repos 
            INNER JOIN repos_snap
                ON repos.Id = repos_snap.Id_repo
            INNER JOIN repos_env 
                ON repos_snap.Id = repos_env.Id_snap
            WHERE repos_snap.Status = 'active'
            ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_env.Env ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $datas;
        }

        return $repos;
    }

    /**
     *  Retourne la liste des repos éligibles aux planifications
     *  Il s'agit des repos ayant au moins 1 snapshot actif
     */
    public function listForPlan()
    {
        try {
            $result = $this->db->query("SELECT
            repos.Id AS repoId,
            repos_snap.Id AS snapId,
            repos.Name,
            repos.Dist,
            repos.Section,
            repos.Source,
            repos.Package_type,
            repos_snap.Date,
            repos_snap.Time,
            repos_snap.Signed,
            repos_snap.Type
            FROM repos
            LEFT JOIN repos_snap
                ON repos.Id = repos_snap.Id_repo
            WHERE repos_snap.Status = 'active'
            AND repos_snap.Type = 'mirror'
            ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $datas;
        }

        return $repos;
    }

    /**
     *  Retourne un array de tous les noms de repos, sans informations des snapshots et environnements associés
     *  Si le paramètre 'true' est passé alors la fonction renverra uniquement les noms des repos qui ont un snapshot actif rattaché
     *  Si le paramètre 'false' est passé alors la fonction renverra tous les noms de repos avec ou sans snapshot rattaché
     */
    public function listNameOnly(bool $bool)
    {
        try {
            if ($bool == false) {
                $result = $this->db->query("SELECT DISTINCT *
                FROM repos
                ORDER BY Name ASC, Dist ASC, Section ASC");
            }

            if ($bool == true) {
                $result = $this->db->query("SELECT DISTINCT
                repos.Id,
                repos.Name,
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
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $datas;
        }

        return $repos;
    }

    /**
     *  Retourne la liste des repos actifs, par groupe
     */
    public function listByGroup(string $groupName)
    {
        /**
         *  Si le groupe == 'Default' (groupe fictif) alors on affiche tous les repos n'ayant pas de groupe
         */
        try {
            if ($groupName == 'Default') {
                $reposInGroup = $this->db->query("SELECT DISTINCT repos.Id AS repoId, repos_snap.Id AS snapId, repos_env.Id AS envId, repos.Name, repos.Dist, repos.Section, repos.Source, repos.Package_type, repos_env.Env, repos_snap.Date, repos_snap.Time, repos_snap.Signed, repos_snap.Type, repos_snap.Reconstruct, repos_snap.Status, repos_env.Description
                FROM repos 
                LEFT JOIN repos_snap
                    ON repos.Id = repos_snap.Id_repo
                LEFT JOIN repos_env 
                    ON repos_env.Id_snap = repos_snap.Id
                WHERE repos_snap.Status = 'active' AND repos.Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_snap.Date DESC");
            } else {
                $stmt = $this->db->prepare("SELECT DISTINCT repos.Id AS repoId, repos_snap.Id AS snapId, repos_env.Id AS envId, repos.Name, repos.Dist, repos.Section, repos.Source, repos.Package_type, repos_env.Env, repos_snap.Date, repos_snap.Time, repos_snap.Signed, repos_snap.Type, repos_snap.Reconstruct, repos_snap.Status, repos_env.Description
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
                $reposInGroup = $stmt->execute();
            }
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $reposIn = array();

        while ($datas = $reposInGroup->fetchArray(SQLITE3_ASSOC)) {
            $reposIn[] = $datas;
        }

        return $reposIn;
    }

    /**
     *  Retourne le liste des noms de repos actifs, par groupe
     *  Utilisée notamment pour les planifications de groupes
     */
    public function listNameByGroup(string $groupName)
    {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT
            repos.Id AS repoId,
            repos.Name,
            repos.Dist,
            repos.Section
            FROM repos
            LEFT JOIN group_members
                ON repos.Id = group_members.Id_repo
            LEFT JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name = :groupname
            ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC");
            $stmt->bindValue(':groupname', $groupName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $row;
        }

        return $repos;
    }

    /**
     *  Retourne le nombre total de repos
     */
    public function count()
    {
        try {
            $result = $this->db->query("SELECT DISTINCT
            repos.Name,
            repos.Dist,
            repos.Section
            FROM repos 
            LEFT JOIN repos_snap
                ON repos.Id = repos_snap.Id_repo
            WHERE repos_snap.Status = 'active'");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        return $this->db->count($result);
    }

    /**
     *  Ajouter un nouveau nom de repo à la table repos
     */
    public function add(string $source, string $packageType, string $name, string $dist = null, string $section = null)
    {
        try {
            /**
             *  Cas où seul le nom a été renseigné
             */
            if (empty($dist) or empty($section)) {
                $stmt = $this->db->prepare("INSERT INTO repos ('Name', 'Source', 'Package_type') VALUES (:name, :source, :packageType)");

            /**
             *  Cas où une distribution et une section ont été renseignés
             */
            } else {
                $stmt = $this->db->prepare("INSERT INTO repos ('Name', 'Dist', 'Section', 'Source', 'Package_type') VALUES (:name, :dist, :section, :source, :packageType)");
                $stmt->bindValue(':dist', $dist);
                $stmt->bindValue(':section', $section);
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':source', $source);
            $stmt->bindValue(':packageType', $packageType);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
    }

    /**
     * Ajout d'un nouveau snapshot de repo en base de données, lié à un Id de repo
     */
    public function addSnap(string $date, string $time, string $gpgSignature, string $type, string $status, string $repoId)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos_snap ('Date', 'Time', 'Signed', 'Type', 'Status', 'Id_repo') VALUES (:date, :time, :signed, :type, :status, :repoId)");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':signed', $gpgSignature);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
    }

    /**
     *  Déclaration d'un nouvel environnement en base de données, associé à un Id de snapshot
     */
    public function addEnv(string $env, string $description, string $snapId)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos_env ('Env', 'Description', 'Id_snap') VALUES (:env, :description, :snapId)");
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
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
            \Controllers\Common::dbError($e);
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
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
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
            \Controllers\Common::dbError($e);
        }

        \Controllers\Common::clearCache();
    }

    /**
     *  Suppression d'un environnement en base de données
     */
    public function deleteEnv(string $envId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM repos_env WHERE Id = :envId");
            $stmt->bindValue(':envId', $envId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);

        \Controllers\Common::clearCache();
    }
}
