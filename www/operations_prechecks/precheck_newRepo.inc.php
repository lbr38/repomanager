<?php
function precheck_newRepo() {
    global $OS_FAMILY;
    global $WWW_DIR;
    global $REPOMANAGER_YUM_DIR;
    global $DEFAULT_ENV;

    require_once('class/Repo.php');
        
    /**
     *  1. On vérifie qu'on a bien reçu toutes les variables nécéssaires en GET
     */
    $actionId   = checkArguments('required', 'actionId');
    $repoSource = checkArguments('required', 'repoSource');
    if ($OS_FAMILY == "Debian") {
        $repoDist     = checkArguments('required', 'repoDist');
        $repoSection  = checkArguments('required', 'repoSection');
    }
    $repoAlias        = checkArguments('optionnal', 'repoAlias');
    $repoGroup        = checkArguments('optionnal', 'repoGroup');
    $repoDescription  = checkArguments('optionnal', 'repoDescription');
    $repoGpgCheck     = checkArguments('required', 'repoGpgCheck');
    $repoGpgResign    = checkArguments('required', 'repoGpgResign');

    /**
     *  2. Si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
     */
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoSource) AND !empty($repoAlias) AND !empty($repoGroup) AND !empty($repoDescription) AND !empty($repoGpgCheck) AND !empty($repoGpgResign)) {
            if ($repoAlias === "noalias") {
                $repoName = $repoSource;
            } else {
                $repoName = $repoAlias;
            }
            $repoEnv = $DEFAULT_ENV;
            $repoType = 'mirror';
            $repo = new Repo(compact('repoName', 'repoSource', 'repoEnv', 'repoGroup', 'repoDescription', 'repoType'));

            /**
             *  3. Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà
             */
            if ($repo->exists($repo->name) === true) {
                echo "<span class=\"redtext\">Erreur : Un repo du même nom existe déjà en <b>{$repo->env}</b></span>";
                return;
            } 

            /**
             *  4. On vérifie que le repo existe dans /etc/yum.repos.d/repomanager/
             */
            $checkifRepoRealnameExist = exec("grep '^\\[{$repo->source}\\]' ${REPOMANAGER_YUM_DIR}/*.repo");
            if (empty($checkifRepoRealnameExist)) {
                echo "<tr><td>Erreur : Il n'existe aucun repo source pour le nom de repo [{$repo->source}]</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  5. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va créer un nouveau repo :</td></tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repo->name ({$repo->source})</b></td></tr>";
                echo '<tr><td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  6. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("php ${WWW_DIR}/operations/newRepo.php $repo->name $repo->source $repoGpgCheck $repoGpgResign '$repoGroup' '$repoDescription' mirror >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/run.php');</script>"; // On redirige vers la page de logs pour voir l'exécution
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
        if (!empty($repoSource) AND !empty($repoAlias) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoGroup) AND !empty($repoDescription) AND !empty($repoGpgCheck) AND !empty($repoGpgResign)) {
            // Si repoAlias a été transmis vide (noalias), alors repoName reprend le nom de l'hote
            if ($repoAlias === "noalias") {
                $repoName = $repoSource;
            } else {
                $repoName = $repoAlias;
            }
            $repoEnv = $DEFAULT_ENV;
            $repoType = 'mirror';
            $repo = new Repo(compact('repoName', 'repoSource', 'repoDist', 'repoSection', 'repoGroup', 'repoDescription', 'repoType'));

            /**
             *  3. Ok on a toutes les infos mais il faut vérifier qu'un repo du même nom n'existe pas déjà
             */
            if ($repo->section_exists($repo->name, $repo->dist, $repo->section) === true) {
                echo "<span class=\"redtext\">Erreur : Une section du même nom existe déjà en <b>{$repo->env}</b></span>";
                return;
            }

            /**
             *  4. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va créer une nouvelle section de repo :</td></tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>$repo->name ({$repo->source})</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>{$repo->dist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>{$repo->section}</b></td></tr>";
                echo '<tr><td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  5. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("php ${WWW_DIR}/operations/newRepo.php $repo->name $repo->dist $repo->section $repo->source $repoGpgCheck $repoGpgResign '$repoGroup' '$repoDescription' mirror >/dev/null 2>/dev/null &");
                echo "<script>window.location.replace('/run.php');</script>"; // On redirige vers la page de logs pour voir l'exécution
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