<?php

namespace Models;

use Controllers\Database\Log as DbLog;
use SQLite3;
use Exception;

class Connection extends SQLite3
{
    public function __construct(string $database, int|null $databaseId = null, bool $check = true)
    {
        /**
         *  Open database from its name
         *  If database does not exist it is automatically created
         */
        try {
            if (!is_dir(DB_DIR)) {
                if (!mkdir(DB_DIR, 0770, true)) {
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
                $this->busyTimeout(30000);
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
                $this->busyTimeout(30000);
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
                $this->busyTimeout(30000);
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
             *  Case where database is 'host', it is a host database 'properties.db', databaseId must be set
             */
            } elseif ($database == 'host' and isset($databaseId)) {
                $this->open(HOSTS_DIR . '/' . $databaseId . '/properties.db');
                $this->busyTimeout(30000);
                $this->enableExceptions(true);
                $this->enableWAL();
                $this->generateHostTables();

            /**
             *  Case where database is 'ws', it is the websockets database 'repomanager-ws.db'
             */
            } elseif ($database == 'ws') {
                $this->open(WS_DB);
                $this->busyTimeout(30000);
                $this->enableExceptions(true);
                $this->enableWAL();
                $this->generateWsTables();

            /**
             *  Case where database is 'task-log', it is a task log database 'task-<databaseId>-log.db'
             */
            } elseif ($database == 'task-log' and isset($databaseId)) {
                $this->open(MAIN_LOGS_DIR . '/repomanager-task-' . $databaseId . '-log.db');
                $this->busyTimeout(30000);
                $this->enableExceptions(true);
                $this->enableWAL();
                $this->generateTaskLogTables();

            /**
             *  Case where database is not 'main', 'stats', 'hosts' or 'host'
             */
            } else {
                throw new Exception("unknown database: $database");
            }
        } catch (Exception $e) {
            throw new Exception('Error while opening ' . $database . ' database: ' . $e->getMessage());
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
     *  Count the number of tables in the database
     */
    public function countTables()
    {
        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        return $this->count($result);
    }

    /**
     *  Check if all tables are present in the main database
     */
    public function checkMainTables()
    {
        $required = 28;

        /**
         *  If the number of tables != $required then we try to regenerate the tables
         */
        if ($this->countTables() != $required) {
            $this->generateMainTables();

            /**
             *  Count again the number of tables after the regeneration attempt, return false if it's still not good
             */
            if ($this->countTables() != $required) {
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
        if ($this->countTables() != $required) {
            $this->generateStatsTables();

            /**
             *  Count again the number of tables after the regeneration attempt, return false if it's still not good
             */
            if ($this->countTables() != $required) {
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
        $required = 5;

        /**
         *  If the number of tables != $required then we try to regenerate the tables
         */
        if ($this->countTables() != $required) {
            $this->generateHostsTables();

            /**
             *  Count again the number of tables after the regeneration attempt, return false if it's still not good
             */
            if ($this->countTables() != $required) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Check if all tables are present in a ws database
     */
    public function checkWsTables()
    {
        $required = 1;

        /**
         *  If the number of tables != $required then we try to regenerate the tables
         */
        if ($this->countTables() != $required) {
            $this->generateWsTables();

            /**
             *  Count again the number of tables after the regeneration attempt, return false if it's still not good
             */
            if ($this->countTables() != $required) {
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
         *  Create indexes
         */
        $this->exec("CREATE INDEX IF NOT EXISTS repos_ALL_index ON repos (Name, Releasever, Dist, Section, Source, Package_type)");

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
         *  Create indexes
         */
        $this->exec("CREATE INDEX IF NOT EXISTS repos_snap_index ON repos_snap (Date, Time, Signed, Arch, Pkg_translation, Pkg_included, Pkg_excluded, Type, Reconstruct, Status, Id_repo)");
        $this->exec("CREATE INDEX IF NOT EXISTS repos_snap_status_id_repo_index ON repos_snap (Status, Id_repo)");
        $this->exec("CREATE INDEX IF NOT EXISTS repos_snap_id_repo_index ON repos_snap (Id_repo)");

        /**
         *  repos_env table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS repos_env (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Env VARCHAR(255),
        Description VARCHAR(255),
        Id_snap INTEGER NOT NULL)");

        /**
         *  Create indexes
         */
        $this->exec("CREATE INDEX IF NOT EXISTS repos_env_index ON repos_env (Env, Description, Id_snap)");

        /**
         *  Create indexes
         */
        $this->exec("CREATE INDEX IF NOT EXISTS repos_env_id_snap_index ON repos_env (Id_snap)");

        /**
         *  env table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS env (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Name VARCHAR(255) NOT NULL,
        Color VARCHAR(255))");

        /**
         *  Insert default env if table is empty
         */
        $result = $this->query("SELECT Id FROM env");
        if ($this->isempty($result) === true) {
            $this->exec("INSERT INTO env ('Name', 'Color') VALUES ('preprod', '#ffffff')");
            $this->exec("INSERT INTO env ('Name', 'Color') VALUES ('prod', '#F32F63')");
        }

        /**
         *  sources table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS sources (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Definition TEXT,
        Method VARCHAR(255))");

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
         *  Create user_permissions table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS user_permissions (
        Permissions VARCHAR(255),
        User_id INTEGER NOT NULL)");

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
             *  super-administrator role: all permissions
             */
            $this->exec("INSERT INTO user_role ('Name') VALUES ('super-administrator')");

            /**
             *  administrator role: all permissions except user management (only super-administrator can manage users)
             */
            $this->exec("INSERT INTO user_role ('Name') VALUES ('administrator')");

            /**
             *  usage role: read-only permissions
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
            } catch (Exception $e) {
                DbLog::error($e);
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
        Username VARCHAR(255),
        Ip VARCHAR(255),
        Ip_forwarded VARCHAR(255),
        User_agent VARCHAR(255),
        Action VARCHAR(255) NOT NULL,
        State CHAR(7))"); /* success or error */

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

        /**
         *  Create indexes
         */
        $this->exec("CREATE INDEX IF NOT EXISTS group_members_id_repo_index ON group_members (Id_repo)");
        $this->exec("CREATE INDEX IF NOT EXISTS group_members_id_group_index ON group_members (Id_group)");

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
         *  Create indexes
         */
        $this->exec("CREATE INDEX IF NOT EXISTS tasks_rawparams_status ON tasks (Raw_params, Status)");
        $this->exec("CREATE INDEX IF NOT EXISTS tasks_status ON tasks (Status)");

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
        Service_reload VARCHAR(255),
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
         *  Create system_monitoring table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS system_monitoring (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Timestamp VARCHAR(255) NOT NULL,
        Cpu_usage REAL,
        Memory_usage REAL,
        Disk_usage REAL)");

        /**
         *  Create indexes on system_monitoring table
         */
        $this->exec("CREATE INDEX IF NOT EXISTS system_monitoring_index ON system_monitoring (Timestamp, Cpu_usage, Memory_usage, Disk_usage)");

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
        SESSION_TIMEOUT INTEGER,
        PROXY VARCHAR(255),
        TASK_EXECUTION_MEMORY_LIMIT INTEGER,
        TASK_QUEUING CHAR(5),
        TASK_QUEUING_MAX_SIMULTANEOUS INTEGER,
        TASK_CLEAN_OLDER_THAN INTEGER,
        /* Repo settings */
        REPO_DEDUPLICATION CHAR(5), /* true, false */
        RETENTION INTEGER,
        REPO_CONF_FILES_PREFIX VARCHAR(255),
        /* Mirroring */
        MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT INTEGER,
        MIRRORING_PACKAGE_CHECKSUM_FAILURE VARCHAR(20), /* error, ignore, keep */
        /* RPM */
        RPM_REPO CHAR(5),
        RPM_SIGN_PACKAGES CHAR(5),
        RELEASEVER CHAR(5),
        RPM_DEFAULT_ARCH VARCHAR(255),
        RPM_MISSING_SIGNATURE VARCHAR(255), /* download, ignore, error */
        RPM_INVALID_SIGNATURE VARCHAR(255), /* download, ignore, error */
        RPM_SIGNATURE_FAIL VARCHAR(255), /* keep, ignore, error */
        /* DEB */
        DEB_REPO CHAR(5),
        DEB_SIGN_REPO CHAR(5),
        DEB_DEFAULT_ARCH VARCHAR(255),
        DEB_DEFAULT_TRANSLATION VARCHAR(255),
        DEB_ALLOW_EMPTY_REPO CHAR(5),
        DEB_INVALID_SIGNATURE VARCHAR(255), /* ignore, error */
        /* GPG signing key */
        GPG_SIGNING_KEYID VARCHAR(255),
        /* Scheduled tasks settings */
        SCHEDULED_TASKS_REMINDERS CHAR(5),
        /* Statistics & metrics settings */
        STATS_ENABLED CHAR(5),
        /* Hosts and profiles settings */
        MANAGE_HOSTS CHAR(5),
        /* CVE settings */
        CVE_IMPORT CHAR(5),
        CVE_IMPORT_TIME TIME,
        CVE_SCAN_HOSTS CHAR(5),
        /* OIDC settings */
        OIDC_ENABLED CHAR(5),
        SSO_OIDC_ONLY CHAR(5),
        OIDC_PROVIDER_URL VARCHAR(255),
        OIDC_AUTHORIZATION_ENDPOINT VARCHAR(255),
        OIDC_TOKEN_ENDPOINT VARCHAR(255),
        OIDC_USERINFO_ENDPOINT VARCHAR(255),
        OIDC_SCOPES VARCHAR(255),
        OIDC_CLIENT_ID VARCHAR(255),
        OIDC_CLIENT_SECRET VARCHAR(255),
        OIDC_USERNAME VARCHAR(255),
        OIDC_FIRST_NAME VARCHAR(255),
        OIDC_LAST_NAME VARCHAR(255),
        OIDC_EMAIL VARCHAR(255),
        OIDC_GROUPS VARCHAR(255),
        OIDC_GROUP_ADMINISTRATOR VARCHAR(255),
        OIDC_GROUP_SUPER_ADMINISTRATOR VARCHAR(255),
        OIDC_HTTP_PROXY VARCHAR(255),
        OIDC_CERT_PATH VARCHAR(255))");

        /**
         *  If settings table is empty then populate it
         */
        $result = $this->query("SELECT * FROM settings");
        if ($this->isempty($result) === true) {
            /**
             *  Set default values
             */
            $fqdn = 'localhost';

            /**
             *  FQDN file is created on container startup (entrypoint)
             */
            if (file_exists(ROOT . '/.fqdn')) {
                $fqdn = trim(file_get_contents(ROOT . '/.fqdn'));
            }

            /**
             *  GPG key Id
             */
            $gpgKeyId = 'repomanager@' . $fqdn;

            /**
             *  For each OIDC setting, use the value from custom settings file (app.yaml) if defined, otherwise use default value
             *  (if a value was not defined, it means that there was no app.yaml file or the value was not defined in it)
             */
            $oidcEnabled = defined('OIDC_ENABLED') ? OIDC_ENABLED : 'false';
            $ssoOidcOnly = defined('SSO_OIDC_ONLY') ? SSO_OIDC_ONLY : 'false';
            $oidcProviderUrl = defined('OIDC_PROVIDER_URL') ? OIDC_PROVIDER_URL : '';
            $oidcAuthorizationEndpoint = defined('OIDC_AUTHORIZATION_ENDPOINT') ? OIDC_AUTHORIZATION_ENDPOINT : '';
            $oidcTokenEndpoint = defined('OIDC_TOKEN_ENDPOINT') ? OIDC_TOKEN_ENDPOINT : '';
            $oidcUserinfoEndpoint = defined('OIDC_USERINFO_ENDPOINT') ? OIDC_USERINFO_ENDPOINT : '';
            $oidcScopes = defined('OIDC_SCOPES') ? OIDC_SCOPES : 'groups,email,profile';
            $oidcClientId = defined('OIDC_CLIENT_ID') ? OIDC_CLIENT_ID : '';
            $oidcClientSecret = defined('OIDC_CLIENT_SECRET') ? OIDC_CLIENT_SECRET : '';
            $oidcUsername = defined('OIDC_USERNAME') ? OIDC_USERNAME : 'preferred_username';
            $oidcFirstName = defined('OIDC_FIRST_NAME') ? OIDC_FIRST_NAME : 'given_name';
            $oidcLastName = defined('OIDC_LAST_NAME') ? OIDC_LAST_NAME : 'family_name';
            $oidcEmail = defined('OIDC_EMAIL') ? OIDC_EMAIL : 'email';
            $oidcGroups = defined('OIDC_GROUPS') ? OIDC_GROUPS : 'groups';
            $oidcGroupAdministrator = defined('OIDC_GROUP_ADMINISTRATOR') ? OIDC_GROUP_ADMINISTRATOR : 'administrator';
            $oidcGroupSuperAdministrator = defined('OIDC_GROUP_SUPER_ADMINISTRATOR') ? OIDC_GROUP_SUPER_ADMINISTRATOR : 'super-administrator';
            $oidcHttpProxy = defined('OIDC_HTTP_PROXY') ? OIDC_HTTP_PROXY : '';
            $oidcCertPath = defined('OIDC_CERT_PATH') ? OIDC_CERT_PATH : '';

            $this->exec("INSERT INTO settings (
                EMAIL_RECIPIENT,
                SESSION_TIMEOUT,
                DEBUG_MODE,
                REPO_DEDUPLICATION,
                REPO_CONF_FILES_PREFIX,
                TIMEZONE,
                TASK_EXECUTION_MEMORY_LIMIT,
                TASK_QUEUING,
                TASK_QUEUING_MAX_SIMULTANEOUS,
                TASK_CLEAN_OLDER_THAN,
                MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT,
                MIRRORING_PACKAGE_CHECKSUM_FAILURE,
                RPM_REPO,
                RPM_SIGN_PACKAGES,
                RELEASEVER,
                RPM_DEFAULT_ARCH,
                RPM_MISSING_SIGNATURE,
                RPM_INVALID_SIGNATURE,
                RPM_SIGNATURE_FAIL,
                DEB_REPO,
                DEB_SIGN_REPO,
                DEB_DEFAULT_ARCH,
                DEB_DEFAULT_TRANSLATION,
                DEB_ALLOW_EMPTY_REPO,
                DEB_INVALID_SIGNATURE,
                GPG_SIGNING_KEYID,
                SCHEDULED_TASKS_REMINDERS,
                RETENTION,
                STATS_ENABLED,
                MANAGE_HOSTS,
                CVE_IMPORT,
                CVE_IMPORT_TIME,
                CVE_SCAN_HOSTS,
                OIDC_ENABLED,
                SSO_OIDC_ONLY,
                OIDC_PROVIDER_URL,
                OIDC_AUTHORIZATION_ENDPOINT,
                OIDC_TOKEN_ENDPOINT,
                OIDC_USERINFO_ENDPOINT,
                OIDC_SCOPES,
                OIDC_CLIENT_ID,
                OIDC_CLIENT_SECRET,
                OIDC_USERNAME,
                OIDC_FIRST_NAME,
                OIDC_LAST_NAME,
                OIDC_EMAIL,
                OIDC_GROUPS,
                OIDC_GROUP_ADMINISTRATOR,
                OIDC_GROUP_SUPER_ADMINISTRATOR,
                OIDC_HTTP_PROXY,
                OIDC_CERT_PATH
            )
            VALUES (
                '',
                '3600',
                'false',
                'true',
                'repomanager-',
                'Europe/Paris',
                '1024',
                'false',
                '3',
                '730',
                '300',
                'error',
                'true',
                'true',
                '8',
                'noarch,x86_64',
                'error',
                'error',
                'error',
                'true',
                'true',
                'amd64',
                '',
                'false',
                'error',
                '$gpgKeyId',
                'false',
                '3',
                'false',
                'false',
                'false',
                '00:00',
                'false',
                '$oidcEnabled',
                '$ssoOidcOnly',
                '$oidcProviderUrl',
                '$oidcAuthorizationEndpoint',
                '$oidcTokenEndpoint',
                '$oidcUserinfoEndpoint',
                '$oidcScopes',
                '$oidcClientId',
                '$oidcClientSecret',
                '$oidcUsername',
                '$oidcFirstName',
                '$oidcLastName',
                '$oidcEmail',
                '$oidcGroups',
                '$oidcGroupAdministrator',
                '$oidcGroupSuperAdministrator',
                '$oidcHttpProxy',
                '$oidcCertPath'
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
        Container VARCHAR(255) NOT NULL)");
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
        Releasever VARCHAR(255) NOT NULL,
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
        $this->exec("CREATE INDEX IF NOT EXISTS access_rpm_index ON access_rpm (Date, Time, Name, Releasever, Env, Source, IP, Request, Request_result)");
        $this->exec("CREATE INDEX IF NOT EXISTS access_rpm_name_env_index ON access_rpm (Name, Releasever, Env)"); // To optimize SELECT COUNT(*)
        // Index for stats:
        $this->exec("CREATE INDEX IF NOT EXISTS stats_index ON stats (Date, Time, Size, Packages_count, Id_env)");
    }

    /**
     *  Generate tables in the hosts database
     */
    private function generateHostsTables()
    {
        /**
         *  requests table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS requests (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Date DATE NOT NULL,
        Time TIME NOT NULL,
        Request VARCHAR(255) NOT NULL,
        Status VARCHAR(255) NOT NULL, /* new, sent, received, failed, completed */
        Info VARCHAR(255), /* error or info message */
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
        Cpu VARCHAR(255),
        Ram VARCHAR(255),
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
        Uptime VARCHAR(255),
        Linupdate_version VARCHAR(255))"); /* active / disabled / deleted */

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
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_index ON hosts (Ip, Hostname, Os, Os_version, Os_family, Kernel, Arch, Type, Profile, Env, AuthId, Token, Online_status, Online_status_date, Online_status_time, Reboot_required, Linupdate_version)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_authid_index ON hosts (AuthId)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_token_index ON hosts (Token)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_authid_token_index ON hosts (AuthId, Token)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_hostname_index ON hosts (Hostname)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_kernel_index ON hosts (Kernel)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_profile_index ON hosts (Profile)");
        $this->exec("CREATE INDEX IF NOT EXISTS hosts_status_online_date_time ON hosts (Online_status, Online_status_date, Online_status_time)");
        // groups table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS groups_index ON groups (Name)");
        // group_members table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS group_members_index ON group_members (Id_host, Id_group)");
        $this->exec("CREATE INDEX IF NOT EXISTS group_members_id_host_index ON group_members (Id_host)");
        $this->exec("CREATE INDEX IF NOT EXISTS group_members_id_group_index ON group_members (Id_group)");
        // requests table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS requests_id_host ON requests (Id_host)");
        $this->exec("CREATE INDEX IF NOT EXISTS requests_status ON requests (Status)");
        $this->exec("CREATE INDEX IF NOT EXISTS requests_date_time ON requests (Date, Time)");
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
        Version VARCHAR(255),
        Repository VARCHAR(255))");

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
        Status VARCHAR(7))"); /* error / warning / unknown / done */

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
     *  Generate tables in the ws database
     */
    private function generateWsTables()
    {
        /**
         *  ws_connections table
         */
        $this->exec("CREATE TABLE IF NOT EXISTS ws_connections (
        Connection_id INTEGER,
        Type VARCHAR(255),
        Id_host INTEGER,
        Authenticated CHAR(5))"); /* true, false */

        // ws_connections table indexes:
        $this->exec("CREATE INDEX IF NOT EXISTS ws_connections_type ON ws_connections (Type)");
        $this->exec("CREATE INDEX IF NOT EXISTS ws_connections_authenticated ON ws_connections (Authenticated)");
        $this->exec("CREATE INDEX IF NOT EXISTS ws_connections_connection_id ON ws_connections (Connection_id)");
        $this->exec("CREATE INDEX IF NOT EXISTS ws_connections_id_host ON ws_connections (Id_host)");
    }

    /**
     *  Generate tables in the task logs database
     */
    private function generateTaskLogTables()
    {
        // steps table
        $this->exec("CREATE TABLE IF NOT EXISTS steps (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Identifier VARCHAR(255),
        Title VARCHAR(255),
        Status CHAR(9), /* running, completed, error */
        Start REAL,
        End REAL,
        Duration REAL,
        Message VARCHAR(255),
        Task_id INTEGER)");

        // substeps table
        $this->exec("CREATE TABLE IF NOT EXISTS substeps (
        Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        Identifier VARCHAR(255),
        Title VARCHAR(255),
        Note VARCHAR(255),
        Status CHAR(9), /* new, running, completed, error */
        Start REAL,
        End REAL,
        Duration REAL,
        Output TEXT,
        Step_id INTEGER)");

        $this->exec("CREATE INDEX IF NOT EXISTS steps_task_id ON steps (Task_id)");
        $this->exec("CREATE INDEX IF NOT EXISTS steps_step_id ON substeps (Step_id)");
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
        $columns = [];

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
