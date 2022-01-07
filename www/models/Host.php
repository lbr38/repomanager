<?php

class Host extends Model {
    protected $host_db; // BDD dédiée à l'hôte

    /**
     *  Propriétés relatives à l'hôte
     */
    protected $id;      // Id de l'hôte en BDD
    protected $idArray = array();
    protected $ip;
    protected $hostname;
    protected $os;
    protected $os_version;
    protected $env;
    protected $authId;
    protected $token;
    protected $onlineStatus;
    protected $callFromApi = 'no'; // défini si l'appel des fonctions ci-dessous est effectué depuis l'api ou non. N'affichera pas les messages d'erreurs 'printAlert' si c'est le cas (yes).

    /**
     *  Propriétés relatives aux paquets de l'hôte
     */
    protected $packageId;
    protected $packageName;
    protected $packageVersion;

    public function __construct()
    {
        /**
         *  Ouverture de la base de données 'hosts' (repomanager-hosts.db)
         */
        $this->getConnection('hosts', 'rw');
    }

    public function setId(string $id)
    {
        $this->id = validateData($id);
    }

    public function setIp(string $ip)
    {
        $this->ip = validateData($ip);
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = validateData($hostname);
    }

    public function setOS(string $os)
    {
        $this->os = validateData($os);
    }

    public function setOS_version(string $os_version)
    {
        $this->os_version = validateData($os_version);
    }

    public function setProfile(string $profile)
    {
        $this->profile = validateData($profile);
    }

    public function setEnv(string $env)
    {
        $this->env = validateData($env);
    }

    /**
     *  Défini toutes les propriétés de l'hôte à partir de son Id et des informations correspondantes en base de données
     */
    public function setAll(string $id)
    {
        if (!is_numeric($id)) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT * from hosts WHERE Id = :id");
        $stmt->bindValue(':id', $this->id);
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->hostname = $row['Hostname'];
            $this->ip = $row['Ip'];
            $this->onlineStatus = $row['Online_status'];
            $this->os = $row['Os'];
            $this->os_version = $row['Os_version'];
            $this->profile = $row['Profile'];
            $this->env = $row['Env'];
        }
    }

    public function setPackageId(string $packageId)
    {
        $this->packageId = validateData($packageId);
    }

    public function setPackageName(string $packageName)
    {
        $this->packageName = validateData($packageName);
    }

    public function setPackageVersion(string $packageVersion)
    {
        $this->packageVersion = validateData($packageVersion);
    }

    public function setPackagesInventory(string $packages_inventory)
    {
        $this->packages_inventory = validateData($packages_inventory);
    }

    public function setAuthId(string $authId)
    {
        $this->authId = validateData($authId);
    }

    public function setToken(string $token)
    {
        $this->token = validateData($token);
    }

    public function setFromApi()
    {
        $this->callFromApi = 'yes';
    }

    public function getId()
    {
        if (!empty($this->id)) return $this->id;
        return '';
    }

    public function getAuthId()
    {
        if (!empty($this->authId)) return $this->authId;
        return '';
    }

    public function getToken()
    {
        if (!empty($this->token)) return $this->token;
        return '';
    }

    /**
     *  Ouverture de la BDD dédiée de l'hôte si ce n'est pas déjà fait
     *  Fournir l'id de l'hôte et le mode d'ouverture de la base (ro = lecture seule / rw = lecture-écriture)
     */
    public function openHostDb(string $hostId, string $mode)
    {
        $this->getConnection('host', $mode, $hostId);
    }

    /**
     *  Fermeture de la BDD dédiée de l'hôte
     */
    public function closeHostDb()
    {
        $this->host_db->close();
    }

