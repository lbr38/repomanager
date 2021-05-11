<?php
function precheck_deleteRepo() {
    require_once('class/Repo.php');
    global $OS_FAMILY;
    
    /**
     *  1. On vérifie qu'on a bien reçu toutes les variables nécéssaires en GET
     */
    $actionId    = checkArguments('required', 'actionId');
    $repoName    = checkArguments('required', 'repoName');
    if ($OS_FAMILY == "Redhat") {
        $repoEnv = checkArguments('required', 'repoEnv');
    }

    /**
     *  2. Si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
     */
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoEnv)) {
            $repo = new Repo(compact('repoName', 'repoEnv'));

            /**
             *  3. Ok on a toutes les infos mais il faut vérifier que le repo mentionné existe
             */
            if ($repo->exists($repo->name) === false) {
                echo "<tr><td>Erreur : Il n'existe aucun repo $repo->name en $repo->env</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  4. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va supprimer le repo suivant :</td></tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>{$repo->name}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env :</td><td><b>{$repo->env}</b></td></tr>";
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  5. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                $log = new Log('repomanager');

                $title = 'SUPPRIMER UN REPO';

                ob_start();
                $repo->delete();
                $content = ob_get_clean();
                echo $content;

                include_once('templates/operation_log.inc.php');
                $log->write($logContent);
                $log->close();
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
            $repo = new Repo(compact('repoName'));

            /**
             *  3. Ok on a toutes les infos mais il faut vérifier que le repo mentionné existe
             */
            if ($repo->exists($repo->name) === false) {
                echo "<tr><td>Erreur : Il n'existe aucun repo $repo->name</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  4. Ok le repo existe mais peut être que celui-ci contient plusieurs distrib et sections qui seront supprimées, on récupère les distrib et les sections concernées
             *     et on les affichera dans la demande de confirmation
             */
            $distAndSectionsToBeDeleted = $repo->db->query("SELECT Dist, Section, Env from repos WHERE Name = '$repo->name'");

            /**
             *  5. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va supprimer tout le contenu du repo suivant :</td></tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repo->name</b></td></tr>";                
                if (!empty($distAndSectionsToBeDeleted)) {
                    echo '<tr><td colspan="100%"><br>Attention, cela supprimera les distributions et sections suivantes :</td></tr>';
                    while ($distAndSection = $distAndSectionsToBeDeleted->fetchArray()) {
                        $dist = $distAndSection['Dist'];
                        $section = $distAndSection['Section'];
                        $env = $distAndSection['Env'];
                        echo "<tr><td colspan=\"100%\"><b>$dist -> $section ($env)</b></td></tr>";
                    }
                } else {
                    echo '<tr><td colspan="100%">Attention, impossible de récupérer le nom des distributions et des sections impactées.<br>L\'opération supprimera tout le contenu du repo et donc les distributions et les sections qu\'il contient (tout environnement confondu)</td>';
                }
                echo '<tr><td colspan="100%"><br>Cela inclu également les sections archivées si il y en a</td></tr>';
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  6. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require_once('class/Log.php');
                $log = new Log('repomanager');

                $title = 'SUPPRIMER UN REPO';

                ob_start();
                $repo->delete();
                $content = ob_get_clean();
                echo $content;

                include_once('templates/operation_log.inc.php');
                $log->write($logContent);
                $log->close();
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