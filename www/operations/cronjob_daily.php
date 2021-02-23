<?php
// Actions regulières exécutées par cron ($WWW_USER)

$WWW_DIR = dirname(__FILE__, 2);

// Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
require "${WWW_DIR}/functions/load_common_variables.php";
require "${WWW_DIR}/functions/common-functions.php";
require "${WWW_DIR}/functions/generateConf.php";

$permissionsError = 0;
$checkVersionError = 0;
$generateConfError = 0;
$return = '';

// Création du répertoire temporaire de cronjob_daily si n'existe pas
if (!file_exists("${TEMP_DIR}/cronjob_daily")) { mkdir("${TEMP_DIR}/cronjob_daily", 0770, true); }

// Création du répertoire de résultat des tâches cron si n'existe pas
if (!file_exists($CRON_DIR)) { mkdir($CRON_DIR, 0770, true); }

// Création du répertoire de logs si n'existe pas
if (!file_exists($CRON_LOGS_DIR)) { mkdir($CRON_LOGS_DIR, 0770, true); }

// Vidage du fichier de log
exec("echo -n> $CRON_LOG");


// VERSION DISPONIBLE SUR GITHUB //

// Vérification d'une nouvelle version disponible sur github
// Récupère le numéro de version qui est publié sur github dans le fichier 'version'
$githubAvailableVersion = exec("curl -s -H 'Cache-Control: no-cache' 'https://raw.githubusercontent.com/lbr38/repomanager/${UPDATE_BRANCH}/version' | grep 'VERSION=' | cut -d'=' -f2 | sed 's/\"//g'");

if (empty($githubAvailableVersion)) {
  ++$checkVersionError;
} else {
  file_put_contents("$CRON_DIR/github.version", "# Version disponible sur github\nGITHUB_VERSION=\"$githubAvailableVersion\"");
}


// GENERATION DES FICHIERS DE CONF DE PROFILS //

// Regénération de tous les fichiers de conf repo (.list ou .repo) utilisés par les profils, au cas où certains seraient manquants
if ($MANAGE_PROFILES == "yes" AND $CRON_GENERATE_REPOS_CONF == "yes") {
  // Création du répertoire des configurations de profils si n'existe pas
  if (!file_exists($REPOS_PROFILES_CONF_DIR)) { mkdir($REPOS_PROFILES_CONF_DIR, 0770, true); }
  if (!is_dir("${TEMP_DIR}/cronjob_daily/files")) { mkdir("${TEMP_DIR}/cronjob_daily/files", 0770, true); }

  // On récupère toute la liste des repos actifs pour regénérer leur fichier de conf
  $rows = explode("\n", file_get_contents($REPOS_LIST));
  foreach($rows as $row) {
    if(!empty($row) AND $row !== "[REPOS]") { // on ne traite pas les lignes vides ni la ligne [REPOS] (1ère ligne du fichier)
      $rowData = explode(',', $row);
      if ($OS_FAMILY == "Redhat") {
        $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']);
      }
      if ($OS_FAMILY == "Debian") {
        $repoName = strtr($rowData['0'], ['Name=' => '', '"' => '']);
        $repoDist = strtr($rowData['2'], ['Dist=' => '', '"' => '']);
        $repoSection = strtr($rowData['3'], ['Section=' => '', '"' => '']);
      }

      // On génère les fichiers à l'aide de la fonction generateConf et on les place dans un répertoire temporaire
			if ($OS_FAMILY == "Redhat") {
        if (generateConf_rpm($repoName, "${TEMP_DIR}/cronjob_daily/files") === false) {
					++$generateConfError;
				}
			}
			if ($OS_FAMILY == "Debian") {
				if (generateConf_deb($repoName, $repoDist, $repoSection, "${TEMP_DIR}/cronjob_daily/files") === false) {
					++$generateConfError;
				}
			}

      // Enfin on copie les fichiers générés dans le répertoire temporaire dans le répertoire habituel des fichiers de conf, en copiant uniquement les différences et en supprimant les fichiers inutilisés
      exec("rsync -a --delete-after ${TEMP_DIR}/cronjob_daily/files/ ${REPOS_PROFILES_CONF_DIR}/", $output, $return);
      if ($return != 0) {	++$generateConfError; }
		}
	}
  // Suppression du répertoire temporaire
  if (is_dir("${TEMP_DIR}/cronjob_daily/files")) {
    exec("rm -rf ${TEMP_DIR}/cronjob_daily/files", $output, $return);
    if ($return != 0) {	++$generateConfError; }
  }
}


// APPLICATION DES PERMISSIONS //

// Réapplique les bons droits sur le répertoire parent des repos
// Laisser cette tâche en dernier car c'est la plus longue

// NOTE : trouver comment gérer le retour d'erreur sur cette commande find (peut être voir du côté de xargs plutôt que exec)
if ($CRON_APPLY_PERMS == "yes") {
  exec("find $REPOS_DIR -type d -print0 | xargs -r0 chmod 0770", $output, $return);
  if ($return != 0) {	++$permissionsError; }

  exec("find $REPOS_DIR -type f -print0 | xargs -r0 chmod 0660", $output, $return);
  if ($return != 0) {	++$permissionsError; }

  exec("chown -R ${WWW_USER}:repomanager $REPOS_DIR", $output, $return);
  if ($return != 0) {	++$permissionsError; }
}

// Vérification des erreurs et ajout dans le fichier de log si c'est le cas
// Si une erreur a eu lieu sur l'une des opérations alors on affiche un status KO
if ($checkVersionError != 0 OR $generateConfError != 0 OR $permissionsError != 0) {
	file_put_contents($CRON_LOG, 'Status="KO"'.PHP_EOL);
} else { // Si aucune erreur n'a eu lieu, on affiche un status OK
	file_put_contents($CRON_LOG, 'Status="OK"'.PHP_EOL);
}

if ($checkVersionError != 0) {
	file_put_contents($CRON_LOG, "Problème lors de la vérification d'une nouvelle version", FILE_APPEND);
}

if ($generateConfError != 0) {
	file_put_contents($CRON_LOG, "Problème lors de regénération des fichiers de conf repo des profils", FILE_APPEND);
}

if ($permissionsError != 0) {
	file_put_contents($CRON_LOG, "Problème lors de l'application des permissions", FILE_APPEND);
}
?>