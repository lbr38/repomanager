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
                hosts.Reboot_required,
                hosts.Linupdate_version,
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
            $this->db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
    public function updateHostname(string $id, string $hostname)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Hostname = :hostname WHERE Id = :id");
            $stmt->bindValue(':hostname', $hostname);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update OS in database
     */
    public function updateOS(string $id, string $os)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os = :os WHERE Id = :id");
            $stmt->bindValue(':os', $os);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update OS release version in database
     */
    public function updateOsVersion(string $id, string $osVersion)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os_version = :os_version WHERE Id = :id");
            $stmt->bindValue(':os_version', $osVersion);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update OS family in database
     */
    public function updateOsFamily(string $id, string $osFamily)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os_family = :os_family WHERE Id = :id");
            $stmt->bindValue(':os_family', $osFamily);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update virtualization type in database
     */
    public function updateType(string $id, string $type)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Type = :type WHERE Id = :id");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update kernel version in database
     */
    public function updateKernel(string $id, string $kernel)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Kernel = :kernel WHERE Id = :id");
            $stmt->bindValue(':kernel', $kernel);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update arch in database
     */
    public function updateArch(string $id, string $arch)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Arch = :arch WHERE Id = :id");
            $stmt->bindValue(':arch', $arch);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update profile in database
     */
    public function updateProfile(string $id, string $profile)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Profile = :profile WHERE Id = :id");
            $stmt->bindValue(':profile', $profile);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update environment in database
     */
    public function updateEnv(string $id, string $env)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Env = :env WHERE Id = :id");
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update agent status in database
     */
    public function updateAgentStatus(string $id, string $status)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Online_status = :onlineStatus, Online_status_date = :onlineStatusDate, Online_status_time = :OnlineStatusTime WHERE Id = :id");
            $stmt->bindValue(':onlineStatus', $status);
            $stmt->bindValue(':onlineStatusDate', date('Y-m-d'));
            $stmt->bindValue(':OnlineStatusTime', date('H:i:s'));
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update linupdate version in database
     */
    public function updateLinupdateVersion(string $id, string $version)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Linupdate_version = :version WHERE Id = :id");
            $stmt->bindValue(':version', $version);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update host's reboot required status in database
     */
    public function updateRebootRequired(string $id, string $status)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Reboot_required = :reboot WHERE Id = :id");
            $stmt->bindValue(':reboot', $status);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
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
            $this->host_db->logError($e);
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
    public function getIdByAuth(string $authId)
    {
        $id = '';

        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :authId");
            $stmt->bindValue(':authId', $authId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

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
            $this->db->logError($e);
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
            $stmt = $this->db->prepare("SELECT Id, Hostname, Ip, Os, Os_family FROM hosts
            WHERE Kernel = :kernel
            AND Status = 'active'
            ORDER BY Hostname ASC");
            $stmt->bindValue(':kernel', $kernel);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
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
            $stmt = $this->db->prepare("SELECT Id, Hostname, Ip, Os, Os_family FROM hosts
            WHERE Profile = :profile
            AND Status = 'active'
            ORDER BY Hostname ASC");
            $stmt->bindValue(':profile', $profile);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
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
            $this->host_db->logError($e);
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
                $this->host_db->logError($e);
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
                $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :authId and Token = :token and Status = 'active'");
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
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
     *  List all hosts agent status and count them
     */
    public function listCountAgentStatus()
    {
        $agentStatus = array();

        $stmt = $this->db->prepare("SELECT * FROM
        (SELECT COUNT(*) as Linupdate_agent_status_online_count
        FROM hosts
        WHERE Status = 'active' AND Online_status = 'running' AND Online_status_date = :todayDate AND Online_status_time >= :maxTime),
        (SELECT COUNT(*) as Linupdate_agent_status_seems_stopped_count
        FROM hosts
        WHERE Status = 'active' AND Online_status != 'stopped' AND (Online_status_date != :todayDate OR Online_status_time <= :maxTime)),
        (SELECT COUNT(*) as Linupdate_agent_status_disabled_count
        FROM hosts
        WHERE Status = 'active' AND Online_status = 'disabled'),
        (SELECT COUNT(*) as Linupdate_agent_status_stopped_count
        FROM hosts
        WHERE Status = 'active' AND Online_status = 'stopped')");
        $stmt->bindValue(':todayDate', DATE_YMD);
        $stmt->bindValue(':maxTime', date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes')));
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $agentStatus['Linupdate_agent_status_online_count'] = $row['Linupdate_agent_status_online_count'];
            $agentStatus['Linupdate_agent_status_stopped_count'] = $row['Linupdate_agent_status_stopped_count'];
            $agentStatus['Linupdate_agent_status_seems_stopped_count'] = $row['Linupdate_agent_status_seems_stopped_count'];
            $agentStatus['Linupdate_agent_status_disabled_count'] = $row['Linupdate_agent_status_disabled_count'];
        }

        return $agentStatus;
    }

    /**
     *  List all hosts agent and count them
     *  Returns agent version and total
     */
    public function listCountAgentVersion()
    {
        $agent = array();

        $result = $this->db->query("SELECT Linupdate_version, COUNT(*) as Linupdate_version_count FROM hosts WHERE Status = 'active' GROUP BY Linupdate_version");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $agent[] = $row;
        }

        return $agent;
    }

    /**
     *  List all hosts that require a reboot and count them
     */
    public function listRebootRequired()
    {
        $hosts = array();

        $result = $this->db->query("SELECT Id, Hostname, Ip, Os, Os_family FROM hosts WHERE Status = 'active' AND Reboot_required = 'true' ORDER BY Hostname ASC");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
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
            $this->db->logError($e);
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
            $this->db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
        }

        if ($this->host_db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Search hosts with specified package
     */
    public function getHostsWithPackage(string $packageName)
    {
        $packages = array();

        try {
            $stmt = $this->host_db->prepare("SELECT Name, Version FROM packages WHERE Name LIKE :name");
            $stmt->bindValue(':name', "${packageName}%");
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->host_db->logError($e);
        }

        /**
         *  If no result, return empty array
         */
        if ($this->host_db->isempty($result) === true) {
            return $packages;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $packageName = $row['Name'];
            $packageVersion = $row['Version'];
            $packages[$packageName] = $packageVersion;
        }

        /**
         *  Le résultat sera traité par js donc on transmets un array au format JSON
         */
        return $packages;
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings = $row;
        }

        return $settings;
    }

    /**
     *  Edit the display settings on the hosts page
     */
    public function setSettings(string $packagesConsideredOutdated, string $packagesConsideredCritical)
    {
        try {
            $stmt = $this->db->prepare("UPDATE settings SET pkgs_count_considered_outdated = :packagesConsideredOutdated, pkgs_count_considered_critical = :packagesConsideredCritical");
            $stmt->bindValue(':packagesConsideredOutdated', $packagesConsideredOutdated);
            $stmt->bindValue(':packagesConsideredCritical', $packagesConsideredCritical);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Retrieve the list of packages available for update on the host
     *  It is possible to add an offset to the request
     */
    public function getPackagesAvailable(bool $withOffset, int $offset)
    {
        $data = array();

        try {
            $query = "SELECT * FROM packages_available";

            /**
             *  Add offset if needed
             */
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            /**
             *  Prepare query
             */
            $stmt = $this->host_db->prepare($query);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->host_db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Retrieve the list of requests sent to the host
     *  It is possible to add an offset to the request
     */
    public function getRequests(int $id, bool $withOffset, int $offset)
    {
        $data = array();

        try {
            $query = "SELECT * FROM requests WHERE Id_host = :id ORDER BY Date DESC, Time DESC";

            /**
             *  Add offset if needed
             */
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            /**
             *  Prepare query
             */
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

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
     *  Return the last pending request sent to the host
     */
    public function getLastPendingRequest(int $id)
    {
        $data = array();

        try {
            $stmt = $this->db->prepare("SELECT * from requests WHERE Id_host = :id ORDER BY DATE DESC, TIME DESC LIMIT 1");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Retrieve information about all actions performed on host packages (install, update, remove...)
     *  It is possible to add an offset to the request
     */
    public function getEventsHistory(bool $withOffset, int $offset)
    {
        $data = array();

        try {
            $query = "SELECT * FROM events ORDER BY Date DESC, Time DESC";

            /**
             *  Add offset if needed
             */
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            /**
             *  Prepare query
             */
            $stmt = $this->host_db->prepare($query);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->host_db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            /**
             *  Add a column Event_type to the result to define that it is an 'event'. Will be useful when displaying data.
             */
            $row['Event_type'] = 'event';
            $data[] = $row;
        }

        return $data;
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
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
            $this->host_db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $events[] = $row;
        }

        return $events;
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
            $this->host_db->logError($e);
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
     *  Return the hostname of the host by its Id
     */
    public function getHostnameById(int $id)
    {
        $hostname = '';

        try {
            $stmt = $this->db->prepare("SELECT Hostname FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
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
            $this->db->logError($e);
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
            $this->db->logError($e);
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
            $this->db->logError($e);
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
            $this->db->logError($e);
        }
    }

    /**
     *  Désactive un hôte en base de données
     *  Pour identifier l'hôte on peut soit spécifier son Id, soit ses informations d'identification
     */
    public function setHostInactive(string $hostId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Status = 'deleted', AuthId = null, Token = null WHERE id = :hostId");
            $stmt->bindValue(':hostId', $hostId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Reset host data
     */
    public function resetHost(string $hostId)
    {
        try {
            /**
             *  Reset host general informations
             */
            $stmt = $this->db->prepare("UPDATE hosts SET Os = null, Os_version = null, Profile = null, Env = null, Kernel = null, Arch = null WHERE id = :id");
            $stmt->bindValue(':id', $hostId);
            $stmt->execute();

            /**
             *  Retrieve all requests made to the host
             */
            $stmt = $this->db->prepare("SELECT Id FROM requests WHERE Id_host = :id");
            $stmt->bindValue(':id', $hostId);
            $result = $stmt->execute();

            /**
             *  Delete all requests logs files
             */
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if (file_exists(WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log')) {
                    if (!unlink(WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log')) {
                        throw new \Exception('Unable to delete request log file: ' . WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log');
                    }
                }
            }

            /**
             *  Delete all requests in requests table
             */
            $stmt = $this->db->prepare("DELETE FROM requests WHERE Id_host = :id");
            $stmt->bindValue(':id', $hostId);
            $stmt->execute();

            /**
             *  Delete all tables in host database
             */
            $this->host_db->exec("DROP TABLE events");
            $this->host_db->exec("DROP TABLE packages");
            $this->host_db->exec("DROP TABLE packages_available");
            $this->host_db->exec("DROP TABLE packages_history");

            /**
             *  Then we regenerate them empty
             */
            $this->host_db->generateHostTables();
        } catch (\Exception $e) {
            $this->host_db->logError($e);
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
     *  Retourne true si l'Id d'hôte existe en base de données
     */
    public function existsId(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE Id = :id");
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
            $this->db->logError($e);
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
            $this->db->logError($e);
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
            $this->db->logError($e);
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
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return count($hosts);
    }
}
