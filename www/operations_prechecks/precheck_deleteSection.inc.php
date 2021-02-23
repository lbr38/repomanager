<?php
function precheck_deleteSection() {
    require 'functions/load_common_variables.php';
    
    // 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en GET :
    $actionId    = checkArguments('required', 'actionId');
    $repoName    = checkArguments('required', 'repoName');
    $repoDist    = checkArguments('required', 'repoDist');
    $repoSection = checkArguments('required', 'repoSection');
    $repoEnv     = checkArguments('required', 'repoEnv');

    // 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
    if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoEnv)) {

        // Ok on a toutes les infos mais il faut vérifier que la section mentionnée existe :
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

        // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
        if (empty($_GET['confirm'])) {
            echo '<tr>';
            echo '<td colspan="100%">L\'opération va supprimer la section de repo suivante :</td>';
            echo '</tr>';
            echo "<tr><td class=\"td-fit\">Section :</td><td><b>${repoSection} ($repoEnv)</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>${repoDist}</b></td></tr>";
            echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
            echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
        }

        // Si on a reçu la confirmation en GET alors on traite :
        if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
            echo "<tr><td class=\"td-fit\">Section :</td><td><b>$repoSection ($repoEnv)</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repoName</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>$repoDist</b></td></tr>";
            require('operations/deleteSection.php');
            list($PID, $LOGNAME) = createLog();
            deleteSection($repoName, $repoDist, $repoSection, $repoEnv);
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