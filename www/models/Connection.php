<?php

namespace Models;

use SQLite3;
use Exception;

class Connection extends SQLite3
{
    public function __construct(string $database, string $hostId = null)
    {
        /**
         *  Ouvre la base de données à partir du chemin et du mode renseigné (read-write ou read-only)
         *  Si celle-ci n'existe pas elle est créée automatiquement
         */
        try {
            /**
             *  Ouverture de la base de données
             */

            /**
             *  Cas où la base de données renseignée est "main", il s'agit de la base de données principale repomanager.db
             */
            if ($database == "main") {
                $this->open(DB);

                /**
                 *  Activation des exception pour SQLite
                 */
                $this->enableExceptions(true);

            /**
             *  Cas où la base de données est "stats", il s'agit de la base de données repomanager-stats.db
             */
            } elseif ($database == "stats") {
                $this->open(STATS_DB);

                /**
                 *  Activation des exception pour SQLite
                 */
                $this->enableExceptions(true);

            /**
             *  Cas où la base de données est "hosts", il s'agit de la base de données repomanager-hosts.db
             */
            } elseif ($database == "hosts") {
                $this->open(HOSTS_DB);

                /**
                 *  Activation des exception pour SQLite
                 */
                $this->enableExceptions(true);

            /**
             *  Cas où il s'agit d'une base de données dédiée à un hôte, l'Id de l'hôte doit être renseigné
             */
            } elseif ($database == "host") {
                $this->open(HOSTS_DIR . '/' . $hostId . '/properties.db');

                /**
                 *  Activation du mode WAL
                 */
                $this->exec('PRAGMA journal_mode = wal;');

                /**
                 *  Génération des tables si n'existent pas
                 */
                $this->generateHostTables();

                /**
                 *  Activation des exception pour SQLite
                 */
                $this->enableExceptions(true);

            /**
             *  Cas où la base de données ne correspond à aucun cas ci-dessus
             */
            } else {
                throw new Exception("unknown database: $database");
            }
        } catch (\Exception $e) {
            die('Error while opening database: ' . $e->getMessage());
        }

        /**
         *  Ajout d'un timeout de 10sec pour l'ouverture de la base de données
         */
        try {
            $this->busyTimeout(10000);
        } catch (\Exception $e) {
            die('Error while configuring timeout on database: ' . $e->getMessage());
        }
    }

    /**
     *  Activation du mode WAL SQLite
     */
    private function enableWAL()
    {
        $this->exec('pragma journal_mode = WAL;');
        $this->exec('pragma synchronous = normal;');
        $this->exec('pragma temp_store = memory;');
        $this->exec('pragma mmap_size = 30000000000;');
    }

    /**
     *  Désactivation du mode WAL SQLite
     */
    private function disableWAL()
    {
        $this->exec('pragma journal_mode = DELETE;');
    }

    /**
     *
     *  Fonctions de vérification des tables
     *
     */

    /**
     *  Compte le nombre de tables dans la base de données principale
     */
    public function countMainTables()
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table'
        AND name='repos'
        OR name='repos_env'
        OR name='repos_snap'
        OR name='env'
        OR name='sources'
        OR name='groups' 
        OR name='group_members' 
        OR name='operations' 
        OR name='planifications'
        OR name='profile'
        OR name='profile_settings'
        OR name='profile_repo_members'
        OR name='profile_package'
        OR name='profile_service'
        OR name='users'
        OR name='user_role'
        OR name='history'
        OR name='repos_list_settings'");