/**
 * 
 *  Insertion en base de données
 * 
 */
    /**
     *  Copie l'état actuel d'un paquet de la table packages vers la table packages_history afin de conserver une trace de cet état
     */
    public function db_setPackageHistory(string $packageId)
    {
        /**
         *  D'abord on récupère l'état actuel du paquet
         */
        $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Id = :packageId");
        $stmt->bindValue(':packageId', $packageId);
        $result = $stmt->execute();

        $data = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $data = $row;
        if (!empty($data['Name'])) $packageName = $data['Name'];
        if (!empty($data['Version'])) $packageVersion = $data['Version'];
        if (!empty($data['State'])) $packageState = $data['State'];
        if (!empty($data['Type'])) $packageType = $data['Type'];
        if (!empty($data['Date'])) $packageDate = $data['Date'];
        if (!empty($data['Time'])) $packageTime = $data['Time'];
        if (!empty($data['Id_event'])) 
            $package_id_event = $data['Id_event'];
        else
            $package_id_event = '';
 
        /**
         *  Puis on copie cet état dans packages_history
         */
        $stmt = $this->host_db->prepare("INSERT INTO packages_history ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, :state, :type, :date, :time, :id_event)");
        $stmt->bindValue(':name', $packageName);
        $stmt->bindValue(':version', $packageVersion);
        $stmt->bindValue(':state', $packageState);
        $stmt->bindValue(':type', $packageType);
        $stmt->bindValue(':date', $packageDate);
        $stmt->bindValue(':time', $packageTime);
        $stmt->bindValue(':id_event', $package_id_event);
        $stmt->execute();

        return true;
    }

    /**
     *  Ajoute un paquet installé en base de données
     */
    public function db_setPackageInstalled(string $name, string $version, string $date, string $time, string $id_event = null)
    {
        /**
         *  Nom du paquet
         */
        $this->setPackageName($name);

        /**
         *  Version du paquet
         */
        $this->setPackageVersion($version);

        /**
         *  Insertion en BDD
         *  Si le paquet existe déjà en BDD, on le mets à jour
         */
        if ($this->packageExists($this->packageName) === true) {
            /**
             *  D'abord on fait une copie de l'état actuel du paquet dans packages_history afin de conserver un suivi.
             */
            /**
             *  Récupération de l'Id du paquet au préalable
             */
            $this->setPackageId($this->db_getPackageId($this->packageName));

            /**
             *  Sauvegarde de l'état
             */
            $this->db_setPackageHistory($this->packageId);

            /**
             *  Puis on met à jour la version du paquet en base par celle qui vient d'être installée
             */
            $stmt = $this->host_db->prepare("UPDATE packages SET Version = :version, Date = :date, Time = :time, State = 'installed', Id_event = :id_event WHERE Name = :name");

        } else {
            /**
             *  Si le paquet n'existe pas on l'ajoute en BDD directement en état "installé"
             */
            $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, 'installed', 'package', :date, :time, :id_event)");
        }
        $stmt->bindValue(':name', $this->packageName);
        $stmt->bindValue(':version', $this->packageVersion);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':time', $time);
        $stmt->bindValue(':id_event', $id_event);
        $stmt->execute();

        /**
         *  Enfin si le paquet était présent dans packages_available on le retire
         */
        $this->db_deletePackageAvailable($this->packageName, $this->packageVersion);
    }

    /**
     *  Ajout d'un nouveau paquet mis à jour en BDD
     */
    public function db_setPackageUpgraded(string $name, string $version, string $date, string $time, string $id_event = null)
    {
        /**
         *  Nom du paquet
         */
        $this->setPackageName($name);

        /**
         *  Version du paquet
         */
        $this->setPackageVersion($version);

        /**
         *  Insertion en BDD
         *  Si le paquet existe déjà en BDD, on le mets à jour
         */
        if ($this->packageExists($this->packageName) === true) {
            /**
             *  D'abord on fait une copie de l'état actuel du paquet dans packages_history afin de conserver un suivi.
             */
            /**
             *  Récupération de l'Id du paquet au préalable
             */
            $this->setPackageId($this->db_getPackageId($this->packageName));

            /**
             *  Sauvegarde de l'état
             */
            $this->db_setPackageHistory($this->packageId);

            /**
             *  Puis on met à jour la version du paquet en base par celle qui vient d'être installée
             */
            $stmt = $this->host_db->prepare("UPDATE packages SET Version = :version, Date = :date, Time = :time, State = 'upgraded', Id_event = :id_event WHERE Name = :name");

        } else {
            /**
             *  Si le paquet n'existe pas on l'ajoute en BDD directement en état "upgraded"
             */
            $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, 'upgraded', 'package', :date, :time, :id_event)");
        }
        $stmt->bindValue(':name', $this->packageName);
        $stmt->bindValue(':version', $this->packageVersion);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':time', $time);
        $stmt->bindValue(':id_event', $id_event);
        $stmt->execute();

        /**
         *  Enfin si le paquet était présent dans packages_available on le retire
         */
        $this->db_deletePackageAvailable($this->packageName, $this->packageVersion);
    }

    /**
     *  Retrait d'un paquet supprimé en BDD
     */
    public function db_setPackageRemoved(string $name, string $version, string $date, string $time, string $id_event = null)
    {
        /**
         *  Nom du paquet
         */
        $this->setPackageName($name);

        /**
         *  Version du paquet
         */
        $this->setPackageVersion($version);

         /**
         *  Suppression en BDD (changement de l'état du paquet à 'removed')
         *  Si le paquet existe déjà en BDD, on met à jour son état
         */
        if ($this->packageExists($this->packageName) === true) {            
            /**
             *  D'abord on fait une copie de l'état actuel du paquet dans packages_history afin de conserver un suivi.
             *  Récupération de l'Id du paquet au préalable :
             */
            $this->setPackageId($this->db_getPackageId($this->packageName));

            /**
             *  Sauvegarde de l'état
             */
            $this->db_setPackageHistory($this->packageId);

            /**
             *  Puis on met à jour la version du paquet en base par celle qui vient d'être supprimée et on passe l'état du paquet à 'removed'
             */
            $stmt = $this->host_db->prepare("UPDATE packages SET Version = :version, Date = :date, Time = :time, State = 'removed', Id_event = :id_event WHERE Name = :name");        

        } else {
            /**
             *  Si le paquet n'existe pas on l'ajoute en BDD directement en état "removed"
             */
            $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, 'removed', 'package', :date, :time, :id_event)");
        }
        $stmt->bindValue(':name', $this->packageName);
        $stmt->bindValue(':version', $this->packageVersion);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':time', $time);
        $stmt->bindValue(':id_event', $id_event);
        $stmt->execute();

        /**
         *  Enfin si le paquet était présent dans packages_available on le retire
         */
        $this->db_deletePackageAvailable($this->packageName, $this->packageVersion);
    }

    /**
     *  Ajout d'un paquet downgradé en BDD
     */
    public function db_setPackageDowngraded(string $name, string $version, string $date, string $time, string $id_event = null)
    {
        /**
         *  Nom du paquet
         */
        $this->setPackageName($name);

        /**
         *  Version du paquet
         */
        $this->setPackageVersion($version);

        /**
         *  Insertion en BDD
         *  Si le paquet existe déjà en BDD, on le mets à jour
         */
        if ($this->packageExists($this->packageName) === true) {
            /**
             *  D'abord on fait une copie de l'état actuel du paquet dans packages_history afin de conserver un suivi.
             */
            /**
             *  Récupération de l'Id du paquet au préalable
             */
            $this->setPackageId($this->db_getPackageId($this->packageName));

            /**
             *  Sauvegarde de l'état
             */
            $this->db_setPackageHistory($this->packageId);

            /**
             *  Puis on met à jour la version du paquet en base par celle qui vient d'être installée
             */
            $stmt = $this->host_db->prepare("UPDATE packages SET Version = :version, Date = :date, Time = :time, State = 'downgraded', Id_event = :id_event WHERE Name = :name");

        } else {
            /**
             *  Si le paquet n'existe pas on l'ajoute en BDD directement en état "downgraded"
             */
            $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, 'downgraded', 'package', :date, :time, :id_event)");
        }
        $stmt->bindValue(':name', $this->packageName);
        $stmt->bindValue(':version', $this->packageVersion);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':time', $time);
        $stmt->bindValue(':id_event', $id_event);
        $stmt->execute();

        /**
         *  Enfin si le paquet était présent dans packages_available on le retire
         */
        $this->db_deletePackageAvailable($this->packageName, $this->packageVersion);
    }

    /**
     *  Ajout d'un paquet réinstallé en BDD
     */
    public function db_setPackageReinstalled(string $name, string $version, string $date, string $time, string $id_event = null)
    {
        /**
         *  Nom du paquet
         */
        $this->setPackageName($name);

        /**
         *  Version du paquet
         */
        $this->setPackageVersion($version);

        /**
         *  Insertion en BDD
         *  Si le paquet existe déjà en BDD, on le mets à jour
         */
        if ($this->packageExists($this->packageName) === true) {
            /**
             *  D'abord on fait une copie de l'état actuel du paquet dans packages_history afin de conserver un suivi.
             */
            /**
             *  Récupération de l'Id du paquet au préalable
             */
            $this->setPackageId($this->db_getPackageId($this->packageName));

            /**
             *  Sauvegarde de l'état
             */
            $this->db_setPackageHistory($this->packageId);

            /**
             *  Puis on met à jour la version du paquet en base par celle qui vient d'être installée
             */
            $stmt = $this->host_db->prepare("UPDATE packages SET Version = :version, Date = :date, Time = :time, State = 'reinstalled', Id_event = :id_event WHERE Name = :name");

        } else {
            /**
             *  Si le paquet n'existe pas on l'ajoute en BDD directement en état "reinstalled"
             */
            $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, 'reinstalled', 'package', :date, :time, :id_event)");
        }
        $stmt->bindValue(':name', $this->packageName);
        $stmt->bindValue(':version', $this->packageVersion);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':time', $time);
        $stmt->bindValue(':id_event', $id_event);
        $stmt->execute();

        /**
         *  Enfin si le paquet était présent dans packages_available on le retire
         */
        $this->db_deletePackageAvailable($this->packageName, $this->packageVersion);
    }

