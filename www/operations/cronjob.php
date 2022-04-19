<?php
/**
 *  Actions regulières exécutées par cron
 *  Les actions sont exécutées par l'utilisateur WWW_USER
 */

define("ROOT", dirname(__FILE__, 2));

/**
 *  Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
 */
require_once(ROOT.'/models/Autoloader.php');
Autoloader::loadFromApi();

/**
 *  Si il y a eu un pb lors du chargement des constantes alors on quitte
 */
if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
    file_put_contents(CRON_LOG, 'Erreur lors du chargement des constantes');
    exit();
}

$permissionsError = 0;
$checkVersionError = 0;
$generateConfError = 0;
$backupError = 0;
$return = '';

/**
 *  Création du répertoire temporaire de cronjob_daily si n'existe pas
 */
if (!file_exists(TEMP_DIR."/cronjob_daily")) mkdir(TEMP_DIR."/cronjob_daily", 0770, true);

/**
 *  Création du répertoire de résultat des tâches cron si n'existe pas
 */
if (!file_exists(CRON_DIR)) mkdir(CRON_DIR, 0770, true);

/**
 *  Création du répertoire de logs si n'existe pas
 */
if (!file_exists(CRON_LOGS_DIR)) mkdir(CRON_LOGS_DIR, 0770, true);

/**
 *  Vidage du fichier de log
 */
exec("echo -n> ".CRON_LOG);


// VERSION DISPONIBLE SUR GITHUB //

/**
 *  Vérification d'une nouvelle version disponible sur github
 *  Récupère le numéro de version qui est publié sur github dans le fichier 'version'
 */
$githubAvailableVersion = exec("curl -s -H 'Cache-Control: no-cache' 'https://raw.githubusercontent.com/lbr38/repomanager/".UPDATE_BRANCH."/version'");

if (empty($githubAvailableVersion))
    ++$checkVersionError;
else
    file_put_contents(CRON_DIR."/github.version", $githubAvailableVersion.PHP_EOL);


// SAUVEGARDE DE LA BASE DE DONNEES ET DES FICHIERS DE CONFIGURATIONS //

if (CRON_SAVE_CONF == "yes") {
    if (is_dir(BACKUP_DIR)) {
        /**
         *  Sauvegarde de la base de données
         */
        if (!is_dir(BACKUP_DIR."/db")) {
            if (!mkdir(BACKUP_DIR."/db", 0770, true)) {
                ++$backupError;
            }
        }
        if (is_dir(BACKUP_DIR."/db")) {
            copy(ROOT."/db/repomanager.db", BACKUP_DIR."/db/".DATE_YMD."_".TIME."_repomanager.db");
            copy(ROOT."/db/repomanager-stats.db", BACKUP_DIR."/db/".DATE_YMD."_".TIME."_repomanager-stats.db");
        }

        /**
         *  Création du répertoire de sauvegarde
         */
        if (!is_dir(BACKUP_DIR."/configurations")) {
            if (!mkdir(BACKUP_DIR."/configurations", 0770, true)) {
                ++$backupError;
            }
        }
        /**
         *  Sauvegarde des fichiers de configuration
         */
        if (is_dir(BACKUP_DIR."/configurations")) {
            copy(ROOT."/configurations/repomanager.conf", BACKUP_DIR."/configurations/".DATE_YMD."_".TIME."_repomanager.conf");
        }
    }
}

// GENERATION DES FICHIERS DE CONF DE PROFILS //

/**
 *  Regénération de tous les fichiers de conf repo (.list ou .repo) utilisés par les profils, au cas où certains seraient manquants
 */
