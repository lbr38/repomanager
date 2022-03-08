<?php
/**
 *  Classe d'autochargement des classes et des constantes
 */

class Autoloader
{
    static function register()
    {
        /**
         *  Fait appel à la classe Autoloader (cette même classe) et à sa fonction autoload
         */
        spl_autoload_register(array('Autoloader', 'autoload'));
    }

    static function autoload($class)
    {
        require ROOT . '/models/' . $class . '.php';
    }

    /**
     *  Chargement de tous les paramètres nécessaires pour le fonctionnement des pages web (voir loadFromApi() pour l'api)
     *  - chargement des sessions
     *  - constantes
     *  - vérifications de la présence de tous les répertoires et fichiers nécessaires
     */
    static function load()
    {
        $__LOAD_GENERAL_ERROR = 0;
        $__LOAD_ERROR_MESSAGES = array();

        date_default_timezone_set('Europe/Paris');

        /**
         *  On défini un cookie contenant l'URI en cours, utile pour rediriger directement vers cette URI après s'être identifié sur la page de login
         */
        if (!empty($_SERVER['REQUEST_URI'])) {
            setcookie('origin', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
        }

        if (!defined('ROOT')) define('ROOT', dirname(__FILE__, 2));

        /**
         *  Chargement de toutes les fonctions nécessaires
         */
        Autoloader::loadAll();

        /**
         *  On récupère les éventuelles erreurs de chargement
         */
        /**
         *  Erreur liées au chargement de la configuration principale
         */
        if (__LOAD_MAIN_CONF_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "Certains paramètres généraux ne sont pas configurés :<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __LOAD_MAIN_CONF_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Erreur liées au chargement des environnements
         */
        if (__LOAD_ERROR_EMPTY_ENVS > 0) {
            $__LOAD_ERROR_MESSAGES[] = 'Vous devez configurer au moins 1 environnement.';
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  On définit une constante qui contient le nb d'erreur rencontrées
         */
        if (!defined('__LOAD_GENERAL_ERROR')) define('__LOAD_GENERAL_ERROR', $__LOAD_GENERAL_ERROR);

        /**
         *  On définit une constante qui contient tous les messages d'erreurs récoltés
         */
        if (!defined('__LOAD_ERROR_MESSAGES')) define('__LOAD_ERROR_MESSAGES', $__LOAD_ERROR_MESSAGES);

        unset($__LOAD_GENERAL_ERROR, $__LOAD_ERROR_MESSAGES);
    }

    /**
     *  Chargement de tous les paramètres nécessaires pour le fonctionnement de l'api
     *  Charge moins de fonctions que load() notamment les sessions ne sont par démarrées car empêcheraient le bon fonctionnement de l'api
     */
    static function loadFromApi()
    {
        $__LOAD_GENERAL_ERROR = 0;
        $__LOAD_ERROR_MESSAGES = array();

        date_default_timezone_set('Europe/Paris');

        if (!defined('ROOT')) define('ROOT', dirname(__FILE__, 2));

        /**
         *  Chargement des fonctions nécessaires
         */
        Autoloader::register();
        Autoloader::loadSystem();
        Autoloader::loadConfiguration();
        Autoloader::loadDirs();
        Autoloader::loadEnvs();

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
        if (!defined('__LOAD_GENERAL_ERROR')) define('__LOAD_GENERAL_ERROR', $__LOAD_GENERAL_ERROR);

        unset($__LOAD_GENERAL_ERROR);
    }

    /**
     *  Exécution de toutes les fonctions
     */
    static function loadAll()
    {
        Autoloader::register();
        Autoloader::loadSession();
        Autoloader::loadSystem();
        Autoloader::loadConfiguration();
        Autoloader::loadDirs();
        Autoloader::loadEnvs();
        Autoloader::checkForUpdate();
        Autoloader::startStats();
        Autoloader::loadReposListDisplayConf();
    }
    
    /**
     *  Démarrage et vérification de la session en cours
     */
    static function loadSession() {
        /**
         *  On démarre la session
         */
        if(!isset($_SESSION)){
            session_start();
        }

        /**
         *  Si la session a dépassé les 30min alors on redirige vers logout.php qui se chargera de détruire la session
         */
        if (isset($_SESSION['start_time']) && (time() - $_SESSION['start_time'] > 1800)) {
            History::set($_SESSION['username'], "Session expirée, déconnexion", 'success');
            header('Location: logout.php');
            exit();
        }

        /**
         *  On défini l'heure de création de la session (ou on la renouvelle si la session est toujours en cours)
         */
        // if (empty($_SESSION['start_time'])) {
        //     $_SESSION['start_time'] = time();
        // }
        $_SESSION['start_time'] = time();

        /**
         *  Si les variables de session username ou role sont vides alors on redirige vers la page de login
         */
        if(empty($_SESSION['username']) OR empty($_SESSION['role'])) {
            header('Location: login.php');
            exit();
        }
    }

    /**
     *  Chargement des chemins vers les répertoires et fichiers de base
     *  Création si n'existent pas
     */
    static function loadDirs()
    {
        /**
         *  Emplacement des répertoires de bases
         */

        // Emplacement de la DB
        if (!defined('DB_DIR')) define('DB_DIR', ROOT."/db");
        if (!defined('DB')) define('DB', ROOT."/db/repomanager.db");
        // Emplacement du répertoire de cache
        if (!defined('WWW_CACHE')) define('WWW_CACHE', ROOT."/cache");
        // Emplacement du répertoire de clé GPG
        if (!defined('GPGHOME')) define('GPGHOME', ROOT."/.gnupg");
        // Répertoire des résultats de tâches cron
        if (!defined('CRON_DIR')) define('CRON_DIR', ROOT."/cron");
        // Répertoire principal des logs
        if (!defined('LOGS_DIR')) define('LOGS_DIR', ROOT."/logs");
        // Logs du programme
        if (!defined('MAIN_LOGS_DIR')) define('MAIN_LOGS_DIR', LOGS_DIR.'/main');
        if (!defined('EXCEPTIONS_LOG')) define('EXCEPTIONS_LOG', LOGS_DIR.'/exceptions');
        // Logs des cron
        if (!defined('CRON_LOGS_DIR')) define('CRON_LOGS_DIR', LOGS_DIR.'/cron');
        if (!defined('CRON_LOG')) define('CRON_LOG', CRON_LOGS_DIR.'/cronjob-daily.log');
        if (!defined('CRON_STATS_LOG')) define('CRON_STATS_LOG', CRON_LOGS_DIR.'/cronjob-stats.log');
        // Pool de taches asynchrones
        if (!defined('POOL')) define('POOL', ROOT."/operations/pool");

        // PIDs
        if (!defined('PID_DIR')) define('PID_DIR', ROOT."/operations/pid");
        // Répertoire contenant des fichiers temporaires
        if (!defined('TEMP_DIR')) define('TEMP_DIR', ROOT."/.temp");
        // Profils
        if (!defined('PROFILES_MAIN_DIR')) define('PROFILES_MAIN_DIR', REPOS_DIR.'/profiles');
        if (defined('__SERVER_URL__')) {
            if (!defined('WWW_PROFILES_DIR_URL')) define('WWW_PROFILES_DIR_URL', __SERVER_URL__.'/profiles');
        }
        if (!defined('REPOS_PROFILES_CONF_DIR')) define('REPOS_PROFILES_CONF_DIR', PROFILES_MAIN_DIR.'/_configurations');
        if (!defined('REPOSERVER_PROFILES_CONF_DIR')) define('REPOSERVER_PROFILES_CONF_DIR', PROFILES_MAIN_DIR.'/_reposerver');
        if (!defined('PROFILE_SERVER_CONF')) define('PROFILE_SERVER_CONF', REPOSERVER_PROFILES_CONF_DIR.'/main.conf');
        // Hotes
        if (!defined('HOSTS_DIR')) define('HOSTS_DIR', ROOT.'/hosts');
        // Répertoires et fichiers supplémentaires pour Redhat
        if (OS_FAMILY == "Redhat") {
            // Emplacement de la conf yum
            if (!defined('REPOMANAGER_YUM_DIR')) define('REPOMANAGER_YUM_DIR', "/etc/yum.repos.d/repomanager");
            if (!defined('REPOMANAGER_YUM_CONF')) define('REPOMANAGER_YUM_CONF', "/etc/yum.repos.d/repomanager/repomanager.conf");
            // Emplacement des clés gpg importées par repomanager
            if (!defined('RPM_GPG_DIR')) define('RPM_GPG_DIR', "/etc/pki/rpm-gpg/repomanager");
            if (!defined('PASSPHRASE_FILE')) define('PASSPHRASE_FILE', GPGHOME.'/passphrase');
        }

        /**
         *  Création des fichiers et répertoires de base si n'existent pas
         */
        if (!is_dir(DB_DIR))        mkdir(DB_DIR, 0770, true);
        if (!is_dir(GPGHOME))       mkdir(GPGHOME, 0770, true);
        if (!is_dir(LOGS_DIR))      mkdir(LOGS_DIR, 0770, true);
        if (!is_dir(MAIN_LOGS_DIR)) mkdir(MAIN_LOGS_DIR, 0770, true);
        if (!is_dir(CRON_LOGS_DIR)) mkdir(CRON_LOGS_DIR, 0770, true);
        if (!is_dir(CRON_DIR))      mkdir(CRON_DIR, 0770, true);
        if (!is_dir(POOL))          mkdir(POOL, 0770, true);
        if (!is_dir(PID_DIR))       mkdir(PID_DIR, 0770, true);
        if (!is_dir(TEMP_DIR))      mkdir(TEMP_DIR, 0770, true);
        if (!is_dir(HOSTS_DIR))     mkdir(HOSTS_DIR, 0770, true);
        if (!file_exists(WWW_CACHE)) {
            // Si /dev/shm/ (répertoire en mémoire) existe, alors on crée un lien symbolique vers ce répertoire, sinon on crée un répertoire 'cache' classique
            if (file_exists("/dev/shm")) { 
                exec("cd ".ROOT." && ln -sfn /dev/shm cache"); 
            } else { 
                mkdir(ROOT."/cache", 0770, true); 
            }
        }

        /**
         *  Création du répertoire de backup si n'existe pas
         */
        if (!is_dir(BACKUP_DIR)) {
            if (!mkdir(BACKUP_DIR, 0770, true)) {
                $GENERAL_ERROR_MESSAGES[] = 'Impossible de créer le répertoire de sauvegarde : '.$BACKUP_DIR;
            }
        }
        /**
         *  Création du répertoire de mise à jour si n'existe pas
         */
        if (!is_dir(ROOT."/update")) {
            if (!mkdir(ROOT."/update", 0770, true)) {
                $GENERAL_ERROR_MESSAGES[] = 'Impossible de créer le répertoire de mise à jour : '.ROOT.'/update';
            }
        }

        // Crée le répertoire principal des profils si n'existe pas
        if (!file_exists(PROFILES_MAIN_DIR)) mkdir(PROFILES_MAIN_DIR, 0775, true);

        // Crée le répertoire qui accueille les fichiers de conf .list ou .repo si n'existe pas
        if (!file_exists(REPOS_PROFILES_CONF_DIR)) mkdir(REPOS_PROFILES_CONF_DIR, 0775, true);

        // Crée le répertoire qui accueille le fichier de conf du serveur de repo
        if (!file_exists(REPOSERVER_PROFILES_CONF_DIR)) mkdir(REPOSERVER_PROFILES_CONF_DIR, 0775, true);

        // Crée le fichier de conf du serveur n'existe pas on le crée
        if (!file_exists(PROFILE_SERVER_CONF)) touch(PROFILE_SERVER_CONF);

        /**
         *  Vérification de la présence de la base de données
         *  Si aucun fichier de base de données n'existe ou bien si on a précisé le paramètre ?initialize
         */
        if (!file_exists(DB) OR isset($_GET['initialize'])) {
            /**
             *  On va vérifier la présence des tables et les créer si nécessaire
             */
            $myconn = new Connection('main');

            if (!$myconn->checkMainTables()) {
                /**
                 *  Si la vérification a échouée alors on quitte.
                 */
                die();
            }
        }

        /**
         *  Si la clé de signature GPG n'existe pas alors on l'exporte
         */
        if (GPG_SIGN_PACKAGES == "yes" AND !file_exists(REPOS_DIR."/gpgkeys/".WWW_HOSTNAME.".pub")) {
            if (!is_dir(REPOS_DIR."/gpgkeys")) {
                mkdir(REPOS_DIR."/gpgkeys", 0770, true);
            }
            exec("gpg2 --no-permission-warning --homedir '".GPGHOME."' --export -a '".GPG_KEYID."' > ".REPOS_DIR."/gpgkeys/".WWW_HOSTNAME.".pub 2>/dev/null");
        }
    }

    /**
     *  Chargement des informations du système et de l'OS
     */
    static function loadSystem()
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
            if (!defined('__SERVER_URL__')) define('__SERVER_URL__', "$__SERVER_PROTOCOL__://".$_SERVER['SERVER_NAME']);
        }
        /**
         *  Adresse IP du serveur
         */
        if (!empty($_SERVER['SERVER_ADDR'])) {
            if (!defined('__SERVER_IP__')) define('__SERVER_IP__', $_SERVER['SERVER_ADDR']);
        }
        /**
         *  URL + URI complètes
         */
        if (!empty($_SERVER['HTTP_HOST']) AND !empty($_SERVER['REQUEST_URI'])) {
            if (!defined('__ACTUAL_URL__')) define('__ACTUAL_URL__', "$__SERVER_PROTOCOL__://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
        }
        /**
         *  URI
         */
        if (!empty($_SERVER['REQUEST_URI'])) {
            if (!defined('__ACTUAL_URI__')) {
                if (!defined('__ACTUAL_URI__')) define('__ACTUAL_URI__', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
            }
        }

        /**
         *  Récupération du nom et de la version de l'OS, le tout étant retourné sous forme d'array dans $OS_INFO
         */
        if (!is_readable('/etc/os-release')) {
            echo 'Erreur : impossible de détecter la version du système';
            die;
        }
        $os      = file_get_contents('/etc/os-release');
        $listIds = preg_match_all('/.*=/', $os, $matchListIds);
        $listIds = $matchListIds[0];
        $listVal = preg_match_all('/=.*/', $os, $matchListVal);
        $listVal = $matchListVal[0];
        array_walk($listIds, function(&$v, $k){
            $v = strtolower(str_replace('=', '', $v));
        });
        array_walk($listVal, function(&$v, $k){
            $v = preg_replace('/=|"/', '', $v);
        });

        if (!defined('OS_INFO')) define('OS_INFO', array_combine($listIds, $listVal));

        /**
         *  Puis à partir de l'array OS_INFO on détermine la famille d'os, son nom et sa version
         */
        if (!empty(OS_INFO['id_like'])) {
            if(preg_match('(rhel|centos|fedora)', OS_INFO['id_like']) === 1) {
                if (!defined('OS_FAMILY')) define('OS_FAMILY', "Redhat");
            }
            if(preg_match('(debian|ubuntu|kubuntu|xubuntu|armbian|mint)', OS_INFO['id_like']) === 1) { 
                if (!defined('OS_FAMILY')) define('OS_FAMILY', "Debian");
            }
        } else if (!empty(OS_INFO['id'])) {
            if(preg_match('(rhel|centos|fedora)', OS_INFO['id']) === 1) {
                if (!defined('OS_FAMILY')) define('OS_FAMILY', "Redhat");
            }
            if(preg_match('(debian|ubuntu|kubuntu|xubuntu|armbian|mint)', OS_INFO['id']) === 1) { 
                if (!defined('OS_FAMILY')) define('OS_FAMILY', "Debian");
            }
        }
        
        /**
         *  A partir d'ici si OS_FAMILY n'est pas défini alors le système sur lequel est installé Repomanager est incompatible
         */
        if (!defined('OS_FAMILY')) {
            die('Erreur : Repomanager est incompatible sur cet OS');
        }

        if (!defined('OS_NAME')) define('OS_NAME', OS_INFO['name']);
        if (!defined('OS_ID')) define('OS_ID', OS_INFO['id']);
        if (!defined('OS_VERSION')) define('OS_VERSION', OS_INFO['version_id']);

        if (!defined('PACKAGE_TYPE')) {
            if (OS_FAMILY == "Redhat") {
                define('PACKAGE_TYPE', 'rpm');
            }
            if (OS_FAMILY == "Debian") {
                define('PACKAGE_TYPE', 'deb');
            }
        }
    }

    /**
     *  Chargement de la configuration de repomanager
     */
    static function loadConfiguration()
    {
        $__LOAD_MAIN_CONF_ERROR = 0;
        $__LOAD_MAIN_CONF_MESSAGES = array();

        /**
         *  Emplacements du fichier de conf
         */
        if (!defined('REPOMANAGER_CONF')) define('REPOMANAGER_CONF', ROOT."/configurations/repomanager.conf");

        /**
         *  Vérification de la présence de repomanager.conf
         */
        if (!file_exists(REPOMANAGER_CONF)) {
            echo "Erreur : fichier de configuration introuvable. Vous devez relancer l'installation de repomanager.".ROOT;
            die();
        }

        /**
         *  Récupération de tous les paramètres définis dans le fichier repomanager.conf
         */
        $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF);

        /**
         *  Si certains paramètres sont vides alors on incrémente $EMPTY_CONFIGURATION_VARIABLES qui fera afficher un bandeau d'alertes
         */
        foreach($repomanager_conf_array as $key => $value) {
            if(empty($value)) {
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
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Le répertoire de stockage des repos '".REPOS_DIR."' n'est pas accessible en écriture.";
                }
            } else {
                define('REPOS_DIR', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = 'Le répertoire de stockage des repos n\'est pas renseigné.';
            }
        }

        if (!defined('GPG_SIGN_PACKAGES')) {
            if (!empty($repomanager_conf_array['GPG_SIGN_PACKAGES'])) {
                define('GPG_SIGN_PACKAGES', $repomanager_conf_array['GPG_SIGN_PACKAGES']);
            } else {
                define('GPG_SIGN_PACKAGES', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation de la signature des paquets avec GPG n'est pas renseigné.";
            }
        }

        if (!defined('GPG_KEYID')) {
            if (GPG_SIGN_PACKAGES == "yes") {
                if (!empty($repomanager_conf_array['GPG_KEYID'])) {
                    define('GPG_KEYID', $repomanager_conf_array['GPG_KEYID']);
                } else {
                    define('GPG_KEYID', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "L'ID de la clé de signature GPG n'est pas renseigné.";
                }
            }
        }

        if (!defined('EMAIL_DEST')) {
            if (!empty($repomanager_conf_array['EMAIL_DEST'])) {
                define('EMAIL_DEST', $repomanager_conf_array['EMAIL_DEST']);
            } else {
                define('EMAIL_DEST', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = 'Aucune adresse mail de contact n\'est renseignée.';
            }
        }

        if (!defined('UPDATE_AUTO')) {
            if (!empty($repomanager_conf_array['UPDATE_AUTO'])) {
                define('UPDATE_AUTO', $repomanager_conf_array['UPDATE_AUTO']);
            } else {
                define('UPDATE_AUTO', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation des mises à jour automatiques de repomanager n'est pas renseignée.";
            }
        }

        if (!defined('UPDATE_BRANCH')) {
            if (!empty($repomanager_conf_array['UPDATE_BRANCH'])) {
                define('UPDATE_BRANCH', $repomanager_conf_array['UPDATE_BRANCH']);
            } else {
                define('UPDATE_BRANCH', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "La branche de mise à jour n'est pas renseignée.";
            }
        }

        if (!defined('UPDATE_BACKUP_ENABLED')) {
            if (!empty($repomanager_conf_array['UPDATE_BACKUP_ENABLED'])) {
                define('UPDATE_BACKUP_ENABLED', $repomanager_conf_array['UPDATE_BACKUP_ENABLED']);
            } else {
                define('UPDATE_BACKUP_ENABLED', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation des sauvegardes avant mise à jour n'est pas renseignée.";
            }
        }

        if (!defined('BACKUP_DIR')) {
            if (UPDATE_BACKUP_ENABLED == "yes") {
                if (!empty($repomanager_conf_array['BACKUP_DIR'])) {
                    define('BACKUP_DIR', $repomanager_conf_array['BACKUP_DIR']);
                    /**
                     *  On teste l'accès au répertoire renseigné
                     */
                    if (!is_writable(BACKUP_DIR)) {
                        ++$__LOAD_MAIN_CONF_ERROR; // On force l'affichage d'un message d'erreur même si le paramètre n'est pas vide
                        $__LOAD_MAIN_CONF_MESSAGES[] = "Le répertoire de sauvegarde pre-mise à jour '".BACKUP_DIR."' n'est pas accessible en écriture.";
                    }
                } else {
                    define('BACKUP_DIR', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Le répertoire de stockage des sauvegardes avant mises à jour n'est pas renseigné.";
                }
            }
        }

        if (!defined('DEBUG_MODE')) {
            if (!empty($repomanager_conf_array['DEBUG_MODE'])) {
                define('DEBUG_MODE', $repomanager_conf_array['DEBUG_MODE']);
            } else {
                define('DEBUG_MODE', 'disabled');
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
                $__LOAD_MAIN_CONF_MESSAGES[] = "";
            }
        }
        
        if (!defined('WWW_REPOS_DIR_URL')) {
            if (!empty($repomanager_conf_array['WWW_REPOS_DIR_URL'])) {
                define('WWW_REPOS_DIR_URL', $repomanager_conf_array['WWW_REPOS_DIR_URL']);
            } else {
                define('WWW_REPOS_DIR_URL', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "";
            }
        }

        if (!defined('WWW_USER')) {
            if (!empty($repomanager_conf_array['WWW_USER'])) {
                define('WWW_USER', $repomanager_conf_array['WWW_USER']);
            } else {
                define('WWW_USER', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'utilisateur exécutant le service web de ce serveur n'est pas renseigné.";
            }
        }

        /**
         *  Paramètres d'automatisation
         */
        if (!defined('AUTOMATISATION_ENABLED')) {
            if (!empty($repomanager_conf_array['AUTOMATISATION_ENABLED'])) {
                define('AUTOMATISATION_ENABLED', $repomanager_conf_array['AUTOMATISATION_ENABLED']);
            } else {
                define('AUTOMATISATION_ENABLED', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation des planifications n'est pas renseignée.";
            }
        }

        if (defined('AUTOMATISATION_ENABLED') AND AUTOMATISATION_ENABLED == "yes") {
            if (!defined('ALLOW_AUTOUPDATE_REPOS')) {
                if (!empty($repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS'])) {
                    define('ALLOW_AUTOUPDATE_REPOS', $repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS']);
                } else {
                    define('ALLOW_AUTOUPDATE_REPOS', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation des planifications de mise à jour de repo n'est pas renseignée.";
                }
            }

            if (!defined('ALLOW_AUTOUPDATE_REPOS_ENV')) {
                if (!empty($repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS_ENV'])) {
                    define('ALLOW_AUTOUPDATE_REPOS_ENV', $repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS_ENV']);
                } else {
                    define('ALLOW_AUTOUPDATE_REPOS_ENV', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation des planifications de création d'environnement n'est pas renseignée.";
                }
            }

            if (!defined('ALLOW_AUTODELETE_ARCHIVED_REPOS')) {
                if (!empty($repomanager_conf_array['ALLOW_AUTODELETE_ARCHIVED_REPOS'])) {
                    define('ALLOW_AUTODELETE_ARCHIVED_REPOS', $repomanager_conf_array['ALLOW_AUTODELETE_ARCHIVED_REPOS']);
                } else {
                    define('ALLOW_AUTODELETE_ARCHIVED_REPOS', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation de la suppression automatique des repos archivés n'est pas renseignée.";
                }
            }

            if (!defined('RETENTION')) {
                if (!empty($repomanager_conf_array['RETENTION'])) {
                    define('RETENTION', intval($repomanager_conf_array['RETENTION'], 8));
                } else {
                    define('RETENTION', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Aucune rétention de sauvegardes n'est configurée.";
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
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation de la gestion des hôtes n'est pas renseignée.";
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
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation de la gestion des profils d'hôtes n'est pas renseignée.";
            }
        }

        if (!defined('REPO_CONF_FILES_PREFIX')) {
            if (!empty($repomanager_conf_array['REPO_CONF_FILES_PREFIX'])) {
                define('REPO_CONF_FILES_PREFIX', $repomanager_conf_array['REPO_CONF_FILES_PREFIX']);
            } else {
                define('REPO_CONF_FILES_PREFIX', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "";
            }
        }

        /**
         *  Paramètres cron
         */
        if (!defined('CRON_STATS_ENABLED')) {
            if (!empty($repomanager_conf_array['CRON_STATS_ENABLED'])) {
                define('CRON_STATS_ENABLED', $repomanager_conf_array['CRON_STATS_ENABLED']);
            } else {
                define('CRON_STATS_ENABLED', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation des statistiques n'est pas renseignée.";
            }
        }

        if (CRON_STATS_ENABLED == "yes") {
            if (!defined('WWW_STATS_LOG_PATH')) {
                if (!empty($repomanager_conf_array['WWW_STATS_LOG_PATH'])) {
                    define('WWW_STATS_LOG_PATH', $repomanager_conf_array['WWW_STATS_LOG_PATH']);

                    /**
                     *  On teste l'accès au chemin renseigné
                     */
                    if (!is_readable(WWW_STATS_LOG_PATH)) {
                        ++$__LOAD_MAIN_CONF_ERROR; // On force l'affichage d'un message d'erreur même si le paramètre n'est pas vide
                        $__LOAD_MAIN_CONF_MESSAGES[] = "Le fichier de log (access log) à analyser pour les statistiques n'est pas accessible en lecture : '".WWW_STATS_LOG_PATH."'";
                    }

                } else {
                    define('WWW_STATS_LOG_PATH', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "Le chemin d'accès au fichier de log (access log) à analyser pour les statistiques n'est pas renseigné.";
                }
            }
        }

        if (!defined('CRON_DAILY_ENABLED')) {
            if (!empty($repomanager_conf_array['CRON_DAILY_ENABLED'])) {
                define('CRON_DAILY_ENABLED', $repomanager_conf_array['CRON_DAILY_ENABLED']);
            } else {
                define('CRON_DAILY_ENABLED', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "L'activation / désactivation du cronjob régulier n'est pas renseignée.";
            }
        }

        if (!defined('CRON_GENERATE_REPOS_CONF')) {
            if (!empty($repomanager_conf_array['CRON_GENERATE_REPOS_CONF'])) {
                define('CRON_GENERATE_REPOS_CONF', $repomanager_conf_array['CRON_GENERATE_REPOS_CONF']);
            } else {
                define('CRON_GENERATE_REPOS_CONF', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "";
            }
        }

        if (!defined('CRON_APPLY_PERMS')) {
            if (!empty($repomanager_conf_array['CRON_APPLY_PERMS'])) {
                define('CRON_APPLY_PERMS', $repomanager_conf_array['CRON_APPLY_PERMS']);
            } else {
                define('CRON_APPLY_PERMS', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "";
            }
        }

        if (!defined('CRON_SAVE_CONF')) {
            if (!empty($repomanager_conf_array['CRON_SAVE_CONF'])) {
                define('CRON_SAVE_CONF', $repomanager_conf_array['CRON_SAVE_CONF']);
            } else {
                define('CRON_SAVE_CONF', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "";
            }
        }

        if (!defined('CRON_PLAN_REMINDERS_ENABLED')) {
            if (!empty($repomanager_conf_array['CRON_PLAN_REMINDERS_ENABLED'])) {
                define('CRON_PLAN_REMINDERS_ENABLED', $repomanager_conf_array['CRON_PLAN_REMINDERS_ENABLED']);
            } else {
                define('CRON_PLAN_REMINDERS_ENABLED', '');
                $__LOAD_MAIN_CONF_MESSAGES[] = "";
            }
        }

        /**
         *  Paramètres supplémentaires si Redhat
         */
        if (OS_FAMILY == "Redhat") {
            if (!defined('RELEASEVER')) {
                if (!empty($repomanager_conf_array['RELEASEVER'])) {
                    define('RELEASEVER', $repomanager_conf_array['RELEASEVER']);
                } else {
                    define('RELEASEVER', '');
                    $__LOAD_MAIN_CONF_MESSAGES[] = "";
                }
            }
        }

        /**
         *  Date et heure du jour
         */
        if (!defined('DATE_DMY')) define('DATE_DMY', date("d-m-Y"));
        if (!defined('DATE_YMD')) define('DATE_YMD', date("Y-m-d"));
        if (!defined('TIME')) define('TIME', date("H-i"));

        if (!defined('__LOAD_MAIN_CONF_ERROR')) define('__LOAD_MAIN_CONF_ERROR', $__LOAD_MAIN_CONF_ERROR);
        if (!defined('__LOAD_MAIN_CONF_MESSAGES')) define('__LOAD_MAIN_CONF_MESSAGES', $__LOAD_MAIN_CONF_MESSAGES);

        unset($repomanager_conf_array);
    }

    /**
     *  Chargement des environnements
     */
    static function loadEnvs()
    {
        /**
         *  Récupération des environnements en base de données
         */
        $myenv = new Environnement();
        if (!defined('ENVS')) define('ENVS', $myenv->listAll());
        if (!defined('ENVS_TOTAL')) define('ENVS_TOTAL', $myenv->total());
        if (!defined('DEFAULT_ENV')) define('DEFAULT_ENV', $myenv->default());
        if (!defined('LAST_ENV')) define('LAST_ENV', $myenv->last());

        /**
         *  Si il n'y a aucun environnement configuré alors __LOAD_ERROR_EMPTY_ENVS = 1
         */
        if(empty(ENVS)) {
            if (!defined('__LOAD_ERROR_EMPTY_ENVS')) define('__LOAD_ERROR_EMPTY_ENVS', 1);
        } else {
            if (!defined('__LOAD_ERROR_EMPTY_ENVS')) define('__LOAD_ERROR_EMPTY_ENVS', 0);
        }
    }

    /**
     *  Vérification des nouvelles versions disponibles
     *  Vérification si une mise à jour est en cours ou non
     */
    static function checkForUpdate()
    {
        /**
         *  Version actuelle et version disponible sur github
         */
        if (!defined('VERSION')) define('VERSION', file_get_contents(ROOT.'/version'));
        if (!defined('GIT_VERSION')) define('GIT_VERSION', file_get_contents(ROOT.'/cron/github.version'));
        if (defined('VERSION') AND defined('GIT_VERSION')) {
            if (VERSION !== GIT_VERSION) {
                if (!defined('UPDATE_AVAILABLE')) define('UPDATE_AVAILABLE', 'yes');
            } else {
                if (!defined('UPDATE_AVAILABLE')) define('UPDATE_AVAILABLE', 'no');
            }
        }

        /**
         *  Vérification si une mise à jour de repomanager est en cours
         */
        if (file_exists(ROOT."/update-running")) {
            if (!defined('UPDATE_RUNNING')) define('UPDATE_RUNNING', 'yes');
        } else {
            if (!defined('UPDATE_RUNNING')) define('UPDATE_RUNNING', 'no');
        }

        /**
         *  Si la mise à jour automatique est activé et qu'une mise à jour est disponible alors on l'installe en arrière-plan.
         *  L'action est effectuée uniquement si une mise à jour n'est pas déjà en cours (présence du fichier update-running)
         *  La mise à jour mettra en place une page de maintenance automatiquement
         */
        if (UPDATE_AUTO == "yes" AND UPDATE_AVAILABLE == "yes") {
            if (!file_exists(ROOT."/update-running")) {
                exec('curl '.$_SERVER['HTTP_HOST'].'configuration.php?action=update &');
                sleep(1);
            }
        }
    }

    /**
     *  Démarrage du script de parsage des logs, pour les statistiques
     */
    static function startStats()
    {
        /**
         *  Si les stats sont activées mais que le parser de log ne tourne pas, alors on le lance en arrière-plan
         */
        if (CRON_STATS_ENABLED == "yes" AND empty(shell_exec("/bin/ps -ax | grep 'stats-log-parser' | grep -v 'grep'"))) {  
            exec("bash ".ROOT."/tools/stats-log-parser '".WWW_STATS_LOG_PATH."' >/dev/null 2>/dev/null &");
        }
    }

    /**
     *  Chargement de la configuration de l'affichage de la liste des repos
     */
    static function loadReposListDisplayConf()
    {
        /**
         *  On ne charge ces paramètres uniquement sur certaines pages
         */
        if (defined('__ACTUAL_URI__')) {
            if (__ACTUAL_URI__ == "/" OR 
                __ACTUAL_URI__ == "/index.php" OR 
                __ACTUAL_URI__ == "/operation.php" OR 
                __ACTUAL_URI__ == "/planifications.php"
            ) {
            
                /**
                 *  Emplacement du fichier de conf
                 */
                define('DISPLAY_CONF', ROOT."/configurations/display.ini");

                /**
                 *  Récupération de tous les paramètres définis dans le fichier display.ini
                 */
                $display_ini_array = parse_ini_file(ROOT."/configurations/display.ini");

                /**
                 *  Chargement des paramètres d'affichage de la liste des repos
                 */
                define('PRINT_REPO_SIZE', $display_ini_array['printRepoSize']);
                define('PRINT_REPO_TYPE', $display_ini_array['printRepoType']);
                define('PRINT_REPO_SIGNATURE', $display_ini_array['printRepoSignature']);
                define('CACHE_REPOS_LIST', $display_ini_array['cache_repos_list']);

                unset($display_ini_array);
            }
        }
    }
}
?>