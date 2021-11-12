<?php
date_default_timezone_set('Europe/Paris');

/**
 *  CHARGEMENT DES CONSTANTES
 */

$WWW_DIR = dirname(__FILE__, 2);

$EMPTY_CONFIGURATION_VARIABLES = 0;
$GENERAL_ERROR_MESSAGES = [];

// Vérification de la présence de repomanager.conf
if (!file_exists("${WWW_DIR}/configurations/repomanager.conf")) {
    echo "Erreur : fichier de configuration introuvable. Vous devez relancer l'installation de repomanager.";
    die();
}

// Récupération de tous les paramètres définis dans le fichier repomanager.conf
$repomanager_conf_array = parse_ini_file("${WWW_DIR}/configurations/repomanager.conf");

// Si certains paramètres sont vides alors on incrémente EMPTY_CONFIGURATION_VARIABLES qui fera afficher un bandeau d'alerte
foreach($repomanager_conf_array as $key => $value) {
    if(empty($value)) {
        ++$EMPTY_CONFIGURATION_VARIABLES;
    }
}

$REPOS_DIR = $repomanager_conf_array['REPOS_DIR'];

// Emplacements des fichiers de conf
$REPOMANAGER_CONF = "${WWW_DIR}/configurations/repomanager.conf";
$DISPLAY_CONF = "${WWW_DIR}/configurations/display.ini";
//$ENV_CONF = "${WWW_DIR}/configurations/envs.conf";

// Emplacement de la DB
$DB_DIR = "${WWW_DIR}/db";
$DB = "${WWW_DIR}/db/repomanager.db";
// Emplacement des groupes
$GROUPS_DIR = "${WWW_DIR}/configurations/groups";
// Emplacement du répertoire de cache
$WWW_CACHE = "${WWW_DIR}/cache";
// Emplacement du répertoire de clé GPG
$GPGHOME = "${WWW_DIR}/.gnupg";
// Répertoire des résultats de tâches cron
$CRON_DIR = "${WWW_DIR}/cron";
// Répertoire principal des logs
$LOGS_DIR = "${WWW_DIR}/logs";
    // Logs du programme
    $MAIN_LOGS_DIR = "${LOGS_DIR}/main";
    // Logs des cron
    $CRON_LOGS_DIR = "${LOGS_DIR}/cron";
    $CRON_LOG = "${CRON_LOGS_DIR}/cronjob-daily.log";
    $CRON_STATS_LOG = "${CRON_LOGS_DIR}/cronjob-stats.log";

// PIDs
$PID_DIR = "${WWW_DIR}/operations/pid";
// Répertoire contenant des fichiers temporaires
$TEMP_DIR = "${WWW_DIR}/.temp";

/**
 *  Création des fichiers et répertoires précédemment définis, si n'existent pas
 */
//if (!file_exists($ENV_CONF)) touch($ENV_CONF);
if (!is_dir($DB_DIR))        mkdir($DB_DIR, 0770, true);
if (!is_dir($GPGHOME))       mkdir($GPGHOME, 0770, true);
if (!is_dir($LOGS_DIR))      mkdir($LOGS_DIR, 0770, true);
if (!is_dir($MAIN_LOGS_DIR)) mkdir($MAIN_LOGS_DIR, 0770, true);
if (!is_dir($CRON_LOGS_DIR)) mkdir($CRON_LOGS_DIR, 0770, true);
if (!is_dir($CRON_DIR))      mkdir($CRON_DIR, 0770, true);
if (!is_dir($PID_DIR))       mkdir($PID_DIR, 0770, true);
if (!is_dir($TEMP_DIR))      mkdir($TEMP_DIR, 0770, true);
if (!file_exists($WWW_CACHE)) {
    // Si /dev/shm/ (répertoire en mémoire) existe, alors on crée un lien symbolique vers ce répertoire, sinon on crée un répertoire 'cache' classique
    if (file_exists("/dev/shm")) { 
        exec("cd $WWW_DIR && ln -sfn /dev/shm cache"); 
    } else { 
        mkdir("${WWW_DIR}/cache", 0770, true); 
    }
}

/**
 *  Récupération du nom et de la version de l'OS, le tout étant retourné sous forme d'array dans $OS_INFO
 */
if (false == function_exists("shell_exec") || false == is_readable("/etc/os-release")) {
    echo "Erreur : impossible de détecter la version du système";
    exit;
}
$os      = shell_exec('cat /etc/os-release');
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
$OS_INFO = array_combine($listIds, $listVal);

