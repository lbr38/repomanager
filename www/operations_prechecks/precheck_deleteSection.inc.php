<?php
function precheck_deleteSection() {
    require_once('class/Repo.php');
    global $OS_FAMILY;
    
    /**
     *  1. On vérifie qu'on a bien reçu toutes les variables nécéssaires en GET
     */
    $actionId    = checkArguments('required', 'actionId');
    $repoName    = checkArguments('required', 'repoName');
    $repoDist    = checkArguments('required', 'repoDist');
    $repoSection = checkArguments('required', 'repoSection');
    $repoEnv     = checkArguments('required', 'repoEnv');

    /**
     *  2. Si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
     */
    if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoEnv)) {
        $repo = new Repo(compact('repoName', 'repoDist', 'repoSection', 'repoEnv'));

        /**
         *  3. Ok on a toutes les infos mais il faut vérifier que la section mentionnée existe
         */
        if ($repo->section_existsEnv($repo->name, $repo->dist, $repo->section, $repo->env) === false) {
            echo "<tr><td>Erreur : Il n'existe aucune section $repo->section du repo $repo->name (distribution $repo->dist) en $repo->env</td></tr>";
            echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
            return;
        }

        /**
         *  4. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
         */
        if (empty($_GET['confirm'])) {
            echo '<tr><td colspan="100%">L\'opération va supprimer la section de repo suivante :</td></tr>';
            echo "<tr><td class=\"td-fit\">Section :</td><td><b>{$repo->section} ({$repo->env})</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>{$repo->name}</b></td></tr>";
            echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>{$repo->dist}</b></td></tr>";
            echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
            echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
        }

        /**
         *  5. Si on a reçu la confirmation en GET alors on traite
         */
        if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
            require_once('class/Log.php');
            $log = new Log('repomanager');

            $title = 'SUPPRIMER UNE SECTION DE REPO';

            ob_start();
            $repo->deleteSection();
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