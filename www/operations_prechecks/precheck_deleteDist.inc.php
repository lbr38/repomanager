<?php
function precheck_deleteDist() {
    require_once('class/Repo.php');
    global $OS_FAMILY;
    
    /**
     *  1. On vérifie qu'on a bien reçu toutes les variables nécéssaires en GET
     */
    $actionId = checkArguments('required', 'actionId');
    $repoName = checkArguments('required', 'repoName');
    $repoDist = checkArguments('required', 'repoDist');

    /**
     *  2. Si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
     */
    if (!empty($repoName) AND !empty($repoDist)) {
        $repo = new Repo(compact('repoName', 'repoDist'));

        /**
         *  3. Ok on a toutes les infos mais il faut vérifier que la distribution mentionnée existe
         */
        if ($repo->dist_exists($repo->name, $repo->dist) === false) {
            echo "<tr><td>Erreur : Il n'existe aucune distribution <b>${repoDist}</b> du repo <b>${repoName}</b></td></tr>";
            echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
            return;
        }

        /**
         *  4. Ok la distribution existe mais peut être que celle-ci contient plusieurs sections qui seront supprimées, on récupère les sections concernées
         *     et on les affichera dans la demande de confirmation
         */
        $sectionsToBeDeleted = $repo->db->query("SELECT Section, Env from repos WHERE Name = '$repo->name' AND Dist = '$repo->dist'");

        /**
         *  5.  Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
         */
        if (empty($_GET['confirm'])) {
            echo '<tr><td colspan="100%">L\'opération va supprimer tout le contenu de la distribution suivante :</td></tr>';
            echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>{$repo->dist}</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Repo :</td><td><b>{$repo->name}</b></td></tr>";
            if (!empty($sectionsToBeDeleted)) {
                echo '<tr><td colspan="100%"><br>Attention, cela supprimera les sections suivantes :</td></tr>';
                while ($sections = $sectionsToBeDeleted->fetchArray()) {
                    $section = $sections['Section'];
                    $env = $sections['Env'];
                    echo "<tr><td colspan=\"100%\"><b>$section ($env)</b></td></tr>";
                }
            } else {
                echo '<tr><td colspan="100%">Erreur : impossible de récupérer le nom des sections impactées.<br>L\'opération supprimera tout le contenu de la distribution et donc les sections qu\'elle contient (tout environnement confondu)</td></tr>';
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

            $title = 'SUPPRIMER UNE DISTRIBUTION';

            ob_start();
            $repo->deleteDist();
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
?>