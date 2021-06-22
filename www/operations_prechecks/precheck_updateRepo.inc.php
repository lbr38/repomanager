<?php
function precheck_updateRepo() {
    require_once('class/Repo.php');
    require_once('class/Source.php');
    global $OS_FAMILY;
    global $WWW_DIR;
    global $REPOMANAGER_YUM_DIR;
    global $DEFAULT_ENV;

    /**
     *  1. On vérifie qu'on a bien reçu toutes les variables nécéssaires en GET
     */
    $actionId        = checkArguments('required', 'actionId');
    $repoName        = checkArguments('required', 'repoName');
    if ($OS_FAMILY == "Debian") {
        $repoDist    = checkArguments('required', 'repoDist');
        $repoSection = checkArguments('required', 'repoSection');
    }
    $repoGpgCheck    = checkArguments('required', 'repoGpgCheck');
    $repoGpgResign   = checkArguments('required', 'repoGpgResign');

    /**
     *  2. Si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
     */
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoGpgCheck) AND !empty($repoGpgResign)) {
            $repoEnv = $DEFAULT_ENV;
            $repo = new Repo(compact('repoName', 'repoEnv'));

            /**
             *  3. On récupère toutes les informations du repo à mettre à jour à partir de la BDD, notamment la source
             */
            $repo->db_getAll();

            /**
             *  4. On vérifie que le repo source a bien été récupéré
             */
            if (empty($repo->source)) {
                echo "<span class=\"redtext\">Erreur : impossible de récupérer le repo source de <b>$repo->name</b></span>";
                return;
            }

            /**
             *  5. On vérifie que le repo source existe dans /etc/yum.repos.d/repomanager/
             */
            $checkifRepoRealnameExist = exec("grep '^\\[{$repo->source}\\]' ${REPOMANAGER_YUM_DIR}/*.repo");
            if (empty($checkifRepoRealnameExist)) {
                echo "<tr><td>Erreur : Il n'existe aucun repo source pour le nom de repo [{$repo->source}]</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  6. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va mettre à jour le repo :</td></tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>{$repo->name}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env. :</td><td><b>{$repo->env}</b></td></tr>";
                echo '<tr><td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  7. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("php ${WWW_DIR}/operations/updateRepo.php $repo->name $repo->source $repoGpgCheck $repoGpgResign >/dev/null 2>/dev/null &");
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
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoGpgCheck) AND !empty($repoGpgResign)) {
            $repoEnv = $DEFAULT_ENV;
            $repo = new Repo(compact('repoName', 'repoDist', 'repoSection', 'repoEnv'));
            $source = new Source();

            /**
             *  3. On récupère toutes les informations du repo à mettre à jour à partir de la BDD, notamment la source
             */
            $repo->db_getAll();

            /**
             *  4. On vérifie que le repo source a bien été récupéré
             */
            if (empty($repo->source)) {
                echo "<span class=\"redtext\">Erreur : impossible de récupérer le repo source de <b>$repo->name</b></span>";
                return;
            }

            /**
             *  5. On vérifie dans le fichiers des hotes que le repo souce récupéré existe bien
             */
            if ($source->db->countRows("SELECT * FROM sources WHERE Name = '$repo->source'") == 0) {
                echo "<tr><td>Erreur : L'hôte source $repo->source du repo $repo->name n'existe pas/plus</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  6. Ok on a toutes les infos mais pour mettre à jour la section, il faut vérifier qu'elle existe
             */
            if ($repo->section_exists($repo->name, $repo->dist, $repo->section) === false) {
                echo "<tr><td colspan=\"100%\">Erreur : Il n'existe aucune section $repo->section du repo $repo->name (distribution {$repo->dist}) en ${DEFAULT_ENV} à mettre à jour. Il faut choisir l'option 'Créer une nouvelle section'</td></tr>";
            }     

            /**
             *  7. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr>';
                echo '<td colspan="100%">L\'opération va mettre à jour la section de repo :</td>';
                echo '</tr>';
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>{$repo->section}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>{$repo->name}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>{$repo->dist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env. :</td><td><b>{$repo->env}</b></td></tr>";
                echo '<tr>';
                echo '<td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td>';
                echo '</tr>';
            }

            /**
             *  8. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                echo '<tr><td>Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                exec("php ${WWW_DIR}/operations/updateRepo.php $repo->name $repo->dist $repo->section $repo->source $repoGpgCheck $repoGpgResign >/dev/null 2>/dev/null &");
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