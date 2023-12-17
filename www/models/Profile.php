<?php

namespace Models;

use Exception;

class Profile extends Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return profile Id by name
     */
    public function getIdByName(string $name)
    {
        $id = '';

        try {
            $stmt = $this->db->prepare("SELECT Id FROM profile WHERE Name = :name");
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

    /**
     *  Return profile name by Id
     */
    public function getNameById(int $id)
    {
        $name = '';

        try {
            $stmt = $this->db->prepare("SELECT Name FROM profile WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $name = $row['Name'];
        }

        return $name;
    }

    /**
     *  Return a list of all packages in profile_package table
     */
    public function getPackages()
    {
        $packages = array();

        try {
            $result = $this->db->query("SELECT Name FROM profile_package ORDER BY Name ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $packages[] = $datas['Name'];
        }

        return $packages;
    }

    /**
     *  Return a list of all services in profile_service table
     */
    public function getServices()
    {
        $data = array();

        try {
            $result = $this->db->query("SELECT Name FROM profile_service ORDER BY Name ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row['Name'];
        }

        return $data;
    }

    /**
     *  Get profile full configuration from database
     */
    public function getProfileFullConfiguration(string $profileId)
    {
        $profile = array();

        try {
            $stmt = $this->db->prepare("SELECT
            Package_exclude,
            Package_exclude_major,
            Service_restart,
            Notes
            FROM profile WHERE Id = :profileId");
            $stmt->bindValue(':profileId', $profileId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $profile = $row;
        }

        return $profile;
    }

    /**
     *  Return server configuration for profiles management
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
     *  Return true if profile exists in database
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
         *  If result is empty then profile does not exist
         */
        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if profile Id exists in database
     */
    public function existsId(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM profile WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  If result is empty then profile does not exist
         */
        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Create new profile in database
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
     *  Configure profile
     */
    public function configure(int $id, string $name, string $packageExclude = null, string $packageExcludeMajor = null, string $serviceRestart = null, string $notes = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE profile SET Name = :name, Package_exclude = :packageExclude, Package_exclude_major = :packageExcludeMajor, Service_restart = :serviceRestart, Notes = :notes WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':packageExclude', $packageExclude);
            $stmt->bindValue(':packageExcludeMajor', $packageExcludeMajor);
            $stmt->bindValue(':serviceRestart', $serviceRestart);
            $stmt->bindValue(':notes', $notes);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Delete a profile
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM profile WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Return a list of all profiles names
     */
    public function listName()
    {
        try {
            $result = $this->db->query("SELECT Name FROM profile ORDER BY Name ASC");
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
     *  Return a list of all profiles
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
     *  Return an array containing repos names members of a profile
     *  Check that repos members have at least 1 active snapshot (repos.Status == 'active'), if not then the repo is not considered as a member of the profile
     */
    public function getReposMembersList($profileId)
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
     *  Return an array containing repos Id members of a profile
     *  Check that repos members have at least 1 active snapshot (repos.Status == 'active'), if not then the repo is not considered as a member of the profile
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
         *  Returned array is like array('Id', 'Id', 'Id'...)
         */
        return $repos;
    }

    /**
     *  Add package to profile_package table if it does not already exist
     */
    public function addPackage(string $packageName)
    {
        /**
         *  Check if package is already present in profile_package table
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM profile_package WHERE Name = :name");
            $stmt->bindValue(':name', $packageName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  If package is already present in profile_package table then do nothing
         */
        if ($this->db->isempty($result) === false) {
            return;
        }

        /**
         *  Add package to profile_package table
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
     *  Add a new service name in profile_service table
     */
    public function addService(string $serviceName)
    {
        /**
         *  Check if service is already present in profile_service table
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM profile_service WHERE Name = :name");
            $stmt->bindValue(':name', $serviceName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  If service is already present in profile_service table then do nothing
         */
        if ($this->db->isempty($result) === false) {
            return;
        }

        /**
         *  Add service to profile_service table
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
     *  Delete all repos members of a profile in profile_repo_members table (usually before adding new ones)
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
     *  Add a repo member to a profile
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
     *  Return the number of hosts using the specified profile
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
