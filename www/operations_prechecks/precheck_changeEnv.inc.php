<?php
function precheck_changeEnv() {
    require('functions/load_common_variables.php');
    require("functions/cleanArchives.php");
    
    // 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en GET :
    $actionId        = checkArguments('required', 'actionId');
    $repoName        = checkArguments('required', 'repoName');
    if ($OS_FAMILY == "Debian") {
        $repoDist    = checkArguments('required', 'repoDist');
        $repoSection = checkArguments('required', 'repoSection');
    }
    $repoEnv         = checkArguments('required', 'repoEnv');
    $repoNewEnv      = checkArguments('required', 'repoNewEnv');
    $repoDescription = checkArguments('optionnal', 'repoDescription');

    // 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoEnv) AND !empty($repoNewEnv) AND !empty($repoDescription)) {
            // Ok on a toutes les infos mais pour changer l'env d'un repo, il faut vérifier qu'il existe
            // On vérifie qu'un repo de même nom existe à l'env indiqué :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST");
            if (empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucun repo <b>${repoName}</b> en <b>${repoEnv}</b>.</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Ensuite on vérifie si un repo existe déjà dans le nouvel env indiqué. Si c'est le cas, alors il sera archivé (sauf si il est toujours utilisé par un autre environnement)
            $repoArchive = "no"; // on déclare une variable à 'no' par défaut
            $checkifRepoExist2 = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"' $REPOS_LIST");
            if (!empty($checkifRepoExist2)) { // si un repo existe
                // du coup on vérifie que le repo à archiver n'est pas utilisé par un autre environnement :
                // on récupère sa date de synchro et on regarde si elle est utilisée par un autre env :
                $repoArchiveDate = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"' $REPOS_LIST | awk -F ',' '{print $4}' | cut -d'=' -f2 | sed 's/\"//g'");
                $checkifRepoExist3 = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\".*\",Date=\"${repoArchiveDate}\"' $REPOS_LIST | grep -v '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoNewEnv}\"'");
                if (empty($checkifRepoExist3)) { // si le repo n'est pas utilisé par un autre environnement, alors on pourra indiquer qu'il sera archivé
                    $repoArchive = "yes";
                } 
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo "<td colspan=\"100%\">L'opération va faire pointer un environnement <b>${repoNewEnv}</b> sur le repo suivant : </td>";
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env. :</td><td><b>${repoEnv}</b></td></tr>";
                if ($repoArchive == "yes") { echo "<tr><td colspan=\"100%\"><br>Le repo actuellement en <b>${repoNewEnv}</b> en date du <b>${repoArchiveDate}</b> sera archivé</td></tr>"; } // si il y a un repo à archiver, on l'indique ainsi que sa date de synchro
                echo '<tr>';
                echo '<td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td>';
                echo '</tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require('operations/changeEnv_rpm.php');
                list($PID, $LOGNAME) = createLog();
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repoName</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Environnement source :</td><td><b>$repoEnv</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Environnement cible :</td><td><b>$repoNewEnv</b></td></tr>";
                changeEnv_rpm($repoName, $repoEnv, $repoNewEnv, $repoDescription);
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
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoEnv) AND !empty($repoNewEnv) AND !empty($repoDescription)) {
            // Ok on a toutes les infos mais pour changer l'env d'un repo (section en réalité), il faut vérifier qu'il existe
            // On vérifie qu'un repo de même nom existe à l'env indiqué :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoEnv}\"' $REPOS_LIST");
            if (empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucune section ${repoSection} du repo ${repoName} (distribution ${repoDist}) en ${repoEnv}</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Ensuite on vérifie si un repo existe déjà dans le nouvel env indiqué. Si c'est le cas, alors il sera archivé (sauf si il est toujours utilisé par un autre environnement)
            $repoArchive = "no"; // on déclare une variable à 'no' par défaut
            $checkifRepoExist2 = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoNewEnv}\"' $REPOS_LIST");
            if (!empty($checkifRepoExist2)) { // si un repo existe
                // du coup on vérifie que le repo à archiver n'est pas utilisé par un autre environnement :
                // on récupère sa date de synchro et on regarde si elle est utilisée par un autre env :
                $repoArchiveDate = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoNewEnv}\"' $REPOS_LIST | awk -F ',' '{print $6}' | cut -d'=' -f2 | sed 's/\"//g'");
                $checkifRepoExist3 = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\".*\",Date=\"${repoArchiveDate}\"' $REPOS_LIST | grep -v '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${repoNewEnv}\"'");
                if (empty($checkifRepoExist3)) { // si le repo n'est pas utilisé par un autre environnement, alors on pourra indiquer qu'il sera archivé
                    $repoArchive = "yes";
                }
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo "<td colspan=\"100%\">L'opération va faire pointer un environnement <b>${repoNewEnv}</b> sur la section de repo suivante : </td>";
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>${repoSection}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>${repoDist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env. :</td><td><b>${repoEnv}</b></td></tr>";
                if ($repoArchive == "yes") { echo "<tr><td colspan=\"100%\"><br>La section actuellement en <b>${repoNewEnv}</b> en date du <b>${repoArchiveDate}</b> sera archivée</td></tr>"; } // si il y a un repo à archiver, on l'indique ainsi que sa date de synchro
                echo '<tr>';
                echo '<td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td>';
                echo '</tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require('operations/changeEnv_deb.php');
                list($PID, $LOGNAME) = createLog();
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>$repoSection</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repoName</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>$repoDist</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Environnement source :</td><td><b>$repoEnv</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Environnement cible :</td><td><b>$repoNewEnv</b></td></tr>";
                changeEnv_deb($repoName, $repoDist, $repoSection, $repoEnv, $repoNewEnv, $repoDescription);
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