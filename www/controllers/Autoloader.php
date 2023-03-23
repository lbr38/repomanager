<?php

namespace Controllers;

use Exception;

/**
 *  Classe d'autochargement des classes et des constantes
 */

class Autoloader
{
    private static function register()
    {
        /**
         *  Fait appel à la classe Autoloader (cette même classe) et à sa fonction autoload
         */
        spl_autoload_register(function ($className) {

            $className = str_replace('\\', '/', $className);
            $className = str_replace('Models', 'models', $className);
            $className = str_replace('Controllers', 'controllers', $className);
            $className = str_replace('Views', 'views', $className);

            if (file_exists(ROOT . '/' . $className . '.php')) {
                require_once(ROOT . '/' . $className . '.php');
            }
        });
    }

    /**
     *  Chargement de tous les paramètres nécessaires pour le fonctionnement des pages web (voir api() pour l'api)
     *  - chargement des sessions
     *  - constantes
     *  - vérifications de la présence de tous les répertoires et fichiers nécessaires
     */
    public static function load()
    {
        $__LOAD_GENERAL_ERROR = 0;
        $__LOAD_ERROR_MESSAGES = array();

        /**
         *  On défini un cookie contenant l'URI en cours, utile pour rediriger directement vers cette URI après s'être identifié sur la page de login
         */
        if (!empty($_SERVER['REQUEST_URI']) and !isset($_GET['reload'])) {
            if ($_SERVER["REQUEST_URI"] != '/login' and $_SERVER["REQUEST_URI"] != '/logout') {
                setcookie('origin', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), array('secure' => true, 'httponly' => true));
            }
        }

        /**
         *  Chargement de toutes les fonctions nécessaires
         */
        self::loadAll();

