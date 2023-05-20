<?php

namespace Controllers\Autoloader;

use Exception;

/**
 *  Classe d'autochargement des classes et des constantes
 */

class Autoloader
{
    private static function register()
    {
        if (!defined('ROOT')) {
            define('ROOT', dirname(__FILE__, 3));
        }

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
            $__LOAD_ERROR_MESSAGES[] = "<b>Some settings are not properly configured</b>:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __LOAD_SETTINGS_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Create dirs and files errors
         */
        if (__CREATE_DIRS_AND_FILES_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "<br><b>Some directories or files could not be generated</b>:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __CREATE_DIRS_AND_FILES_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  PHP modules errors
         */
        if (__LOAD_PHP_MODULES_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "<br><b>Some PHP modules are missing or are disabled</b>:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __LOAD_PHP_MODULES_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Erreur liées au chargement des environnements
         */
        if (__LOAD_ERROR_EMPTY_ENVS > 0) {
            $__LOAD_ERROR_MESSAGES[] = '<br><b>You must at least configure 1 environment.</b>';
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
        Constant\Main::get();
        Constant\Settings::get();
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
        Constant\Main::get();
        Constant\Settings::get();
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
        Constant\Main::get();
        Constant\Settings::get();
        self::createDirsAndFiles();
        self::loadSession();
        self::loadEnvs();
        self::checkForUpdate();
        self::checkPhpModules();
        self::loadLogs();
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
        if (!is_dir(DB_UPDATE_DONE_DIR)) {
            mkdir(DB_UPDATE_DONE_DIR, 0770, true);
        }

        /**
         *  Création du répertoire de mise à jour si n'existe pas
         */
        if (!is_dir(ROOT . '/update')) {
            if (!mkdir(ROOT . '/update', 0770, true)) {
                $__CREATE_DIRS_AND_FILES_ERROR++;
                $__CREATE_DIRS_AND_FILES_MESSAGES[] = 'Cannot create release update directory: ' . ROOT . '/update';
            }
        }

        /**
         *  Generate GPG key and configuration if not exists
         */
        try {
            $mygpg = new \Controllers\Gpg();
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
     *  Loading logs
     */
    private static function loadLogs()
    {
        $LOG = 0;
        $LOG_MESSAGES = array();
        $mylog = new \Controllers\Log\Log();

        /**
         *  Count unread notifications
         */
        $LOG = count($mylog->getUnread(''));

        /**
         *  Retrieve 5 last unread notifications from database
         */
        if ($LOG > 0) {
            $LOG_MESSAGES = $mylog->getUnread('', 5);
        }

        if (!defined('LOG')) {
            define('LOG', $LOG);
        }

        if (!defined('LOG_MESSAGES')) {
            define('LOG_MESSAGES', $LOG_MESSAGES);
        }

        unset($LOG, $LOG_MESSAGES, $mylog);
    }

    /**
     *  Loading notifications
     */
    private static function loadNotifications()
    {
        $NOTIFICATION = 0;
        $NOTIFICATION_MESSAGES = array();
        $mynotification = new \Controllers\Notification();

        /**
         *  Retrieve unread notifications from database
         */
        $NOTIFICATION_MESSAGES = $mynotification->getUnread();
        $NOTIFICATION = count($NOTIFICATION_MESSAGES);

        /**
         *  If an update is available, generate a new notification
         */
        if (UPDATE_AVAILABLE == 'true') {
            $message = '<span class="yellowtext">A new release is available: <b>' . GIT_VERSION . '</b>.</span><br><br>Please update your docker image by following the steps documented here: <b><a href="https://github.com/lbr38/repomanager/wiki/01.-Installation#update-repomanager">Update repomanager</a></b></span>';
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
    }
}
