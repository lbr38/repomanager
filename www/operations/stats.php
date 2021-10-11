<?php
/**
 *  Génération des données de statistiques des repos
 *  Les actions sont exécutées par l'utilisateur $WWW_USER
 */

$WWW_DIR = dirname(__FILE__, 2);

/**
 *  Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
 */
require_once("${WWW_DIR}/functions/load_common_variables.php");
require_once("${WWW_DIR}/class/Repo.php");
require_once("${WWW_DIR}/class/Database.php");

$repo = new Repo();

$permissionsError = 0;
$checkVersionError = 0;
$generateConfError = 0;
$backupError = 0;
$return = '';

$STATS_ENABLED = "yes"; // forcé pour le moment
if ($STATS_ENABLED == "yes") {
    /**
     *  On récupère toute la liste des repos actifs
     */
    $reposList = $repo->listAll();

    if (!empty($reposList)) {
        foreach($reposList as $repo) {
            $repoId = $repo['Id'];
            $repoName = $repo['Name'];
            if ($OS_FAMILY == "Debian") {
                $repoDist = $repo['Dist'];
                $repoSection = $repo['Section'];
            }
            $repoEnv = $repo['Env'];

            /**
             *  Ouverture de la BDD
             */
            $stats_db = new Database_stats();

            if ($OS_FAMILY == "Redhat") {
                if (file_exists("${REPOS_DIR}/${repoName}_${repoEnv}")) {
                    /**
                     *  Calcul de la taille du repo
                     */
                    $repoSize = exec("du -s ${REPOS_DIR}/${repoName}_${repoEnv}/ | awk '{print $1}'");

                    /**
                     *  Calcul du nombre de paquets présents dans le repo
                     */
                    $packagesCount = exec("find ${REPOS_DIR}/${repoName}_${repoEnv}/ -type f -name '*.rpm' | wc -l");
                }
            }
            if ($OS_FAMILY == "Debian") {
                if (file_exists("${REPOS_DIR}/${repoName}/${repoDist}/${repoSection}_${repoEnv}")) {
                    /**
                     *  Calcul de la taille de la section
                     */
                    $repoSize = exec("du -s ${REPOS_DIR}/${repoName}/${repoDist}/${repoSection}_${repoEnv}/ | awk '{print $1}'");

                    /**
                     *  Calcul du nombre de paquets présents dans la section
                     */
                    $packagesCount = exec("find ${REPOS_DIR}/${repoName}/${repoDist}/${repoSection}_${repoEnv}/ -type f -name '*.deb' | wc -l");
                }
            }

            /**
             *  Ajout de la taille dans la table size
             */
            if (!empty($repoSize)) {
                $stmt = $stats_db->prepare("INSERT INTO stats (Date, Time, Id_repo, Size, Packages_count) VALUES (:date, :time, :id_repo, :size, :packages_count)");
                $stmt->bindValue(':date', date('Y-m-d'));
                $stmt->bindValue(':time', date('H:i:s'));
                $stmt->bindValue(':id_repo', $repoId);
                $stmt->bindValue(':size', $repoSize);
                $stmt->bindValue(':packages_count', $packagesCount);
                $stmt->execute();
            }
        }
    }
}


// Vérification des erreurs et ajout dans le fichier de log si c'est le cas
// Si une erreur a eu lieu sur l'une des opérations alors on affiche un status KO
if ($checkVersionError != 0 OR $generateConfError != 0 OR $permissionsError != 0 OR $backupError != 0)
	file_put_contents($CRON_LOG, 'Status="KO"'.PHP_EOL);
else // Si aucune erreur n'a eu lieu, on affiche un status OK
	file_put_contents($CRON_LOG, 'Status="OK"'.PHP_EOL);

if ($backupError != 0) file_put_contents($CRON_LOG, "Problème lors de la sauvegarde des fichiers de configuration/db", FILE_APPEND);
if ($checkVersionError != 0) file_put_contents($CRON_LOG, "Problème lors de la vérification d'une nouvelle version", FILE_APPEND);
if ($generateConfError != 0) file_put_contents($CRON_LOG, "Problème lors de regénération des fichiers de conf repo des profils", FILE_APPEND);
if ($permissionsError != 0) file_put_contents($CRON_LOG, "Problème lors de l'application des permissions", FILE_APPEND);
?>