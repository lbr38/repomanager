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
        \Controllers\Autoloader::loadAll();

        /**
         *  On récupère les éventuelles erreurs de chargement
         */
        /**
         *  Erreur liées au chargement de la configuration principale
         */
        if (__LOAD_MAIN_CONF_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "Some main parameters are not configured:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __LOAD_MAIN_CONF_MESSAGES);
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
        \Controllers\Autoloader::loadConstant();
        \Controllers\Autoloader::register();
        \Controllers\Autoloader::loadConfiguration();
        \Controllers\Autoloader::loadDirs();
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
        \Controllers\Autoloader::loadConstant();
        \Controllers\Autoloader::register();
        \Controllers\Autoloader::loadConfiguration();
        \Controllers\Autoloader::loadDirs();
        \Controllers\Autoloader::loadEnvs();

        /**
         *  On récupère les éventuelles erreurs de chargement
         */
        /**
         *  Erreur liées au chargement de la configuration principale
         */
        if (__LOAD_MAIN_CONF_ERROR > 0) {
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
        \Controllers\Autoloader::loadConstant();
        \Controllers\Autoloader::register();
        \Controllers\Autoloader::loadSession();
        \Controllers\Autoloader::loadConfiguration();
        \Controllers\Autoloader::loadDirs();
        \Controllers\Autoloader::loadEnvs();
        \Controllers\Autoloader::checkForUpdate();
        \Controllers\Autoloader::serviceIsActive();
        \Controllers\Autoloader::loadReposListDisplayConf();
        \Controllers\Autoloader::checkPhpModules();
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

    private static function loadConstant()
    {
        date_default_timezone_set('Europe/Paris');

        /**
         *  Load system constants
         */
        \Controllers\Autoloader::loadSystemConstant();

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
        // Emplacement du répertoire de clé GPG
        if (!defined('GPGHOME')) {
            define('GPGHOME', DATA_DIR . "/.gnupg");
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

        if (!defined('PASSPHRASE_FILE')) {
            define('PASSPHRASE_FILE', GPGHOME . '/passphrase');
        }

        /**
         *  Actual release version and available version on github
         */
        if (!defined('VERSION')) {
            define('VERSION', trim(file_get_contents(ROOT . '/version')));
        }
        if (!defined('GIT_VERSION')) {
            if (file_exists(DATA_DIR . '/version.available')) {
                define('GIT_VERSION', trim(file_get_contents(DATA_DIR . '/version.available')));
            }
        }
        if (!defined('LAST_VERSION')) {
            if (file_exists(DATA_DIR . '/version.last')) {
                define('LAST_VERSION', trim(file_get_contents(DATA_DIR . '/version.last')));
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
    }

    /**
     *  Chargement des chemins vers les répertoires et fichiers de base
     *  Création si n'existent pas
     */
    private static function loadDirs()
    {
        /**
         *  Emplacement des répertoires de bases
         */
        if (!is_dir(DATA_DIR . '/.rpm')) {
            mkdir(DATA_DIR . '/.rpm', 0770, true);
        }
        // Fichier de macros pour rpm
        if (!file_exists(MACROS_FILE)) {
            file_put_contents(MACROS_FILE, '%__gpg /usr/bin/gpg' . PHP_EOL);
            file_put_contents(MACROS_FILE, '%_gpg_name ' . RPM_SIGN_GPG_KEYID . PHP_EOL, FILE_APPEND);
            file_put_contents(MACROS_FILE, '%__gpg_sign_cmd %{__gpg} gpg --homedir ' . GPGHOME . ' --no-verbose --no-armor --batch --pinentry-mode loopback --passphrase-file ' . PASSPHRASE_FILE . ' %{?_gpg_digest_algo:--digest-algo %{_gpg_digest_algo}} --no-secmem-warning -u "%{_gpg_name}" -sbo %{__signature_filename} %{__plaintext_filename}' . PHP_EOL, FILE_APPEND);
        }

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

        if (!file_exists(WWW_CACHE)) {
            // Si /dev/shm/ (répertoire en mémoire) existe, alors on crée un lien symbolique vers ce répertoire, sinon on crée un répertoire 'cache' classique
            if (file_exists("/dev/shm")) {
                exec('cd ' . DATA_DIR . ' && ln -sfn /dev/shm cache');
            } else {
                mkdir(DATA_DIR . '/cache', 0770, true);
            }
        }

        /**
         *  Création du répertoire de backup si n'existe pas
         */
        if (defined('BACKUP_DIR') and !empty(BACKUP_DIR) and !is_dir(BACKUP_DIR)) {
            if (!mkdir(BACKUP_DIR, 0770, true)) {
                $GENERAL_ERROR_MESSAGES[] = 'Cannot create backup directory: ' . BACKUP_DIR;
            }
        }
        /**
         *  Création du répertoire de mise à jour si n'existe pas
         */
        if (!is_dir(ROOT . "/update")) {
            if (!mkdir(ROOT . "/update", 0770, true)) {
                $GENERAL_ERROR_MESSAGES[] = 'Cannot create release update directory: ' . ROOT . '/update';
            }
        }

        /**
         *  Vérification de la présence de la base de données
         *  Si aucun fichier de base de données n'existe ou bien si on a précisé le paramètre ?initialize
         */
        if (!file_exists(DB) or isset($_GET['initialize'])) {
            /**
             *  On va vérifier la présence des tables et les créer si nécessaire
             */
            $myconn = new \Models\Connection('main');

            if (!$myconn->checkMainTables()) {
                /**
                 *  Si la vérification a échouée alors on quitte.
                 */
                die();
            }
        }

        if (!is_dir(REPOS_DIR . '/gpgkeys')) {
            mkdir(REPOS_DIR . '/gpgkeys', 0770, true);
        }

        /**
         *  Si la clé de signature GPG n'existe pas alors on l'exporte
         */
        if (RPM_SIGN_PACKAGES == 'true') {
            exec("gpg2 --no-permission-warning --homedir '" . GPGHOME . "' --export -a '" . RPM_SIGN_GPG_KEYID . "' > " . REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '_rpm.pub 2>/dev/null');
        }
        if (DEB_SIGN_REPO == 'true') {
            exec("gpg2 --no-permission-warning --homedir '" . GPGHOME . "' --export -a '" . DEB_SIGN_GPG_KEYID . "' > " . REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '_deb.pub 2>/dev/null');
        }
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
                define('__SERVER_URL__', "$__SERVER_PROTOCOL__://" . $_SERVER['SERVER_NAME']);
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
                define('__ACTUAL_URL__', "$__SERVER_PROTOCOL__://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
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
            define('OS_NAME', OS_INFO['name']);
        }
        if (!defined('OS_ID')) {
            define('OS_ID', OS_INFO['id']);
        }
        if (!defined('OS_VERSION')) {
            define('OS_VERSION', OS_INFO['version_id']);
        }
    }

    /**
     *  Chargement de la configuration de repomanager
     */
    private static function loadConfiguration()
    {
        $__LOAD_MAIN_CONF_ERROR = 0;
        $__LOAD_MAIN_CONF_MESSAGES = array();

        /**
         *  Vérification de la présence de repomanager.conf
         */
        if (!file_exists(REPOMANAGER_CONF)) {
            echo "Error: configuration file is missing. You must relaunch Repomanager installation.";
            die();
        }

        /**
         *  Récupération de tous les paramètres définis dans le fichier repomanager.conf
         */
        $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF);

        /**
         *  Si certains paramètres sont vides alors on incrémente $EMPTY_CONFIGURATION_VARIABLES qui fera afficher un bandeau d'alertes.
         *  Certains paramètres font exceptions et peuvent rester vides
         */
        foreach ($repomanager_conf_array as $key => $value) {
            /**
             *  Les paramètres suivants peuvent rester vides, on n'incrémente pas le compteur d'erreurs dans leur cas
             */
            $ignoreEmptyParam = array('STATS_LOG_PATH', 'RPM_DEFAULT_ARCH', 'DEB_DEFAULT_ARCH', 'DEB_DEFAULT_TRANSLATION');

            if (in_array($key, $ignoreEmptyParam)) {
                continue;
            }

            if (empty($value)) {
                ++$__LOAD_MAIN_CONF_ERROR;
            }
        }

        /**
         *  Paramètres généraux
         */
        if (!defined('REPOS_DIR')) {
            if (!empty($repomanager_conf_array['REPOS_DIR'])) {
                define('REPOS_DIR', $repomanager_conf_array['REPOS_DIR']);
                /**
                 *  On teste l'accès au répertoire renseigné
                 */
                if (!is_writable(REPOS_DIR)) {
                    ++$__LOAD_MAIN_CONF_ERROR; // On force l'affichage d'un message d'erreur même si le paramètre n'est pas vide
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Repos directory '" . REPOS_DIR . "' is not writeable.";
                }
            } else {
                define('REPOS_DIR', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = 'Repos directory is not defined. ';
            }
        }

        if (!defined('EMAIL_DEST')) {
            if (!empty($repomanager_conf_array['EMAIL_DEST'])) {
                define('EMAIL_DEST', $repomanager_conf_array['EMAIL_DEST']);
            } else {
                define('EMAIL_DEST', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = 'No recipient email adress is defined. ';
            }
        }

        if (!defined('UPDATE_AUTO')) {
            if (!empty($repomanager_conf_array['UPDATE_AUTO'])) {
                define('UPDATE_AUTO', $repomanager_conf_array['UPDATE_AUTO']);
            } else {
                define('UPDATE_AUTO', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Enabling automatic update is not defined.";
            }
        }

        if (!defined('UPDATE_BRANCH')) {
            if (!empty($repomanager_conf_array['UPDATE_BRANCH'])) {
                define('UPDATE_BRANCH', $repomanager_conf_array['UPDATE_BRANCH']);
            } else {
                define('UPDATE_BRANCH', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Update target branch is not defined.";
            }
        }

        if (!defined('UPDATE_BACKUP_ENABLED')) {
            if (!empty($repomanager_conf_array['UPDATE_BACKUP_ENABLED'])) {
                define('UPDATE_BACKUP_ENABLED', $repomanager_conf_array['UPDATE_BACKUP_ENABLED']);
            } else {
                define('UPDATE_BACKUP_ENABLED', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Enabling backup before update is not defined.";
            }
        }

        if (!defined('BACKUP_DIR')) {
            if (UPDATE_BACKUP_ENABLED == "true") {
                if (!empty($repomanager_conf_array['BACKUP_DIR'])) {
                    define('BACKUP_DIR', $repomanager_conf_array['BACKUP_DIR']);
                    /**
                     *  On teste l'accès au répertoire renseigné
                     */
                    if (!is_writable(BACKUP_DIR)) {
                        ++$__LOAD_MAIN_CONF_ERROR; // On force l'affichage d'un message d'erreur même si le paramètre n'est pas vide
                        $__LOAD_MAIN_CONF_MESSAGES[] = "Backup before update directory '" . BACKUP_DIR . "' is not writeable.";
                    }
                } else {
                    define('BACKUP_DIR', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Backup before update directory is not defined.";
                }
            }
        }

        if (!defined('DEBUG_MODE')) {
            if (!empty($repomanager_conf_array['DEBUG_MODE'])) {
                define('DEBUG_MODE', $repomanager_conf_array['DEBUG_MODE']);
            } else {
                define('DEBUG_MODE', 'false');
            }
        }

        /**
         *  Paramètres web
         */
        if (!defined('WWW_HOSTNAME')) {
            if (!empty($repomanager_conf_array['WWW_HOSTNAME'])) {
                define('WWW_HOSTNAME', $repomanager_conf_array['WWW_HOSTNAME']);
            } else {
                define('WWW_HOSTNAME', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Repomanager dedied hostname is not defined.";
            }
        }

        if (!defined('WWW_REPOS_DIR_URL')) {
            if (!empty($repomanager_conf_array['WWW_REPOS_DIR_URL'])) {
                define('WWW_REPOS_DIR_URL', $repomanager_conf_array['WWW_REPOS_DIR_URL']);
            } else {
                define('WWW_REPOS_DIR_URL', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Target URL to repo directory is not defined.";
            }
        }

        if (!defined('WWW_USER')) {
            if (!empty($repomanager_conf_array['WWW_USER'])) {
                define('WWW_USER', $repomanager_conf_array['WWW_USER']);
            } else {
                define('WWW_USER', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Linux web dedied user is not defined.";
            }
        }

        /**
         *  Paramètres de repos
         */

        // RPM
        if (!defined('RPM_REPO')) {
            if (!empty($repomanager_conf_array['RPM_REPO'])) {
                define('RPM_REPO', $repomanager_conf_array['RPM_REPO']);
            } else {
                define('RPM_REPO', 'false');
            }
        }

        if (!defined('RPM_SIGN_PACKAGES')) {
            if (!empty($repomanager_conf_array['RPM_SIGN_PACKAGES'])) {
                define('RPM_SIGN_PACKAGES', $repomanager_conf_array['RPM_SIGN_PACKAGES']);
            } else {
                define('RPM_SIGN_PACKAGES', 'false');
            }
        }

        if (!defined('RPM_SIGN_GPG_KEYID')) {
            if (!empty($repomanager_conf_array['RPM_SIGN_GPG_KEYID'])) {
                define('RPM_SIGN_GPG_KEYID', $repomanager_conf_array['RPM_SIGN_GPG_KEYID']);
            } else {
                define('RPM_SIGN_GPG_KEYID', '');

                /**
                 *  On affiche un message uniquement si la signature est activée
                 */
                if (RPM_SIGN_PACKAGES == 'true') {
                    $__LOAD_MAIN_CONF_MESSAGES[] = "GPG key Id for signing RPM packages is not defined.";
                }
            }
        }

        if (!defined('RPM_SIGN_METHOD')) {
            if (!empty($repomanager_conf_array['RPM_SIGN_METHOD'])) {
                define('RPM_SIGN_METHOD', $repomanager_conf_array['RPM_SIGN_METHOD']);
            } else {
                /**
                 *  On défini la méthode par défaut en cas de valeur vide
                 */
                define('RPM_SIGN_METHOD', 'rpmsign');

                /**
                 *  On affiche un message uniquement si la signature est activée
                 */
                if (RPM_SIGN_PACKAGES == 'true') {
                    $__LOAD_MAIN_CONF_MESSAGES[] = "GPG signing method for signing RPM packages is not defined.";
                }
            }
        }

        if (!defined('RELEASEVER')) {
            if (!empty($repomanager_conf_array['RELEASEVER'])) {
                define('RELEASEVER', $repomanager_conf_array['RELEASEVER']);
            } else {
                define('RELEASEVER', '');

                /**
                 *  On affiche un message uniquement si les repos RPM sont activés.
                 */
                if (RPM_REPO == 'true') {
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Release version for RPM repositories is not defined.";
                }
            }
        }

        if (!defined('RPM_DEFAULT_ARCH')) {
            if (!empty($repomanager_conf_array['RPM_DEFAULT_ARCH'])) {
                define('RPM_DEFAULT_ARCH', explode(',', $repomanager_conf_array['RPM_DEFAULT_ARCH']));
            } else {
                define('RPM_DEFAULT_ARCH', array());
            }
        }

        if (!defined('RPM_INCLUDE_SOURCE')) {
            if (!empty($repomanager_conf_array['RPM_INCLUDE_SOURCE'])) {
                define('RPM_INCLUDE_SOURCE', $repomanager_conf_array['RPM_INCLUDE_SOURCE']);
            } else {
                define('RPM_INCLUDE_SOURCE', 'false');
            }
        }

        // DEB
        if (!defined('DEB_REPO')) {
            if (!empty($repomanager_conf_array['DEB_REPO'])) {
                define('DEB_REPO', $repomanager_conf_array['DEB_REPO']);
            } else {
                define('DEB_REPO', 'false');
            }
        }

        if (!defined('DEB_SIGN_REPO')) {
            if (!empty($repomanager_conf_array['DEB_SIGN_REPO'])) {
                define('DEB_SIGN_REPO', $repomanager_conf_array['DEB_SIGN_REPO']);
            } else {
                define('DEB_SIGN_REPO', 'false');
            }
        }

        if (!defined('DEB_SIGN_GPG_KEYID')) {
            if (!empty($repomanager_conf_array['DEB_SIGN_GPG_KEYID'])) {
                define('DEB_SIGN_GPG_KEYID', $repomanager_conf_array['DEB_SIGN_GPG_KEYID']);
            } else {
                define('DEB_SIGN_GPG_KEYID', '');

                /**
                 *  On affiche un message uniquement si la signature est activée
                 */
                if (DEB_SIGN_REPO == 'true') {
                    $__LOAD_MAIN_CONF_MESSAGES[] = "GPG key Id for signing DEB packages is not defined.";
                }
            }
        }

        if (!defined('DEB_DEFAULT_ARCH')) {
            if (!empty($repomanager_conf_array['DEB_DEFAULT_ARCH'])) {
                define('DEB_DEFAULT_ARCH', explode(',', $repomanager_conf_array['DEB_DEFAULT_ARCH']));
            } else {
                define('DEB_DEFAULT_ARCH', array());
            }
        }

        if (!defined('DEB_INCLUDE_SOURCE')) {
            if (!empty($repomanager_conf_array['DEB_INCLUDE_SOURCE'])) {
                define('DEB_INCLUDE_SOURCE', $repomanager_conf_array['DEB_INCLUDE_SOURCE']);
            } else {
                define('DEB_INCLUDE_SOURCE', 'false');
            }
        }

        if (!defined('DEB_DEFAULT_TRANSLATION')) {
            if (!empty($repomanager_conf_array['DEB_DEFAULT_TRANSLATION'])) {
                define('DEB_DEFAULT_TRANSLATION', explode(',', $repomanager_conf_array['DEB_DEFAULT_TRANSLATION']));
            } else {
                define('DEB_DEFAULT_TRANSLATION', array());
            }
        }

        /**
         *  Paramètres d'automatisation
         */
        if (!defined('PLANS_ENABLED')) {
            if (!empty($repomanager_conf_array['PLANS_ENABLED'])) {
                define('PLANS_ENABLED', $repomanager_conf_array['PLANS_ENABLED']);
            } else {
                define('PLANS_ENABLED', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Enabling plans is not defined.";
            }
        }

        if (!defined('ALLOW_AUTOUPDATE_REPOS')) {
            if (!empty($repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS'])) {
                define('ALLOW_AUTOUPDATE_REPOS', $repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS']);
            } else {
                define('ALLOW_AUTOUPDATE_REPOS', '');
                if (defined('PLANS_ENABLED') and PLANS_ENABLED == "true") {
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Allowing plans to update repositories is not defined.";
                }
            }
        }

        // if (!defined('ALLOW_AUTOUPDATE_REPOS_ENV')) {
        //     if (!empty($repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS_ENV'])) {
        //         define('ALLOW_AUTOUPDATE_REPOS_ENV', $repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS_ENV']);
        //     } else {
        //         define('ALLOW_AUTOUPDATE_REPOS_ENV', '');
        //         if (defined('PLANS_ENABLED') and PLANS_ENABLED == "true") {
        //             $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation des planifications de création d'environnement n'est pas renseignée.";
        //         }
        //     }
        // }

        if (!defined('ALLOW_AUTODELETE_ARCHIVED_REPOS')) {
            if (!empty($repomanager_conf_array['ALLOW_AUTODELETE_ARCHIVED_REPOS'])) {
                define('ALLOW_AUTODELETE_ARCHIVED_REPOS', $repomanager_conf_array['ALLOW_AUTODELETE_ARCHIVED_REPOS']);
            } else {
                define('ALLOW_AUTODELETE_ARCHIVED_REPOS', '');
                if (defined('PLANS_ENABLED') and PLANS_ENABLED == "true") {
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Allowing plans to delete old repos snapshots is not defined.";
                }
            }
        }

        if (!defined('RETENTION')) {
            if (isset($repomanager_conf_array['RETENTION']) and $repomanager_conf_array['RETENTION'] >= 0) {
                define('RETENTION', intval($repomanager_conf_array['RETENTION'], 8));
            } else {
                define('RETENTION', '');
                if (defined('PLANS_ENABLED') and PLANS_ENABLED == "true") {
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Old repos snapshots retention is not defined.";
                }
            }
        }

        /**
         *  Paramètres des hôtes
         */
        if (!defined('MANAGE_HOSTS')) {
            if (!empty($repomanager_conf_array['MANAGE_HOSTS'])) {
                define('MANAGE_HOSTS', $repomanager_conf_array['MANAGE_HOSTS']);
            } else {
                define('MANAGE_HOSTS', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Enabling hosts management is not defined.";
            }
        }

        /**
         *  Paramètres des profils
         */
        if (!defined('MANAGE_PROFILES')) {
            if (!empty($repomanager_conf_array['MANAGE_PROFILES'])) {
                define('MANAGE_PROFILES', $repomanager_conf_array['MANAGE_PROFILES']);
            } else {
                define('MANAGE_PROFILES', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Enabling profiles management is not defined.";
            }
        }

        if (!defined('REPO_CONF_FILES_PREFIX')) {
            if (!empty($repomanager_conf_array['REPO_CONF_FILES_PREFIX'])) {
                define('REPO_CONF_FILES_PREFIX', $repomanager_conf_array['REPO_CONF_FILES_PREFIX']);
            } else {
                define('REPO_CONF_FILES_PREFIX', '');
            }
        }

        /**
         *  Paramètres statistiques
         */
        if (!defined('STATS_ENABLED')) {
            if (!empty($repomanager_conf_array['STATS_ENABLED'])) {
                define('STATS_ENABLED', $repomanager_conf_array['STATS_ENABLED']);
            } else {
                define('STATS_ENABLED', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "Enabling repos statistics is not defined.";
            }
        }

        if (STATS_ENABLED == "true") {
            if (!defined('STATS_LOG_PATH')) {
                if (!empty($repomanager_conf_array['STATS_LOG_PATH'])) {
                    define('STATS_LOG_PATH', $repomanager_conf_array['STATS_LOG_PATH']);

                    /**
                     *  On teste l'accès au chemin renseigné
                     */
                    if (!is_readable(STATS_LOG_PATH)) {
                        ++$__LOAD_MAIN_CONF_ERROR; // On force l'affichage d'un message d'erreur même si le paramètre n'est pas vide
                        $__LOAD_MAIN_CONF_MESSAGES[] = "Access log file to scan for statistics is not readable: '" . STATS_LOG_PATH . "'";
                    }
                } else {
                    define('STATS_LOG_PATH', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Access log file to scan for statistics is not defined.";
                }
            }
        }

        if (!defined('PLAN_REMINDERS_ENABLED')) {
            if (!empty($repomanager_conf_array['PLAN_REMINDERS_ENABLED'])) {
                define('PLAN_REMINDERS_ENABLED', $repomanager_conf_array['PLAN_REMINDERS_ENABLED']);
            } else {
                define('PLAN_REMINDERS_ENABLED', 'false');
            }
        }

        /**
         *  Paramètres supplémentaires pour rpm / yum
         */
        if (!defined('MACROS_FILE')) {
            define('MACROS_FILE', DATA_DIR . '/.rpm/.mcs');
        }

        /**
         *  Date et heure du jour
         */
        if (!defined('DATE_DMY')) {
            define('DATE_DMY', date("d-m-Y"));
        }
        if (!defined('DATE_YMD')) {
            define('DATE_YMD', date("Y-m-d"));
        }
        if (!defined('TIME')) {
            define('TIME', date("H-i"));
        }

        if (!defined('__LOAD_MAIN_CONF_ERROR')) {
            define('__LOAD_MAIN_CONF_ERROR', $__LOAD_MAIN_CONF_ERROR);
        }
        if (!defined('__LOAD_MAIN_CONF_MESSAGES')) {
            define('__LOAD_MAIN_CONF_MESSAGES', $__LOAD_MAIN_CONF_MESSAGES);
        }

        unset($repomanager_conf_array);
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
     *  Return true if repomanager systemd service is running
     */
    private static function serviceIsActive()
    {
        exec('systemctl is-active repomanager --quiet', $output, $return);

        if ($return == 0) {
            if (!defined('SERVICE_RUNNING')) {
                define('SERVICE_RUNNING', true);
            }
        }

        if (!defined('SERVICE_RUNNING')) {
            define('SERVICE_RUNNING', false);
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
                        define('CACHE_REPOS_LIST', $row['cache_repos_list']);
                    }
                } catch (\Exception $e) {
                    \Controllers\Common::dbError($e);
                }

                $myconn->close();
            }
        }
    }
}