        /**
         *  On retourne le nombre de tables
         */
        return $this->count($result);
    }

    /**
     *  Compte le nombre de tables dans la base de données stats
     */
    public function countStatsTables()
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table'
        and name='stats'
        OR name='access'");

        /**
         *  On retourne le nombre de tables
         */
        return $this->count($result);
    }

    /**
     *  Compte le nombre de tables dans la base de données hosts
     */
    public function countHostsTables()
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table'
        and name='hosts'
        OR name='groups'
        OR name='group_members'
        OR name='settings'");

        /**
         *  On retourne le nombre de tables
         */
        return $this->count($result);
    }

    /**
     *  Vérification de la présence de toutes les tables dans la base de données principale
     */
    public function checkMainTables()
    {
        /**
         *  Si le nombre de tables présentes != 18 alors on tente de regénérer les tables
         */
        if ($this->countMainTables() != 18) {
            $this->generateMainTables();

            /**
             *  On compte de nouveau les tables après la tentative de re-génération, on retourne false si c'est toujours pas bon
             */
            if ($this->countMainTables() != 18) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Vérification de la présence de toutes les tables dans la base de données stats
     */
    public function checkStatsTables()
    {
        /**
         *  Si le nombre de tables présentes != 2 alors on tente de regénérer les tables
         */
        if ($this->countStatsTables() != 2) {
            $this->generateStatsTables();

            /**
             *  On compte de nouveau les tables après la tentative de re-génération, on retourne false si c'est toujours pas bon
             */
            if ($this->countStatsTables() != 2) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Vérifications de la présence de toutes les tables dans la base de données hosts
     */
    public function checkHostsTables()
    {
        /**
         *  Si le nombre de tables présentes != 4 alors on tente de regénérer les tables
         */
        if ($this->countHostsTables() != 4) {
            $this->generateHostsTables();

            /**
             *  On compte de nouveau les tables après la tentative de re-génération, on retourne false si c'est toujours pas bon
             */
            if ($this->countHostsTables() != 4) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     *  Fonctions de génération des tables si n'existent pas
     *
     */
    /**
     *  Génération des tables dans la base de données repomanager.db
     */
    private function generateMainTables()
    {
        /**
         *  Crée la table repos si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS repos (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Dist VARCHAR(255),
        Section VARCHAR(255),
        Source VARCHAR(255) NOT NULL,
        Package_type VARCHAR(10) NOT NULL)");

        /**
         *  Crée la table repos_snap si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS repos_snap (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Signed CHAR(3) NOT NULL,
        Arch VARCHAR(255),
        Pkg_source CHAR(3),
        Pkg_translation VARCHAR(255),
        Type CHAR(6) NOT NULL,
        Reconstruct CHAR(8), /* needed, running, failed */
        Status CHAR(8) NOT NULL,
        Id_repo INTEGER NOT NULL)");

        /**
         *  Crée la table repos_env si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS repos_env (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Env VARCHAR(255),
        Description VARCHAR(255),
        Id_snap INTEGER NOT NULL)");

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
        Type CHAR(3) NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Url VARCHAR(255) NOT NULL,
        Gpgkey VARCHAR(255),
        Ssl_certificate_path VARCHAR(255),
        Ssl_private_key_path VARCHAR(255))");

        /**
         *  Crée la table users si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS users (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Username VARCHAR(255) NOT NULL,
        Password CHAR(60),
        First_name VARCHAR(50),
        Last_name VARCHAR(50),
        Email VARCHAR(100),
        Role INTEGER NOT NULL,
        Type CHAR(5) NOT NULL,
        State CHAR(7) NOT NULL)"); /* active / deleted */

        /**
         *  Crée la table user_role si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS user_role (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name CHAR(15) NOT NULL UNIQUE)");

        /**
         *  Si la table user_role est vide (vient d'être créée) alors on crée les roles par défaut
         */
        $result = $this->query("SELECT Id FROM user_role");
        if ($this->isempty($result) === true) {
            /**
             *  Rôle super-administrator : tous les droits
             */
            $this->exec("INSERT INTO user_role ('Name') VALUES ('super-administrator')");
            /**
             *  Rôle administrator
             */
            $this->exec("INSERT INTO user_role ('Name') VALUES ('administrator')");
            /**
             *  Rôle usage
             */
            $this->exec("INSERT INTO user_role ('Name') VALUES ('usage')");
        }

        /**
         *  Si la table users est vide (vient d'être créée) alors on crée l'utilisateur admin (mdp repomanager et role n°1 (super-administrator))
         */
        $result = $this->query("SELECT Id FROM users");
        if ($this->isempty($result) === true) {
            $password_hashed = '$2y$10$FD6/70o2nXPf76SAPYIGSutauQ96LqKie5PLanoYBNbCWen492cX6';
            try {
                $stmt = $this->prepare("INSERT INTO users ('Username', 'Password', 'First_name', 'Role', 'State', 'Type') VALUES ('admin', :password_hashed, 'Administrator', '1', 'active', 'local')");
                $stmt->bindValue(':password_hashed', $password_hashed);
                $stmt->execute();
            } catch (\Exception $e) {
                \Controllers\Common::dbError($e);
            }
        }

        /**
         *  Crée la table history si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS history (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Id_user INTEGER NOT NULL,
        Action VARCHAR(255) NOT NULL,
        State CHAR(7))"); /* success ou error */

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
        Action VARCHAR(255) NOT NULL,
        Type CHAR(6) NOT NULL,         /* manual, plan */
        Id_repo_source VARCHAR(255),
        Id_snap_source INTEGER,
        Id_env_source INTEGER,
        Id_repo_target VARCHAR(255),
        Id_snap_target INTEGER,
        Id_env_target INTEGER,
        Id_group INTEGER,
        Id_plan INTEGER,
        GpgCheck CHAR(3),
        GpgResign CHAR(3),
        Pid INTEGER NOT NULL,
        Pool_id INTEGER NOT NULL,
        Logfile VARCHAR(255) NOT NULL,
        Duration INTEGER,
        Status CHAR(7) NOT NULL)");    /* running, done, stopped */

        /**
         *  Crée la table planifications si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS planifications (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Type CHAR(7) NOT NULL, /* regular ou plan */
        Frequency CHAR(15), /* every-day, every-hour... */
        Day CHAR(70),
        Date DATE,
        Time TIME,
        Action VARCHAR(255) NOT NULL,
        Id_snap INTEGER,
        Id_group INTEGER,
        Target_env VARCHAR(255),
        Gpgcheck CHAR(3),
        Gpgresign CHAR(3),
        Reminder VARCHAR(255),
        Notification_error CHAR(3),
        Notification_success CHAR(3),
        Mail_recipient VARCHAR(255),
        Status CHAR(10) NOT NULL, /* queued, done, running, canceled */
        Error VARCHAR(255),
        Logfile VARCHAR(255))");

        /**
         *  Crée la table profile_settings si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_settings (
        Package_type VARCHAR(255),
        Manage_client_conf CHAR(3),
        Manage_client_repos CHAR(3))");

        /**
         *  Si la table profile_settings est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT * FROM profile_settings");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO profile_settings (Manage_client_conf, Manage_client_repos) VALUES ('no', 'no')");
        }

        /**
         *  Crée la table profile si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Package_exclude VARCHAR(255),
        Package_exclude_major VARCHAR(255),
        Service_restart VARCHAR(255),
        Linupdate_get_pkg_conf CHAR(5),
        Linupdate_get_repos_conf CHAR(5),
        Notes VARCHAR(255))");

        /**
         *  Crée la table profile_repo_members si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_repo_members (
        Id_profile INTEGER NOT NULL,
        Id_repo INTEGER NOT NULL)");

        /**
         *  Crée la table profile_package si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_package (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");
        /**
         *  Si la table profile_package est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT Id FROM profile_package");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO profile_package (Name) VALUES ('apache'), ('httpd'), ('php'), ('php-fpm'), ('mysql'), ('fail2ban'), ('nrpe'), ('munin-node'), ('node'), ('newrelic'), ('nginx'), ('haproxy'), ('netdata'), ('nfs'), ('rsnapshot'), ('kernel'), ('java'), ('redis'), ('varnish'), ('mongo'), ('rabbit'), ('clamav'), ('clam'), ('gpg'), ('gnupg')");
        }

        /**
         *  Crée la table profile_service si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_service (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");

        /**
         *  Si la table profile_service est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT Id FROM profile_service");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO profile_service (Name) VALUES ('apache'), ('httpd'), ('php-fpm'), ('mysqld'), ('fail2ban'), ('nrpe'), ('munin-node'), ('nginx'), ('haproxy'), ('netdata'), ('nfsd'), ('redis'), ('varnish'), ('mongod'), ('clamd')");
        }

        /**
         *  Crée la table repos_list_settings si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS repos_list_settings (
        print_repo_size CHAR(3) NOT NULL,
        print_repo_type CHAR(3) NOT NULL,
        print_repo_signature CHAR(3) NOT NULL,
        cache_repos_list CHAR(3) NOT NULL)");

        /**
         *  Si la table repos_list_settings est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT print_repo_size FROM repos_list_settings");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO repos_list_settings (print_repo_size, print_repo_type, print_repo_signature, cache_repos_list) VALUES ('yes', 'yes', 'yes', 'no')");
        }

        /**
         *  Activation du mode WAL
         */
        $this->enableWAL();
    }

    /**
     *  Génération des tables dans la base de données repomanager-stats.db
     */
    private function generateStatsTables()
    {
        /**
         *  Crée la table stats si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS stats (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Size INTEGER NOT NULL,
        Packages_count INTEGER NOT NULL,
        Id_env INTEGER NOT NULL)");

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

        /**
         *  Crée un index sur certaines colonnes de la table access
         */
        $this->exec("CREATE INDEX IF NOT EXISTS access_index ON access (Date, Time, Request)");
        $this->exec("CREATE INDEX IF NOT EXISTS stats_index ON stats (Date, Time, Size, Packages_count, Id_env)");

        /**
         *  Activation du mode WAL
         */
        $this->enableWAL();
    }

    /**
     *  Génération des tables dans la base de données repomanager-hosts.db
     */
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
        Os_family VARCHAR(255),
        Kernel VARCHAR(255),
        Arch CHAR(10),
        Type VARCHAR(255),
        Profile VARCHAR(255),
        Env VARCHAR(255),
        AuthId VARCHAR(255),
        Token VARCHAR(255),
        Online_status CHAR(8),
        Online_status_date DATE,
        Online_status_time TIME,
        Linupdate_version VARCHAR(255),
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
        Id_group INTEGER NOT NULL)");

        /**
         *  Crée la table settings si n'existe pas
         */
        $this->exec("CREATE TABLE IF NOT EXISTS settings (
        pkgs_count_considered_outdated INTEGER NOT NULL,
        pkgs_count_considered_critical INTEGER NOT NULL)");

        /**
         *  Si la table settings est vide (vient d'être créée) alors on la peuple
         */
        $result = $this->query("SELECT pkgs_count_considered_outdated FROM settings");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO settings ('pkgs_count_considered_outdated', 'pkgs_count_considered_critical') VALUES ('1', '10')");
        }

        /**
         *  Activation du mode WAL
         */
        $this->enableWAL();
    }

    /**
     *  Génération des tables dans la base de données dédiée à un hôte
     *  Cette fonction est public car elle peut être appelée lors de la réinitialisation d'un hôte
     */
    public function generateHostTables()
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
        Type CHAR(32), /* packages-update, general-status-update, packages-status-update */
        Status VARCHAR(10))"); /* running, done, error */

        /**
         *  Activation du mode WAL
         */
        $this->enableWAL();
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

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $count++;
        }

        if ($count == 0) {
            return true;
        }

        return false;
    }

    /**
     *  Fonction permettant de retourner le nombre de lignes résultant d'une requête
     */
    public function count(object $result)
    {
        $count = 0;

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $count++;
        }

        return $count;
    }

    /**
     *  Return true if column name exists in the specified table
     */
    public function columnExist(string $tableName, string $columnName)
    {
        $columns = array();

        $result = $this->query("PRAGMA table_info($tableName)");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row;
        }

        foreach ($columns as $column) {
            if ($column['name'] == $columnName) {
                return true;
            }
        }

        return false;
    }
}