if (MANAGE_PROFILES == "yes" and CRON_GENERATE_REPOS_CONF == "yes") {

    $myrepo = new Repo();

    /**
     *  Création du répertoire des configurations de profils si n'existe pas
     */
    if (!file_exists(REPOS_PROFILES_CONF_DIR))     mkdir(REPOS_PROFILES_CONF_DIR, 0770, true);
    if (!is_dir(TEMP_DIR."/cronjob_daily/files"))  mkdir(TEMP_DIR."/cronjob_daily/files", 0770, true);

    /**
     *  On récupère toute la liste des repos actifs
     */
    $reposList = $myrepo->listAll();

    if (!empty($reposList)) {
        foreach ($reposList as $repo) {
            $repoName = $repo['Name'];
            if (OS_FAMILY == "Debian") {
                $repoDist = $repo['Dist'];
                $repoSection = $repo['Section'];
            }

            if (MANAGE_PROFILES == "yes" and CRON_GENERATE_REPOS_CONF == "yes") {
                /**
                 *  On génère les fichiers à l'aide de la fonction generateConf et on les place dans un répertoire temporaire
                 */
                $myrepo->setName($repoName);
                if (OS_FAMILY == "Debian") {
                    $myrepo->setDist($repoDist);
                    $myrepo->setSection($repoSection);
                }

                if ($myrepo->generateConf(TEMP_DIR."/cronjob_daily/files") === false) {
                    ++$generateConfError;
                }

                /**
                 *  Enfin on copie les fichiers générés dans le répertoire temporaire dans le répertoire habituel des fichiers de conf, en copiant uniquement les différences et en supprimant les fichiers inutilisés
                 *  On exclu main.conf afin qu'il ne soit pas supprimé
                 */
                exec("rsync -a --delete-after --exclude 'main.conf' ".TEMP_DIR."/cronjob_daily/files/ ".REPOS_PROFILES_CONF_DIR."/", $output, $return);
                if ($return != 0) ++$generateConfError;
            }
        }
    }
}

/**
 *  Suppression du répertoire temporaire
 */
if (is_dir(TEMP_DIR."/cronjob_daily/files")) {
    exec("rm -rf ".TEMP_DIR."/cronjob_daily/files", $output, $return);
    if ($return != 0) ++$generateConfError;
}


// SUPPRESSION DES FICHIERS TEMPORAIRES //

/**
 *  Supprime les fichiers temporaires dans .temp + vieux de 2 jours
 */
if (is_dir(TEMP_DIR)) exec("find ".TEMP_DIR."/ -mtime +2 -exec rm -rv {} \;");


// APPLICATION DES PERMISSIONS //

/**
 *  Réapplique les bons droits sur le répertoire parent des repos
 *  Laisser cette tâche en dernier car c'est la plus longue
 */

// NOTE : trouver comment gérer le retour d'erreur sur cette commande find (peut être voir du côté de xargs plutôt que exec)
if (CRON_APPLY_PERMS == "yes") {
    exec("find ".REPOS_DIR." -type d -print0 | xargs -r0 chmod 0770", $output, $return);
    if ($return != 0) ++$permissionsError;

    exec("find ".REPOS_DIR." -type f -print0 | xargs -r0 chmod 0660", $output, $return);
    if ($return != 0) ++$permissionsError;

    exec("chown -R ".WWW_USER.":repomanager ".REPOS_DIR, $output, $return);
    if ($return != 0) ++$permissionsError;
}

// Vérification des erreurs et ajout dans le fichier de log si c'est le cas
// Si une erreur a eu lieu sur l'une des opérations alors on affiche un status KO
if ($checkVersionError != 0 or $generateConfError != 0 or $permissionsError != 0 or $backupError != 0)
	file_put_contents(CRON_LOG, 'Status="KO"'.PHP_EOL);
else // Si aucune erreur n'a eu lieu, on affiche un status OK
	file_put_contents(CRON_LOG, 'Status="OK"'.PHP_EOL);

if ($backupError != 0) file_put_contents(CRON_LOG, "Problème lors de la sauvegarde des fichiers de configuration/db", FILE_APPEND);
if ($checkVersionError != 0) file_put_contents(CRON_LOG, "Problème lors de la vérification d'une nouvelle version", FILE_APPEND);
if ($generateConfError != 0) file_put_contents(CRON_LOG, "Problème lors de regénération des fichiers de conf repo des profils", FILE_APPEND);
if ($permissionsError != 0) file_put_contents(CRON_LOG, "Problème lors de l'application des permissions", FILE_APPEND);
?>