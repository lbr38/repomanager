<?php

namespace Models;

use Exception;
use Datetime;

class Host extends Model
{
    protected $host_db; // BDD dédiée à l'hôte

    public function __construct()
    {
        /**
         *  Ouverture de la base de données 'hosts' (repomanager-hosts.db)
         */
        $this->getConnection('hosts');
    }

    /**
     *  Ouverture de la BDD dédiée de l'hôte si ce n'est pas déjà fait
     *  Fournir l'id de l'hôte et le mode d'ouverture de la base (ro = lecture seule / rw = lecture-écriture)
     */
    public function openHostDb(string $hostId)
    {
        $this->getConnection('host', $hostId);
    }

    /**
     *  Fermeture de la BDD dédiée de l'hôte
     */
    public function closeHostDb()
    {
        $this->host_db->close();
    }

    /**
     *  Retourne la liste de tous les hôtes d'un groupe
     */
    public function listByGroup(string $groupName)
    {
        /**
         *  Si le nom du groupe est 'Default' (groupe fictif) alors on affiche tous les hotes n'ayant pas de groupe
         */
        try {
            if ($groupName == 'Default') {
                $hostsInGroup = $this->db->query("SELECT *
                FROM hosts
                WHERE Id NOT IN (SELECT Id_host FROM group_members)
                AND Status = 'active'
                ORDER BY hosts.Hostname ASC");
            } else {
                // Note : ne pas utiliser SELECT *, comme il s'agit d'une jointure il faut bien préciser les données souhaitées
                $stmt = $this->db->prepare("SELECT
                hosts.Id,
                hosts.Ip,
                hosts.Hostname,
                hosts.Os,
                hosts.Os_version,
                hosts.Os_family,
                hosts.Type,
                hosts.Kernel,
                hosts.Arch,
                hosts.Profile,
                hosts.Env,
                hosts.Online_status,
                hosts.Online_status_date,
                hosts.Online_status_time,
                hosts.Status
                FROM hosts
                INNER JOIN group_members
                    ON hosts.Id = group_members.Id_host
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name=:groupname
                and hosts.Status = 'active'
                ORDER BY hosts.Hostname ASC");
                $stmt->bindValue(':groupname', $groupName);
                $hostsInGroup = $stmt->execute();
                unset($stmt);
            }
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $hostsIn = array();

        while ($datas = $hostsInGroup->fetchArray(SQLITE3_ASSOC)) {
            $hostsIn[] = $datas;
        }

        return $hostsIn;
    }

    /**
     *  Copie l'état actuel d'un paquet de la table packages vers la table packages_history afin de conserver une trace de cet état
     */
    public function setPackageHistory(string $packageName, string $packageVersion, string $packageState, string $packageType, string $packageDate, string $packageTime, string $eventId)
    {
        try {
            $stmt = $this->host_db->prepare("INSERT INTO packages_history ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, :state, :type, :date, :time, :id_event)");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $stmt->bindValue(':state', $packageState);
            $stmt->bindValue(':type', $packageType);
            $stmt->bindValue(':date', $packageDate);
            $stmt->bindValue(':time', $packageTime);
            $stmt->bindValue(':id_event', $eventId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Ajout d'un état de paquet en BDD
     */
    public function setPackageState(string $name, string $version, string $state, string $date, string $time, string $id_event = null)
    {
        try {
            $stmt = $this->host_db->prepare("UPDATE packages SET Version = :version, Date = :date, Time = :time, State = :state, Id_event = :id_event WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $stmt->bindValue(':state', $state);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':id_event', $id_event);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Ajout d'un nouveau paquet en base données
     */
    public function addPackage(string $name, string $version, string $state, string $type, string $date, string $time, string $id_event = null)
    {
        try {
            if (!empty($id_event)) {
                $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, :state, :type, :date, :time, :id_event)");
                $stmt->bindValue(':id_event', $id_event);
            } else {
                $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time') VALUES (:name, :version, 'inventored', 'package', :date, :time)");
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $stmt->bindValue(':state', $state);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

/**
 *
 *  Mises à jour en base de données
 *
 */
    /**
     *  Update hostname in database
     */
    public function updateHostname(string $authId, string $token, string $hostname)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Hostname = :hostname WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':hostname', $hostname);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update OS in database
     */
    public function updateOS(string $authId, string $token, string $os)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os = :os WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':os', $os);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update OS release version in database
     */
    public function updateOsVersion(string $authId, string $token, string $osVersion)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os_version = :os_version WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':os_version', $osVersion);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update OS family in database
     */
    public function updateOsFamily(string $authId, string $token, string $osFamily)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os_family = :os_family WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':os_family', $osFamily);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update virtualization type in database
     */
    public function updateType(string $authId, string $token, string $type)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Type = :type WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update kernel version in database
     */
    public function updateKernel(string $authId, string $token, string $kernel)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Kernel = :kernel WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':kernel', $kernel);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update arch in database
     */
    public function updateArch(string $authId, string $token, string $arch)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Arch = :arch WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':arch', $arch);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update profile in database
     */
    public function updateProfile(string $authId, string $token, string $profile)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Profile = :profile WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':profile', $profile);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update environment in database
     */
    public function updateEnv(string $authId, string $token, string $env)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Env = :env WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update agent status in database
     */
    public function updateAgentStatus(string $id, string $status)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Online_status = :onlineStatus, Online_status_date = :onlineStatusDate, Online_status_time = :OnlineStatusTime WHERE Id = :hostId");
            $stmt->bindValue(':onlineStatus', $status);
            $stmt->bindValue(':onlineStatusDate', date('Y-m-d'));
            $stmt->bindValue(':OnlineStatusTime', date('H:i:s'));
            $stmt->bindValue(':hostId', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update linupdate version in database
     */
    public function updateLinupdateVersion(string $id, string $version)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Linupdate_version = :version WHERE Id = :hostId");
            $stmt->bindValue(':version', $version);
            $stmt->bindValue(':hostId', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Suppression d'un paquet dans la table packages_available
     */
    public function deletePackageAvailable(string $packageName, string $packageVersion)
    {
        try {
            $stmt = $this->host_db->prepare("DELETE FROM packages_available WHERE Name = :name and Version = :version");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Vidage de la table packages_available
     */
    public function cleanPackageAvailableTable()
    {
        $this->host_db->exec("DELETE FROM packages_available");

        /**
         *  Nettoie l'espace inutilisé suite à la suppression du contenu de la table
         */
        $this->host_db->exec("VACUUM");
    }

    /**
     *  Récupère l'ID en BDD d'un hôte
     */
    public function getIdByAuth(string $authId, string $token)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId=:authId and Token=:token");
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
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
     *  Récupère toutes les informations de l'hôte à partir de son ID
     */
    public function getAllById(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * from hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $data = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return hosts that have the specified kernel
     */
    public function getHostWithKernel(string $kernel)
    {
        $hosts = array();

        try {
            $stmt = $this->db->prepare("SELECT Hostname, Ip FROM hosts
            WHERE Kernel = :kernel and Status = 'active'");
            $stmt->bindValue(':kernel', $kernel);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
    }

    /**
     *  Return hosts that have the specified profile
     */
    public function getHostWithProfile(string $profile)
    {
        $hosts = array();

        try {
            $stmt = $this->db->prepare("SELECT Hostname, Ip FROM hosts
            WHERE Profile = :profile and Status = 'active'");
            $stmt->bindValue(':profile', $profile);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
    }

    /**
     *  Retourne un array avec toutes les informations concernant un paquet
     */
    public function getPackageInfo(string $packageId)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Id = :packageId");
            $stmt->bindValue(':packageId', $packageId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $data = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Retourne l'Id d'un package en BDD
     */
    public function getPackageId(string $packageName, string $packageVersion = null)
    {
        /**
         *  Récupération à partir du nom et de la version si les deux ont été fourni
         */
        if (!empty($packageName) and !empty($packageVersion)) {
            try {
                $stmt = $this->host_db->prepare("SELECT Id FROM packages WHERE Name = :name and Version = :version");
                $stmt->bindValue(':name', $packageName);
                $stmt->bindValue(':version', $packageVersion);
                $result = $stmt->execute();
            } catch (\Exception $e) {
                \Controllers\Common::dbError($e);
            }

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $id = $row['Id'];
            }

            return $id;

        /**
         *  Sinon si on a fourni uniquement le nom du paquet à chercher
         */
        } elseif (!empty($packageName)) {
            try {
                $stmt = $this->host_db->prepare("SELECT Id FROM packages WHERE Name = :name");
                $stmt->bindValue(':name', $packageName);
                $result = $stmt->execute();
            } catch (\Exception $e) {
                \Controllers\Common::dbError($e);
            }

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $id = $row['Id'];
            }

            return $id;
        }

        return false;
    }

    /**
     *  Retourne l'état actuel d'un paquet (table packages)
     */
    public function getPackageState(string $packageName, string $packageVersion = null)
    {
        try {
            /**
             *  Cas où on a précisé un numéro de version
             */
            if (!empty($packageVersion)) {
                $stmt = $this->host_db->prepare("SELECT State FROM packages WHERE Name = :name and Version = :version");
                $stmt->bindValue(':version', $packageVersion);

            /**
             *  Cas où on n'a pas précisé un numéro de version
             */
            } else {
                $stmt = $this->host_db->prepare("SELECT State FROM packages WHERE Name = :name");
            }

            $stmt->bindValue(':name', $packageName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $state = $row['State'];
        }

        return $state;
    }

    /**
     *  Vérifie que le couple ID/token est valide
     */
    public function checkIdToken(string $authId, string $token)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :hostId and Token = :token and Status = 'active'");
            $stmt->bindValue(':hostId', $authId);
            $stmt->bindValue(':token', $token);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Liste tous les hôtes
     */
    public function listAll(string $status)
    {
        if ($status == 'active') {
            $result = $this->db->query("SELECT * FROM hosts WHERE Status = 'active'");
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Fonction qui liste tous les noms d'OS référencés en les comptant
     *  Retourne le nom des Os et leur nombre
     */
    public function listCountOS()
    {
        $os = array();

        $result = $this->db->query("SELECT Os, Os_version, COUNT(*) as Os_count FROM hosts WHERE Status = 'active' GROUP BY Os, Os_version");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $os[] = $row;
        }

        return $os;
    }

    /**
     *  Fonction qui liste tous les kernel d'hôtes référencés en les comptant
     *  Retourne la version des kernels et leur nombre
     */
    public function listCountKernel()
    {
        $kernel = array();

        $result = $this->db->query("SELECT Kernel, COUNT(*) as Kernel_count FROM hosts WHERE Status = 'active' GROUP BY Kernel");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $kernel[] = $row;
        }

        return $kernel;
    }

    /**
     *  Fonction qui liste tous les arch d'hôtes référencés en les comptant
     *  Retourne la version des arch et leur nombre
     */
    public function listCountArch()
    {
        $arch = array();

        $result = $this->db->query("SELECT Arch, COUNT(*) as Arch_count FROM hosts WHERE Status = 'active' GROUP BY Arch");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $arch[] = $row;
        }

        return $arch;
    }

    /**
     *  Fonction qui liste tous les env d'hôtes référencés en les comptant
     *  Retourne le nom des env et leur nombre
     */
    public function listCountEnv()
    {
        $env = array();

        $result = $this->db->query("SELECT Env, COUNT(*) as Env_count FROM hosts WHERE Status = 'active' GROUP BY Env");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $env[] = $row;
        }

        return $env;
    }

    /**
     *  Fonction qui liste tous les profils d'hôtes référencés en les comptant
     *  Retourne le nom des profils et leur nombre
     */
    public function listCountProfile()
    {
        $profile = array();

        $result = $this->db->query("SELECT Profile, COUNT(*) as Profile_count FROM hosts WHERE Status = 'active' GROUP BY Profile");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $profile[] = $row;
        }

        return $profile;
    }

    /**
     *  Vérifie si l'Ip existe en BDD parmis les hôtes actifs
     */
    public function ipExists(string $ip)
    {
        try {
            $stmt = $this->db->prepare("SELECT Ip FROM hosts WHERE Ip = :ip and Status = 'active'");
            $stmt->bindValue(':ip', \Controllers\Common::validateData($ip));
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
     *  Vérifie si le hostname de l'hôte existe en BDD
     */
    public function hostnameExists(string $hostname)
    {
        try {
            $stmt = $this->db->prepare("SELECT Hostname FROM hosts WHERE Hostname = :hostname");
            $stmt->bindValue(':hostname', \Controllers\Common::validateData($hostname));
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
     *  Vérifie l'existence d'un paquet dans la table packages
     */
    public function packageExists(string $packageName)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Name = :name");
            $stmt->bindValue(':name', \Controllers\Common::validateData($packageName));
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->host_db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet et sa version dans la table package
     */
    public function packageVersionExists(string $packageName, string $packageVersion)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Name = :name and Version = :version");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->host_db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Rechercher l'existance d'un paquet sur un hôte et retourner sa version
     */
    public function searchPackage(string $packageName)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT Name, Version FROM packages WHERE Name LIKE :name");
            $stmt->bindValue(':name', "${packageName}%");
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Si aucun résultat on renvoi false
         */
        if ($this->host_db->isempty($result) === true) {
            return false;
        }

        /**
         *  Sinon on récupère les données
         */
        $packages = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $packageName = $row['Name'];
            $packageVersion = $row['Version'];
            $packages += [$packageName => $packageVersion];
        }

        /**
         *  Le résultat sera traité par js donc on transmets un array au format JSON
         */
        return json_encode($packages);
    }

    /**
     *  Vérifie l'existence d'un paquet dans la table packages_available
     */
    public function packageAvailableExists(string $packageName)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages_available WHERE Name = :name");
            $stmt->bindValue(':name', $packageName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->host_db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet et sa version dans la table package_available
     */
    public function packageVersionAvailableExists(string $packageName, string $packageVersion)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages_available WHERE Name = :name and Version = :version");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->host_db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Ajout d'un paquet dans la table 'packages_available'
     */
    public function addPackageAvailable(string $name, string $version)
    {
        try {
            $stmt = $this->host_db->prepare("INSERT INTO packages_available ('Name', 'Version') VALUES (:name, :version)");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Mise à jour d'un paquet dans la table 'packages_available'
     */
    public function updatePackageAvailable(string $name, string $version)
    {
        try {
            $stmt = $this->host_db->prepare("UPDATE packages_available SET Version = :version WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne true si un évènement existe à la date et heure spécifiés
     */
    public function eventExists(string $dateStart, string $timeStart)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT Id FROM events WHERE Date = :date_start and Time = :time_start");
            $stmt->bindValue(':date_start', $dateStart);
            $stmt->bindValue(':time_start', $timeStart);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->host_db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Ajoute un nouvel évènement en base de données
     */
    public function addEvent(string $dateStart, string $dateEnd, string $timeStart, string $timeEnd)
    {
        try {
            $stmt = $this->host_db->prepare("INSERT INTO events ('Date', 'Date_end', 'Time', 'Time_end', 'Status') VALUES (:date_start, :date_end, :time_start, :time_end, 'done')");
            $stmt->bindValue(':date_start', $dateStart);
            $stmt->bindValue(':date_end', $dateEnd);
            $stmt->bindValue(':time_start', $timeStart);
            $stmt->bindValue(':time_end', $timeEnd);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Récupération des paramètres généraux (table settings)
     */
    public function getSettings()
    {
        $settings = array();

        try {
            $result = $this->db->query("SELECT * FROM settings");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings = $row;
        }

        return $settings;
    }

    /**
     *  Modifie les paramètres d'affichage sur la page des hotes
     */
    public function setSettings(string $pkgs_considered_outdated, string $pkgs_considered_critical)
    {
        /**
         *  Modification des paramètres en base de données
         */
        try {
            $stmt = $this->db->prepare("UPDATE settings SET pkgs_count_considered_outdated = :pkgs_considered_outdated, pkgs_count_considered_critical = :pkgs_considered_critical");
            $stmt->bindValue(':pkgs_considered_outdated', $pkgs_considered_outdated);
            $stmt->bindValue(':pkgs_considered_critical', $pkgs_considered_critical);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Récupère la liste des paquets présents sur l'hôte
     */
    public function getPackagesInventory()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) {
            return false;
        }

        /**
         *  Récupération du total des paquets installés sur l'hôte
         */
        $datas = array();

        try {
            $result = $this->host_db->query("SELECT * FROM packages ORDER BY Name ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Retourne les paquets installés sur l'hôte
     */
    public function getPackagesInstalled()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) {
            return false;
        }

        /**
         *  Récupération du total des paquets installés sur l'hôte
         */
        $datas = array();

        try {
            $result = $this->host_db->query("SELECT * FROM packages WHERE State = 'inventored' or State = 'installed' or State = 'dep-installed' or State = 'upgraded' or State = 'downgraded'");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Récupère la liste des paquets disponibles pour mise à jour sur l'hôte
     */
    public function getPackagesAvailable()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
        */
        if (empty($this->host_db)) {
            return false;
        }

        /**
         *  Récupération du total des paquets installés sur l'hôte
         */
        $datas = array();

        try {
            $result = $this->host_db->query("SELECT * FROM packages_available");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Récupère la liste des mises à jour demandées par repomanager à l'hôte
     */
    public function getUpdatesRequests()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) {
            return false;
        }

        $datas = array();

        try {
            $result = $this->host_db->query("SELECT * FROM updates_requests ORDER BY Date DESC, Time DESC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            /**
             *  On ajoute une colonne Event_type au résultat afin de définir qu'il s'agit d'une 'update_request'. Sera utile au moment de l'affichage des données.
             */
            $row['Event_type'] = 'update_request';
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Récupère les informations de toutes les actions effectuées sur les paquets de l'hôte (installation, mise à jour, désinstallation...)
     */
    public function getEventsHistory()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) {
            return false;
        }

        $datas = array();

        try {
            $result = $this->host_db->query("SELECT * FROM events ORDER BY Date DESC, Time DESC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            /**
             *  On ajoute une colonne Event_type au résultat afin de définir qu'il s'agit d'un 'event'. Sera utile au moment de l'affichage des données.
             */
            $row['Event_type'] = 'event';
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Récupère la liste des paquets issus d'un évènemnt et dont l'état des paquets est défini par $packageState (installed, upgraded, removed)
     *  Les informations sont récupérées à la fois dans la table packages et dans packages_history
     */
    public function getEventPackagesList(string $eventId, string $packageState)
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) {
            return false;
        }

        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages        
            WHERE Id_event = :eventId and State = :packageState
            UNION
            SELECT * FROM packages_history       
            WHERE Id_event = :eventId and State = :packageState");
            $stmt->bindValue(':eventId', \Controllers\Common::validateData($eventId));
            $stmt->bindValue(':packageState', \Controllers\Common::validateData($packageState));
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Récupère le détails d'un évènement sur un type de paquets en particulier (installés, mis à jour, etc...)
     *  Cette fonction est notamment déclenchée au passage de la souris sur une ligne de l'historique des évènements
     */
    public function getEventDetails(string $eventId, string $packageState)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT Name, Version FROM packages
            WHERE Id_event = :id_event and State = :state
            UNION
            SELECT Name, Version FROM packages_history
            WHERE Id_event = :id_event and State = :state");
            $stmt->bindValue(':id_event', \Controllers\Common::validateData($eventId));
            $stmt->bindValue(':state', \Controllers\Common::validateData($packageState));
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $packages = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $packages[] = $row;
        }

        $this->host_db->close();

        return $packages;
    }

    /**
     *  Récupère l'historique complet d'un paquet (son installation, ses mises à jour, etc...)
     */
    public function getPackageTimeline(string $packageName)
    {
        $events = array();

        /**
         *  Récupération de l'historique du paquet (table packages_history) ainsi que son état actuel (table packages)
         */
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages_history
            WHERE Name = :packagename
            UNION SELECT * FROM packages
            WHERE Name = :packagename
            ORDER BY Date DESC, Time DESC");
            $stmt->bindValue(':packagename', $packageName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $events[] = $row;
        }

        return $events;
    }

    /**
     *  Récupère les informations concernant la dernière requête de mise à jour envoyée à l'hôte
     */
    public function getLastUpdateStatus()
    {
        $datas = array();

        try {
            $result = $this->host_db->query("SELECT Date, Time, Status FROM events ORDER BY Id DESC LIMIT 1");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        try {
            $result = $this->host_db->query("SELECT Date, Time, Status FROM updates_requests ORDER BY Id DESC LIMIT 1");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Récupère le status de la dernière demande de mise à jour de l'hôte
     */
    public function getLastRequestedUpdateStatus()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) {
            return false;
        }

        $datas = array();

        try {
            $result = $this->host_db->query("SELECT Date, Time, Type, Status FROM updates_requests ORDER BY Id DESC LIMIT 1");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas = $row;
        }

        return $datas;
    }

    /**
     *  Compte le nombre de paquets installés, mis à jour, désinstallés... au cours des X derniers jours.
     *  Retourne un array contenant les dates => nombre de paquet
     *  Fonction utilisées notamment pour la création du graphique ChrtJS de type 'line' sur la page d'un hôte
     */
    public function getLastPackagesStatusCount(string $status, string $dateStart, string $dateEnd)
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) {
            return false;
        }

        try {
            $stmt = $this->host_db->prepare("SELECT Date, COUNT(*) as date_count FROM packages WHERE State = :status and Date BETWEEN :dateStart and :dateEnd GROUP BY Date
                                            UNION
                                            SELECT Date, COUNT(*) as date_count FROM packages_history WHERE State = :status and Date BETWEEN :dateStart and :dateEnd GROUP BY Date");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':dateStart', $dateStart);
            $stmt->bindValue(':dateEnd', $dateEnd);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $array = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!array_key_exists($row['Date'], $array)) {
                $array[$row['Date']] = $row['date_count'];
            } else {
                $array[$row['Date']] += $row['date_count'];
            }
        }

        return $array;
    }

    /**
     *  Retourne le hostname d'un hôte à partir de son Id
     */
    public function getHostnameById(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Hostname FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hostname = $row['Hostname'];
        }

        return $hostname;
    }

    /**
     *  Retourne l'IP d'un hôte à partir de son Id
     */
    public function getIpById(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Ip FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ip = $row['Ip'];
        }

        return $ip;
    }

    /**
     *  Retourne l'état (ping) d'un hôte en base de données
     */
    public function getHostStatus(string $hostname)
    {
        try {
            $stmt = $this->db->prepare("SELECT Status FROM hosts WHERE Hostname = :hostname");
            $stmt->bindValue(':hostname', $hostname);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $status = '';

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $status = $row['Status'];
        }

        return $status;
    }

    /**
     *  Ajout d'un nouvel hôte en base de données
     */
    public function addHost(string $ip, string $hostname, string $authId, string $token, string $onlineStatus, string $date, string $time)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO hosts (Ip, Hostname, AuthId, Token, Online_status, Online_status_date, Online_status_time, Status) VALUES (:ip, :hostname, :id, :token, :online_status, :date, :time, 'active')");
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':hostname', $hostname);
            $stmt->bindValue(':id', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->bindValue(':online_status', $onlineStatus);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Mise à jour d'un hôte en base de données
     */
    public function updateHost(string $ip, string $hostname, string $authId, string $token, string $onlineStatus, string $date, string $time)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Ip = :ip, AuthId = :id, Token = :token, Online_status = :online_status, Online_status_date = :date, Online_status_time = :time, Status = 'active' WHERE Hostname = :hostname");
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':hostname', $hostname);
            $stmt->bindValue(':id', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->bindValue(':online_status', $onlineStatus);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Désactive un hôte en base de données
     *  Pour identifier l'hôte on peut soit spécifier son Id, soit ses informations d'identification
     */
    public function setHostInactive(string $hostId = null, string $authId = null, string $token = null)
    {
        try {
            if (!empty($hostId)) {
                $stmt = $this->db->prepare("UPDATE hosts SET Status = 'deleted', AuthId = null, Token = null WHERE id = :hostId");
                $stmt->bindValue(':hostId', $hostId);
            }
            if (!empty($authId) and !empty($token)) {
                $stmt = $this->db->prepare("UPDATE hosts SET Status = 'deleted', AuthId = null, Token = null WHERE AuthId = :authId and Token = :token");
                $stmt->bindValue(':authId', $authId);
                $stmt->bindValue(':token', $token);
            }
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Reset les données d'un hôte
     */
    public function resetHost(string $hostId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os = null, Os_version = null, Profile = null, Env = null, Kernel = null, Arch = null WHERE id = :id");
            $stmt->bindValue(':id', $hostId);
            $stmt->execute();

            /**
             *  On supprime toutes les tables dans cette base de données
             */
            $this->host_db->exec("DROP TABLE events");
            $this->host_db->exec("DROP TABLE packages");
            $this->host_db->exec("DROP TABLE packages_available");
            $this->host_db->exec("DROP TABLE packages_history");
            $this->host_db->exec("DROP TABLE updates_requests");

            /**
             *  Puis on les re-génère à vide
             */
            $this->host_db->generateHostTables();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Ajout d'une requête
     */
    public function addUpdateRequest(string $type)
    {
        try {
            $stmt = $this->host_db->prepare("INSERT INTO updates_requests ('Date', 'Time', 'Type', 'Status') VALUES (:date, :time, :type, 'requested')");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->bindValue(':type', $type);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Mise à jour du status d'une requête
     */
    public function setUpdateRequestStatus(string $type, string $status)
    {
        try {
            $stmt = $this->host_db->prepare("UPDATE updates_requests SET Status = :status WHERE Type = :type and Status = 'requested' or Status = 'running'");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':type', $type);
            $stmt->execute();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *  Retourne l'Id de la dernière ligne insérée en base de données dédiée à un hôte ('host_db')
     */
    public function getHostLastInsertRowID()
    {
        return $this->host_db->lastInsertRowID();
    }

    /**
     *  Retourne les hosts membres d'un groupe à partir de son Id
     */
    public function getHostsGroupMembers(string $groupId)
    {
        try {
            $stmt = $this->db->prepare("SELECT
            hosts.Id AS hostId,
            hosts.Hostname,
            hosts.Ip
            FROM hosts
            INNER JOIN group_members
                ON hosts.Id = group_members.Id_host
            INNER JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE Id_group = :idgroup
            AND hosts.Status = 'active'");
            $stmt->bindValue(':idgroup', $groupId);
            ;
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $hosts = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
    }

    /**
     *  Retourne les hosts qui ne sont membres d'aucun groupe
     */
    public function getHostsNotMembersOfAnyGroup()
    {
        try {
            $result = $this->db->query("SELECT
            hosts.Id,
            hosts.Hostname,
            hosts.Ip
            FROM hosts
            WHERE hosts.Id NOT IN (SELECT Id_host FROM group_members)
            AND hosts.Status = 'active'");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $hosts = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
    }

    /**
     *  Retourne true si l'Id d'hôte existe en base de données
     */
    public function existsId(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE Id = :id");
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
     *  Ajout de l'hôte au groupe spécifié en base de données
     */
    public function addToGroup(string $hostId, string $groupId)
    {
        /**
         *  On vérifie d'abord que l'hôte n'est pas déjà membre du groupe
         *  Le raffraichissement du <select> peut provoquer deux fois l'ajout du repo dans le groupe, donc on fait cette vérification pour palier à ce bug
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM group_members WHERE Id_host = :hostId AND Id_group = :groupId");
            $stmt->bindValue(':hostId', $hostId);
            $stmt->bindValue(':groupId', $groupId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Si l'hôte est déjà présent on ne fait rien
         */
        if ($this->db->isempty($result) === false) {
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO group_members (Id_host, Id_group) VALUES (:id_host, :id_group)");
            $stmt->bindValue(':id_host', $hostId);
            $stmt->bindValue(':id_group', $groupId);
            $stmt->execute();
        } catch (Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retrait d'un hôte du groupe spécifié
     */
    public function removeFromGroup(string $hostId, string $groupId = null)
    {
        try {
            /**
             *  Si on a précisé l'Id du groupe
             */
            if (!empty($groupId)) {
                $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_host = :hostId AND Id_group = :groupId");
                $stmt->bindValue(':groupId', $groupId);
            } else {
                $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_host = :hostId");
            }
            $stmt->bindValue(':hostId', $hostId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne le nombre d'hôtes utilisant le profil spécifié
     */
    public function countByProfile(string $profile)
    {
        $hosts = array();

        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE Profile = :profile AND Status = 'active'");
            $stmt->bindValue(':profile', $profile);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return count($hosts);
    }
}
