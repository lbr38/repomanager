<?php
function precheck_restoreOldRepo() {
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
    $repoDate        = checkArguments('required', 'repoDate');
    $repoEnv         = checkArguments('required', 'repoEnv');
    $repoDescription = checkArguments('optionnal', 'repoDescription');

    /**
     *  2. Si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
     */
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoDate) AND !empty($repoEnv) AND !empty($repoDescription)) {
            $repo = new Repo(compact('repoName', 'repoDate', 'repoEnv', 'repoDescription'));

            /**
             *  3. Ok on a toutes les infos mais il faut vérifier que le repo archivé mentionné existe
             */
            if ($repo->existsDate($repo->name, $repo->date, 'archived') === false) {
                echo "<tr><td>Erreur : Il n'existe aucun repo archivé $repo->name en date du $repo->date</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  4. On vérifie si un repo du même nom existe sur l'env $repoEnv, si c'est le cas et que son miroir n'est pas utilisé par d'autres environnements, il sera archivé
             */
            $repoArchive = 'no'; // on déclare une variable à 'no' par défaut
            if ($repo->existsEnv($repo->name, $repo->env) === true) {
                // Si le résultat précedent === true, alors il y a un miroir qui sera potentiellement archivé. 
                // On récupère sa date et on regarde si cette date n'est pas utilisée par un autre env.
                $result = $repo->db->querySingleRow("SELECT Date from repos WHERE Name = '$repo->name' AND Env = '$repo->env'");
                $repoToBeArchivedDate = $result['Date'];
                $repoToBeArchived = $repo->db->countRows("SELECT * FROM repos WHERE Name = '$repo->name' AND Date = '$repoToBeArchivedDate' AND Env != '$repo->env'");
                // Si d'autres env utilisent le miroir en date du '$repoToBeArchivedDate' alors on ne peut pas archiver. Sinon on archive :
                if ($repoToBeArchived == 0) {
                    $repoArchive = 'yes';
                }
            }
            
            /**
             *  5. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va restaurer le repo archivé suivant :</td></tr>';
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>{$repo->name}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Date du repo :</td><td><b>{$repo->date}</b></td></tr>";
                echo "<tr><td colspan=\"100%\"><br>La restauration placera le repo sur l'environnement <b>{$repo->env}</b>.</td></tr>";
                if ($repoArchive == "yes") {
                    echo "<tr><td colspan=\"100%\"><br>Le repo actuellement en <b>{$repo->env}</b> en date du <b>${repoToBeArchivedDate}</b> sera archivé.</td></tr>";
                }
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  6. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require_once('class/Log.php');
                $log = new Log('repomanager');
    
                $title = 'RESTAURER UN REPO';
    
                ob_start();
                $repo->restore();
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
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoDate) AND !empty($repoEnv) AND !empty($repoDescription)) {
            $repo = new Repo(compact('repoName', 'repoDist', 'repoSection', 'repoDate', 'repoEnv', 'repoDescription'));

            /**
             *  3. Ok on a toutes les infos mais il faut vérifier que la section archivée mentionnée existe 
             */
            if ($repo->section_existsDate($repo->name, $repo->dist, $repo->section, $repo->date, 'archived') === false) {
                echo "<tr><td>Erreur : Il n'existe aucune section archivée $repo->section du repo $repo->name (distribution $repo->dist)</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }
 
            /**
             *  4. On vérifie si une section du même nom existe sur l'env $repoEnv, si c'est le cas et que son miroir n'est pas utilisé par d'autres environnements, il sera archivé
             */
            $repoArchive = 'no'; // on déclare une variable à 'no' par défaut
            if ($repo->section_existsEnv($repo->name, $repo->dist, $repo->section, $repo->env) === true) {
                // Si le résultat précedent === true, alors il y a un miroir qui sera potentiellement archivé. 
                // On récupère sa date et on regarde si cette date n'est pas utilisée par un autre env.
                $result = $repo->db->querySingleRow("SELECT Date from repos WHERE Name = '$repo->name' AND Dist = '$repo->dist' AND Section = '$repo->section' AND Env = '$repo->env'");
                $repoToBeArchivedDate = $result['Date'];
                $repoToBeArchived = $repo->db->countRows("SELECT * FROM repos WHERE Name = '$repo->name' AND Dist = '$repo->dist' AND Section = '$repo->section' AND Date = '$repoToBeArchivedDate' AND Env != '$repo->env'");
                // Si d'autres env utilisent le miroir en date du '$repoToBeArchivedDate' alors on ne peut pas archiver. Sinon on archive :
                if ($repoToBeArchived == 0) {
                    $repoArchive = 'yes';
                }
            }

            /**
             *  5. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo '<tr><td colspan="100%">L\'opération va restaurer la section de repo archivée suivante :</td></tr>';
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>{$repo->section}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>{$repo->name}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>{$repo->dist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Date de la section :</td><td><b>{$repo->dateFormatted}</b></td></tr>";
                echo "<tr><td colspan=\"100%\"><br>La restauration placera la section sur l'environnement <b>{$repo->env}</b>.</td></tr>";
                if ($repoArchive == "yes") {
                    echo "<tr><td colspan=\"100%\"><br>La section actuellement en <b>{$repo->env}</b> en date du <b>${repoToBeArchivedDate}</b> sera archivée.</td></tr>";
                }
                echo '<tr class="loading"><td colspan="100%">Chargement <img src="images/loading.gif" class="icon" /></td></tr>';
                echo '<tr><td colspan="100%"><button type="submit" id="confirmButton" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  6. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require_once('class/Log.php');
                $log = new Log('repomanager');
    
                $title = 'RESTAURER UNE SECTION DE REPO';
    
                ob_start();
                $repo->restore();
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