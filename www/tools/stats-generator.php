<?php

/**
 *  Génération des données de statistiques des repos
 *  Les actions sont exécutées par cron avec l'utilisateur WWW_USER
 */

define('ROOT', dirname(__FILE__, 2));
require_once(ROOT . '/controllers/Autoloader.php');
\Controllers\Autoloader::loadFromApi();

$myrepo = new \Controllers\Repo();

if (STATS_ENABLED == "yes") {
    /**
     *  On récupère toute la liste des repos actifs ayant au moins 1 environnement actif
     */
    $reposList = $myrepo->list();

    if (!empty($reposList)) {
        foreach ($reposList as $repo) {
            /**
             *  Si le snapshot de repo n'a aucun env rattaché alors on passe au suivant, car les
             *  statistiques ne concernent que les environnements de snapshots
             */
            if (empty($repo['envId'])) {
                continue;
            }

            $repoId = $repo['repoId'];
            $snapId = $repo['snapId'];
            $envId = $repo['envId'];
            $repoName = $repo['Name'];
            $repoDist = $repo['Dist'];
            $repoSection = $repo['Section'];
            $repoEnv = $repo['Env'];
            $repoPackage_type = $repo['Package_type'];

            if ($repoPackage_type == 'rpm') {
                if (file_exists(REPOS_DIR . '/' . $repoName . '_' . $repoEnv)) {
                    /**
                     *  Calcul de la taille du repo
                     */
                    $repoSize = exec('du -s ' . REPOS_DIR . '/' . $repoName . '_' . $repoEnv . "/ | awk '{print $1}'");

                    /**
                     *  Calcul du nombre de paquets présents dans le repo
                     */
                    $packagesCount = exec('find ' . REPOS_DIR . '/' . $repoName . '_' . $repoEnv . '/ -type f -name "*.rpm" | wc -l');
                }
            }

            if ($repoPackage_type == 'deb') {
                if (file_exists(REPOS_DIR . '/' . $repoName . '/' . $repoDist . '/' . $repoSection . '_' . $repoEnv)) {
                    /**
                     *  Calcul de la taille de la section
                     */
                    $repoSize = exec('du -s ' . REPOS_DIR . '/' . $repoName . '/' . $repoDist . '/' . $repoSection . '_' . $repoEnv . "/ | awk '{print $1}'");

                    /**
                     *  Calcul du nombre de paquets présents dans la section
                     */
                    $packagesCount = exec('find ' . REPOS_DIR . '/' . $repoName . '/' . $repoDist . '/' . $repoSection . '_' . $repoEnv . '/ -type f -name "*.deb" | wc -l');
                }
            }

            /**
             *  Ajout de la taille dans la table size
             */
            if (!empty($repoSize)) {
                /**
                 *  Ouverture de la BDD
                 */
                $mystats = new \Controllers\Stat();

                /**
                 *  Ajout des nouvelles données récupérées en base de données
                 */
                $mystats->add(date('Y-m-d'), date('H:i:s'), $repoSize, $packagesCount, $envId);

                /**
                 *  Fermeture de la BDD
                 */
                $mystats->closeConnection();
            }
        }
    }
}