// Puis à partir de l'array $OS_INFO on détermine la famille d'os, son nom et sa version
if (!empty($OS_INFO['id_like'])) {
    if(preg_match('(rhel|centos|fedora)', $OS_INFO['id_like']) === 1) { 
        $OS_FAMILY="Redhat";
    }
    if(preg_match('(debian|ubuntu|kubuntu|xubuntu|armbian|mint)', $OS_INFO['id_like']) === 1) { 
        $OS_FAMILY="Debian";
    }
} else if (!empty($OS_INFO['id'])) {
    if(preg_match('(rhel|centos|fedora)', $OS_INFO['id']) === 1) { 
        $OS_FAMILY="Redhat";
    }
    if(preg_match('(debian|ubuntu|kubuntu|xubuntu|armbian|mint)', $OS_INFO['id']) === 1) { 
        $OS_FAMILY="Debian";
    }
}
$OS_NAME = $OS_INFO['name'];
$OS_VERSION = $OS_INFO['version_id'];

// pour Redhat : emplacement de la conf yum
if ($OS_FAMILY == "Redhat") {
    $REPOMANAGER_YUM_DIR = "/etc/yum.repos.d/repomanager";
    $REPOMANAGER_YUM_CONF = "/etc/yum.repos.d/repomanager/repomanager.conf";
    // emplacement des clés gpg importées par repomanager
    $RPM_GPG_DIR = "/etc/pki/rpm-gpg/repomanager";
    $RELEASEVER = $repomanager_conf_array['RELEASEVER'];
    $PASSPHRASE_FILE = "${GPGHOME}/passphrase";
}

// Profils
$MANAGE_PROFILES = $repomanager_conf_array['MANAGE_PROFILES'];
$PROFILES_MAIN_DIR = "${REPOS_DIR}/profiles";
$REPOS_PROFILES_CONF_DIR = "${PROFILES_MAIN_DIR}/_configurations";
$REPOSERVER_PROFILES_CONF_DIR = "${PROFILES_MAIN_DIR}/_reposerver";
$PROFILE_SERVER_CONF = "${REPOSERVER_PROFILES_CONF_DIR}/main.conf";
$REPO_CONF_FILES_PREFIX = $repomanager_conf_array['REPO_CONF_FILES_PREFIX'];

// Config générale pour repomanager
if ($OS_FAMILY == "Redhat") { $PACKAGE_TYPE = 'rpm'; }
if ($OS_FAMILY == "Debian") { $PACKAGE_TYPE = 'deb'; }
$AUTOMATISATION_ENABLED = $repomanager_conf_array['AUTOMATISATION_ENABLED'];
if ($AUTOMATISATION_ENABLED == "yes") {
  $ALLOW_AUTOUPDATE_REPOS = $repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS'];
  $ALLOW_AUTOUPDATE_REPOS_ENV = $repomanager_conf_array['ALLOW_AUTOUPDATE_REPOS_ENV'];
  $ALLOW_AUTODELETE_ARCHIVED_REPOS = $repomanager_conf_array['ALLOW_AUTODELETE_ARCHIVED_REPOS'];
  $RETENTION = $repomanager_conf_array['RETENTION'];
}

/**
 *  Récupération des environnements
 */
require_once("${WWW_DIR}/class/Environnement.php");
$myenv = new Environnement();
$ENVS = $myenv->listAll();
$ENVS_TOTAL = $myenv->total();
$DEFAULT_ENV = $myenv->default();
$LAST_ENV = $myenv->last();
unset($myenv);
if(empty($ENVS)) {
    ++$EMPTY_CONFIGURATION_VARIABLES;
}
/*$ENVS = explode("\n", shell_exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]'"));
$ENVS = array_filter($ENVS); // on supprime les lignes vides du tableau si il y en a
if(empty($ENVS)) {
    ++$EMPTY_CONFIGURATION_VARIABLES;
}
$ENVS_TOTAL = shell_exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]' | wc -l");
$DEFAULT_ENV = exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]' | head -n1");
$LAST_ENV = exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]' | tail -n1");*/

$GPG_SIGN_PACKAGES = $repomanager_conf_array['GPG_SIGN_PACKAGES'];
$GPG_KEYID = $repomanager_conf_array['GPG_KEYID'];
$EMAIL_DEST = $repomanager_conf_array['EMAIL_DEST'];
$UPDATE_AUTO = $repomanager_conf_array['UPDATE_AUTO'];
$UPDATE_BACKUP_ENABLED = $repomanager_conf_array['UPDATE_BACKUP_ENABLED'];
$UPDATE_BRANCH = $repomanager_conf_array['UPDATE_BRANCH'];
$BACKUP_DIR = $repomanager_conf_array['BACKUP_DIR'];
$DEBUG_MODE = $repomanager_conf_array['DEBUG_MODE'];

/**
 *  Création du répertoire de backup si n'existe pas
 */
if (!is_dir($BACKUP_DIR)) {
    if (!mkdir($BACKUP_DIR, 0770, true)) {
        $GENERAL_ERROR_MESSAGES[] = "Impossible de créer le répertoire de sauvegarde : $BACKUP_DIR";
    }
}

/**
 *  Création du répertoire de mise à jour si n'existe pas
 */
