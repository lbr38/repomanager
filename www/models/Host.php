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
        $this->getConnection('hosts');
    }

    public function setId(string $id)
    {
        $this->id = Common::validateData($id);
    }

    public function setIp(string $ip)
    {
        $this->ip = Common::validateData($ip);
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = Common::validateData($hostname);
    }

    public function setOS(string $os)
    {
        $this->os = Common::validateData($os);
    }

    public function setOS_version(string $os_version)
    {
        $this->os_version = Common::validateData($os_version);
    }

    public function setProfile(string $profile)
    {
        $this->profile = Common::validateData($profile);
    }

    public function setEnv(string $env)
    {
        $this->env = Common::validateData($env);
    }

    /**
     *  Défini toutes les propriétés de l'hôte à partir de son Id et des informations correspondantes en base de données
     */
    public function setAll(string $id)
    {
        if (!is_numeric($id)) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("SELECT * from hosts WHERE Id = :id");
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

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
        $this->packageId = Common::validateData($packageId);
    }

    public function setPackageName(string $packageName)
    {
        $this->packageName = Common::validateData($packageName);
    }

    public function setPackageVersion(string $packageVersion)
    {
        $this->packageVersion = Common::validateData($packageVersion);
    }

    public function setPackagesInventory(string $packages_inventory)
    {
        $this->packages_inventory = Common::validateData($packages_inventory);
    }

    public function setAuthId(string $authId)
    {
        $this->authId = Common::validateData($authId);
    }

    public function setToken(string $token)
    {
        $this->token = Common::validateData($token);
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
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Id = :packageId");
            $stmt->bindValue(':packageId', $packageId);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

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
        try {
            $stmt = $this->host_db->prepare("INSERT INTO packages_history ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, :state, :type, :date, :time, :id_event)");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $stmt->bindValue(':state', $packageState);
            $stmt->bindValue(':type', $packageType);
            $stmt->bindValue(':date', $packageDate);
            $stmt->bindValue(':time', $packageTime);
            $stmt->bindValue(':id_event', $package_id_event);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        return true;
    }

    /**
     *  Ajout d'un état de paquet en BDD
     */
    public function db_setPackageState(string $name, string $version, string $state, string $date, string $time, string $id_event = null)
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
             *
             *  Récupération de l'Id du paquet au préalable
             */
            $this->setPackageId($this->db_getPackageId($this->packageName));

            /**
             *  Sauvegarde de l'état actuel
             */
            $this->db_setPackageHistory($this->packageId);

            /**
             *  Puis on met à jour l'état du paquet et sa version en base par les infos qui ont été transmises
             */
            try {
                $stmt = $this->host_db->prepare("UPDATE packages SET Version = :version, Date = :date, Time = :time, State = :state, Id_event = :id_event WHERE Name = :name");
            } catch(Exception $e) {
                Common::dbError($e);
            }

        } else {
            /**
             *  Si le paquet n'existe pas on l'ajoute en BDD directement dans l'état spécifié (installed, upgraded, removed...)
             */
            try {
                $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, :state, 'package', :date, :time, :id_event)");
            } catch(Exception $e) {
                Common::dbError($e);
            }
        }

        try {
            $stmt->bindValue(':name', $this->packageName);
            $stmt->bindValue(':version', $this->packageVersion);
            $stmt->bindValue(':state', $state);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':id_event', $id_event);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Enfin si le paquet et sa version était présent dans packages_available on le retire
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
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os=:os WHERE AuthId=:authId AND Token=:token");
            $stmt->bindValue(':os', $this->os);
            $stmt->bindValue(':authId', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        return true;
    }

    /**
     *  Mise à jour de la version d'OS en BDD
     */
    public function db_updateOS_version()
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os_version=:os_version WHERE AuthId=:authId AND Token=:token");
            $stmt->bindValue(':os_version', $this->os_version);
            $stmt->bindValue(':authId', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        return true;
    }

    /**
     *  Mise à jour du profil en BDD
     */
    public function db_updateProfile()
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Profile = :profile WHERE AuthId = :authId AND Token = :token");
            $stmt->bindValue(':profile', $this->profile);
            $stmt->bindValue(':authId', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        return true;
    }

    /**
     *  Mise à jour de l'env de l'hôte en BDD
     */
    public function db_updateEnv()
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Env = :env WHERE AuthId = :authId AND Token = :token");
            $stmt->bindValue(':env', $this->env);
            $stmt->bindValue(':authId', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

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
        try {
            $stmt = $this->host_db->prepare("DELETE FROM packages_available WHERE Name = :name AND Version = :version");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }
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
            try {
                $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId=:authId AND Token=:token");
                $stmt->bindValue(':authId', $this->authId);
                $stmt->bindValue(':token', $this->token);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

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
            Common::printAlert("Erreur : l'ID spécifié n'est pas numérique", 'error');   
            return false;
        }

        try {
            $stmt = $this->db->prepare("SELECT * from hosts WHERE Id = :id");
            $stmt->bindValue(':id', $this->id);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

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
            try {
                $stmt = $this->host_db->prepare("SELECT Id FROM packages WHERE Name = :name AND Version = :version");
                $stmt->bindValue(':name', $packageName);
                $stmt->bindValue(':version', $packageVersion);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $id = $row['Id'];

            return $id;

        /**
         *  Sinon si on a fourni uniquement le nom du paquet à chercher
         */
        } elseif (!empty($packageName)) {
            try {
                $stmt = $this->host_db->prepare("SELECT Id FROM packages WHERE Name = :name");
                $stmt->bindValue(':name', $packageName);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) $id = $row['Id'];
    
            return $id;
        }

        return false;
    }

    /**
     *  Ajoute un nouvel hôte en BDD
     *  Depuis l'interface web ou depuis l'API
     */
    public function api_register()
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
            try {
                $stmt = $this->db->prepare("SELECT Status FROM hosts WHERE Hostname = :hostname");
                $stmt->bindValue(':hostname', $this->hostname);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

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
            try {
                $stmt = $this->db->prepare("UPDATE hosts SET Ip = :ip, AuthId = :id, Token = :token, Online_status = :online_status, Online_status_date = :date, Online_status_time = :time, Status = 'active' WHERE Hostname = :hostname");
                $stmt->bindValue(':ip', $this->ip);
                $stmt->bindValue(':hostname', $this->hostname);
                $stmt->bindValue(':id', $this->authId);
                $stmt->bindValue(':token', $this->token);
                $stmt->bindValue(':online_status', $this->onlineStatus);
                $stmt->bindValue(':date', date('Y-m-d'));
                $stmt->bindValue(':time', date('H:i:s'));
                $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

        /**
         *  Cas où on ajoute l'hôte en base de données
         */
        } else {
            try {
                $stmt = $this->db->prepare("INSERT INTO hosts (Ip, Hostname, AuthId, Token, Online_status, Online_status_date, Online_status_time, Status) VALUES (:ip, :hostname, :id, :token, :online_status, :date, :time, 'active')");
                $stmt->bindValue(':ip', $this->ip);
                $stmt->bindValue(':hostname', $this->hostname);
                $stmt->bindValue(':id', $this->authId);
                $stmt->bindValue(':token', $this->token);
                $stmt->bindValue(':online_status', $this->onlineStatus);
                $stmt->bindValue(':date', date('Y-m-d'));
                $stmt->bindValue(':time', date('H:i:s'));
                $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            /**
             *  Récupération de l'Id de l'hôte ajouté en BDD
             */
            $this->id = $this->db->lastInsertRowID();

            /**
             *  Création d'un répertoire dédié pour cet hôte, à partir de son ID
             *  Sert à stocker des rapport de mise à jour et une BDD pour l'hôte
             */
            if (!mkdir(HOSTS_DIR."/{$this->id}", 0770, true)) {
                if ($this->callFromApi == 'no') Common::printAlert("Impossible de finaliser l'enregistrement de l'hôte", 'error');
                return 5;
            }

            /**
             *  On effectue une première ouverture de la BDD dédiée à cet hôte afin de générer les tables
             */
            $this->openHostDb($this->id);
            $this->closeHostDb();

            /**
             *  Création d'un répertoire 'reports' pour cet hôte
             */
            if (!mkdir(HOSTS_DIR."/{$this->id}/reports", 0770, true)) {
                if ($this->callFromApi == 'no') Common::printAlert("Impossible de finaliser l'enregistrement de l'hôte", 'error');
                return 5;
            }
        }

        return true;
    }

    /**
     *  Suppression d'un hôte depuis l'api
     */
    public function api_unregister()
    {
        /**
         *  On vérifie que l'ip et le token correspondent bien à un hôte, si ce n'est pas le cas on quitte
         */
        if ($this->checkIdToken() === false) {
            return 2;
        }

        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Status = 'deleted', AuthId = null, Token = null WHERE AuthId = :authId AND Token = :token");
            $stmt->bindValue(':authId', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        return true;
    }

    public function api_setUpdateRequestStatus(string $type, string $status)
    {
        $type = Common::validateData($type);
        $status = Common::validateData($status);

        /**
         *  On vérifie que l'action spécifiée par l'hôte est valide
         */
        if ($type != 'packages-update' AND $type != 'general-status-update' AND $type != 'available-packages-status-update' AND $type != 'installed-packages-status-update') {
            return false;
        }

        /**
         *  On vérifie que le status spécifié par l'hôte est valide
         */
        if ($status != 'running' AND $status != 'done') {
            return false;
        }

        /**
         *  Ouverture de la base de données de l'hôte
         */
        $this->openHostDb($this->id);

        try {
            $stmt = $this->host_db->prepare("UPDATE updates_requests SET Status = :status WHERE Type = :type AND Status = 'requested' OR Status = 'running'");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':type', $type);
            $stmt->execute();
        } catch(Exception $e) {
            return false;
        }

        return true;
    }

    /**
     *  Demande à un ou plusieurs hôte(s) d'exécuter une action
     *  - update
     *  - reset
     *  - delete
     */
    public function hostExec(array $hostsId, string $action)
    {
        /**
         *  On vérifie que l'action est valide
         */
        if ($action != 'delete' AND 
            $action != 'reset' AND 
            $action != 'update' AND 
            $action != 'general-status-update' AND
            $action != 'available-packages-status-update' AND
            $action != 'installed-packages-status-update' AND
            $action != 'full-history-update') {
            throw new Exception("L'action à exécuter est invalide");
        }

        $hostIdError                      = array();
        $hostUpdateError                  = array();
        $hostUpdateOK                     = array();
        $hostResetError                   = array();
        $hostResetOK                      = array();
        $hostDeleteError                  = array();
        $hostDeleteOK                     = array();
        $hostGeneralUpdateError           = array();
        $hostGeneralUpdateOK              = array();
        $hostAvailPackagesUpdateError     = array();
        $hostAvailPackagesUpdateOK        = array();
        $hostInstalledPackagesUpdateError = array();
        $hostInstalledPackagesUpdateOK    = array();
        $hostFullHistoryUpdateError       = array();
        $hostFullHistoryUpdateOK          = array();

        /**
         *  On traite l'array contenant les Id d'hôtes à traiter
         */
        foreach ($hostsId as $hostId) {
            $this->setId(Common::validateData($hostId));

            /**
             *  Si l'Id de l'hôte n'est pas un chiffre, on enregistre son id dans $hostIdError[] puis on passe à l'hôte suivant
             */
            if (!is_numeric($this->id)) {
                $hostIdError[] = $this->id;
                continue;
            }
    
            /**
             *  D'abord on récupère l'IP et le hostname de l'hôte à traiter
             */
            try {
                $stmt = $this->db->prepare("SELECT Hostname, Ip FROM hosts WHERE Id = :id");
                $stmt->bindValue(':id', $this->id);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

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
             *  Ouverture de la base de données de l'hôte
             */
            $this->openHostDb($this->id);

            /**
             *  Cas où l'action demandée est une mise à jour
             */
            if ($action == 'update') {
                /**
                 *  Envoi d'un ping avec le message 'r-update-pkgs' en hexadecimal pour ordonner à l'hôte de se mettre à jour
                 */
                exec("ping -W2 -c 1 -p 722d7570646174652d706b6773 $this->ip");

                /**
                 *  Modification de l'état en BDD pour cet hôte (requested = demande envoyée, en attente)
                 */
                try {
                    $stmt = $this->host_db->prepare("INSERT INTO updates_requests ('Date', 'Time', 'Type', 'Status') VALUES (:date, :time, 'packages-update', 'requested')");
                    $stmt->bindValue(':date', date('Y-m-d'));
                    $stmt->bindValue(':time', date('H:i:s'));
                    $stmt->execute();
                } catch(Exception $e) {
                    Common::dbError($e);
                }

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostUpdateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostUpdateOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action est un reset de l'hôte
             */
            if ($action == 'reset') {
                /**
                 *  Reset de certaines informations générales de l'hôte
                 */
                try {
                    $stmt = $this->db->prepare("UPDATE hosts SET Os = null, Os_version = null, Profile = null, Env = null WHERE id = :id");
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

                } catch(Exception $e) {
                    Common::dbError($e);
                }

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostResetOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostResetOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action est une suppression de l'hôte
             */
            if ($action == 'delete') {
                /**
                 *  Passage de l'hôte en état 'deleted' en BDD
                 */
                try {
                    $stmt = $this->db->prepare("UPDATE hosts SET Status = 'deleted', AuthId = null, Token = null WHERE id = :id");
                    $stmt->bindValue(':id', $hostId);
                    $stmt->execute();
                } catch(Exception $e) {
                    Common::dbError($e);
                }

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostDeleteOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostDeleteOK[] = array('ip' => $this->ip);
                }
            }
           
            /**
             *  Si l'action correspond à l'une des suivantes, on ajoute une entrée dans la base de données de l'hôte
             */
            if ($action == 'general-status-update' OR
                $action == 'available-packages-status-update' OR
                $action == 'installed-packages-status-update' OR
                $action == 'full-history-update') {

                /**
                 *  Modification de l'état en BDD pour cet hôte (requested = demande envoyée, en attente)
                 */
                try {
                    $stmt = $this->host_db->prepare("INSERT INTO updates_requests ('Date', 'Time', 'Type', 'Status') VALUES (:date, :time, :action, 'requested')");
                    $stmt->bindValue(':date', date('Y-m-d'));
                    $stmt->bindValue(':time', date('H:i:s'));
                    $stmt->bindValue(':action', $action);
                    $stmt->execute();
                } catch(Exception $e) {
                    Common::dbError($e);
                }
            }

            /**
             *  Si l'action est une demande de mise à jour des informations générales de l'hôte
             */
            if ($action == 'general-status-update') {
                /**
                 *  Envoi d'un ping avec le message 'r-general-status' en hexadecimal pour ordonner à l'hôte d'envoyer les informations
                 */
                exec("ping -W2 -c 1 -p 722d67656e6572616c2d737461747573 $this->ip");

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostGeneralUpdateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostGeneralUpdateOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action est une demande de mise à jour des informations concernant les paquets disponibles sur l'hôte
             */
            if ($action == 'available-packages-status-update') {
                /**
                 *  Envoi d'un ping avec le message 'r-avail-pkgs' en hexadecimal pour ordonner à l'hôte d'envoyer les informations
                 */
                exec("ping -W2 -c 1 -p 722d617661696c2d706b6773 $this->ip");

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostAvailPackagesUpdateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostAvailPackagesUpdateOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action est une demande de mise à jour des informations concernant les paquets installés sur l'hôte
             */
            if ($action == 'installed-packages-status-update') {
                /**
                 *  Envoi d'un ping avec le message 'r-installed-pkgs' en hexadecimal pour ordonner à l'hôte d'envoyer les informations
                 */
                exec("ping -W2 -c 1 -p 722d696e7374616c6c65642d706b6773 $this->ip");

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostInstalledPackagesUpdateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostInstalledPackagesUpdateOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Si l'action est une demande de mise à jour de l'historique des évènements sur l'hôte
             */
            if ($action == 'full-history-update') {
                /**
                 *  Envoi d'un ping avec le message 'r-full-history' en hexadecimal pour ordonner à l'hôte d'envoyer les informations
                 */
                exec("ping -W2 -c 1 -p 722d66756c6c2d686973746f7279 $this->ip");

                /**
                 *  Si l'hôte a un Hostname, on le pousse dans l'array, sinon on pousse uniquement son adresse ip
                 */
                if (!empty($this->hostname)) {
                    $hostFullHistoryUpdateOK[] = array('ip' => $this->ip, 'hostname' => $this->hostname);
                } else {
                    $hostFullHistoryUpdateOK[] = array('ip' => $this->ip);
                }
            }

            /**
             *  Clotûre de la base de données de l'hôte
             */
            $this->closeHostDb();
        }

        /**
         *  Génération d'un message de confirmation avec le nom/ip des hôtes sur lesquels l'action a été effectuée
         */
        $message = '';

        /**
         *  Génération des messages pour les hôtes dont l'id est invalide
         */
        if (!empty($hostIdError)) {
            $message .= "Les ID d'hôtes suivants sont invalides :<br>";

            foreach ($hostIdError as $id) {
                $message .= $id.'<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'update'
         */
        if (!empty($hostUpdateError)) {
            $message .= 'La demande de mise à jour a échouée pour les hôtes suivants (injoignables) :<br>';

            foreach ($hostUpdateError as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }
        if (!empty($hostUpdateOK)) {
            $message .= 'La demande de mise à jour a été envoyée aux hôtes suivants :<br>';

            foreach ($hostUpdateOK as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'reset'
         */
        if (!empty($hostErrorError)) {
            $message .= 'La réinitialisation a échouée pour les hôtes suivants :<br>';

            foreach ($hostErrorError as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }
        if (!empty($hostResetOK)) {
            $message .= 'Les hôtes suivants ont été réinitialisé :<br>';

            foreach ($hostResetOK as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'delete'
         */
        if (!empty($hostDeleteError)) {
            $message .= "Les hôtes suivants n'ont pas pu être supprimés :<br>";

            foreach ($hostDeleteError as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }
        if (!empty($hostDeleteOK)) {
            $message .= 'Les hôtes suivants ont été supprimés :<br>';

            foreach ($hostDeleteOK as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'general-status-update'
         */
        if (!empty($hostGeneralUpdateError)) {
            $message .= "La demande n'a pas pu être envoyée aux hôtes suivants :<br>";

            foreach ($hostGeneralUpdateError as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }
        if (!empty($hostGeneralUpdateOK)) {
            $message .= 'La demande a été envoyée aux hôtes suivants :<br>';

            foreach ($hostGeneralUpdateOK as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'available-packages-status-update'
         */
        if (!empty($hostAvailPackagesUpdateError)) {
            $message .= "La demande n'a pas pu être envoyée aux hôtes suivants :<br>";

            foreach ($hostAvailPackagesUpdateError as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }
        if (!empty($hostAvailPackagesUpdateOK)) {
            $message .= 'La demande a été envoyée aux hôtes suivants :<br>';

            foreach ($hostAvailPackagesUpdateOK as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'installed-packages-status-update'
         */
        if (!empty($hostInstalledPackagesUpdateError)) {
            $message .= "La demande n'a pas pu être envoyée aux hôtes suivants :<br>";

            foreach ($hostInstalledPackagesUpdateError as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }
        if (!empty($hostInstalledPackagesUpdateOK)) {
            $message .= 'La demande a été envoyée aux hôtes suivants :<br>';

            foreach ($hostInstalledPackagesUpdateOK as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }

        /**
         *  Génération des messages pour une action de type 'full-history-update'
         */
        if (!empty($hostFullHistoryUpdateError)) {
            $message .= "La demande n'a pas pu être envoyée aux hôtes suivants :<br>";

            foreach ($hostFullHistoryUpdateError as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }
        if (!empty($hostFullHistoryUpdateOK)) {
            $message .= 'La demande a été envoyée aux hôtes suivants :<br>';

            foreach ($hostFullHistoryUpdateOK as $host) {
                $message .= $host['hostname'].' ('.$host['ip'].')<br>';
            }
        }

        return $message;
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
         *  D'abord on vérifie qu'un hôte avec l'id et le token correspondant existe bien
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :hostId AND Token = :token");
            $stmt->bindValue(':hostId', $this->authId);
            $stmt->bindValue(':token', $this->token);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

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
        $packagesList = array_filter(explode(",", Common::validateData($packagesInventory)));

        /**
         *  On traite si l'array n'est pas vide
         */
        if (!empty($packagesList)) {

            /**
             *  Ouverture de la BDD dédiée de l'hôte
             */
            $this->openHostDb($this->id);

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
                    try {
                        $stmt = $this->host_db->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time') VALUES (:name, :version, 'inventored', 'package', :date, :time)");
                        $stmt->bindValue(':name', $this->packageName);
                        $stmt->bindValue(':version', $this->packageVersion);
                        $stmt->bindValue(':date', date('Y-m-d'));
                        $stmt->bindValue(':time', date('H:i:s'));
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }
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
        $packagesList = array_filter(explode(",", Common::validateData($packagesAvailable)));

        /**
         *  On traite si l'array n'est pas vide
         */
        if (!empty($packagesList)) {

            /**
             *  Ouverture de la BDD dédiée de l'hôte
             */
            $this->openHostDb($this->id);

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
                        try {
                            $stmt = $this->host_db->prepare("UPDATE packages_available SET Version = :version WHERE Name = :name");
                            $stmt->bindValue(':name', $this->packageName);
                            $stmt->bindValue(':version', $this->packageVersion);
                            $stmt->execute();
                        } catch(Exception $e) {
                            Common::dbError($e);
                        }
                    }

                } else {
                    /**
                     *  Si le paquet n'existe pas on l'ajoute en BDD
                     */
                    try {
                        $stmt = $this->host_db->prepare("INSERT INTO packages_available ('Name', 'Version') VALUES (:name, :version)");
                        $stmt->bindValue(':name', $this->packageName);
                        $stmt->bindValue(':version', $this->packageVersion);
                        $stmt->execute();
                    } catch(Exception $e) {
                        Common::dbError($e);
                    }
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
        $this->openHostDb($this->id);

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
            try {
                $stmt = $this->host_db->prepare("SELECT Id FROM events WHERE Date = :date_start AND Time = :time_start");
                $stmt->bindValue(':date_start', $event->date_start);
                $stmt->bindValue(':time_start', $event->time_start);
                $result = $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            if ($this->host_db->isempty($result) === false) {
                continue;
            }

            /**
             *  Insertion de l'évènement en base de données
             */
            try {
                $stmt = $this->host_db->prepare("INSERT INTO events ('Date', 'Date_end', 'Time', 'Time_end', 'Status') VALUES (:date_start, :date_end, :time_start, :time_end, 'done')");
                $stmt->bindValue(':date_start', $event->date_start);
                $stmt->bindValue(':date_end', $event->date_end);
                $stmt->bindValue(':time_start', $event->time_start);
                $stmt->bindValue(':time_end', $event->time_end);
                $stmt->execute();
            } catch(Exception $e) {
                Common::dbError($e);
            }

            /**
             *  Récupération de l'Id inséré en BDD
             */
            $id_event = $this->host_db->lastInsertRowID();

            /**
             *  Si l'évènement comporte des paquets installés
             */
            if (!empty($event->installed)) {
                foreach ($event->installed as $package_installed) {
                    $this->db_setPackageState($package_installed->name, $package_installed->version, 'installed', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets mis à jour
             */
            if (!empty($event->upgraded)) {
                foreach ($event->upgraded as $package_upgraded) {
                    $this->db_setPackageState($package_upgraded->name, $package_upgraded->version, 'upgraded', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets désinstallés
             */
            if (!empty($event->removed)) {
                foreach ($event->removed as $package_removed) {
                    $this->db_setPackageState($package_removed->name, $package_removed->version, 'removed', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets rétrogradés
             */
            if (!empty($event->downgraded)) {
                foreach ($event->downgraded as $package_downgraded) {
                    $this->db_setPackageState($package_downgraded->name, $package_downgraded->version, 'downgraded', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets réinstallés
             */
            if (!empty($event->reinstalled)) {
                foreach ($event->reinstalled as $package_reinstalled) {
                    $this->db_setPackageState($package_reinstalled->name, $package_reinstalled->version, 'reinstalled', $event->date_start, $event->time_start, $id_event);
                }
            }
            /**
             *  Si l'évènement comporte des paquets purgés
             */
            if (!empty($event->purged)) {
                foreach ($event->purged as $package_purged) {
                    $this->db_setPackageState($package_purged->name, $package_purged->version, 'purged', $event->date_start, $event->time_start, $id_event);
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
        try {
            $stmt = $this->db->prepare("SELECT Ip FROM hosts WHERE Ip = :ip AND Status = 'active'");
            $stmt->bindValue(':ip', Common::validateData($ip));
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;
        
        return true;
    }

    /**
     *  Vérifie si le hostname de l'hôte existe en BDD
     */
    private function hostnameExists(string $hostname)
    {
        try {
            $stmt = $this->db->prepare("SELECT Hostname FROM hosts WHERE Hostname = :hostname");
            $stmt->bindValue(':hostname', Common::validateData($hostname));
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) return false;

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet dans la table packages
     */
    public function packageExists(string $packageName)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Name = :name");
            $stmt->bindValue(':name', Common::validateData($packageName));
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->host_db->isempty($result) === true) return false;

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet et sa version dans la table package
     */
    public function packageVersionExists(string $packageName, string $packageVersion)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages WHERE Name=:name AND Version=:version");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->host_db->isempty($result) === true) return false;

        return true;
    }

    /**
     *  Rechercher l'existance d'un paquet sur un hôte et retourner sa version
     */
    public function searchPackage(string $packageName)
    {
        /**
         *  Si il manque l'id de l'hôte, on quitte car on en a besoin pour ouvrir sa BDD dédiée
         */
        if (empty($this->id)) return false;

        /**
         *  Ouverture de la BDD dédiée de l'hôte
         */
        $this->openHostDb($this->id);

        try {
            $stmt = $this->host_db->prepare("SELECT Version FROM packages WHERE Name = :name");
            $stmt->bindValue(':name', $packageName);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

        /**
         *  Si aucun résultat on renvoi false
         */
        if ($this->host_db->isempty($result) === true) return false;

        /**
         *  Sinon on récupère les données
         */
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $version = $row['Version'];

        return $version;
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
        } catch(Exception $e) {
            Common::dbError($e);
        }

        if ($this->host_db->isempty($result) === true) return false;

        return true;
    }

    /**
     *  Vérifie l'existence d'un paquet et sa version dans la table package_available
     */
    public function packageVersionAvailableExists(string $packageName, string $packageVersion)
    {
        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages_available WHERE Name = :name AND Version = :version");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

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

        $result = $this->host_db->query("SELECT * FROM packages ORDER BY Name ASC");

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

        try {
            $stmt = $this->host_db->prepare("SELECT * FROM packages        
            WHERE Id_event = :eventId AND State = :packageState
            UNION
            SELECT * FROM packages_history       
            WHERE Id_event = :eventId AND State = :packageState");
            $stmt->bindValue(':eventId', Common::validateData($eventId));
            $stmt->bindValue(':packageState', Common::validateData($packageState));
            $result = $stmt->execute();
        } catch(Exception $e) {
            Common::dbError($e);
        }

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
        $this->openHostDb($this->id);

        try {
            $stmt = $this->host_db->prepare("SELECT Name, Version FROM packages
            WHERE Id_event = :id_event AND State = :state
            UNION
            SELECT Name, Version FROM packages_history
            WHERE Id_event = :id_event AND State = :state");
            $stmt->bindValue(':id_event', Common::validateData($eventId));
            $stmt->bindValue(':state', Common::validateData($packageState));
            $result = $stmt->execute();
        } catch(Exception $e) {
            throw new Exception('');
        }

        $packageState = Common::validateData($packageState);
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
        $this->openHostDb($this->id);

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
        } catch(Exception $e) {
            Common::dbError($e);
        }
        
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
            if ($event['State'] == "reinstalled") {
                $content_color = 'yellow';
                $content_text = '<img src="../ressources/icons/arrow-back.png" class="icon" /> Réinstallé';
            }
            if ($event['State'] == "purged") {
                $content_color = 'red';
                $content_text = '<img src="../ressources/icons/arrow-back.png" class="icon" /> Purgé';
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
     *  Récupère les informations concernant la dernière requête de mise à jour envoyée à l'hôte
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
        if (!empty($datas[0])) return $datas[0];

        return '';
    }


    public function getLastRequestedUpdateStatus()
    {
        /**
         *  Si la BDD dédiée à l'hôte n'est pas instanciée dans $this->host_db alors on quitte
         */
        if (empty($this->host_db)) return false;

        $datas = array();

        $result = $this->host_db->query("SELECT Date, Time, Type, Status FROM updates_requests ORDER BY Id DESC LIMIT 1");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

        return $datas;
    }
}