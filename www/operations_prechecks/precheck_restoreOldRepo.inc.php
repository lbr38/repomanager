<?php
function precheck_restoreOldRepo() {
    require 'functions/load_common_variables.php';
    
    // 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en GET :
    $actionId        = checkArguments('required', 'actionId');
    $repoName        = checkArguments('required', 'repoName');
    if ($OS_FAMILY == "Debian") {
        $repoDist    = checkArguments('required', 'repoDist');
        $repoSection = checkArguments('required', 'repoSection');
    }
    $repoDate        = checkArguments('required', 'repoDate');
    $repoEnv         = checkArguments('required', 'repoEnv');
    $repoDescription = checkArguments('optionnal', 'repoDescription');

    // 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoDate) AND !empty($repoEnv) AND !empty($repoDescription)) {
            // Ok on a toutes les infos mais il faut vérifier que le repo archivé mentionné existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Date=\"${repoDate}\"' $REPOS_ARCHIVE_LIST");
            if (empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucun repo archivé ${repoName} en date du ${repoDate}</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // On vérifie si un repo du même nom existe sur l'env $repoEnv, si c'est le cas et que son miroir n'est pas utilisé par d'autres environnements, il sera archivé
            $repoArchive = 'no'; // on déclare une variable à 'no' par défaut
            $repoToBeArchived = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST");
            // Si le résultat précedent n'est pas vide, alors il y a un miroir qui sera potentiellement archivé. 
            // On récupère sa date et on regarde si cette date n'est pas utilisée par un autre env.
            if (!empty($repoToBeArchived)) {
                $repoToBeArchivedDate = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $4}' | cut -d'=' -f2 | sed 's/\"//g'");
                $othersReposToBeArchived = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\".*\",Date=\"${repoToBeArchivedDate}\"' $REPOS_LIST | grep -v '${repoEnv}'"); // on exclu $repoEnv de la recherche, car on cherche les autres envs impactés
                // Si d'autres env utilisent le miroir en date du '$repoToBeArchivedDate' alors on ne peut pas archiver. Sinon on archive.
                if (empty($othersReposToBeArchived)) {
                    $repoArchive = 'yes';
                }
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo '<td colspan="100%">L\'opération va restaurer le repo archivé suivant :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Date du repo :</td><td><b>${repoDate}</b></td></tr>";
                echo "<tr><td colspan=\"100%\"><br>La restauration placera le repo sur l'environnement <b>${repoEnv}</b>.</td></tr>";
                if ($repoArchive == "yes") {
                    echo "<tr><td colspan=\"100%\"><br>Le repo actuellement en <b>${repoEnv}</b> en date du <b>${repoToBeArchivedDate}</b> sera archivé.</td></tr>";
                }
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repoName</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Date du repo :</td><td><b>${repoDate}</b></td></tr>";
                require('operations/restoreOldRepo_rpm.php');
                list($PID, $LOGNAME) = createLog();
                restoreOldRepo_rpm($repoName, $repoDate, $repoEnv, $repoDescription);
                closeOperation($PID);
            }
    
        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo '<tr>';
            echo '<td colspan="100%"><button type="submit" class="button-submit-large-red">Valider</button></td>';
            echo '</tr>';
        }
    }

    // Cas Debian :
    if ($OS_FAMILY == "Debian") {    
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoDate) AND !empty($repoEnv) AND !empty($repoDescription)) {
            // Ok on a toutes les infos mais il faut vérifier que la section archivée mentionnée existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPOS_ARCHIVE_LIST");
            if (empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucune section archivée ${repoSection} du repo ${repoName} (distribution ${repoDist})</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // On vérifie si une section du même nom existe sur l'env $repoEnv, si c'est le cas et que son miroir n'est pas utilisé par d'autres environnements, il sera archivé
            $repoArchive = 'no'; // on déclare une variable à 'no' par défaut
            $repoToBeArchived = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' $REPOS_LIST");
            // Si le résultat précedent n'est pas vide, alors il y a un miroir qui sera potentiellement archivé. 
            // On récupère sa date et on regarde si cette date n'est pas utilisée par un autre env.
            if (!empty($repoToBeArchived)) {
                $repoToBeArchivedDate = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' $REPOS_LIST | awk -F ',' '{print $6}' | cut -d'=' -f2 | sed 's/\"//g'");
                $othersReposToBeArchived = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\".*\",Date=\"${repoToBeArchivedDate}\"' $REPOS_LIST | grep -v '${repoEnv}'"); // on exclu l'env $repoEnv de la recherche, car on cherche les autres envs impactés
                // Si d'autres env utilisent le miroir en date du '$repoToBeArchivedDate' alors on ne peut pas archiver. Sinon on archive.
                if (empty($othersReposToBeArchived)) {
                    $repoArchive = 'yes';
                }
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo '<td colspan="100%">L\'opération va restaurer la section de repo archivée suivante :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>${repoSection}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>${repoDist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Date de la section :</td><td><b>${repoDate}</b></td></tr>";
                echo "<tr><td colspan=\"100%\"><br>La restauration placera la section sur l'environnement <b>${repoEnv}</b>.</td></tr>";
                if ($repoArchive == "yes") {
                    echo "<tr><td colspan=\"100%\"><br>La section actuellement en <b>${repoEnv}</b> en date du <b>${repoToBeArchivedDate}</b> sera archivée.</td></tr>";
                }
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>$repoSection</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repoName</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>$repoDist</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Date de la section :</td><td><b>${repoDate}</b></td></tr>";
                require('operations/restoreOldRepo_deb.php');
                list($PID, $LOGNAME) = createLog();
                restoreOldRepo_deb($repoName, $repoDist, $repoSection, $repoDate, $repoEnv, $repoDescription);
                closeOperation($PID);
            }

        // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
        } else {
            echo '<tr>';
            echo '<td colspan="100%"><button type="submit" class="button-submit-large-red">Valider</button></td>';
            echo '</tr>';
        }
    }
}
?>