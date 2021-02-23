<?php
function precheck_deleteRepo() {
    require 'functions/load_common_variables.php';
    
    // 1ère étape : on vérifie qu'on a bien reçu toutes les variables nécéssaires en GET :
    $actionId    = checkArguments('required', 'actionId');
    $repoName    = checkArguments('required', 'repoName');
    if ($OS_FAMILY == "Redhat") {
        $repoEnv = checkArguments('required', 'repoEnv');
    }

    // 2ème étape, si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoEnv)) {

            // Ok on a toutes les infos mais il faut vérifier que le repo mentionné existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\",Realname=\".*\",Env=\"${repoEnv}\"' $REPOS_LIST");
            if (empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucun repo ${repoName} en ${repoEnv}</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo '<td colspan="100%">L\'opération va supprimer le repo suivant :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env :</td><td><b>${repoEnv}</b></td></tr>";
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repoName</b></td></tr>";
                require('operations/deleteRepo_rpm.php');
                list($PID, $LOGNAME) = createLog();
                deleteRepo_rpm($repoName, $repoEnv);
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
        if (!empty($repoName)) {
            // Ok on a toutes les infos mais il faut vérifier que le repo mentionné existe :
            $checkifRepoExist = exec("grep '^Name=\"${repoName}\"' $REPOS_LIST");
            if (empty($checkifRepoExist)) {
                echo '<tr>';
                echo "<td>Erreur : Il n'existe aucun repo ${repoName}</td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td>';
                echo '</tr>';
                return 1; // On sort de la fonction pour ne pas que les conditions suivantes (ci-dessous) s'exécutent
            }

            // Ok le repo existe mais peut être que celui-ci contient plusieurs distrib et sections qui seront supprimées, on récupère les distrib et les sections concernées
            // et on les affichera dans la demande de confirmation
            $distAndSectionsToBeDeleted = shell_exec("grep '^Name=\"${repoName}\"' $REPOS_LIST | awk -F ',' '{print $3, $4, $5}' | sed 's|Dist=\"||g' | sed 's|\" Section=\"| -> |g'  | sed 's|\" Env=\"| (|g' | sed 's|\"|)|g'");
            $distAndSectionsToBeDeleted = explode("\n", $distAndSectionsToBeDeleted);
            $distAndSectionsToBeDeleted = array_filter($distAndSectionsToBeDeleted);

            // Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo '<td colspan="100%">L\'opération va supprimer tout le contenu du repo suivant :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>${repoName}</b></td></tr>";                
                if (!empty($distAndSectionsToBeDeleted)) {
                    echo '<tr><td colspan="100%"><br>Attention, cela supprimera les distributions et sections suivantes :</td></tr>';
                    foreach ($distAndSectionsToBeDeleted as $distAndSection) {
                        echo "<tr><td colspan=\"100%\"><b>${distAndSection}</b></td></tr>";
                    }
                } else {
                    echo '<tr><td colspan="100%">Attention, impossible de récupérer le nom des distributions et des sections impactées.<br>L\'opération supprimera tout le contenu du repo et donc les distributions et les sections qu\'il contient (tout environnement confondu)</td>';
                }
                echo '<tr><td colspan="100%"><br>Cela inclu également les sections archivées si il y en a</td></tr>';
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            // Si on a reçu la confirmation en GET alors on traite :
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repoName</b></td></tr>";
                require('operations/deleteRepo_deb.php');
                list($PID, $LOGNAME) = createLog();
                deleteRepo_deb($repoName);
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