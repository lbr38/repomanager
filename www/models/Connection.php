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
            if (!is_dir(DB_DIR)) {
                if (!mkdir(DB_DIR, 0777, true)) {
                    throw new Exception('Unable to create database directory');
                }
            }

            /**
             *  Ouverture de la base de données
             */

            /**
             *  Cas où la base de données renseignée est "main", il s'agit de la base de données principale repomanager.db
             */
            if ($database == "main") {
                $this->open(DB);
                $this->busyTimeout(10000);
                $this->enableExceptions(true);
                $this->enableWAL();
                $this->checkMainTables();

            /**
             *  Cas où la base de données est "stats", il s'agit de la base de données repomanager-stats.db
             */
            } elseif ($database == "stats") {
                $this->open(STATS_DB);
                $this->busyTimeout(10000);
                $this->enableExceptions(true);
                $this->enableWAL();
                $this->checkStatsTables();

            /**
             *  Cas où la base de données est "hosts", il s'agit de la base de données repomanager-hosts.db
             */
            } elseif ($database == "hosts") {
                $this->open(HOSTS_DB);
                $this->busyTimeout(10000);
                $this->enableExceptions(true);
                $this->enableWAL();
                $this->checkHostsTables();

            /**
             *  Cas où il s'agit d'une base de données dédiée à un hôte, l'Id de l'hôte doit être renseigné
             */
            } elseif ($database == "host") {
                $this->open(HOSTS_DIR . '/' . $hostId . '/properties.db');
                $this->busyTimeout(10000);
                $this->enableExceptions(true);
                $this->enableWAL();
                $this->generateHostTables();

            /**
             *  Cas où la base de données ne correspond à aucun cas ci-dessus
             */
            } else {
                throw new Exception("unknown database: $database");
            }
        } catch (\Exception $e) {
            die('Error while opening database: ' . $e->getMessage());
        }
    }

    /**
     *  Activation du mode WAL SQLite
     */
    private function enableWAL()
    {
        $this->exec('pragma journal_mode = WAL; pragma synchronous = normal; pragma temp_store = memory; pragma mmap_size = 30000000000;');
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
        OR name='notifications'
        OR name='logs'
        OR name='settings'
        OR name='layout_container_state'
        OR name='cve'
        OR name='cve_cpe'
        OR name='cve_reference'
        OR name='cve_affected_hosts'
        OR name='cve_import'
        OR name='cve_affected_hosts_import'");

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
         *  Si le nombre de tables présentes != 27 alors on tente de regénérer les tables
         */
        if ($this->countMainTables() != 27) {
            $this->generateMainTables();

            /**
             *  On compte de nouveau les tables après la tentative de re-génération, on retourne false si c'est toujours pas bon
             */
            if ($this->countMainTables() != 27) {
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
        Releasever VARCHAR(255),
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
         *  Insert default env if table is empty
         */
        $result = $this->query("SELECT Id FROM env");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO env ('Name') VALUES ('preprod')");
        }

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
        Api_key CHAR(32),
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
        OnlySyncDifference CHAR(3),	
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
            $this->exec("INSERT INTO profile_settings (Package_type, Manage_client_conf, Manage_client_repos) VALUES ('deb,rpm', 'no', 'no')");
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
         *  Generate notifications table if not exists
         */
        $this->exec("CREATE TABLE IF NOT EXISTS notifications (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Id_notification CHAR(5) NOT NULL,
        Title VARCHAR(255) NOT NULL,
        Message VARCHAR(255) NOT NULL,
        Status CHAR(9) NOT NULL)"); /* new, acquitted */

        /**
         *  Generate settings table if not exists
         */
        $this->exec("CREATE TABLE IF NOT EXISTS settings (
        /* General settings */
        REPOS_DIR VARCHAR(255) NOT NULL,
        EMAIL_RECIPIENT VARCHAR(255), /* EMAIL_RECIPIENT */
        DEBUG_MODE CHAR(5),
        REPO_CONF_FILES_PREFIX VARCHAR(255),
        TIMEZONE VARCHAR(255),
        /* Web settings */
        WWW_DIR VARCHAR(255) NOT NULL,
        WWW_USER VARCHAR(255),
        WWW_HOSTNAME VARCHAR(255),
        /* Update settings */
        UPDATE_AUTO CHAR(5),
        UPDATE_BRANCH VARCHAR(255),
        UPDATE_BACKUP CHAR(5),
        UPDATE_BACKUP_DIR VARCHAR(255),
        /* RPM settings */
        RPM_REPO CHAR(5),
        RPM_SIGN_PACKAGES CHAR(5),
        RPM_SIGN_METHOD VARCHAR(255),
        RELEASEVER CHAR(5),
        RPM_DEFAULT_ARCH VARCHAR(255),
        RPM_INCLUDE_SOURCE CHAR(5),
        /* DEB settings */
        DEB_REPO CHAR(5),
        DEB_SIGN_REPO CHAR(5),
        DEB_DEFAULT_ARCH VARCHAR(255),
        DEB_INCLUDE_SOURCE CHAR(5),
        DEB_DEFAULT_TRANSLATION VARCHAR(255),
        /* GPG settings */
        GPG_SIGNING_KEYID VARCHAR(255),
        /* Plans settings */
        PLANS_ENABLED CHAR(5),
        PLANS_REMINDERS_ENABLED CHAR(5),
        PLANS_UPDATE_REPO CHAR(5),
        PLANS_CLEAN_REPOS CHAR(5),
        RETENTION INTEGER,
        /* Stats settings */
        STATS_ENABLED CHAR(5),
        STATS_LOG_PATH VARCHAR(255),
        /* Hosts settings */
        MANAGE_HOSTS CHAR(5),
        MANAGE_PROFILES CHAR(5),
        /* CVE settings */
        CVE_IMPORT CHAR(5),
        CVE_IMPORT_TIME TIME,
        CVE_SCAN_HOSTS CHAR(5))");

        /**
         *  If settings table is empty then populate it
         */
        $result = $this->query("SELECT * FROM settings");
        if ($this->isempty($result) === true) {
            /**
             *  FQDN file is created by the dockerfile
             */
            if (file_exists(ROOT . '/.fqdn')) {
                $fqdn = trim(file_get_contents(ROOT . '/.fqdn'));
            } else {
                $fqdn = 'localhost';
            }

            /**
             *  GPG key Id
             */
            $gpgKeyId = 'repomanager@' . $fqdn;

            $this->exec("INSERT INTO settings (WWW_DIR, REPOS_DIR, EMAIL_RECIPIENT, DEBUG_MODE, REPO_CONF_FILES_PREFIX, TIMEZONE, WWW_USER, WWW_HOSTNAME, UPDATE_AUTO, UPDATE_BRANCH, UPDATE_BACKUP, UPDATE_BACKUP_DIR, RPM_REPO, RPM_SIGN_PACKAGES, RPM_SIGN_METHOD, RELEASEVER, RPM_DEFAULT_ARCH, RPM_INCLUDE_SOURCE, DEB_REPO, DEB_SIGN_REPO, DEB_DEFAULT_ARCH, DEB_INCLUDE_SOURCE, DEB_DEFAULT_TRANSLATION, GPG_SIGNING_KEYID, PLANS_ENABLED, PLANS_REMINDERS_ENABLED, PLANS_UPDATE_REPO, PLANS_CLEAN_REPOS, RETENTION, STATS_ENABLED, STATS_LOG_PATH, MANAGE_HOSTS, MANAGE_PROFILES, CVE_IMPORT, CVE_IMPORT_TIME, CVE_SCAN_HOSTS) VALUES ('/var/www/repomanager', '/home/repo', '', 'false', 'repomanager-', 'Europe/Paris', '" . 'www-data' . "', '$fqdn', 'false', 'stable', 'true', '/var/lib/repomanager/backups', 'true', 'true', 'rpmsign', '8', 'x86_64', 'false', 'true', 'true', 'amd64', 'false', '', '$gpgKeyId', 'false', 'false', 'false', 'false', '3', 'false', '/var/log/nginx/repomanager_access.log', 'false', 'false', 'false', '00:00', 'false')");
        }

        /**
         *  Generate cve table if not exists
         *  CVEs table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS cve (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Date DATE,
        Time TIME,
        Updated_date DATE,
        Updated_time TIME,
        Cpe23Uri VARCHAR(255),
        Parts VARCHAR(255),
        Description VARCHAR(255),
        Cvss2_score CHAR(3),
        Cvss3_score CHAR(3))");

        $this->exec("CREATE TABLE IF NOT EXISTS cve_cpe (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Part CHAR(1) NOT NULL,
        Vendor VARCHAR(255) NOT NULL COLLATE NOCASE,
        Product VARCHAR(255) NOT NULL COLLATE NOCASE,
        Version VARCHAR(255) NOT NULL COLLATE NOCASE,
        Id_cve INTEGER NOT NULL)");

        $this->exec("CREATE TABLE IF NOT EXISTS cve_reference (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255),
        Url VARCHAR(255),
        Source VARCHAR(255),
        Tags VARCHAR(255),
        Id_cve INTEGER NOT NULL)");

        $this->exec("CREATE TABLE IF NOT EXISTS cve_affected_hosts (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Host_id INTEGER NOT NULL,
        Product VARCHAR(255) NOT NULL,
        Version VARCHAR(255) NOT NULL,
        Status CHAR(8) NOT NULL, /* possible / affected */
        Id_cve INTEGER NOT NULL)");

        $this->exec("CREATE TABLE IF NOT EXISTS cve_import (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Duration INTEGER,
        Status CHAR(7) NOT NULL)"); /* running, error, done */

        $this->exec("CREATE TABLE IF NOT EXISTS cve_affected_hosts_import (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Duration INTEGER,
        Status CHAR(7) NOT NULL)"); /* running, error, done */

        /**
         *  Create indexes
         */
        $this->exec("CREATE INDEX IF NOT EXISTS cve_index ON cve (Name, Updated_date, Updated_time)");
        $this->exec("CREATE INDEX IF NOT EXISTS cve_cpe_index ON cve_cpe (Part, Vendor, Product, Version, Id_cve)");
        $this->exec("CREATE INDEX IF NOT EXISTS cve_affected_hosts_index ON cve_affected_hosts (Status, Id_cve)");

        /**
         *  Generate logs table if not exists
         */
        $this->exec("CREATE TABLE IF NOT EXISTS logs (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Type CHAR(5) NOT NULL, /* info, error */
        Component VARCHAR(255),
        Message VARCHAR(255) NOT NULL,
        Status CHAR(9) NOT NULL)"); /* new, acquitted */

        /**
         *  Generate layout_container_state table if not exists
         */
        $this->exec("CREATE TABLE IF NOT EXISTS layout_container_state (
        Container VARCHAR(255) NOT NULL,
        Id INTEGER NOT NULL)");
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
        Reboot_required CHAR(5),
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
     *  Return true if table name exists
     */
    public function tableExist(string $tableName)
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tableName}'");

        if ($this->count($result) > 0) {
            return true;
        }

        return false;
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