if (!is_dir("$WWW_DIR/update")) {
    if (!mkdir("$WWW_DIR/update", 0770, true)) {
        $GENERAL_ERROR_MESSAGES[] = "Impossible de créer le répertoire de mise à jour : $WWW_DIR/update";
    }
}

/**
 *  Config web
 */
$WWW_HOSTNAME = $repomanager_conf_array['WWW_HOSTNAME'];
$WWW_REPOS_DIR_URL = $repomanager_conf_array['WWW_REPOS_DIR_URL'];
$WWW_PROFILES_DIR_URL = "http://${WWW_HOSTNAME}/profiles";
$WWW_USER = $repomanager_conf_array['WWW_USER'];
if ($repomanager_conf_array['CRON_STATS_ENABLED'] == "yes") {
    if (!empty($repomanager_conf_array['WWW_STATS_LOG_PATH'])) {
        $WWW_STATS_LOG_PATH = $repomanager_conf_array['WWW_STATS_LOG_PATH'];
    } else {
        ++$EMPTY_CONFIGURATION_VARIABLES;
    }
}
/**
 *  Config cron
 */
$CRON_DAILY_ENABLED = $repomanager_conf_array['CRON_DAILY_ENABLED'];
$CRON_GENERATE_REPOS_CONF = $repomanager_conf_array['CRON_GENERATE_REPOS_CONF'];
$CRON_APPLY_PERMS = $repomanager_conf_array['CRON_APPLY_PERMS'];
$CRON_SAVE_CONF = $repomanager_conf_array['CRON_SAVE_CONF'];
$CRON_PLAN_REMINDERS_ENABLED = $repomanager_conf_array['CRON_PLAN_REMINDERS_ENABLED'];
$CRON_STATS_ENABLED = $repomanager_conf_array['CRON_STATS_ENABLED'];
// Version actuelle et version disponible sur github
$VERSION = file_get_contents("${WWW_DIR}/version");
$GIT_VERSION = file_get_contents("${WWW_DIR}/cron/github.version");
if (!empty($VERSION) AND !empty($GIT_VERSION) AND $VERSION !== $GIT_VERSION)
    $UPDATE_AVAILABLE = "yes";
else
    $UPDATE_AVAILABLE = "no";

// Vérification si une mise à jour de repomanager est en cours
if (file_exists("${WWW_DIR}/update-running"))
    $UPDATE_RUNNING = "yes";
else
    $UPDATE_RUNNING = "no";

// Autres
if (!empty($_SERVER['HTTP_HOST']) AND !empty($_SERVER['REQUEST_URI'])) $actual_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if (!empty($_SERVER['REQUEST_URI'])) $actual_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if (!empty($_SERVER['SERVER_ADDR'])) $serverIP = $_SERVER['SERVER_ADDR'];

// Date et heure du jour
$DATE_DMY = date("d-m-Y");
$DATE_YMD = date("Y-m-d");
$TIME = date("H-i");

unset($repomanager_conf_array);

/**
 *  Si la mise à jour automatique est activé et qu'une mise à jour est disponible alors on l'installe en arrière-plan.
 *  L'action est effectuée uniquement si une mise à jour n'est pas déjà en cours (présence du fichier update-running)
 *  La mise à jour mettra en place une page de maintenance automatiquement
 */
if ($UPDATE_AUTO == "yes" AND $UPDATE_AVAILABLE == "yes") {
    if (!file_exists("${WWW_DIR}/update-running")) {
        exec('curl '.$_SERVER['HTTP_HOST'].'configuration.php?action=update &');
        sleep(1);
    }
}

/**
 *  Si les stats sont activées mais que le parser de log ne tourne pas, alors on le lance en arrière-plan
 *  Note : cette condition est vérifiée à chaque chargement de load_common_variables.php, et donc à chaque fois que cronjob.php ou que plan.php exec-plans se lance, ce qui largement suffisant.
 */
if ($CRON_STATS_ENABLED == "yes" AND empty(shell_exec("/bin/ps -ax | grep 'stats-log-parser' | grep -v 'grep'"))) {  
    exec("bash ${WWW_DIR}/tools/stats-log-parser '$WWW_STATS_LOG_PATH' >/dev/null 2>/dev/null &");
}

/**
 *  Si la clé de signature GPG n'existe pas alors on l'exporte
 */
if ($GPG_SIGN_PACKAGES == "yes" AND !file_exists("${REPOS_DIR}/gpgkeys/${WWW_HOSTNAME}.pub")) {
    if (!is_dir("${REPOS_DIR}/gpgkeys")) {
        mkdir("${REPOS_DIR}/gpgkeys", 0770, true);
    }
    exec("gpg2 --no-permission-warning --homedir '$GPGHOME' --export -a '$GPG_KEYID' > ${REPOS_DIR}/gpgkeys/${WWW_HOSTNAME}.pub 2>/dev/null");
}
?>