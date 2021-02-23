<?php
function precheck_newRepo() {
    require 'functions/load_common_variables.php';
        
    // 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en GET :
    $actionId         = checkArguments('required', 'actionId');
    if ($OS_FAMILY == "Redhat") {
        $repoRealname = checkArguments('required', 'repoRealname');
    }
    if ($OS_FAMILY == "Debian") {
        $repoHostName = checkArguments('required', 'repoHostName');
        $repoDist     = checkArguments('required', 'repoDist');
        $repoSection  = checkArguments('required', 'repoSection');
    }
    $repoAlias        = checkArguments('optionnal', 'repoAlias');
    $repoGroup        = checkArguments('optionnal', 'repoGroup');
    $repoDescription  = checkArguments('optionnal', 'repoDescription');
    $repoGpgCheck     = checkArguments('required', 'repoGpgCheck');
    $repoGpgResign    = checkArguments('required', 'repoGpgResign');

    // 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoRealname) AND !empty($repoAlias) AND !empty($repoGroup) AND !empty($repoDescription) AND !empty($repoGpgCheck) AND !empty($repoGpgResign)) {
            if ($repoAlias === "noalias") {
                $repoName = $repoRealname;
            } else {
                $repoName = $repoAlias;
            }

            // Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà (ou repoAlias)
            // On vérifie qu'un repo de même nom n'exite pas déjà :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Realname=\"${repoRealname}\",Env=\"${DEFAULT_ENV}\"' $REPOS_LIST");
            if (!empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Un repo du même nom existe déjà en <b>${DEFAULT_ENV}</b></td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // On vérifie que le repo existe dans /etc/yum.repos.d/ :
            $checkifRepoRealnameExist = exec("grep '^\\[${repoRealname}\\]' ${REPOMANAGER_YUM_DIR}/*.repo");
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
                echo '<td colspan="100%">L\'opération va créer un nouveau repo :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName} ($repoRealname)</b></td></tr>";
                echo '<tr>';
                echo '<td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td>';
                echo '</tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                list($PID, $LOGNAME) = createLog();
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("php ${WWW_DIR}/operations/newRepo.php $PID $LOGNAME $repoName $repoRealname $repoGpgCheck $repoGpgResign $repoGroup $repoDescription >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/run.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution
/*                list($PID, $LOGNAME) = createLog();
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("bash ${BASE_DIR}/functions/01_newRepo --log \"${MAIN_LOGS_DIR}/${LOGNAME}\" --pid $PID --gpg-check $repoGpgCheck --gpg-resign $repoGpgResign --repo-name $repoName --repo-real-name $repoRealname --repo-group $repoGroup --repo-description $repoDescription >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/run.php');</script>"; // Dans les deux cas on redirige vers la page de logs pour voir l'exécution*/
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
        if (!empty($repoHostName) AND !empty($repoAlias) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoGroup) AND !empty($repoDescription) AND !empty($repoGpgCheck) AND !empty($repoGpgResign)) {
            // Si repoAlias a été transmis vide (noalias), alors repoName reprend le nom de l'hote
            if ($repoAlias === "noalias") {
                $repoName = $repoHostName;
            } else {
                $repoName = $repoAlias;
            }

            // Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà (ou repoAlias)
            // On vérifie qu'un repo de même nom, de même distribution et de même section n'exite pas déjà :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Host=\"${repoHostName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${DEFAULT_ENV}\"' $REPOS_LIST");
            if (!empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Une section et un repo du même nom existe déjà en <b>${DEFAULT_ENV}</b></td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // On vérifie qu'une url hôte source existe pour le nom de repo renseigné :
            $checkifRepoHostExist = exec("grep '^Name=\"${repoHostName}\",' $HOSTS_CONF");
            if (empty($checkifRepoHostExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucune URL hôte pour le nom de repo <b>${repoName}</b></td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo '<td colspan="100%">L\'opération va créer une nouvelle section de repo :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName} ($repoHostName)</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>${repoDist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>${repoSection}</b></td></tr>";
                echo '<tr>';
                echo '<td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td>';
                echo '</tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                list($PID, $LOGNAME) = createLog();
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("php ${WWW_DIR}/operations/newRepo.php $PID $LOGNAME $repoName $repoDist $repoSection $repoHostName $repoGpgCheck $repoGpgResign $repoGroup $repoDescription >/dev/null 2>/dev/null &");
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