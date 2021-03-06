<?php
function precheck_duplicateRepo() {
    require_once('class/Repo.php');
    global $OS_FAMILY;
    
    /**
     *  1. On vérifie qu'on a bien reçu toutes les variables nécéssaires en GET
     */
    $actionId        = checkArguments('required', 'actionId');
    $repoName        = checkArguments('required', 'repoName');
    if ($OS_FAMILY == "Debian") {
        $repoDist    = checkArguments('required', 'repoDist');
        $repoSection = checkArguments('required', 'repoSection');
    }
    $repoEnv         = checkArguments('required', 'repoEnv');
    $repoNewName     = checkArguments('required', 'repoNewName');
    $repoDescription = checkArguments('optionnal', 'repoDescription');
    $repoGroup       = checkArguments('optionnal', 'repoGroup');

    /**
     *  2. Si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
     */
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoEnv) AND !empty($repoNewName) AND !empty($repoGroup) AND !empty($repoDescription)) {
            $repo = new Repo(compact('repoName', 'repoNewName', 'repoEnv', 'repoGroup', 'repoDescription'));

            /**
             *  3. Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà
             */
            if ($repo->exists($repo->newName) === true) {
                echo "<tr><td>Erreur : Un repo du même nom existe déjà</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  4. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va créer un nouveau repo :</td></tr>';
                echo "<tr><td class=\"td-fit\">Repo source :</td><td><b>{$repo->newName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">A partir du repo :</td><td><b>{$repo->name} ({$repo->env})</b></td></tr>";
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  5. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require_once('class/Log.php');
                $log = new Log('repomanager');
                
                $title = 'DUPLIQUER UN REPO';

                ob_start();
                $repo->duplicate();
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
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoEnv) AND !empty($repoNewName) AND !empty($repoGroup) AND !empty($repoDescription)) {
            $repo = new Repo(compact('repoName', 'repoNewName', 'repoDist', 'repoSection', 'repoEnv', 'repoGroup', 'repoDescription'));

            /**
             *  3. Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà
             */
            if ($repo->exists($repo->newName) === true) {
                echo "<tr><td>Erreur : Un repo du même nom existe déjà</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  4. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va créer une nouvelle section de repo :</td></tr>';
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>{$repo->section}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Repo source :</td><td><b>{$repo->newName}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>{$repo->dist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">A partir de la même section du repo :</td><td><b>{$repo->name} ({$repo->env})</b></td></tr>";
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  5. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require_once('class/Log.php');
                $log = new Log('repomanager');

                $title = 'DUPLIQUER UNE SECTION DE REPO';

                ob_start();
                $repo->duplicate();
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