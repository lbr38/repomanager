<?php

namespace Models;

use Exception;

class Profile extends Model
{
    public function __construct()
    {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
    }

    /**
     *  Retourne l'Id du profil en base de données, à partir de son nom
     */
    public function getIdByName(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM profile WHERE Name = :name");
            $stmt->bindValue(':name', $name);
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

    /**
     *  Retourne la liste des paquets dans la table profile_package
     */
    public function getPackages()
    {
        try {
            $result = $this->db->query("SELECT Name FROM profile_package");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $packages = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $packages[] = $datas['Name'];
        }

        return $packages;
    }

    /**
     *  Retourne la liste des services dans la table profile_service
     */
    public function getServices()
    {
        try {
            $result = $this->db->query("SELECT Name FROM profile_service");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $services = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $services[] = $datas['Name'];
        }

        return $services;
    }

    /**
     *  Retourne les informations d'un profil en base données
     */
    public function getProfileConfiguration(string $profileId)
    {
        try {
            $stmt = $this->db->prepare("SELECT
            Package_exclude,
            Package_exclude_major,
            Service_restart,
            Allow_overwrite,
            Allow_repos_overwrite,
            Notes
            FROM profile WHERE Id = :profileId");
            $stmt->bindValue(':profileId', $profileId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $profile = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $profile = $row;
        }

        return $profile;
    }

    /**
     *  Retourne la configuration générale du serveur pour la gestion des profils
     */
    public function getServerConfiguration()
    {
        try {
            $result = $this->db->query("SELECT * FROM profile_settings");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Une première partie de la configuration concerne l'adresse IP et l'url du serveur qu'on peut obtenir à partir de constantes
         */
        $settings = array('Ip' => __SERVER_IP__, 'Url' => __SERVER_URL__);

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $profileSettings = $row;
        }

        $settings = array_merge($settings, $profileSettings);


        return $settings;
    }

    /**
     *  Modifie la configuration générale du serveur pour la gestion des profils
     */
    public function setServerConfiguration(string $serverPackageType, string $serverManageClientConf, string $serverManageClientRepos)
    {
        try {
            $stmt = $this->db->prepare("UPDATE profile_settings SET Package_type = :packageType, Manage_client_conf = :manageClientConf, Manage_client_repos = :manageClientRepos");
            $stmt->bindValue(':packageType', $serverPackageType);
            $stmt->bindValue(':manageClientConf', $serverManageClientConf);
            $stmt->bindValue(':manageClientRepos', $serverManageClientRepos);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne true si un profil existe en base de données
     */
    public function exists(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM profile WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Si le résultat obtenu est vide alors le profil n'existe pas
         */
        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Ajout d'un nouveau profil en base de données
     */
    public function add(string $name)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO profile (Name) VALUES (:name)");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Renommage d'un profil en base de données
     */
    public function rename(string $name, string $newName)
    {
        try {
            $stmt = $this->db->prepare("UPDATE profile SET Name = :newName WHERE Name = :name");
            $stmt->bindValue(':newName', $newName);
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Modification de la configuration d'un profil en base de données
     */
    public function configure(string $profileId, string $packageExclude, string $packageExcludeMajor, string $serviceRestart, string $allowOverwrite, string $allowReposOverwrite, string $notes)
    {
        try {
            $stmt = $this->db->prepare("UPDATE profile SET Package_exclude = :packageExclude, Package_exclude_major = :packageExcludeMajor, Service_restart = :serviceRestart, Allow_overwrite = :allowOverwrite, Allow_repos_overwrite = :allowReposOverwrite, Notes = :notes WHERE Id = :profileId");
            $stmt->bindValue(':profileId', $profileId);
            $stmt->bindValue(':packageExclude', $packageExclude);
            $stmt->bindValue(':packageExcludeMajor', $packageExcludeMajor);
            $stmt->bindValue(':serviceRestart', $serviceRestart);
            $stmt->bindValue(':allowOverwrite', $allowOverwrite);
            $stmt->bindValue(':allowReposOverwrite', $allowReposOverwrite);
            $stmt->bindValue(':notes', $notes);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Supprime un profil en base de données
     */
    public function delete(string $name)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM profile WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne la liste des profils en base de données
     */
    public function list()
    {
        try {
            $result = $this->db->query("SELECT * FROM profile ORDER BY Name ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $profiles = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $profiles[] = $row;
        }

        return $profiles;
    }

    /**
     *  Retourne un array contenant les noms de repos membres d'un profil
     *  Ici on vérifie bien que les repos membres ont au moins 1 snapshot actif (repos.Status == 'active'), si ce
     *  n'est pas le cas alors le repos n'est pas considéré comme membre du profil
     */
    public function reposMembersList($profileId)
    {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT
            repos.Id,
            repos.Name,
            repos.Dist,
            repos.Section,
            repos.Package_type
            FROM profile_repo_members 
            LEFT JOIN repos
                ON repos.Id = profile_repo_members.Id_repo
            LEFT JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            WHERE profile_repo_members.Id_profile = :profileId
            AND repos_snap.Status == 'active'");
            $stmt->bindValue(':profileId', $profileId);
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
     *  Retourne un array contenant les Id de repos membres d'un profil
     *  Ici on vérifie bien que les repos membres ont au moins 1 snapshot actif (repos.Status == 'active'), si ce
     *  n'est pas le cas alors le repos n'est pas considéré comme membre du profil
     */
    public function reposMembersIdList($profileId)
    {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT
            repos.Id
            FROM profile_repo_members 
            LEFT JOIN repos
                ON repos.Id = profile_repo_members.Id_repo
            LEFT JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            WHERE profile_repo_members.Id_profile = :profileId
            AND repos_snap.Status == 'active'");
            $stmt->bindValue(':profileId', $profileId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            array_push($repos, $row['Id']);
        }

        /**
         *  L'array retourné est au format array('Id', 'Id', 'Id'...)
         */
        return $repos;
    }

    /**
     *  Vérifier qu'un nom de service est présent dans la table profile_service
     */
    public function serviceExists(string $service)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM profile_service WHERE Name=:name");
            $stmt->bindValue(':name', $service);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Si le résultat obtenu est vide alors le service n'existe pas, on renvoie false
         */
        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Ajout d'un nouveau nom de paquet dans la table profile_package
     */
    public function addPackage(string $packageName)
    {
        /**
         *  D'abord on vérifie que le paquet n'est pas déjà présent en base de données
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM profile_package WHERE Name = :name");
            $stmt->bindValue(':name', $packageName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Si le paquet est déjà présent dans la table profile_package alors on ne fait rien
         */
        if ($this->db->isempty($result) === false) {
            return;
        }

        /**
         *  Ajout du paquet en base de données
         */
        try {
            $stmt = $this->db->prepare("INSERT INTO profile_package (Name) VALUES (:name)");
            $stmt->bindValue(':name', $packageName);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        return true;
    }

    /**
     *  Ajout d'un nouveau nom de service dans la table profile_service
     */
    public function addService(string $serviceName)
    {
        /**
         *  D'abord on vérifie que le service n'est pas déjà présent en base de données
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM profile_service WHERE Name = :name");
            $stmt->bindValue(':name', $serviceName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Si le service est déjà présent dans la table profile_service alors on ne fait rien
         */
        if ($this->db->isempty($result) === false) {
            return;
        }

        /**
         *  Ajout du service en base de données
         */
        try {
            $stmt = $this->db->prepare("INSERT INTO profile_service (Name) VALUES (:name)");
            $stmt->bindValue(':name', $serviceName);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        return true;
    }

    /**
     *  Retire tous les repos membres d'un profil dans la table profile_repo_members (généralement avant d'en ajouter de nouveaux)
     */
    public function cleanProfileRepoMembers(string $profileId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM profile_repo_members WHERE Id_profile = :profileId");
            $stmt->bindValue(':profileId', $profileId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Ajouter un repo membre à un profil
     */
    public function addRepoToProfile(string $profileId, string $repoId)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO profile_repo_members (Id_profile, Id_repo) VALUES (:profileId, :repoId)");
            $stmt->bindValue(':profileId', $profileId);
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne le nombre d'hôtes utilisant le profil spécifié en base de données
     */
    public function countHosts(string $profile)
    {
        $myhost = new \Controllers\Host();

        $hosts = array();

        try {
            $stmt = $myhost->db->prepare("SELECT Id FROM hosts WHERE Profile = :profile");
            $stmt->bindValue(':profile', $profile);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        $myhost->db->close();

        return count($hosts);
    }

    /**
     *  Remove repo Id from profile members
     */
    public function removeRepoMemberId(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM profile_repo_members WHERE Id_repo = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }
}