/**
 *  
 *  Mises à jour en base de données
 * 
 */
    /**
     *  Mise à jour de l'OS en BDD
     */
    public function db_updateOS()
    {
        $stmt = $this->db->prepare("UPDATE hosts SET Os=:os WHERE AuthId=:authId AND Token=:token");
        $stmt->bindValue(':os', $this->os);
        $stmt->bindValue(':authId', $this->authId);
        $stmt->bindValue(':token', $this->token);
        $stmt->execute();

        return true;
    }

    /**
     *  Mise à jour de la version d'OS en BDD
     */
    public function db_updateOS_version()
    {
        $stmt = $this->db->prepare("UPDATE hosts SET Os_version=:os_version WHERE AuthId=:authId AND Token=:token");
        $stmt->bindValue(':os_version', $this->os_version);
        $stmt->bindValue(':authId', $this->authId);
        $stmt->bindValue(':token', $this->token);
        $stmt->execute();

        return true;
    }

    /**
     *  Mise à jour du profil en BDD
     */
    public function db_updateProfile()
    {
        $stmt = $this->db->prepare("UPDATE hosts SET Profile = :profile WHERE AuthId = :authId AND Token = :token");
        $stmt->bindValue(':profile', $this->profile);
        $stmt->bindValue(':authId', $this->authId);
        $stmt->bindValue(':token', $this->token);
        $stmt->execute();

        return true;
    }

    /**
     *  Mise à jour de l'env de l'hôte en BDD
     */
    public function db_updateEnv()
    {
        $stmt = $this->db->prepare("UPDATE hosts SET Env = :env WHERE AuthId = :authId AND Token = :token");
        $stmt->bindValue(':env', $this->env);
        $stmt->bindValue(':authId', $this->authId);
        $stmt->bindValue(':token', $this->token);
        $stmt->execute();

        return true;
    }

/**
 *  
 *  Suppression en base de données
 * 
 */
    /**
     *  Suppression d'un paquet dans la table packages_available
     */
    public function db_deletePackageAvailable(string $packageName, string $packageVersion)
    {
        $stmt = $this->host_db->prepare("DELETE FROM packages_available WHERE Name = :name AND Version = :version");
        $stmt->bindValue(':name', $packageName);
        $stmt->bindValue(':version', $packageVersion);
        $stmt->execute();
    }