        /**
         *  On récupère les éventuelles erreurs de chargement
         */
        /**
         *  Erreur liées au chargement de la configuration principale
         */
        if (__LOAD_SETTINGS_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "Some settings are not properly configured:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __LOAD_SETTINGS_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Create dirs and files errors
         */
        if (__CREATE_DIRS_AND_FILES_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "Some directories or files could not be generated:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __CREATE_DIRS_AND_FILES_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  PHP modules errors
         */
        if (__LOAD_PHP_MODULES_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "Some PHP modules are missing or are disabled:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __LOAD_PHP_MODULES_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Erreur liées au chargement des environnements
         */
        if (__LOAD_ERROR_EMPTY_ENVS > 0) {
            $__LOAD_ERROR_MESSAGES[] = 'You must at least configure 1 environment.';
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  On définit une constante qui contient le nb d'erreur rencontrées
         */
        if (!defined('__LOAD_GENERAL_ERROR')) {
            define('__LOAD_GENERAL_ERROR', $__LOAD_GENERAL_ERROR);
        }

        /**
         *  On définit une constante qui contient tous les messages d'erreurs récoltés
         */
        if (!defined('__LOAD_ERROR_MESSAGES')) {
            define('__LOAD_ERROR_MESSAGES', $__LOAD_ERROR_MESSAGES);
        }

        unset($__LOAD_GENERAL_ERROR, $__LOAD_ERROR_MESSAGES);
    }

    /**
     *  Chargement du minimum nécessaire pour la page login.php
     */
    public static function loadFromLogin()
    {
        self::register();
        self::loadConstant();
        self::loadSettings();
        self::createDirsAndFiles();
    }

    /**
     *  Chargement de tous les paramètres nécessaires pour le fonctionnement de l'api
     *  Charge moins de fonctions que load() notamment les sessions ne sont par démarrées car empêcheraient le bon fonctionnement de l'api
     */
    public static function api()
    {
        $__LOAD_GENERAL_ERROR = 0;
        $__LOAD_ERROR_MESSAGES = array();

        /**
         *  Chargement des fonctions nécessaires
         */
        self::register();
        self::loadConstant();
        self::loadSettings();
        self::createDirsAndFiles();
        self::loadEnvs();

        /**
         *  On récupère les éventuelles erreurs de chargement
         */
        /**
         *  Erreur liées au chargement de la configuration principale
         */
        if (__LOAD_SETTINGS_ERROR > 0) {
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  On définie une constante qui contient le nb d'erreur rencontrées
         */
        if (!defined('__LOAD_GENERAL_ERROR')) {
            define('__LOAD_GENERAL_ERROR', $__LOAD_GENERAL_ERROR);
        }

        unset($__LOAD_GENERAL_ERROR);
    }

    /**
     *  Exécution de toutes les fonctions
     */
    private static function loadAll()
    {
        self::register();
        self::loadConstant();
        self::loadSettings();
        self::createDirsAndFiles();
        self::loadSession();
        self::loadEnvs();
        self::checkForUpdate();
        self::loadReposListDisplayConf();
        self::checkPhpModules();
        self::loadNotifications();
    }

    private static function checkPhpModules()
    {
        $__LOAD_PHP_MODULES_ERROR = 0;
        $__LOAD_PHP_MODULES_MESSAGES = array();

        /**
         *  Load PHP modules enabled
         */
        $modules = get_loaded_extensions();

        if (empty($modules)) {
            $__LOAD_PHP_MODULES_ERROR++;
            $__LOAD_PHP_MODULES_MESSAGES[] = "Cannot retrieve enabled PHP modules list.";
            return;
        }

        if (!in_array('xml', $modules)) {
            $__LOAD_PHP_MODULES_ERROR++;
            $__LOAD_PHP_MODULES_MESSAGES[] = " - xml module for PHP is not installed or disabled.";
        }
        if (!in_array('curl', $modules)) {
            $__LOAD_PHP_MODULES_ERROR++;
            $__LOAD_PHP_MODULES_MESSAGES[] = " - curl module for PHP is not installed or disabled.";
        }

        if (!defined('__LOAD_PHP_MODULES_ERROR')) {
            define('__LOAD_PHP_MODULES_ERROR', $__LOAD_PHP_MODULES_ERROR);
        }
        if (!defined('__LOAD_PHP_MODULES_MESSAGES')) {
            define('__LOAD_PHP_MODULES_MESSAGES', $__LOAD_PHP_MODULES_MESSAGES);
        }
    }

    /**
     *  Démarrage et vérification de la session en cours
     */
    private static function loadSession()
    {
        /**
         *  On démarre la session
         */
        if (!isset($_SESSION)) {
            session_start();
        }

        /**
         *  Si les variables de session username ou role sont vides alors on redirige vers la page de login
         */
        if (empty($_SESSION['username']) or empty($_SESSION['role'])) {
            header('Location: /login');
            exit();
        }

        /**
         *  Si la session a dépassé les 30min alors on redirige vers logout qui se chargera de détruire la session
         */
        if (isset($_SESSION['start_time']) && (time() - $_SESSION['start_time'] > 1800)) {
            \Models\History::set($_SESSION['username'], "Expired session, deconnection", 'success');
            header('Location: /logout');
            exit();
        }

        /**
         *  On défini l'heure de création de la session (ou on la renouvelle si la session est toujours en cours)
         */
        $_SESSION['start_time'] = time();

        /**
         *  Define IS_ADMIN
         */
        if (!defined('IS_ADMIN')) {
            if ($_SESSION['role'] === 'super-administrator' or $_SESSION['role'] === 'administrator') {
                define('IS_ADMIN', true);
            } else {
                define('IS_ADMIN', false);
            }
        }

        /**
         *  Define IS_SUPERADMIN
         */
        if (!defined('IS_SUPERADMIN')) {
            if ($_SESSION['role'] === 'super-administrator') {
                define('IS_SUPERADMIN', true);
            } else {
                define('IS_SUPERADMIN', false);
            }
        }
    }

    /**
     *  Load constants
     */
    private static function loadConstant()
    {
        /**
         *  Load system constants
         */
        self::loadSystemConstant();

        // Web dir
        if (!defined('ROOT')) {
            define('ROOT', dirname(__FILE__, 2));
        }
        // Data dir
        if (!defined('DATA_DIR')) {
            define('DATA_DIR', '/var/lib/repomanager');
        }
        // Databases dir
        if (!defined('DB_DIR')) {
            define('DB_DIR', DATA_DIR . "/db");
        }
        // Main database
        if (!defined('DB')) {
            define('DB', DB_DIR . "/repomanager.db");
        }
        // Stats database
        if (!defined('STATS_DB')) {
            define('STATS_DB', DB_DIR . "/repomanager-stats.db");
        }
        // Hosts database
        if (!defined('HOSTS_DB')) {
            define('HOSTS_DB', DB_DIR . "/repomanager-hosts.db");
        }
        // Main configuration file
        if (!defined('REPOMANAGER_CONF')) {
            define('REPOMANAGER_CONF', DATA_DIR . '/configurations/repomanager.conf');
        }
        // Emplacement du répertoire de cache
        if (!defined('WWW_CACHE')) {
            define('WWW_CACHE', DATA_DIR . "/cache");
        }
        // no need to use /dev/shm anymore so delete symlink if exists
        if (is_link(WWW_CACHE)) {
            unlink(WWW_CACHE);
        }
        // Emplacement du répertoire de clé GPG
        if (!defined('GPGHOME')) {
            define('GPGHOME', DATA_DIR . "/.gnupg");
        }
        if (!defined('PASSPHRASE_FILE')) {
            define('PASSPHRASE_FILE', GPGHOME . '/passphrase');
        }
        if (!defined('MACROS_FILE')) {
            define('MACROS_FILE', DATA_DIR . '/.rpm/.mcs');
        }
        // Répertoire principal des logs
        if (!defined('LOGS_DIR')) {
            define('LOGS_DIR', DATA_DIR . "/logs");
        }
        // Logs du programme
        if (!defined('MAIN_LOGS_DIR')) {
            define('MAIN_LOGS_DIR', LOGS_DIR . '/main');
        }
        if (!defined('EXCEPTIONS_LOG')) {
            define('EXCEPTIONS_LOG', LOGS_DIR . '/exceptions');
        }
        if (!defined('SERVICE_LOG_DIR')) {
            define('SERVICE_LOG_DIR', LOGS_DIR . '/service');
        }
        if (!defined('SERVICE_LOG')) {
            define('SERVICE_LOG', SERVICE_LOG_DIR . '/repomanager-service.log');
        }
        if (!defined('UPDATE_SUCCESS_LOG')) {
            define('UPDATE_SUCCESS_LOG', LOGS_DIR . '/update/update.success');
        }
        if (!defined('UPDATE_ERROR_LOG')) {
            define('UPDATE_ERROR_LOG', LOGS_DIR . '/update/update.error');
        }
        if (!defined('UPDATE_INFO_LOG')) {
            define('UPDATE_INFO_LOG', LOGS_DIR . '/update/update.info');
        }
        // Pool de taches asynchrones
        if (!defined('POOL')) {
            define('POOL', DATA_DIR . "/operations/pool");
        }
        // PIDs
        if (!defined('PID_DIR')) {
            define('PID_DIR', DATA_DIR . "/operations/pid");
        }
        // Répertoire contenant des fichiers temporaires
        if (!defined('TEMP_DIR')) {
            define('TEMP_DIR', DATA_DIR . "/.temp");
        }
        // Hotes
        if (!defined('HOSTS_DIR')) {
            define('HOSTS_DIR', DATA_DIR . '/hosts');
        }
        // Logbuilder
        if (!defined('LOGBUILDER')) {
            define('LOGBUILDER', ROOT . '/tools/logbuilder.php');
        }
        /**
         *  Actual release version and available version on github
         */
        if (!defined('VERSION')) {
            define('VERSION', trim(file_get_contents(ROOT . '/version')));
        }
        if (!file_exists(DATA_DIR . '/version.available')) {
            file_put_contents(DATA_DIR . '/version.available', VERSION);
        }
        if (!defined('GIT_VERSION')) {
            define('GIT_VERSION', trim(file_get_contents(DATA_DIR . '/version.available')));
        }
        if (!defined('LAST_VERSION')) {
            if (file_exists(DATA_DIR . '/version.last')) {
                define('LAST_VERSION', trim(file_get_contents(DATA_DIR . '/version.last')));
            } else {
                define('LAST_VERSION', VERSION);
            }
        }
        /**
         *  Check if a repomanager update is running
         */
        if (file_exists(DATA_DIR . "/update-running")) {
            if (!defined('UPDATE_RUNNING')) {
                define('UPDATE_RUNNING', 'true');
            }
        } else {
            if (!defined('UPDATE_RUNNING')) {
                define('UPDATE_RUNNING', 'false');
            }
        }
        /**
         *  Date and time
         */
        if (!defined('DATE_DMY')) {
            define('DATE_DMY', date('d-m-Y'));
        }
        if (!defined('DATE_YMD')) {
            define('DATE_YMD', date('Y-m-d'));
        }
        if (!defined('TIME')) {
            define('TIME', date('H-i'));
        }
        /**
         *  Installation type
         */
        if (file_exists(ROOT . '/.docker')) {
            if (!defined('DOCKER')) {
                define('DOCKER', 'true');
            }
        } else {
            if (!defined('DOCKER')) {
                define('DOCKER', 'false');
            }
        }
        /**
         *  Repomanager service status
         */
        if (!defined('SERVICE_RUNNING')) {
            define('SERVICE_RUNNING', System::serviceStatus());
        }
        /**
         *  Debug mode
         */
        if (!defined('DEBUG_MODE')) {
            define('DEBUG_MODE', 'false');
        }
    }

    /**
     *  Chargement des chemins vers les répertoires et fichiers de base
     *  Création si n'existent pas
     */
    private static function createDirsAndFiles()
    {
        $__CREATE_DIRS_AND_FILES_ERROR = 0;
        $__CREATE_DIRS_AND_FILES_MESSAGES = array();

        /**
         *  Création des fichiers et répertoires de base si n'existent pas
         */
        if (!is_dir(DB_DIR)) {
            mkdir(DB_DIR, 0770, true);
        }
        if (!is_dir(GPGHOME)) {
            mkdir(GPGHOME, 0770, true);
        }
        if (!is_dir(LOGS_DIR)) {
            mkdir(LOGS_DIR, 0770, true);
        }
        if (!is_dir(MAIN_LOGS_DIR)) {
            mkdir(MAIN_LOGS_DIR, 0770, true);
        }
        if (!is_dir(SERVICE_LOG_DIR)) {
            mkdir(SERVICE_LOG_DIR, 0770, true);
        }
        if (!is_dir(POOL)) {
            mkdir(POOL, 0770, true);
        }
        if (!is_dir(PID_DIR)) {
            mkdir(PID_DIR, 0770, true);
        }
        if (!is_dir(TEMP_DIR)) {
            mkdir(TEMP_DIR, 0770, true);
        }
        if (!is_dir(HOSTS_DIR)) {
            mkdir(HOSTS_DIR, 0770, true);
        }
        if (!is_dir(WWW_CACHE)) {
            mkdir(DATA_DIR . '/cache', 0770, true);
        }

        /**
         *  Création du répertoire de backup si n'existe pas
         */
        if (defined('UPDATE_BACKUP_DIR') and !empty(UPDATE_BACKUP_DIR) and !is_dir(UPDATE_BACKUP_DIR)) {
            if (!mkdir(UPDATE_BACKUP_DIR, 0770, true)) {
                $__CREATE_DIRS_AND_FILES_ERROR++;
                $__CREATE_DIRS_AND_FILES_MESSAGES[] = 'Cannot create backup directory: ' . UPDATE_BACKUP_DIR;
            }
        }
        /**
         *  Création du répertoire de mise à jour si n'existe pas
         */
        if (!is_dir(ROOT . "/update")) {
            if (!mkdir(ROOT . "/update", 0770, true)) {
                $__CREATE_DIRS_AND_FILES_ERROR++;
                $__CREATE_DIRS_AND_FILES_MESSAGES[] = 'Cannot create release update directory: ' . ROOT . '/update';
            }
        }

        /**
         *  Generate GPG key and configuration if not exists
         */
        try {
            $mygpg = new Gpg();
            $mygpg->init();
        } catch (\Exception $e) {
            $__CREATE_DIRS_AND_FILES_ERROR++;
            $__CREATE_DIRS_AND_FILES_MESSAGES[] = $e->getMessage();
        }

        if (!defined('__CREATE_DIRS_AND_FILES_ERROR')) {
            define('__CREATE_DIRS_AND_FILES_ERROR', $__CREATE_DIRS_AND_FILES_ERROR);
        }
        if (!defined('__CREATE_DIRS_AND_FILES_MESSAGES')) {
            define('__CREATE_DIRS_AND_FILES_MESSAGES', $__CREATE_DIRS_AND_FILES_MESSAGES);
        }

        unset($__CREATE_DIRS_AND_FILES_ERROR, $__CREATE_DIRS_AND_FILES_MESSAGES, $mygpg);
    }

    /**
     *  Chargement des informations du système et de l'OS
     */
    private static function loadSystemConstant()
    {
        /**
         *  Protocol (http ou https)
         */
        if (!empty($_SERVER['HTTPS'])) {
            $__SERVER_PROTOCOL__ = 'https';
        } else {
            $__SERVER_PROTOCOL__ = 'http';
        }
        /**
         *  Url du serveur
         */
        if (!empty($_SERVER['SERVER_NAME'])) {
            if (!defined('__SERVER_URL__')) {
                define('__SERVER_URL__', $__SERVER_PROTOCOL__ . '://' . $_SERVER['HTTP_HOST']);
            }
        }

        /**
         *  Adresse IP du serveur
         */
        if (!empty($_SERVER['SERVER_ADDR'])) {
            if (!defined('__SERVER_IP__')) {
                define('__SERVER_IP__', $_SERVER['SERVER_ADDR']);
            }
        }
        /**
         *  URL + URI complètes
         */
        if (!empty($_SERVER['HTTP_HOST']) and !empty($_SERVER['REQUEST_URI'])) {
            if (!defined('__ACTUAL_URL__')) {
                define('__ACTUAL_URL__', $__SERVER_PROTOCOL__ . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            }
        }

        /**
         *  URI
         */
        if (!empty($_SERVER['REQUEST_URI'])) {
            if (!defined('__ACTUAL_URI__')) {
                define('__ACTUAL_URI__', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
            }
        }
        /**
         *  Paramètres
         */
        if (!empty($_SERVER['QUERY_STRING'])) {
            if (!defined('__QUERY_STRING__')) {
                define('__QUERY_STRING__', parse_url($_SERVER["QUERY_STRING"], PHP_URL_PATH));
            }
        }

        /**
         *  Récupération du nom et de la version de l'OS, le tout étant retourné sous forme d'array dans $OS_INFO
         */
        if (!is_readable('/etc/os-release')) {
            echo 'Error: cannot determine OS release';
            die;
        }

        $os      = file_get_contents('/etc/os-release');
        $listIds = preg_match_all('/.*=/', $os, $matchListIds);
        $listIds = $matchListIds[0];
        $listVal = preg_match_all('/=.*/', $os, $matchListVal);
        $listVal = $matchListVal[0];

        array_walk($listIds, function (&$v, $k) {
            $v = strtolower(str_replace('=', '', $v));
        });

        array_walk($listVal, function (&$v, $k) {
            $v = preg_replace('/=|"/', '', $v);
        });

        if (!defined('OS_INFO')) {
            define('OS_INFO', array_combine($listIds, $listVal));
        }

        unset($os, $listIds, $listVal);

        /**
         *  Puis à partir de l'array OS_INFO on détermine la famille d'os, son nom et sa version
         */
        if (!empty(OS_INFO['id_like'])) {
            if (preg_match('(rhel|centos|fedora)', OS_INFO['id_like']) === 1) {
                if (!defined('OS_FAMILY')) {
                    define('OS_FAMILY', "Redhat");
                }
            }
            if (preg_match('(debian|ubuntu|kubuntu|xubuntu|armbian|mint)', OS_INFO['id_like']) === 1) {
                if (!defined('OS_FAMILY')) {
                    define('OS_FAMILY', "Debian");
                }
            }
        } else if (!empty(OS_INFO['id'])) {
            if (preg_match('(rhel|centos|fedora)', OS_INFO['id']) === 1) {
                if (!defined('OS_FAMILY')) {
                    define('OS_FAMILY', "Redhat");
                }
            }
            if (preg_match('(debian|ubuntu|kubuntu|xubuntu|armbian|mint)', OS_INFO['id']) === 1) {
                if (!defined('OS_FAMILY')) {
                    define('OS_FAMILY', "Debian");
                }
            }
        }

        /**
         *  A partir d'ici si OS_FAMILY n'est pas défini alors le système sur lequel est installé Repomanager est incompatible
         */
        if (!defined('OS_FAMILY')) {
            die('Error: Repomanager is not compatible with this system');
        }

        if (!defined('OS_NAME')) {
            define('OS_NAME', trim(OS_INFO['name']));
        }
        if (!defined('OS_ID')) {
            define('OS_ID', trim(OS_INFO['id']));
        }
        if (!defined('OS_VERSION')) {
            define('OS_VERSION', trim(OS_INFO['version_id']));
        }
    }

    /**
     *  Chargement de la configuration de repomanager
     */
    private static function loadSettings()
    {
        $myconn = new Connection();
        $mysettings = new Settings();

        /**
         *  Check that database exists or generate it with default settings
         */
        $myconn->checkDatabase('main');

        /**
         *  Get all constant settings
         */
        $mysettings->get();

        unset($myconn, $mysettings);
    }

    /**
     *  Loading notifications
     */
    private static function loadNotifications()
    {
        $NOTIFICATION = 0;
        $NOTIFICATION_MESSAGES = array();
        $mynotification = new Notification();

        /**
         *  Retrieve unread notifications from database
         */
        $NOTIFICATION_MESSAGES = $mynotification->getUnread();
        $NOTIFICATION = count($NOTIFICATION_MESSAGES);

        /**
         *  If an update is available, generate a new notification
         */
        if (UPDATE_AVAILABLE == 'true') {
            $message = '<span class="yellowtext">A new release is available: <b>' . GIT_VERSION . '</b></span>';
            $NOTIFICATION_MESSAGES[] = array('Title' => 'Update available', 'Message' =>  $message);
            $NOTIFICATION++;
        }

        /**
         *  If current user email is not set, generate a new notification
         */
        if (empty($_SESSION['email'])) {
            $message = '<span>You can configure your email in your user profile. This email can be used as a recipient to send notifications of Repomanager events like planification status or planification reminders</span>';
            $NOTIFICATION_MESSAGES[] = array('Title' => 'Email contact not set', 'Message' =>  $message);
            $NOTIFICATION++;
        }

        if (!defined('NOTIFICATION')) {
            define('NOTIFICATION', $NOTIFICATION);
        }

        if (!defined('NOTIFICATION_MESSAGES')) {
            define('NOTIFICATION_MESSAGES', $NOTIFICATION_MESSAGES);
        }

        unset($NOTIFICATION, $NOTIFICATION_MESSAGES, $mynotification);
    }

    /**
     *  Chargement des environnements
     */
    private static function loadEnvs()
    {
        /**
         *  Récupération des environnements en base de données
         */
        $myenv = new \Controllers\Environment();
        if (!defined('ENVS')) {
            define('ENVS', $myenv->listAll());
        }
        if (!defined('ENVS_TOTAL')) {
            define('ENVS_TOTAL', $myenv->total());
        }
        if (!defined('DEFAULT_ENV')) {
            define('DEFAULT_ENV', $myenv->default());
        }
        if (!defined('LAST_ENV')) {
            define('LAST_ENV', $myenv->last());
        }

        /**
         *  Si il n'y a aucun environnement configuré alors __LOAD_ERROR_EMPTY_ENVS = 1
         */
        if (empty(ENVS)) {
            if (!defined('__LOAD_ERROR_EMPTY_ENVS')) {
                define('__LOAD_ERROR_EMPTY_ENVS', 1);
            }
        } else {
            if (!defined('__LOAD_ERROR_EMPTY_ENVS')) {
                define('__LOAD_ERROR_EMPTY_ENVS', 0);
            }
        }
    }

    /**
     *  Vérification des nouvelles versions disponibles
     *  Vérification si une mise à jour est en cours ou non
     */
    private static function checkForUpdate()
    {
        if (defined('VERSION') and defined('GIT_VERSION')) {
            if (VERSION !== GIT_VERSION) {
                if (!defined('UPDATE_AVAILABLE')) {
                    define('UPDATE_AVAILABLE', 'true');
                }
            } else {
                if (!defined('UPDATE_AVAILABLE')) {
                    define('UPDATE_AVAILABLE', 'false');
                }
            }
        } else {
            define('UPDATE_AVAILABLE', 'false');
        }

        /**
         *  Si la mise à jour automatique est activé et qu'une mise à jour est disponible alors on l'installe en arrière-plan.
         *  L'action est effectuée uniquement si une mise à jour n'est pas déjà en cours (présence du fichier update-running)
         *  La mise à jour mettra en place une page de maintenance automatiquement
         */
        if (UPDATE_AUTO == "true" and UPDATE_AVAILABLE == "true") {
            if (!file_exists(DATA_DIR . "/update-running")) {
                $myupdate = new \Controllers\Update();
                $myupdate->update();
            }
        }
    }

    /**
     *  Chargement de la configuration de l'affichage de la liste des repos
     */
    private static function loadReposListDisplayConf()
    {
        /**
         *  On ne charge ces paramètres uniquement sur certaines pages
         */
        if (defined('__ACTUAL_URI__')) {
            if (
                __ACTUAL_URI__ == "" or
                __ACTUAL_URI__ == "/" or
                __ACTUAL_URI__ == "/plans"
            ) {
                /**
                 *  Ouverture d'une connexion à la base de données
                 */
                $myconn = new \Models\Connection('main');

                /**
                 *  Récupération des paramètres en base de données
                 */
                try {
                    $result = $myconn->query("SELECT * FROM repos_list_settings");
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        define('PRINT_REPO_SIZE', $row['print_repo_size']);
                        define('PRINT_REPO_TYPE', $row['print_repo_type']);
                        define('PRINT_REPO_SIGNATURE', $row['print_repo_signature']);
                    }
                } catch (\Exception $e) {
                    \Controllers\Common::dbError($e);
                }

                define('CACHE_REPOS_LIST', 'true');

                $myconn->close();
            }
        }
    }
}
