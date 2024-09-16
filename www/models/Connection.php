<?php

namespace Models;

use SQLite3;
use Exception;

class Connection extends SQLite3
{
    public function __construct(string $database, string|null $hostId = null, bool $check = true)
    {
        /**
         *  Open database from its name
         *  If database does not exist it is automatically created
         */
        try {
            if (!is_dir(DB_DIR)) {
                if (!mkdir(DB_DIR, 0777, true)) {
                    throw new Exception('Unable to create database directory');
                }
            }

            /**
             *  Open database
             *
             *  Case where database is 'main', it is the main database 'repomanager.db'
             */
            if ($database == 'main') {
                $this->open(DB);
                $this->busyTimeout(10000);
                $this->enableExceptions(true);
                $this->enableWAL();

                /**
                 *  If check is true, check if tables are missing before generating them
                 *  This avoid to execute all CREATE tables queries each time the class is instanciated, and should speed up page loading
                 */
                if ($check) {
                    $this->checkMainTables();
                } else {
                    $this->generateMainTables();
                }

            /**
             *  Case where database is 'stats', it is the stats database 'repomanager-stats.db'
             */
            } elseif ($database == 'stats') {
                $this->open(STATS_DB);
                $this->busyTimeout(15000);
                $this->enableExceptions(true);
                $this->enableWAL();

                /**
                 *  If check is true, check if tables are missing before generating them
                 *  This avoid to execute all CREATE tables queries each time the class is instanciated, and should speed up page loading
                 */
                if ($check) {
                    $this->checkStatsTables();
                } else {
                    $this->generateStatsTables();
                }

            /**
             *  Case where database is 'hosts', it is the hosts database 'repomanager-hosts.db'
             */
            } elseif ($database == 'hosts') {
                $this->open(HOSTS_DB);
                $this->busyTimeout(15000);
                $this->enableExceptions(true);
                $this->enableWAL();

                /**
                 *  If check is true, check if tables are missing before generating them
                 *  This avoid to execute all CREATE tables queries each time the class is instanciated, and should speed up page loading
                 */
                if ($check) {
                    $this->checkHostsTables();
                } else {
                    $this->generateHostsTables();
                }

            /**
             *  Case where database is 'host', it is a host database 'properties.db', hostId must be set
             */
            } elseif ($database == 'host') {
                $this->open(HOSTS_DIR . '/' . $hostId . '/properties.db');
                $this->busyTimeout(15000);
                $this->enableExceptions(true);
                $this->enableWAL();
                $this->generateHostTables();

            /**
             *  Case where database is not 'main', 'stats', 'hosts' or 'host'
             */
            } else {
                throw new Exception("unknown database: $database");
            }
        } catch (\Exception $e) {
            die('Error while opening database: ' . $e->getMessage());
        }
    }

    /**
     *  Enable WAL mode
     */
    private function enableWAL()
    {
        $this->exec('pragma journal_mode = WAL; pragma synchronous = normal; pragma temp_store = memory; pragma mmap_size = 30000000000;');
    }

    /**
     *  Disable WAL mode
     */
    private function disableWAL()
    {
        $this->exec('pragma journal_mode = DELETE;');
    }

    /**
     *
     *  Functions to check if all tables are present
     *
     */

    /**
     *  Count the number of tables in the main database
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
        OR name='tasks'
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

        return $this->count($result);
    }

    /**
     *  Count the number of tables in the stats database
     */
    public function countStatsTables()
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table'
        and name='stats'
        OR name='access_deb'
        OR name='access_rpm'
        OR name='access_queue'");

