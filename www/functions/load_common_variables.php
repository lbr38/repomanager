<?php
// Chargement des variables //

date_default_timezone_set('Europe/Paris');

$WWW_DIR = dirname(__FILE__, 2);

// Si le fichier repomanager.conf n'existe pas, on redirige vers la page d'install
if (!file_exists("${WWW_DIR}/configurations/repomanager.conf")) {
    header("Location: installation.php");
}

// Récupération de tous les paramètres définis dans le fichier repomanager.conf
$repomanager_conf_array = parse_ini_file("${WWW_DIR}/configurations/repomanager.conf");

// Si certains paramètres sont vides alors on incrémente EMPTY_CONFIGURATION_VARIABLES qui fera afficher un bandeau d'alerte
$EMPTY_CONFIGURATION_VARIABLES = 0;
foreach($repomanager_conf_array as $key => $value) {
    if(empty($value)) {
        ++$EMPTY_CONFIGURATION_VARIABLES;
    }
}

$BASE_DIR = $WWW_DIR;
$REPOS_DIR = $repomanager_conf_array['REPOS_DIR'];

// Emplacements des fichiers de conf
$REPOMANAGER_CONF = "${WWW_DIR}/configurations/repomanager.conf";
$DISPLAY_CONF = "${WWW_DIR}/configurations/display.ini";
$ENV_CONF = "${WWW_DIR}/configurations/envs.conf";

// Emplacement de la DB :
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

// PIDs
$PID_DIR = "${WWW_DIR}/operations/pid";

// Répertoire contenant des fichiers temporaires
$TEMP_DIR = "${WWW_DIR}/.temp";

// Création des fichiers et répertoires précédemment définis, si n'existent pas
if (!file_exists($ENV_CONF)) { touch($ENV_CONF); }
if (!is_dir($DB_DIR)) { mkdir($DB_DIR, 0770, true); }
if (!is_dir($GPGHOME)) { mkdir($GPGHOME, 0770, true); }
if (!is_dir($LOGS_DIR)) { mkdir($LOGS_DIR, 0770, true); }
if (!is_dir($MAIN_LOGS_DIR)) { mkdir($MAIN_LOGS_DIR, 0770, true); }
if (!is_dir($CRON_LOGS_DIR)) { mkdir($CRON_LOGS_DIR, 0770, true); }
if (!is_dir($CRON_DIR)) { mkdir($CRON_DIR, 0770, true); }
if (!is_dir($PID_DIR)) { mkdir($PID_DIR, 0770, true); }
if (!is_dir($TEMP_DIR)) { mkdir($TEMP_DIR, 0770, true); }
if (!file_exists($WWW_CACHE)) {
    // Si /dev/shm/ (répertoire en mémoire) existe, alors on crée un lien symbolique vers ce répertoire, sinon on crée un répertoire 'cache' classique
    if (file_exists("/dev/shm")) { 
        exec("cd $WWW_DIR && ln -sfn /dev/shm cache"); 
    } else { 
        mkdir("${WWW_DIR}/cache", 0770, true); 
    }
}

// Récupération du nom et de la version de l'OS, le tout étant retourné sous forme d'array dans $OS_INFO
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
if ($OS_FAMILY === "Redhat") {
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
$ENVS = explode("\n", shell_exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]'"));
$ENVS = array_filter($ENVS); // on supprime les lignes vides du tableau si il y en a
if(empty($ENVS)) {
    ++$EMPTY_CONFIGURATION_VARIABLES;
}
$ENVS_TOTAL = shell_exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]' | wc -l");
$DEFAULT_ENV = exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]' | head -n1");
$LAST_ENV = exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]' | tail -n1");
$GPG_SIGN_PACKAGES = $repomanager_conf_array['GPG_SIGN_PACKAGES'];
$GPG_KEYID = $repomanager_conf_array['GPG_KEYID'];
$EMAIL_DEST = $repomanager_conf_array['EMAIL_DEST'];
$UPDATE_AUTO = $repomanager_conf_array['UPDATE_AUTO'];
$UPDATE_BACKUP_ENABLED = $repomanager_conf_array['UPDATE_BACKUP_ENABLED'];
$UPDATE_BACKUP_DIR = $repomanager_conf_array['UPDATE_BACKUP_DIR'];
$UPDATE_BRANCH = $repomanager_conf_array['UPDATE_BRANCH'];
$DEBUG_MODE = $repomanager_conf_array['DEBUG_MODE'];

// Config web :
$WWW_HOSTNAME = $repomanager_conf_array['WWW_HOSTNAME'];
$WWW_REPOS_DIR_URL = $repomanager_conf_array['WWW_REPOS_DIR_URL'];
$WWW_PROFILES_DIR_URL = "$WWW_REPOS_DIR_URL/profiles";
$WWW_USER = $repomanager_conf_array['WWW_USER'];

// Config cron
$CRON_DAILY_ENABLED = $repomanager_conf_array['CRON_DAILY_ENABLED'];
$CRON_GENERATE_REPOS_CONF = $repomanager_conf_array['CRON_GENERATE_REPOS_CONF'];
$CRON_APPLY_PERMS = $repomanager_conf_array['CRON_APPLY_PERMS'];
$CRON_PLAN_REMINDERS_ENABLED = $repomanager_conf_array['CRON_PLAN_REMINDERS_ENABLED'];

// Version actuelle et version disponible sur github
$VERSION = file_get_contents("${WWW_DIR}/version");
$GIT_VERSION = file_get_contents("${WWW_DIR}/cron/github.version");
if (!empty($VERSION) AND !empty($GIT_VERSION) AND $VERSION !== $GIT_VERSION) {
    $UPDATE_AVAILABLE = "yes";
} else {
    $UPDATE_AVAILABLE = "no";
}

// Autres :
if (!empty($_SERVER['HTTP_HOST']) AND !empty($_SERVER['REQUEST_URI'])) {
    $actual_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}
if (!empty($_SERVER['REQUEST_URI'])) {
    $actual_uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
}
if (!empty($_SERVER['SERVER_ADDR'])) {
    $serverIP = $_SERVER['SERVER_ADDR'];
}

// Date du jour
$DATE_JMA = exec("date +%d-%m-%Y");
$DATE_AMJ = exec("date +%Y-%m-%d");
$HEURE = exec("date +%H-%M");

unset($repomanager_conf_array);
?>