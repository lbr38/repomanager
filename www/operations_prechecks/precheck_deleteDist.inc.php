<?php
function precheck_deleteDist() {
    require 'functions/load_common_variables.php';
    
    // 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en GET :
    $actionId = checkArguments('required', 'actionId');
    $repoName = checkArguments('required', 'repoName');
    $repoDist = checkArguments('required', 'repoDist');

    // 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
    if (!empty($repoName) AND !empty($repoDist)) {
        // Ok on a toutes les infos mais il faut vérifier que la distribution mentionnée existe :
        $checkifDistExist = exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\"' $REPOS_LIST");
        if (empty($checkifDistExist)) {
            echo '<tr>';
            echo "<td>Erreur : Il n'existe aucune distribution <b>${repoDist}</b> du repo <b>${repoName}</b></td>";
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
            echo '</tr>';
            return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
        }

        // Ok la distribution existe mais peut être que celle-ci contient plusieurs sections qui seront supprimées, on récupère les sections concernées   Section=toto, Env=pprd
        // et on les affichera dans la demande de confirmation
        $sectionsToBeDeleted = shell_exec("grep '^Name=\"${repoName}\",Host=\".*\",Dist=\"${repoDist}\"' $REPOS_LIST | awk -F ',' '{print $4, $5}' | sed 's|Section=\"||g' | sed 's|\" Env=\"| (|g' | sed 's|\"|)|g'");
        $sectionsToBeDeleted = explode("\n", $sectionsToBeDeleted);
        $sectionsToBeDeleted = array_filter($sectionsToBeDeleted);

        // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
        if (empty($_GET['confirm'])) {
            echo '<tr>';
            echo '<td colspan="100%">L\'opération va supprimer tout le contenu de la distribution suivante :</td>';
            echo '</tr>';
            echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>${repoDist}</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Repo :</td><td><b>${repoName}</b></td></tr>";
            if (!empty($sectionsToBeDeleted)) {
                echo '<tr><td colspan="100%"><br>Attention, cela supprimera les sections suivantes :</td></tr>';
                foreach ($sectionsToBeDeleted as $section) {
                    echo "<tr><td colspan=\"100%\"><b>${section}</b></td></tr>";
                }
            } else {
                echo '<tr><td colspan="100%">Erreur : impossible de récupérer le nom des sections impactées.<br>L\'opération supprimera tout le contenu de la distribution et donc les sections qu\'elle contient (tout environnement confondu)</td></tr>';
            }
            echo '<tr><td colspan="100%"><br>Cela inclu également les sections archivées si il y en a</td></tr>';
            echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
            echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
        }

        // Si on a reçu la confirmation en GET alors on traite :
        if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
            echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>$repoDist</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Repo :</td><td><b>$repoName</b></td></tr>";
            require('operations/deleteDist.php');
            list($PID, $LOGNAME) = createLog();
            deleteDist($repoName, $repoDist);
            closeOperation($PID);
        }

    // Dans le cas où on n'a pas transmis toutes les infos, un formulaire est apparu pour demander les infos manquantes, on ajoute alors un bouton submit pour valider ce formulaire :
    } else {
        echo '<tr>';
        echo '<td colspan="100%"><button type="submit" class="button-submit-large-red">Valider</button></td>';
        echo '</tr>';
    }
}
?>