        return $this->count($result);
    }

    /**
     *  Count the number of tables in the hosts database
     */
    public function countHostsTables()
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table'
        and name='ws_connections'
        OR name='ws_requests'
        OR name='hosts'
        OR name='groups'
        OR name='group_members'
        OR name='settings'");

        return $this->count($result);
    }

    /**
     *  Check if all tables are present in the main database
     */
    public function checkMainTables()
    {
        $required = 26;

        /**
         *  If the number of tables != $required then we try to regenerate the tables
         */
        if ($this->countMainTables() != $required) {
            $this->generateMainTables();

            /**
             *  Count again the number of tables after the regeneration attempt, return false if it's still not good
             */
            if ($this->countMainTables() != $required) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Check if all tables are present in the stats database
     */
    public function checkStatsTables()
    {
        $required = 4;

        /**
         *  If the number of tables != $required then we try to regenerate the tables
         */
        if ($this->countStatsTables() != $required) {
            $this->generateStatsTables();

            /**
             *  Count again the number of tables after the regeneration attempt, return false if it's still not good
             */
            if ($this->countStatsTables() != $required) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Check if all tables are present in the hosts database
     */
    public function checkHostsTables()
    {
        $required = 6;

        /**
         *  If the number of tables != $required then we try to regenerate the tables
         */
        if ($this->countHostsTables() != $required) {
            $this->generateHostsTables();

            /**
             *  Count again the number of tables after the regeneration attempt, return false if it's still not good
             */
            if ($this->countHostsTables() != $required) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     *  Functions to generate tables if not exists
     *
     */

    /**
     *  Generate tables in the main database
     */
    private function generateMainTables()
    {
        /**
         *  repos table
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
         *  repos_snap table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS repos_snap (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Signed CHAR(5) NOT NULL, /* true, false */
        Arch VARCHAR(255),
        Pkg_translation VARCHAR(255),
        Pkg_included VARCHAR(255),
        Pkg_excluded VARCHAR(255),
        Type CHAR(6) NOT NULL,
        Reconstruct CHAR(8), /* needed, running, failed */
        Status CHAR(8) NOT NULL,
        Id_repo INTEGER NOT NULL)");

        /**
         *  repos_env table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS repos_env (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Env VARCHAR(255),
        Description VARCHAR(255),
        Id_snap INTEGER NOT NULL)");

        /**
         *  env table
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
         *  sources table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS sources (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Type CHAR(3) NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Url VARCHAR(255) NOT NULL,
        Gpgkey VARCHAR(255),
        Ssl_certificate_path VARCHAR(255),
        Ssl_private_key_path VARCHAR(255),
        Ssl_ca_certificate_path VARCHAR(255))");

        /**
         *  users table
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
         *  user_role table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS user_role (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name CHAR(15) NOT NULL UNIQUE)");

        /**
         *  If user_role table is empty (just created) then we create default roles
         */
        $result = $this->query("SELECT Id FROM user_role");
        if ($this->isempty($result) === true) {
            /**
             *  super-administrator role: all rights
             */
            $this->exec("INSERT INTO user_role ('Name') VALUES ('super-administrator')");

            /**
             *  administrator role: all rights except user management (only super-administrator can manage users)
             */
            $this->exec("INSERT INTO user_role ('Name') VALUES ('administrator')");

            /**
             *  usage role: read-only rights
             */
            $this->exec("INSERT INTO user_role ('Name') VALUES ('usage')");
        }

        /**
         *  If users table is empty (just created) then we create admin user (default password 'repomanager' and role n°1 (super-administrator))
         */
        $result = $this->query("SELECT Id FROM users");
        if ($this->isempty($result) === true) {
            $password_hashed = '$2y$10$FD6/70o2nXPf76SAPYIGSutauQ96LqKie5PLanoYBNbCWen492cX6';

            try {
                $stmt = $this->prepare("INSERT INTO users ('Username', 'Password', 'First_name', 'Role', 'State', 'Type') VALUES ('admin', :password_hashed, 'Administrator', '1', 'active', 'local')");
                $stmt->bindValue(':password_hashed', $password_hashed);
                $stmt->execute();
            } catch (\Exception $e) {
                $this->logError($e);
            }
        }

        /**
         *  history table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS history (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Id_user INTEGER NOT NULL,
        Action VARCHAR(255) NOT NULL,
        State CHAR(7))"); /* success ou error */

        /**
         *  groups table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS groups (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");

        /**
         *  group_members table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS group_members (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Id_repo INTEGER NOT NULL,
        Id_group INTEGER NOT NULL);");

        $this->exec("CREATE TABLE IF NOT EXISTS tasks (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Type CHAR(9), /* immediate, scheduled */
        Date DATE,
        Time TIME,
        Raw_params TEXT NOT NULL,
        Pid INTEGER,
        Logfile VARCHAR(255),
        Duration INTEGER,
        Status CHAR(9))"); /* new, scheduled, running, done, stopped */

        /**
         *  profile_settings table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_settings (
        Package_type VARCHAR(255))");

        /**
         *  If profile_settings table is empty (just created) then we populate it
         */
        $result = $this->query("SELECT * FROM profile_settings");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO profile_settings (Package_type) VALUES ('deb,rpm')");
        }

        /**
         *  profile table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Package_exclude VARCHAR(255),
        Package_exclude_major VARCHAR(255),
        Service_restart VARCHAR(255),
        Notes VARCHAR(255))");

        /**
         *  profile_repo_members table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_repo_members (
        Id_profile INTEGER NOT NULL,
        Id_repo INTEGER NOT NULL)");

        /**
         *  profile_package table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_package (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");

        /**
         *  If profile_package table is empty (just created) then we populate it
         */
        $result = $this->query("SELECT Id FROM profile_package");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO profile_package (Name) VALUES ('apache'), ('httpd'), ('php'), ('php-fpm'), ('mysql'), ('fail2ban'), ('nrpe'), ('munin-node'), ('node'), ('newrelic'), ('nginx'), ('haproxy'), ('netdata'), ('nfs'), ('rsnapshot'), ('kernel'), ('java'), ('redis'), ('varnish'), ('mongo'), ('rabbit'), ('clamav'), ('clam'), ('gpg'), ('gnupg')");
        }

        /**
         *  profile_service table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS profile_service (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");

        /**
         *  If profile_service table is empty (just created) then we populate it
         */
        $result = $this->query("SELECT Id FROM profile_service");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO profile_service (Name) VALUES ('apache'), ('httpd'), ('php-fpm'), ('mysqld'), ('fail2ban'), ('nrpe'), ('munin-node'), ('nginx'), ('haproxy'), ('netdata'), ('nfsd'), ('redis'), ('varnish'), ('mongod'), ('clamd')");
        }

        /**
         *  notifications table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS notifications (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Id_notification CHAR(5) NOT NULL,
        Title VARCHAR(255) NOT NULL,
        Message VARCHAR(255) NOT NULL,
        Status CHAR(9) NOT NULL)"); /* new, acquitted */

        /**
         *  settings table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS settings (
        /* General settings */
        DEBUG_MODE CHAR(5),
        TIMEZONE VARCHAR(255),
        EMAIL_RECIPIENT VARCHAR(255),
        PROXY VARCHAR(255),
        TASK_EXECUTION_MEMORY_LIMIT INTEGER,
        /* Repo settings */
        RETENTION INTEGER,
        REPO_CONF_FILES_PREFIX VARCHAR(255),
        /* Mirroring */
        MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT INTEGER,
        /* RPM */
        RPM_REPO CHAR(5),
        RPM_SIGN_PACKAGES CHAR(5),
        RELEASEVER CHAR(5),
        RPM_DEFAULT_ARCH VARCHAR(255),
        RPM_MISSING_SIGNATURE VARCHAR(255), /* download, ignore, error */
        RPM_INVALID_SIGNATURE VARCHAR(255), /* download, ignore, error */
        /* DEB */
        DEB_REPO CHAR(5),
        DEB_SIGN_REPO CHAR(5),
        DEB_DEFAULT_ARCH VARCHAR(255),
        DEB_DEFAULT_TRANSLATION VARCHAR(255),
        DEB_INVALID_SIGNATURE VARCHAR(255), /* ignore, error */
        /* GPG signing key */
        GPG_SIGNING_KEYID VARCHAR(255),
        /* Scheduled tasks settings */
        SCHEDULED_TASKS_REMINDERS CHAR(5),
        /* Statistics & metrics settings */
        STATS_ENABLED CHAR(5),
        /* Hosts and profiles settings */
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

            $this->exec("INSERT INTO settings (
                EMAIL_RECIPIENT,
                DEBUG_MODE,
                REPO_CONF_FILES_PREFIX,
                TIMEZONE,
                TASK_EXECUTION_MEMORY_LIMIT,
                MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT,
                RPM_REPO,
                RPM_SIGN_PACKAGES,
                RELEASEVER,
                RPM_DEFAULT_ARCH,
                RPM_MISSING_SIGNATURE,
                RPM_INVALID_SIGNATURE,
                DEB_REPO,
                DEB_SIGN_REPO,
                DEB_DEFAULT_ARCH,
                DEB_DEFAULT_TRANSLATION,
                DEB_INVALID_SIGNATURE,
                GPG_SIGNING_KEYID,
                SCHEDULED_TASKS_REMINDERS,
                RETENTION,
                STATS_ENABLED,
                MANAGE_HOSTS,
                MANAGE_PROFILES,
                CVE_IMPORT,
                CVE_IMPORT_TIME,
                CVE_SCAN_HOSTS
            )
            VALUES (
                '',
                'false',
                'repomanager-',
                'Europe/Paris',
                '512',
                '300',
                'true',
                'true',
                '8',
                'noarch,x86_64',
                'error',
                'error',
                'true',
                'true',
                'amd64',
                '',
                'error',
                '$gpgKeyId',
                'false',
                '3',
                'false',
                'false',
                'false',
                'false',
                '00:00',
                'false'
            )");
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
        Details TEXT,
        Status CHAR(9) NOT NULL)"); /* new, acquitted */

        /**
         *  Generate layout_container_state table if not exists
         */
        $this->exec("CREATE TABLE IF NOT EXISTS layout_container_state (
        Container VARCHAR(255) NOT NULL,
        Id INTEGER NOT NULL)");
    }

    /**
     *  Generate tables in the stats database
     */
    private function generateStatsTables()
    {
        /**
         *  stats table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS stats (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Size INTEGER NOT NULL,
        Packages_count INTEGER NOT NULL,
        Id_env INTEGER NOT NULL)");

        /**
         *  access_deb table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS access_deb (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Dist VARCHAR(255) NOT NULL,
        Section VARCHAR(255) NOT NULL,
        Env VARCHAR(255) NOT NULL,
        Source VARCHAR(255) NOT NULL,
        IP VARCHAR(16) NOT NULL,
        Request VARCHAR(255) NOT NULL,
        Request_result VARCHAR(8) NOT NULL)");

        /**
         *  access_rpm table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS access_rpm (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Env VARCHAR(255) NOT NULL,
        Source VARCHAR(255) NOT NULL,
        IP VARCHAR(16) NOT NULL,
        Request VARCHAR(255) NOT NULL,
        Request_result VARCHAR(8) NOT NULL)");

        /**
         *  access_queue table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS access_queue (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Request VARCHAR(255) NOT NULL)");

        /**
         *  Create indexes
         */
        // Indexes for access_deb:
        $this->exec("CREATE INDEX IF NOT EXISTS access_deb_index ON access_deb (Date, Time, Name, Dist, Section, Env, Source, IP, Request, Request_result)");
        $this->exec("CREATE INDEX IF NOT EXISTS access_deb_name_env_index ON access_deb (Name, Dist, Section, Env)"); // To optimize SELECT COUNT(*)
        // Indexes for access_rpm:
        $this->exec("CREATE INDEX IF NOT EXISTS access_rpm_index ON access_rpm (Date, Time, Name, Env, Source, IP, Request, Request_result)");
        $this->exec("CREATE INDEX IF NOT EXISTS access_rpm_name_env_index ON access_rpm (Name, Env)"); // To optimize SELECT COUNT(*)
        // Index for stats:
        $this->exec("CREATE INDEX IF NOT EXISTS stats_index ON stats (Date, Time, Size, Packages_count, Id_env)");
    }

    /**
     *  Generate tables in the hosts database
     */
    private function generateHostsTables()
    {
        /**
         *  ws_connections table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS ws_connections (
        Connection_id INTEGER,
        Id_host INTEGER,
        Authenticated CHAR(5))"); /* true, false */

        /**
         *  ws_requests table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS ws_requests (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Request VARCHAR(255) NOT NULL,
        Request_json VARCHAR(255),
        Status VARCHAR(255) NOT NULL, /* new, sent, received, failed, completed */
        Info VARCHAR(255), /* error or info message */
        Info_json VARCHAR(255), /* Info message with JSON */
        Response VARCHAR(255),
        Response_json VARCHAR(255),
        Retry INTEGER NOT NULL,
        Next_retry VARCHAR(255),
        Id_host INTEGER NOT NULL)");

        /**
         *  hosts table
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
        Online_status CHAR(8), /* online / unreachable */
        Online_status_date DATE,
        Online_status_time TIME,
        Reboot_required CHAR(5),
        Linupdate_version VARCHAR(255),
        Status VARCHAR(8) NOT NULL)"); /* active / disabled / deleted */

        /**
         *  groups table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS groups (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) UNIQUE NOT NULL)");

        /**
         *  group_members table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS group_members (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Id_host INTEGER NOT NULL,
        Id_group INTEGER NOT NULL)");

        /**
         *  settings table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS settings (
        pkgs_count_considered_outdated INTEGER NOT NULL,
        pkgs_count_considered_critical INTEGER NOT NULL)");

        /**
         *  If settings table is empty then populate it
         */
        $result = $this->query("SELECT pkgs_count_considered_outdated FROM settings");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO settings ('pkgs_count_considered_outdated', 'pkgs_count_considered_critical') VALUES ('1', '10')");
        }

        /**
         *  Create indexes
         */
        // hosts table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_index ON hosts (Ip, Hostname, Os, Os_version, Os_family, Kernel, Arch, Type, Profile, Env, AuthId, Token, Online_status, Online_status_date, Online_status_time, Reboot_required, Linupdate_version, Status)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_authid_index ON hosts (AuthId)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_token_index ON hosts (Token)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_authid_token_status_index ON hosts (AuthId, Token, Status)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_hostname_index ON hosts (Hostname)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_kernel_index ON hosts (Kernel)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_profile_index ON hosts (Profile)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_status_online_status_date_time ON hosts (Status, Online_status, Online_status_date, Online_status_time)");
        // groups table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS groups_index ON groups (Name)");
        // group_members table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS group_members_index ON group_members (Id_host, Id_group)");
        $this->exec("CREATE INDEX IF NOT EXISTS group_members_id_host_index ON group_members (Id_host)");
        $this->exec("CREATE INDEX IF NOT EXISTS group_members_id_group_index ON group_members (Id_group)");
        // ws_connections table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS ws_connections_authenticated ON ws_connections (Authenticated)");
        $this->exec("CREATE INDEX IF NOT EXISTS ws_connections_connection_id ON ws_connections (Connection_id)");
        $this->exec("CREATE INDEX IF NOT EXISTS ws_connections_id_host ON ws_connections (Id_host)");
        // ws_requests table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS ws_requests_id_host ON ws_requests (Id_host)");
        $this->exec("CREATE INDEX IF NOT EXISTS ws_requests_status ON ws_requests (Status)");
        $this->exec("CREATE INDEX IF NOT EXISTS ws_requests_date_time ON ws_requests (Date, Time)");
    }

    /**
     *  Generate tables in the database dedicated to a host
     *  This function is public because it can be called when resetting a host
     */
    public function generateHostTables()
    {
        /**
         *  packages table
         *  Inventory of all packages installed on the host
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
         *  history table
         *  History of all packages installed on the host (installed, updated, removed)
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
         *  packages_available table
         *  Available packages for update
         */
        $this->exec("CREATE TABLE IF NOT EXISTS packages_available (
        Name VARCHAR(255),
        Version VARCHAR(255))");

        /**
         *  events table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS events (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Date_end DATE NOT NULL,
        Time_end TIME NOT NULL,
        Report VARCHAR(255),
        Status VARCHAR(7))"); /* error / warning / unknow / done */

        /**
         *  Create indexes
         */
        // packages table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_available_name_version ON packages_available (Name, Version);");
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_name_version ON packages (Name, Version);");
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_state ON packages (State);");
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_id_event_state ON packages (Id_event, State)");
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_state_date ON packages (State, Date)");
        // packages_history table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_history_id_event_State ON packages_history (Id_event, State)");
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_history_name ON packages_history (Name)");
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_history_state ON packages_history (State)");
        $this->exec("CREATE INDEX IF NOT EXISTS host_packages_history_state_date ON packages_history (State, Date)");
    }

    /**
     *  Return true if result is empty and false if not
     */
    public function isempty($result)
    {
        $count = 0;

        /**
         *  Count the number of rows returned by the query
         */
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $count++;
        }

        /**
         *  If count == 0 then result is empty
         */
        if ($count == 0) {
            return true;
        }

        return false;
    }

    /**
     *  Return the number of rows resulting from a query
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

    /**
     *  Log a database error in database
     */
    public function logError(string $exception = null)
    {
        $logController = new \Controllers\Log\Log();

        if (!empty($exception)) {
            $logController->log('error', 'Database', 'An error occured while executing request in database.', $exception);
        } else {
            $logController->log('error', 'Database', 'An error occured while executing request in database.');
        }
    }
}
