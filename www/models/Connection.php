<?php

class Connection extends SQLite3 {

    public function __construct(string $database, string $mode, ?string $hostId)
    {
        global $WWW_DIR;

        /**
         *  Ouvre la base de données à partie du chemin et du mode renseigné (read-write ou read-only)
         *  Si celle-ci n'existe pas elle est créée automatiquement
         */
        try {
            /**
             *  Si le mode renseigné ne correspond pas à 'rw' ni 'ro', on quitte
             */
            if ($mode != "rw" AND $mode != "ro") throw new Exception("mode inconnu : $mode");

            /**
             *  Ouverture de la base de données
             */
            
            /**
             *  Cas où la base de données renseignée est "main", il s'agit de la base de données principale repomanager.db
             */
            if ($database == "main") {
                /**
                 *  Ouverture en mode read-only
                 */
                if ($mode == "ro") $this->open("$WWW_DIR/db/repomanager.db", SQLITE3_OPEN_READONLY);

                /**
                 *  Ouverture en mode read-write
                 */
                if ($mode == "rw") {
                    $this->open("$WWW_DIR/db/repomanager.db");

                    /**
                     *  Génération des tables si n'existent pas
                     */
                    $this->generateMainTables();
                }

            /**
             *  Cas où la base de données est "stats", il s'agit de la base de données repomanager-stats.db
             */
            } elseif ($database == "stats") {
                /**
                 *  Ouverture en mode read-only
                 */
                if ($mode == "ro") $this->open("$WWW_DIR/db/repomanager-stats.db", SQLITE3_OPEN_READONLY);

                /**
                 *  Ouverture en mode read-write
                 */
                if ($mode == "rw") {
                    $this->open("$WWW_DIR/db/repomanager-stats.db");

                    /**
                     *  Génération des tables si n'existent pas
                     */
                    $this->generateStatsTables();
                }
                
            /**
             *  Cas où la base de données est "hosts", il s'agit de la base de données repomanager-hosts.db
             */
            } elseif ($database == "hosts") {
                /**
                 *  Ouverture en mode read-only
                 */
                if ($mode == "ro") $this->open("$WWW_DIR/db/repomanager-hosts.db", SQLITE3_OPEN_READONLY);

                /**
                 *  Ouverture en mode read-write
                 */
                if ($mode == "rw") {
                    $this->open("$WWW_DIR/db/repomanager-hosts.db");

                    /**
                     *  Génération des tables si n'existent pas
                     */
                    $this->generateHostsTables();
                }

            /**
             *  Cas où il s'agit d'une base de données dédiée à un hôte, l'Id de l'hôte doit être renseigné
             */

            } elseif ($database == "host") {

                $HOSTS_DIR = "${WWW_DIR}/hosts";

                /**
                 *  Ouverture en mode read-only
                 */
                if ($mode == "ro") $this->open("$HOSTS_DIR/$hostId/properties.db", SQLITE3_OPEN_READONLY);

                /**
                 *  Ouverture en mode read-write
                 */
                if ($mode == "rw") {
                    $this->open("$HOSTS_DIR/$hostId/properties.db");

                    /**
                     *  Génération des tables si n'existent pas
                     */
                    $this->generateHostTables();
                }

            /**
             *  Cas où la base de données ne correspond à aucun cas ci-dessus
             */
            } else {
                throw new Exception("base de données inconnue : $database");
            }

        } catch(Exception $e) {
            die('Erreur lors de la connexion à la base de données : '.$e->getMessage());
        }

        /**
         *  Ajout d'un timeout de 10sec pour l'ouverture de la base de données
         */
        try {

            $this->busyTimeout(10000);

        } catch(Exception $e) {

            die('Erreur lors de la configuration du timeout de la base de données : '.$e->getMessage());
        }        
    }

