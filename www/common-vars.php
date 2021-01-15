<?php

// Variables communes à toutes les pages

// Emplacements des répertoires de base
$ETC_DIR = "/etc/repomanager";
$BASE_DIR = exec("grep '^BASE_DIR=' ${ETC_DIR}/vars/customs.vars | cut -d'=' -f2 | sed 's/\"//g'");
$REPOS_DIR = exec("grep '^REPOS_DIR=' ${ETC_DIR}/vars/customs.vars | cut -d'=' -f2 | sed 's/\"//g'");
$WWW_DIR = exec("grep '^WWW_DIR=' ${ETC_DIR}/vars/customs.vars | cut -d'=' -f2 | sed 's/\"//g'");

// Emplacements des fichiers de conf
$REPOMANAGER = "${BASE_DIR}/repomanager";
$CONF_FILE = "${ETC_DIR}/repomanager.conf";
$PLAN_CONF_FILE = "${ETC_DIR}/planifications.conf";
$ENV_CONF_FILE = "${ETC_DIR}/envs.conf";
$REPO_GROUPS_FILE = "${ETC_DIR}/groups.conf";
$REPO_ORIGIN_FILE = "${ETC_DIR}/hosts.conf";
$REPO_FILE = "${BASE_DIR}/repos.list";
$REPO_ARCHIVE_FILE = "${BASE_DIR}/repos-archive.list";
$VERSION = exec("grep '^VERSION=' ${BASE_DIR}/version | cut -d'=' -f2 | sed 's/\"//g'");
$LOGS_DIR = "${BASE_DIR}/logs";
$GPGHOME = "${BASE_DIR}/.gnupg";

// pour Redhat : emplacement de la conf yum
$REPOMANAGER_YUM_DIR = "/etc/yum.repos.d/00_repomanager";
$REPOMANAGER_YUM_CONF = "/etc/yum.repos.d/00_repomanager/repomanager.conf";
// emplacement des clés gpg importées par repomanager
$RPM_GPG_DIR = "/etc/pki/rpm-gpg/repomanager";

// profils
$PROFILS_MAIN_DIR = "${REPOS_DIR}/profils";
$REPOS_CONF_FILES_DIR = "${PROFILS_MAIN_DIR}/00_repo-conf-files";

// Config générale pour repomanager
$OS_TYPE = exec("grep '^TYPE=' $CONF_FILE | cut -d'=' -f2 | sed 's/\"//g'");
$MANAGE_PROFILES = exec("grep '^MANAGE_PROFILES=' $CONF_FILE | cut -d'=' -f2 | sed 's/\"//g'");
$AUTOMATISATION_ENABLED = exec("grep '^AUTOMATISATION_ENABLED=' $CONF_FILE | cut -d'=' -f2 | sed 's/\"//g'");
$REPO_FILES_PREFIX = exec("grep '^REPO_FILES_PREFIX=' $CONF_FILE | cut -d'=' -f2 | sed 's/\"//g'");
$OPERATION_STATUS = exec("ps aux | grep '${BASE_DIR}/repomanager ' | grep -v 'grep' | grep -v 'cron' | grep -v 'curl'"); # On affiche les processus repomanager en excluant les process de cron.daily (curl vers github pour récupérer la version)
$REPO_ENVS = shell_exec("cat $ENV_CONF_FILE | grep -v '[ENVIRONNEMENTS]'"); // récupération de tous les env dans un tableau
$REPO_ENVS = explode("\n", $REPO_ENVS);
$REPO_ENVS = array_filter($REPO_ENVS); // on supprime les lignes vides du tableau si il y en a
$REPO_DEFAULT_ENV = exec("cat $ENV_CONF_FILE | grep -v '[ENVIRONNEMENTS]' | head -n1");
$REPO_LAST_ENV = exec("cat $ENV_CONF_FILE | grep -v '[ENVIRONNEMENTS]' | tail -n1");
$GPG_SIGN_PACKAGES = exec("grep '^GPG_SIGN_PACKAGES=' $CONF_FILE | cut -d'=' -f2 | sed 's/\"//g'");

// Config web :
$WWW_HOSTNAME = exec("grep '^WWW_HOSTNAME=' $CONF_FILE | cut -d'=' -f2 | sed 's/\"//g'");
$WWW_USER = exec("grep '^WWW_USER=' $CONF_FILE | cut -d'=' -f2 | sed 's/\"//g'");

// Autres :
$uri = $_SERVER['REQUEST_URI'];
?>