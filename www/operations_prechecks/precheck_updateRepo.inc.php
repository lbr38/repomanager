<?php
function precheck_updateRepo() {
    require 'functions/load_common_variables.php';

    // 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en GET :
    $actionId        = checkArguments('required', 'actionId');
    $repoName        = checkArguments('required', 'repoName');
    if ($OS_FAMILY == "Debian") {
        $repoDist    = checkArguments('required', 'repoDist');
        $repoSection = checkArguments('required', 'repoSection');
    }
    $repoGpgCheck    = checkArguments('required', 'repoGpgCheck');
    $repoGpgResign   = checkArguments('required', 'repoGpgResign');

    // 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoGpgCheck) AND !empty($repoGpgResign)) {
            // recup du vrai nom du repo :
            $repoRealname = exec("grep '^Name=\"${repoName}\",Realname=\".*\",' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
            // On vérifie que le repo existe dans /etc/yum.repos.d/ :
            $checkifRepoRealnameExist = exec("grep '^\[${repoRealname}\]' ${REPOMANAGER_YUM_DIR}/*.repo");
            if (empty($checkifRepoRealnameExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucun fichier de repo dans ${REPOMANAGER_YUM_DIR}/ pour le nom de repo [${repoRealname}]</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo '<td colspan="100%">L\'opération va mettre à jour le repo :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env. :</td><td><b>${DEFAULT_ENV}</b></td></tr>";
                echo '<tr>';
                echo '<td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td>';
                echo '</tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                list($PID, $LOGNAME) = createLog();
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("php ${WWW_DIR}/operations/updateRepo.php $PID $LOGNAME $repoName $repoRealname $repoGpgCheck $repoGpgResign >/dev/null 2>/dev/null &");
                //exec("bash ${BASE_DIR}/functions/02_updateRepo --log \"${MAIN_LOGS_DIR}/${LOGNAME}\" --pid $PID --gpg-check $repoGpgCheck --gpg-resign $repoGpgResign --repo-name $repoName --repo-real-name $repoRealname >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/run.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
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
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoGpgCheck) AND !empty($repoGpgResign)) {

            // on récupère le nom de l'hôte :
            $repoHostName = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPOS_LIST | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'");
            // on vérifie dans le fichiers des hotes que l'hote récupéré existe bien :
            $checkIfHostExists = exec("grep 'Name=\"$repoHostName\",' $HOSTS_CONF");
            if (empty($checkIfHostExists)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucun hôte $checkIfHostExists pour le repo $repoHostName</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1;
            }

            // Ok on a toutes les infos mais pour mettre à jour un repo, il faut vérifier qu'il existe
            // On vérifie qu'un repo de même nom, de même distribution et de même section existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Host=\"${repoHostName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\"' $REPOS_LIST");
            if (empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucune section ${repoSection} du repo ${repoName} (distribution ${repoDist}) en ${DEFAULT_ENV} à mettre à jour. Il faut choisir l'option 'Créer une nouvelle section'</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }        

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo '<td colspan="100%">L\'opération va mettre à jour la section de repo :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>${repoSection}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>${repoDist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env. :</td><td><b>${DEFAULT_ENV}</b></td></tr>";
                echo '<tr>';
                echo '<td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td>';
                echo '</tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                list($PID, $LOGNAME) = createLog();
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("php ${WWW_DIR}/operations/updateRepo.php $PID $LOGNAME $repoName $repoDist $repoSection $repoHostName $repoGpgCheck $repoGpgResign >/dev/null 2>/dev/null &");
                //exec("bash ${BASE_DIR}/functions/02_updateRepo --log \"${MAIN_LOGS_DIR}/${LOGNAME}\" --pid $PID --gpg-check $repoGpgCheck --gpg-resign $repoGpgResign --repo-name $repoName --repo-host-name $repoHostName --repo-dist $repoDist --repo-section $repoSection >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/run.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
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