    /**
     *  
     *  Fonctions de génération des tables si n'existent pas
     * 
     */
    private function generateMainTables()
    {
        global $OS_FAMILY;

        /**
         *  Crée la table repos si n'existe pas
         */
        if ($OS_FAMILY == "Redhat") {
            $this->exec("CREATE TABLE IF NOT EXISTS repos (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Name VARCHAR(255) NOT NULL,
            Source VARCHAR(255) NOT NULL,
            Env VARCHAR(255) NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Description VARCHAR(255),
            Signed CHAR(3) NOT NULL,
            Type CHAR(6) NOT NULL,
            Status CHAR(8) NOT NULL);");
        }
        if ($OS_FAMILY == "Debian") {
            $this->exec("CREATE TABLE IF NOT EXISTS repos (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Name VARCHAR(255) NOT NULL,
            Source VARCHAR(255) NOT NULL,
            Dist VARCHAR(255) NOT NULL,
            Section VARCHAR(255) NOT NULL,
            Env VARCHAR(255) NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Description VARCHAR(255),
            Signed CHAR(3) NOT NULL,
            Type CHAR(6) NOT NULL,
            Status CHAR(8) NOT NULL);");
        }

        /**
         *  Crée la table repos_archive si n'existe pas
         */
        if ($OS_FAMILY == "Redhat") {
            $this->exec("CREATE TABLE IF NOT EXISTS repos_archived (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Name VARCHAR(255) NOT NULL,
            Source VARCHAR(255) NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Description VARCHAR(255),
            Signed CHAR(3) NOT NULL,
            Type CHAR(6) NOT NULL,
            Status CHAR(8) NOT NULL);");
        }

        if ($OS_FAMILY == "Debian") {
            $this->exec("CREATE TABLE IF NOT EXISTS repos_archived (
            Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            Name VARCHAR(255) NOT NULL,
            Source VARCHAR(255) NOT NULL,
            Dist VARCHAR(255) NOT NULL,
            Section VARCHAR(255) NOT NULL,
            Date DATE NOT NULL,
            Time TIME NOT NULL,
            Description VARCHAR(255),
            Signed CHAR(3) NOT NULL,
            Type CHAR(6) NOT NULL,
            Status CHAR(8) NOT NULL);");
        }

        /**
         *  Crée la table env si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS env (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL)");

        /**
         *  Crée la table sources si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS sources (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Url VARCHAR(255) NOT NULL,
        Gpgkey VARCHAR(255))");

        /**
         *  Crée la table users si n'existe pas
         */
        /*$this->exec("CREATE TABLE IF NOT EXISTS users (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Password VARCHAR(255) NOT NULL)");*/
            
        /** 
         *  Crée la table groups si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS groups (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");

        /**
         *  Crée la table group_members si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS group_members (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Id_repo INTEGER NOT NULL,
        Id_group INTEGER NOT NULL);");

        /**
         *  Crée la table operations si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS operations (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Action VARCHAR(255) NOT NULL,  /* update, env->env */
        Type CHAR(6) NOT NULL,         /* manual, auto */
        Id_repo_source VARCHAR(255),
        Id_repo_target VARCHAR(255),
        Id_group INTEGER,
        Id_plan INTEGER,               /* si type = auto */
        GpgCheck CHAR(3),
        GpgResign CHAR(3),
        Pid INTEGER NOT NULL,
        Logfile VARCHAR(255) NOT NULL,
        Duration INTEGER,
        Status CHAR(7) NOT NULL)");    /* running, done, stopped */

