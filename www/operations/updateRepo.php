<?php
/**
 * Import des variables nécessaires
 */

$WWW_DIR = dirname(__FILE__, 2);
require_once("${WWW_DIR}/functions/load_common_variables.php");
require_once("${WWW_DIR}/functions/common-functions.php");
 
// Cas où ce script a été appelé avec des arguments, on récupère ces arguments et on exécute directement la fonction de génération de conf
if (!empty($argv)) {
  if ($OS_FAMILY == "Redhat") {
	if (!empty($argv[1])) { $repoName = $argv[1]; } else { throw new Exception("Erreur : nom du repo non défini"); }
	if (!empty($argv[2])) { $repoSource = $argv[2]; } else { throw new Exception("Erreur : vrai nom du repo non défini"); }
	if (!empty($argv[3])) { $repoGpgCheck = $argv[3]; } else { throw new Exception("Erreur : gpg check non défini"); }
	if (!empty($argv[4])) { $repoGpgResign = $argv[4]; } else { throw new Exception("Erreur : gpg resign non défini"); }
  }
 
  // Debian : on attends 2 autres arguments (dist et section)
  if ($OS_FAMILY == "Debian") {
	if (!empty($argv[1])) { $repoName = $argv[1]; } else { throw new Exception("Erreur : nom du repo non défini"); }
	if (!empty($argv[2])) { $repoDist = $argv[2]; } else { throw new Exception("Erreur : nom de la distribution non défini"); }
	if (!empty($argv[3])) { $repoSection = $argv[3]; } else { throw new Exception("Erreur : nom de la section non défini"); }
	if (!empty($argv[4])) { $repoSource = $argv[4]; } else { throw new Exception("Erreur : hostname non défini"); }
	if (!empty($argv[5])) { $repoGpgCheck = $argv[5]; } else { throw new Exception("Erreur : gpg check non défini"); }
	if (!empty($argv[6])) { $repoGpgResign = $argv[6]; } else { throw new Exception("Erreur : gpg resign non défini"); }
  }
}

//// TRAITEMENT ////

require_once("${WWW_DIR}/class/Repo.php");

if ($OS_FAMILY == "Redhat") {
	$repo = new Repo(compact('repoName', 'repoSource', 'repoGpgCheck', 'repoGpgResign'));
}
if ($OS_FAMILY == "Debian") {
	$repo = new Repo(compact('repoName', 'repoDist', 'repoSection', 'repoSource', 'repoGpgCheck', 'repoGpgResign'));
}

$repo->update();

exit(0);
?>