/**
 *  
 *  Récupération d'informations en base de données
 * 
 */
    /**
     *  Récupère l'ID en BDD d'un hôte
     */
    public function db_getId()
    {
        /**
         *  Récupération à partir d'un id d'hôte et du token
         */
        if (!empty($this->authId) AND !empty($this->token)) {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId=:authId AND Token=:token");
            $stmt->bindValue(':authId', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $this->id = $row['Id'];

            return true;
        }

        return false;
    }

    /**
     *  Récupère toutes les informations de l'hôte à partir de son ID
     */
    public function db_getAll()
    {
        if (!is_numeric($this->id)) {
            printAlert("Erreur : l'ID spécifié n'est pas numérique", 'error');   
            return false;
        }

        $stmt = $this->db->prepare("SELECT * from hosts WHERE Id = :id");
        $stmt->bindValue(':id', $this->id);
        $result = $stmt->execute();

        $data = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $data = $row;

        return $data;
    }

    /**
     *  Récupère l'Id d'un package en BDD
     */
    public function db_getPackageId(string $packageName, string $packageVersion = null)
    {
        /**
         *  Récupération à partir du nom et de la version si les deux ont été fourni
         */
        if (!empty($packageName) AND !empty($packageVersion)) {
            $stmt = $this->host_db->prepare("SELECT Id FROM packages WHERE Name = :name AND Version = :version");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $id = $row['Id'];

            return $id;

        /**
         *  Sinon si on a fourni uniquement le nom du paquet à chercher
         */
        } elseif (!empty($packageName)) {
            $stmt = $this->host_db->prepare("SELECT Id FROM packages WHERE Name = :name");
            $stmt->bindValue(':name', $packageName);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $id = $row['Id'];
    
            return $id;
        }

        return false;
    }

    /**
     *  Ajoute un nouvel hôte en BDD
     *  Depuis l'interface web ou depuis l'API
     */
    public function register()
    {
        /**
         *  Lorsque appelé par l'api, HOSTS_DIR n'est pas setté, donc on le fait
         */
        if (!defined('HOSTS_DIR')) {
            define('HOSTS_DIR', ROOT.'/hosts');
        }

        /**
         *  Si on n'a pas renseigné l'IP ou le hostname alors on quitte
         */
        if (empty($this->ip) OR empty($this->hostname)) return 1;

        /**
         *  On vérifie que le hostname n'existe pas déjà en base de données
         *  Si le hostname existe, on vérifie l'état de l'hôte :
         *   - si celui-ci est 'deleted' alors on peut le réactiver
         *   - si celui-ci est 'active' alors on ne peut pas enregistrer de nouveau cet hôte
         */
        if ($this->hostnameExists($this->hostname) === true) {
            /**
             *  On récupère l'état de l'hôte en base de données
             */
            $stmt = $this->db->prepare("SELECT Status FROM hosts WHERE Hostname = :hostname");
            $stmt->bindValue(':hostname', $this->hostname);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $status = $row['Status'];

            if (empty($status)) return 5;

            /**
             *  Si l'hôte en base de données est 'active' alors on ne peut pas l'enregistrer de nouveau
             */
            if ($status == 'active') {
                return 3;
            }

            /**
             *  Sinon si l'hôte n'est pas 'actif', on set cette variable à 'yes' afin qu'il soit réactivé
             */
            $host_exists_and_is_unactive = 'yes';
        } 
        
        /**
         *  On génère un nouvel id d'authentification pour cet hôte
         */
        $this->authId = 'id_'.bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  On génère un nouveau token pour cet hôte
         */
        $this->token = bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  On tente un premier ping pour déterminer si l'hôte est accessible ou non
         *  Timeout de 2 secondes max
         */
        $testPing = exec("ping -c 1 -W 2 $this->hostname", $output, $testPingResult);
        if ($testPingResult == 0)
            $this->onlineStatus = 'online';
        else
            $this->onlineStatus = 'unreachable';

        /**
         *  Ajout en BDD
         *  Si il s'agit d'un nouvel enregistrement, on ajoute l'hôte en base de données
         *  Si l'hôte existe déjà en base de données mais est inactif alors on le réactive et on met à jour ses données
         */
        /**
         *  Cas où l'hôte existe déjà et qu'il faut le réactiver
         *  On met à jour ses données (réactivation et nouvel id et token)
         */
        if (!empty($host_exists_and_is_unactive) AND $host_exists_and_is_unactive = 'yes') {
            $stmt = $this->db->prepare("UPDATE hosts SET Ip = :ip, AuthId = :id, Token = :token, Online_status = :online_status, Online_status_date = :date, Online_status_time = :time, Status = 'active' WHERE Hostname = :hostname");
            $stmt->bindValue(':ip', $this->ip);
            $stmt->bindValue(':hostname', $this->hostname);
            $stmt->bindValue(':id', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $stmt->bindValue(':online_status', $this->onlineStatus);
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->execute();

        /**
         *  Cas où on ajoute l'hôte en base de données
         */
        } else {
            $stmt = $this->db->prepare("INSERT INTO hosts (Ip, Hostname, AuthId, Token, Online_status, Online_status_date, Online_status_time, Status) VALUES (:ip, :hostname, :id, :token, :online_status, :date, :time, 'active')");
            $stmt->bindValue(':ip', $this->ip);
            $stmt->bindValue(':hostname', $this->hostname);
            $stmt->bindValue(':id', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $stmt->bindValue(':online_status', $this->onlineStatus);
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->execute();

            /**
             *  Récupération de l'Id de l'hôte ajouté en BDD
             */
            $this->id = $this->db->lastInsertRowID();

            /**
             *  Création d'un répertoire dédié pour cet hôte, à partir de son ID
             *  Sert à stocker des rapport de mise à jour et une BDD pour l'hôte
             */
            if (!mkdir(HOSTS_DIR."/{$this->id}", 0770, true)) {
                if ($this->callFromApi == 'no') printAlert("Impossible de finaliser l'enregistrement de l'hôte", 'error');
                return 5;
            }

            /**
             *  On effectue une première ouverture de la BDD dédiée à cet hôte afin de générer les tables
             */
            $this->openHostDb($this->id, 'rw');
            $this->closeHostDb();

            /**
             *  Création d'un répertoire 'reports' pour cet hôte
             */
            if (!mkdir(HOSTS_DIR."/{$this->id}/reports", 0770, true)) {
                if ($this->callFromApi == 'no') printAlert("Impossible de finaliser l'enregistrement de l'hôte", 'error');
                return 5;
            }
        }

        return true;
    }

    /**
     *  Suppression d'un hôte
     *  Depuis l'interface web ou depuis l'API
     */
    public function unregister(array $hostsId = null)
    {
        /**
         *  Cas où on a renseigné 1 ou plusieurs hôtes depuis l'interface web
         */
        if (!empty($hostsId)) {
            $idError = array();
            $deleteError = array();
            $deleteOK = array();

            /**
             *  On traite l'array contenant les Id de l'hôte à supprimer
             */
            foreach ($hostsId as $hostId) {
                /**
                 *  Si l'Id de l'hôte n'est pas un chiffre, on enregistre son id dans $idError[] puis on passe à l'hôte suivant
                 */
                if (!is_numeric(validateData($hostId))) {
                    $idError[] = $hostId;
                    continue;
                }
        
                /**
                 *  D'abord on récupère l'IP et le hostname de l'hôte à mettre à jour
                 */
                $stmt = $this->db->prepare("SELECT Hostname, Ip FROM hosts WHERE Id = :id");
                $stmt->bindValue(':id', $hostId);
                $result = $stmt->execute();

                while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

                if (!empty($datas['Ip'])) $hostIp = $datas['Ip'];
                if (!empty($datas['Hostname'])) $hostHostname = $datas['Hostname'];

                /**
                 *  Suppression en BDD de l'hôte
                 */
                $stmt = $this->db->prepare("UPDATE hosts SET Status = 'deleted', AuthId = null, Token = null WHERE id = :id");
                $stmt->bindValue(':id', $hostId);
                $stmt->execute();

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($hostHostname)) {
                    $deleteOK[] = array('ip' => $hostIp, 'hostname' => $hostHostname);
                } else {
                    $deleteOK[] = array('ip' => $hostIp);
                }
            }

            /**
             *  Affichage d'un message de confirmation avec le nom/ip des hôtes dont la suppression a été effectuée
             */
            $message = '';
            foreach ($deleteOK as $hostDeleted) {
                if (!empty($hostDeleted['ip'])) {
                    $hostIp = $hostDeleted['ip'];
                }
                if (!empty($hostDeleted['hostname'])) {
                    $hostHostname = $hostDeleted['hostname'];
                }

                $message .= "$hostHostname ($hostIp)<br>";
            }

            printAlert("Suppression des hôtes suivants effectuée :<br>$message", 'success');

            return true;
        }

        /**
         *  Cas où on a renseigné un ID + un token (api)
         */
        if (!empty($this->authId) AND !empty($this->token)) {
            /**
             *  On vérifie que l'ip et le token correspondent bien à un hôte
             */
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :authId AND Token = :token");
            $stmt->bindValue(':authId', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $result = $stmt->execute();

            /**
             *  Si l'IP et le token ne correspondent à aucun hôte alors on quittes
             */
            if ($this->db->count($result) == 0) {
                return 2;
            }

            $stmt = $this->db->prepare("UPDATE hosts SET Status = 'deleted', AuthId = null, Token = null WHERE AuthId = :authId AND Token = :token");
            $stmt->bindValue(':authId', $this->authId);
            $stmt->bindValue(':token', $this->token);
        }

        $stmt->execute();

        if ($this->callFromApi == 'no') printAlert('Serveur supprimé', 'success');

        return true;
    }

    /**
     *  Demande à un hôte d'exécuter une mise à jour de paquets
     */
    public function update(array $hostsId)
    {
        $idError = array();
        $updateError = array();
        $updateOK = array();

        /**
         *  On traite l'array contenant les Id de l'hôte à mettre à jour
         */
        foreach ($hostsId as $hostId) {
            $this->setId(validateData($hostId));

            /**
             *  Si l'Id de l'hôte n'est pas un chiffre, on enregistre son id dans $idError[] puis on passe à l'hôte suivant
             */
            if (!is_numeric($this->id)) {
                $idError[] = $this->id;
                continue;
            }
    
            /**
             *  D'abord on récupère l'IP et le hostname de l'hôte à mettre à jour
             */
            $stmt = $this->db->prepare("SELECT Hostname, Ip FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

            /**
             *  Si l'ip récupérée est vide, on passe à l'hôte suivant
             */
            if (empty($datas['Ip'])) {
                continue;
            }
            $this->setIp($datas['Ip']);

            if (!empty($datas['Hostname'])) {
                $this->setHostname($datas['Hostname']);
            }

            /**
             *  Envoi d'un ping avec le message 'update-requested' en hexadecimal pour ordonner à l'hôte de se mettre à jour
             */
            exec("ping -W2 -c 1 -p 7570646174652d726571756573746564 $this->ip", $output, $pingResult);
            if ($pingResult != 0) {
                printAlert("Impossible d'envoyer la demande de mise à jour à l'hôte (injoignable)", 'error');
                return false;
            }

            /**
             *  Ouverture de la base de données de l'hôte
             */
            $this->openHostDb($this->id, 'rw');

            /**
             *  Modification de l'état en BDD pour cet hôte (pending = demande envoyée, en attente)
             */
            $stmt = $this->host_db->prepare("INSERT INTO updates_requests ('Date', 'Time', 'Status') VALUES (:date, :time, 'requested')");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->execute();

            /**
             *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
             */
            if (!empty($this->hostname)) {
                $updateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
            } else {
                $updateOK[] = array('ip' => $this->ip);
            }
        }

        /**
         *  Affichage d'un message de confirmation avec le nom/ip des hôtes dont la mise à jour a été demandée
         */
        $message = '';

        foreach ($updateOK as $hostUpdated) {
            if (!empty($hostUpdated['ip'])) {
                $hostIp = $hostUpdated['ip'];
            }
            if (!empty($hostUpdated['hostname'])) {
                $hostHostname = $hostUpdated['hostname'];
            }
            $message .= "$hostHostname ($hostIp)<br>";
        }

        printAlert("Demande de mise à jour envoyée aux hôtes suivants :<br>$message", 'success');

        return true;
    }

    /**
     *  Vérifie que le couple ID/token est valide
     */
    public function checkIdToken()
    {
        /**
         *  Si l'ID ou le token est manquant alors on quittes
         */
        if (empty($this->authId) OR empty($this->token)) return false;

        /**
         *  D'abord on vérifie qu'un hôte avec l'ip et le token correspondant existe bien
         */
        $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :hostId AND Token = :token");
        $stmt->bindValue(':hostId', $this->authId);
        $stmt->bindValue(':token', $this->token);
        $result = $stmt->execute();

        /**
         *  Si l'ID et le token ne correspondent à aucune ligne en BDD alors on quitte
         */
        if ($this->db->isempty($result)) return false;

        return true;
    }

    /**
     *  Mise à jour de l'inventaire des paquets installés sur l'hôte en BDD
     */
    public function db_setPackagesInventory(string $packagesInventory)
    {
        /**
         *  Si la liste des paquets est vide, on ne peut pas continuer
         */
        if (empty($packagesInventory)) return false;

        /**
         *  Si l'Id de l'hôte en BDD est vide, on ne peut pas continuer (utile pour ouvrir sa BDD)
         */
        if (empty($this->id)) return false;

        /**
         *  Les paquets sont transmis sous forme de chaine, séparés par une virgule. On explode cette chaine en array et on retire les entrées vides.
         */
        $packagesList = array_filter(explode(",", validateData($packagesInventory)));

        /**
         *  On traite si l'array n'est pas vide
         */
        if (!empty($packagesList)) {

            /**
             *  Ouverture de la BDD dédiée de l'hôte
             */
            $this->openHostDb($this->id, 'rw');

            foreach ($packagesList as $packageDetails) {
                /**
                 *  Chaque ligne contient le nom du paquet, sa version et sa description séparés par un | (ex : nginx|xxx-xxxx|nginx description)
                 */
                $packageDetails = explode('|', $packageDetails);

                /**
                 *  Récupération du nom du paquet, si celui-ci est vide alors on passe au suivant
                 */
                if (empty($packageDetails[0])) continue;
                $this->setPackageName($packageDetails[0]);

                /**
                 *  Version du paquet
                 */
                if (!empty($packageDetails[1])) 
                    $this->setPackageVersion($packageDetails[1]);
                else
                    $this->setPackageVersion('unknow');

                /**
                 *  Insertion en BDD
                 *  On vérifie d'abord si le paquet (son nom) existe en BDD
                 */
                if ($this->packageExists($this->packageName) === false) {
                    /**
                     *  Si il n'existe pas on l'ajoute en BDD (sinon on ne fait rien)
                     */
                    $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time') VALUES (:name, :version, 'inventored', 'package', :date, :time)");
                    $stmt->bindValue(':name', $this->packageName);
                    $stmt->bindValue(':version', $this->packageVersion);
                    $stmt->bindValue(':date', date('Y-m-d'));
                    $stmt->bindValue(':time', date('H:i:s'));
                    $stmt->execute();
                }
            }

            /**
             *  Fermeture de la BDD
             */
            $this->closeHostDb();
        }      
        
        return true;
    }

    /**
     *  Mise à jour de l'état des paquets disponibles (à mettre à jour) sur l'hôte en BDD
     */
    public function db_setPackagesAvailable(string $packagesAvailable)
    {
        /**
         *  Si la liste des paquets est vide, on ne peut pas continuer
         */
        if (empty($packagesAvailable)) return false;

        /**
         *  Si l'Id de l'hôte en BDD est vide, on ne peut pas continuer (utile pour ouvrir sa BDD)
         */
        if (empty($this->id)) return false;

        /**
         *  Les paquets sont transmis sous forme de chaine, séparés par une virgule. On explode cette chaine en array et on retire les entrées vides.
         */
        $packagesList = array_filter(explode(",", validateData($packagesAvailable)));

        /**
         *  On traite si l'array n'est pas vide
         */
        if (!empty($packagesList)) {

            /**
             *  Ouverture de la BDD dédiée de l'hôte
             */
            $this->openHostDb($this->id, 'rw');

            /**
             *  On efface la liste des paquets actuellement dans packages_available
             */
            $this->host_db->exec("DELETE FROM packages_available");
            /**
             *  Nettoie l'espace inutilisé suite à la suppression du contenu de la table packages_available
             */
            $this->host_db->exec("VACUUM");

            foreach ($packagesList as $packageDetails) {
                /**
                 *  Chaque ligne contient le nom du paquet, sa version et sa description séparés par un | (ex : nginx|xxx-xxxx|nginx description)
                 */
                $packageDetails = explode('|', $packageDetails);

                /**
                 *  Récupération du nom du paquet, si celui-ci est vide alors on passe au suivant
                 */
                if (empty($packageDetails[0])) continue;
                $this->setPackageName($packageDetails[0]);

                /**
                 *  Version du paquet
                 */
                if (!empty($packageDetails[1])) 
                    $this->setPackageVersion($packageDetails[1]);
                else
                    $this->setPackageVersion('unknow');

                /**
                 *  Si le paquet existe déjà dans packages_available alors on le met à jour (la version a peut être changée)
                 */
                if ($this->packageAvailableExists($this->packageName) === true) {
                    /**
                     *  Si il existe en BDD, on vérifie aussi la version présente en BDD.
                     *  Si la version en BDD est différente alors on met à jour le paquet en BDD, sinon on ne fait rien.
                     */
                    if ($this->packageVersionAvailableExists($this->packageName, $this->packageVersion) === true) {
                        $stmt = $this->host_db->prepare("UPDATE packages_available SET Version = :version WHERE Name = :name");
                        $stmt->bindValue(':name', $this->packageName);
                        $stmt->bindValue(':version', $this->packageVersion);
                        $stmt->execute();
                    }

                } else {
                    /**
                     *  Si le paquet n'existe pas on l'ajoute en BDD
                     */
                    $stmt = $this->host_db->prepare("INSERT INTO packages_available ('Name', 'Version') VALUES (:name, :version)");
                    $stmt->bindValue(':name', $this->packageName);
                    $stmt->bindValue(':version', $this->packageVersion);
                    $stmt->execute();
                }
            }
        }      
        
        return true;
    }

    /**
     *  Ajout de l'historique des évènements relatifs aux paquets (installation, mise à jour, etc...) d'un hôte en base de données
     */
    public function setEventsFullHistory(array $history) {
        /**
         *  Si il manque l'id de l'hôte, on quitte car on en a besoin pour ouvrir sa BDD dédiée
         */
        if (empty($this->id)) return false;

        /**
         *  Ouverture de la BDD dédiée de l'hôte
         */
        $this->openHostDb($this->id, 'rw');

        /**
         *  Chaque évènement est constitué d'une date et heure de début et de fin
         *  Puis d'une liste de paquets installés, mis à jour ou désinstallé..
         *  Exemple :
         *  "date_start":"2021-12-07",
         *  "date_end":"2021-12-07",
         *  "time_start":"17:32:45",
         *  "time_end":"17:34:49",
         *  "upgraded":[
         *    {
         *      "name":"bluez",
         *      "version":"5.48-0ubuntu3.5"
         *    }
         *  ]
         */

        foreach ($history as $event) {
            $event->date_start;
            $event->date_end;
            $event->time_start;
            $event->time_end;

            /**
             *  On vérifie qu'un évènement de la même date et de la même heure n'existe pas déjà, sinon on l'ignore et on passe au suivant
             */
            $stmt = $this->host_db->prepare("SELECT Id FROM events WHERE Date = :date_start AND Time = :time_start");
            $stmt->bindValue(':date_start', $event->date_start);
            $stmt->bindValue(':time_start', $event->time_start);
            $result = $stmt->execute();
            if ($this->host_db->isempty($result) === false) {
                continue;
            }

            /**
             *  Insertion de l'évènement en base de données
             */
            $stmt = $this->host_db->prepare("INSERT INTO events ('Date', 'Date_end', 'Time', 'Time_end', 'Status') VALUES (:date_start, :date_end, :time_start, :time_end, 'done')");
            $stmt->bindValue(':date_start', $event->date_start);
            $stmt->bindValue(':date_end', $event->date_end);
            $stmt->bindValue(':time_start', $event->time_start);
            $stmt->bindValue(':time_end', $event->time_end);
            $stmt->execute();

            /**
             *  Récupération de l'Id inséré en BDD
             */
            $id_event = $this->host_db->lastInsertRowID();

            /**
             *  Si l'évènement comporte des paquets installés
             */
            if (!empty($event->installed)) {
                foreach ($event->installed as $package_installed) {
                    $this->db_setPackageInstalled($package_installed->name, $package_installed->version, $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets mis à jour
             */
            if (!empty($event->upgraded)) {
                foreach ($event->upgraded as $package_upgraded) {
                    $this->db_setPackageUpgraded($package_upgraded->name, $package_upgraded->version, $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets désinstallés
             */
            if (!empty($event->removed)) {
                foreach ($event->removed as $package_removed) {
                    $this->db_setPackageRemoved($package_removed->name, $package_removed->version, $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets rétrogradés
             */
            if (!empty($event->downgraded)) {
                foreach ($event->downgraded as $package_downgraded) {
                    $this->db_setPackageDowngraded($package_downgraded->name, $package_downgraded->version, $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets réinstallés
             */
            if (!empty($event->reinstalled)) {
                foreach ($event->reinstalled as $package_reinstalled) {
                    $this->db_setPackageReinstalled($package_reinstalled->name, $package_reinstalled->version, $event->date_start, $event->time_start, $id_event);
                }
            }
        }

        return true;
    }


/**
 *  Fonctions de listage
 */
    /**
     *  Liste tous les hôtes
     */
    public function listAll(string $status)
    {
        if ($status == 'active') {
            $result = $this->db->query("SELECT * FROM hosts WHERE Status = 'active'");
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

        return $datas;
    }

/**
 *  
 *  Vérifications de l'existence de données en base de données
 * 
 */
    /**
     *  Vérifie si l'Ip existe en BDD parmis les hôtes actifs
     */
    private function ipExists(string $ip)
    {
        $stmt = $this->db->prepare("SELECT Ip FROM hosts WHERE Ip = :ip AND Status = 'active'");
        $stmt->bindValue(':ip', validateData($ip));
        $result = $stmt->execute();

        if ($this->db->isempty($result) === true) return false;
        
        return true;
    }

    /**
     *  Vérifie si le hostname de l'hôte existe en BDD
     */
    private function hostnameExists(string $hostname)
    {
        $stmt = $this->db->prepare("SELECT Hostname FROM hosts WHERE Hostname = :hostname");
        $stmt->bindValue(':hostname', validateData($hostname));
        $result = $stmt->execute();

        if ($this->db->isempty($result) === true) return false;

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet dans la table packages
     */
    public function packageExists(string $packageName)
    {
        $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Name = :name");
        $stmt->bindValue(':name', validateData($packageName));
        $result = $stmt->execute();

        if ($this->host_db->isempty($result) === true) return false;

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet et sa version dans la table package
     */
    public function packageVersionExists(string $packageName, string $packageVersion)
    {
        $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Name=:name AND Version=:version");
        $stmt->bindValue(':name', $packageName);
        $stmt->bindValue(':version', $packageVersion);
        $result = $stmt->execute();

        if ($this->host_db->isempty($result) === true) return false;

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet dans la table packages_available
     */
    public function packageAvailableExists(string $packageName)
    {
        $stmt = $this->host_db->prepare("SELECT * FROM packages_available WHERE Name = :name");
        $stmt->bindValue(':name', $packageName);
        $result = $stmt->execute();

        if ($this->host_db->isempty($result) === true) return false;

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet et sa version dans la table package_available
     */
    public function packageVersionAvailableExists(string $packageName, string $packageVersion)
    {
        $stmt = $this->host_db->prepare("SELECT * FROM packages_available WHERE Name = :name AND Version = :version");
        $stmt->bindValue(':name', $packageName);
        $stmt->bindValue(':version', $packageVersion);
        $result = $stmt->execute();

        if ($this->host_db->isempty($result) === true) return false;

        return true;
    }

/**
 *  
 *  Récupération de données en base de données
 * 
 */
    /**
     *  Récupère la liste des paquets présents sur l'hôte
     */
    public function getPackagesInventory()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) return false;

        /**
         *  Récupération du total des paquets installés sur l'hôte
         */
        $datas = array();

        $result = $this->host_db->query("SELECT * FROM packages");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

        return $datas;
    }

    /**
     *  Retourne le nombre de paquets installés sur l'hôte
     */
    public function getPackagesInstalledCount()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) return false;

        /**
         *  Récupération du total des paquets installés sur l'hôte
         */
        $datas = array();

        $result = $this->host_db->query("SELECT * FROM packages WHERE State = 'inventored' OR State = 'installed' OR State = 'upgraded' OR State = 'downgraded'");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

        return count($datas);
    }

    /**
     *  Récupère la liste des paquets disponibles pour mise à jour sur l'hôte
     */
    public function getPackagesAvailable()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
        */
        if (empty($this->host_db)) return false;

        /**
         *  Récupération du total des paquets installés sur l'hôte
         */
        $datas = array();

        $result = $this->host_db->query("SELECT * FROM packages_available");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

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
        if (empty($this->host_db)) return false;

        $datas = array();

        $result = $this->host_db->query("SELECT * FROM updates_requests ORDER BY Date DESC, Time DESC");
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
        if (empty($this->host_db)) return false;

        $datas = array();

        $result = $this->host_db->query("SELECT * FROM events ORDER BY Date DESC, Time DESC");

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
        if (empty($this->host_db)) return false;

        $stmt = $this->host_db->prepare("SELECT * FROM packages        
        WHERE Id_event = :eventId AND State = :packageState
        UNION
        SELECT * FROM packages_history       
        WHERE Id_event = :eventId AND State = :packageState");
        $stmt->bindValue(':eventId', validateData($eventId));
        $stmt->bindValue(':packageState', validateData($packageState));
        $result = $stmt->execute();

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

        return $datas;
    }

    /**
     *  Récupère le détails d'un évènement sur un type de paquets en particulier (installés, mis à jour, etc...)
     *  Cette fonction est notamment déclenchée au passage de la souris sur une ligne de l'historique des évènements
     */
    public function getEventDetails(string $eventId, string $packageState)
    {
        /**
         *  Si il manque l'id de l'hôte, on quitte car on en a besoin pour ouvrir sa BDD dédiée
         */
        if (empty($this->id)) return false;

        /**
         *  Ouverture de la BDD dédiée de l'hôte
         */
        $this->openHostDb($this->id, 'rw');

        try {
            //$stmt = $this->host_db->prepare("SELECT Name, Version FROM packages, packages_history WHERE Id_event = :id_event AND State = :state");
            $stmt = $this->host_db->prepare("SELECT Name, Version FROM packages
            WHERE Id_event = :id_event AND State = :state
            UNION
            SELECT Name, Version FROM packages_history
            WHERE Id_event = :id_event AND State = :state");
            $stmt->bindValue(':id_event', validateData($eventId));
            $stmt->bindValue(':state', validateData($packageState));
            $result = $stmt->execute();
        } catch(Exception $e) {
            throw new Exception('');
        }

        $packageState = validateData($packageState);
        if ($packageState == 'installed')  $content = '<span><b>Installé(s) :</b></span><br>';
        if ($packageState == 'upgraded')   $content = '<span><b>Mis à jour :</b></span><br>';
        if ($packageState == 'removed')    $content = '<span><b>Désinstallé(s) :</b></span><br>';
        if ($packageState == 'downgraded') $content = '<span><b>Rétrogradé(s) :</b></span><br>';

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $content .= '<span>• <b>'.$row['Name']. '</b> ('.$row['Version'].')</span><br>';
        } 

        $this->host_db->close();

        return $content;
    }

    /**
     *  Récupère l'historique complet d'un paquet (son installation, ses mises à jour, etc...)
     */
    public function getPackageTimeline($packageName)
    {
        /**
         *  Si il manque l'id de l'hôte, on quitte car on en a besoin pour ouvrir sa BDD dédiée
         */
        if (empty($this->id)) return false;

        /**
         *  Ouverture de la BDD dédiée de l'hôte
         */
        $this->openHostDb($this->id, 'rw');

        $events = array();

        /**
         *  Récupération de l'historique du paquet (table packages_history) ainsi que son état actuel (table packages)
         */
        $stmt = $this->host_db->prepare("SELECT * FROM packages_history
        WHERE Name = :packagename
        UNION SELECT * FROM packages
        WHERE Name = :packagename
        ORDER BY Date DESC, Time DESC");
        $stmt->bindValue(':packagename', $packageName);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $events[] = $row;

        /**
         *  On forge la timeline à afficher et on la renvoie au controlleur ajax car c'est jquery qui se chargera de l'afficher ensuite
         */
        $content = '<h4>HISTORIQUE DU PAQUET '.strtoupper($packageName).'</h4>';
        $content .='<div class="timeline">';

        /**
         *  Le premier bloc sera affiché à gauche dans la timeline
         */
        $content_position = 'left';
    
        foreach ($events as $event) {
            /**
             *  Ajout de la date, l'heure et l'état du paquet
             */
            if ($event['State'] == "inventored") {
                $content_color = 'gray';
                $content_text = '<img src="../ressources/icons/products/package.png" class="icon" /> Inventorié';
            }
            if ($event['State'] == "installed") {
                $content_color = 'green';
                $content_text = 'Installé';
            }
            if ($event['State'] == "upgraded") {
                $content_color = 'blue';
                $content_text = '<img src="../ressources/icons/update.png" class="icon" /> Mis à jour';
            }
            if ($event['State'] == "removed") {
                $content_color = 'red';
                $content_text = 'Désinstallé';
            }
            if ($event['State'] == "downgraded") {
                $content_color = 'yellow';
                $content_text = '<img src="../ressources/icons/arrow-back.png" class="icon" /> Rétrogradé';
            }
            $content_version = $event['Version'];

            /**
             *  Position du bloc container dans la timeline en fonction du dernier affiché
             */
            if ($content_position == 'left')  $content .= '<div class="timeline-container timeline-container-'.$content_color.'-left">';
            if ($content_position == 'right') $content .= '<div class="timeline-container timeline-container-'.$content_color.'-right">';

            $content .= '<div class="timeline-container-content-'.$content_color.'">';
                $content .= '<span class="timeline-event-date">'.DateTime::createFromFormat('Y-m-d', $event['Date'])->format('d-m-Y').' à '.$event['Time'].'</span>';
                /**
                 *  Si cet évènement a pour origine un évènement de mise à jour, d'installation ou de désintallation, alors on ondique l'Id de l'évènement
                 */
                if (!empty($event['Id_event'])) {
                    $content .= '<span class="timeline-event-text"><a href="#'.$event['Id_event'].'" >'.$content_text.'</a></span>';
                } else {
                    $content .= '<span class="timeline-event-text">'.$content_text.'</span>';
                }
                $content .= '<span class="timeline-event-version">En version : <b>'.$content_version.'</b></span>';
            $content .= '</div>';

            if ($content_position == "left") {
                $content_position = 'right';
            } elseif ($content_position == "right") {
                $content_position = 'left';
            }

            $content .= '</div>';
        }

        /**
         *  Fermeture de la timeline
         */
        $content .= '</div>';

        return $content;
    }

    /**
     *  Récupère les informations concernant la dernière mise à jour exécutée sur l'hôte
     */
    public function getLastUpdateStatus()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) return false;

        $datas = array();

        $result = $this->host_db->query("SELECT Date, Time, Status FROM events ORDER BY Id DESC LIMIT 1");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

        $result = $this->host_db->query("SELECT Date, Time, Status FROM updates_requests ORDER BY Id DESC LIMIT 1");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

        /**
         *  Parmis les deux arrays récupérées, on tri par date pour avoir le + récent en haut
         */
        array_multisort(array_column($datas, 'Date'), SORT_DESC, array_column($datas, 'Time'), SORT_DESC, $datas);
   
        /**
         *  On ne retourne uniquement le 1er array (le plus récent)
         */
        return $datas[0];
    }

}