        /**
         *  Crée la table planifications si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS planifications (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Type CHAR(7), /* regular ou plan */
        Frequency CHAR(15), /* every-day, every-hour... */
        Date DATE,
        Time TIME,
        Action VARCHAR(255) NOT NULL,
        Id_repo INTEGER,
        Id_group INTEGER,
        Gpgcheck CHAR(3),
        Gpgresign CHAR(3),
        Reminder VARCHAR(255),
        Notification_error CHAR(3),
        Notification_success CHAR(3),
        Mail_recipient VARCHAR(255),
        Status CHAR(10), /* queued, done, running, canceled */
        Error VARCHAR(255),
        Logfile VARCHAR(255))");

        /**
         *  Crée la table profile_package si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_package (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");
        /**
         *  Si la table profile_package est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT * FROM profile_package");
        if ($this->isempty($result) === true) $this->exec("INSERT INTO profile_package (Name) VALUES ('apache'), ('httpd'), ('php'), ('php-fpm'), ('mysql'), ('fail2ban'), ('nrpe'), ('munin-node'), ('node'), ('newrelic'), ('nginx'), ('haproxy'), ('netdata'), ('nfs'), ('rsnapshot'), ('kernel'), ('java'), ('redis'), ('varnish'), ('mongo'), ('rabbit'), ('clamav'), ('clam'), ('gpg'), ('gnupg')");

        /**
         *  Crée la table profile_service si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_service (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");
        /**
         *  Si la table profile_service est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT * FROM profile_service");
        if ($this->isempty($result) === true) $this->exec("INSERT INTO profile_service (Name) VALUES ('apache'), ('httpd'), ('php-fpm'), ('mysqld'), ('fail2ban'), ('nrpe'), ('munin-node'), ('nginx'), ('haproxy'), ('netdata'), ('nfsd'), ('redis'), ('varnish'), ('mongod'), ('clamd')");
    }

    private function generateStatsTables()
    {
        /**
         *  Crée la table stats si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS stats (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Id_repo INTEGER NOT NULL,
        Size INTEGER NOT NULL,
        Packages_count INTEGER NOT NULL)");

        /**
         *  Crée la table access si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS access (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Source VARCHAR(255) NOT NULL,
        IP VARCHAR(16) NOT NULL,
        Request VARCHAR(255) NOT NULL,
        Request_result VARCHAR(8) NOT NULL)");
    }

    private function generateHostsTables()
    {
        /**
         *  Crée la table hosts si n'existe pas
         *  Online_status : online / unreachable
         *  Status : active / disabled / deleted
         *  Last_update_status : done / running / error
         */
        $this->exec("CREATE TABLE IF NOT EXISTS hosts (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Ip VARCHAR(15) NOT NULL,
        Hostname VARCHAR(255) NOT NULL,
        Os VARCHAR(255),
        Os_version VARCHAR(255),
        Profile VARCHAR(255),
        Env VARCHAR(255),
        AuthId VARCHAR(255),
        Token VARCHAR(255),
        Online_status VARCHAR(11),
        Online_status_date,
        Online_status_time,
        Status VARCHAR(8) NOT NULL)");
    
        /** 
         *  Crée la table groups si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS groups (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");
        
        /**
         *  Crée la table group_members si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS group_members (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Id_host INTEGER NOT NULL,
        Id_group INTEGER NOT NULL);");
    }

    private function generateHostTables()
    {
        /**
         *  Inventaire de tous les paquets du serveur
         */
        $this->exec("CREATE TABLE IF NOT EXISTS packages (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255),
        Version VARCHAR(255),
        State VARCHAR(255),
        Type VARCHAR(255),
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Id_event INTEGER)");
        
        /**
         *  Historique des paquets répertoriés (suppression, installation...)
         */
        $this->exec("CREATE TABLE IF NOT EXISTS packages_history (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255),
        Version VARCHAR(255),
        State VARCHAR(255),
        Type VARCHAR(255),
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Id_event INTEGER)");

        /**
         *  Liste des paquets disponibles pour mise à jour
         */
        $this->exec("CREATE TABLE IF NOT EXISTS packages_available (
        Name VARCHAR(255),
        Version VARCHAR(255))");

        /**
         *  Historique de toutes les mises à jour
         *  Status = error / warning / unknow /done
         */
        $this->exec("CREATE TABLE IF NOT EXISTS 'events' (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Date_end DATE NOT NULL,
        Time_end TIME NOT NULL,
        Report VARCHAR(255),
        Status VARCHAR(7))");

        /**
         *  Historique des demandes de mises à jour
         */
        $this->exec("CREATE TABLE IF NOT EXISTS 'updates_requests' (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Status VARCHAR(10))");
    }

    /**
     * 
     *  Fonctions utiles
     * 
     */
    /**
     *  Retourne true si le résultat est vide et false si il est non-vide.
     */
    public function isempty($result)
    {
        /**
         *  Compte le nombre de lignes retournées par la requête
         */
        $count = 0;

        while ($row = $result->fetchArray()) $count++;

        if ($count == 0) return true;

        return false;
    }

    /**
     *  Fonction permettant de retourner le nombre de lignes résultant d'une requête
     */
    public function count(object $result)
    {
        $count = 0;

        while ($row = $result->fetchArray()) $count++;

        return $count;
    }

    /**
     *  Transforme un résultat de requête ($result = $stmt->execute()) en un array
     */
    public function fetch(object $result, string $option = '')
    {
        /**
         *  On vérifie d'abord que $result n'est pas vide, sauf si on a précisé l'option "ignore-null"
         */
        if ($option != "ignore-null") {
            if ($this->isempty($result) === true) {
                throw new Exception('Erreur : le résultat les données à traiter est vide');
            }
        }

        $datas = array();

        /**
         *  Fetch le résultat puis retourne l'array créé
         */
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

        return $datas;
    }

    /**
     *  Execute une requête et renvoi un array contenant les résultats
     */
    public function queryArray(string $query)
    {
        $result = $this->query($query);

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

        if (!empty($datas)) return $datas;
    }
}
?>