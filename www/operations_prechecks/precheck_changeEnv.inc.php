<?php
function precheck_changeEnv() {
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
    $repoNewEnv      = checkArguments('required', 'repoNewEnv');
    $repoDescription = checkArguments('optionnal', 'repoDescription');

    /**
     *  2. Si on a toutes les variables, on demande une confirmation puis si on a la confirmation alors on lance l'exécution
     */
    // Cas Redhat :
    if ($OS_FAMILY == "Redhat") {
        if (!empty($repoName) AND !empty($repoEnv) AND !empty($repoNewEnv) AND !empty($repoDescription)) {
            $repo = new Repo(compact('repoName', 'repoEnv', 'repoNewEnv', 'repoDescription'));

            /**
             *  3. Ok on a toutes les infos mais pour changer l'env d'un repo, mais il faut vérifier qu'il existe
             */
            if ($repo->existsEnv($repo->name, $repo->env) === false) {
                echo "<tr><td>Erreur : Il n'existe aucun repo <b>{$repo->name}</b> en <b>{$repo->env}</b>.</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  4. Ensuite on vérifie si un repo existe déjà dans le nouvel env indiqué. Si c'est le cas, alors son miroir sera archivé si il n'est pas utilisé par un autre environnement
             */
            $repoArchive = 'no';
            if ($repo->existsEnv($repo->name, $repo->newEnv) === true) {
                // du coup on vérifie que le miroir du repo à archiver n'est pas utilisé par un autre environnement :
                // pour cela on récupère sa date de synchro et on regarde si elle est utilisée par un autre env :
                $result = $repo->db->querySingleRow("SELECT Date from repos WHERE Name = '$repo->name' AND Env = '$repo->newEnv'");
                $repoArchiveDate = $result['Date'];
                $repoToArchive = $repo->db->countRows("SELECT Name, Env from repos WHERE Name = '$repo->name' AND Date = '$repoArchiveDate' AND Env != '$repo->newEnv';");
                if ($repoToArchive == 0) {
                    $repoArchive = "yes"; // si le repo n'est pas utilisé par un autre environnement, alors on pourra indiquer qu'il sera archivé
                }
            }

            /**
             *  5. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo "<tr><td colspan=\"100%\">L'opération va faire pointer un environnement <b>{$repo->newEnv}</b> sur le repo suivant :</td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>{$repo->name}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env. :</td><td><b>{$repo->env}</b></td></tr>";
                if ($repoArchive == "yes") { echo "<tr><td colspan=\"100%\"><br>Le repo actuellement en <b>{$repo->newEnv}</b> en date du <b>${repoArchiveDate}</b> sera archivé</td></tr>"; }
                echo '<tr><td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  6. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require_once('class/Log.php');
                $log = new Log('repomanager');
    
                $title = 'NOUVEL ENVIRONNEMENT DE REPO';
    
                ob_start();
                $repo->changeEnv();
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
        if (!empty($repoName) AND !empty($repoDist) AND !empty($repoSection) AND !empty($repoEnv) AND !empty($repoNewEnv) AND !empty($repoDescription)) {
            $repo = new Repo(compact('repoName', 'repoDist', 'repoSection', 'repoEnv', 'repoNewEnv', 'repoDescription'));

            /**
             *  3. Ok on a toutes les infos mais pour changer l'env d'une section, mais il faut vérifier qu'elle existe
             */
            if ($repo->section_existsEnv($repo->name, $repo->dist, $repo->section, $repo->env) === false) {
                echo "<tr><td>Erreur : Il n'existe aucune section <b>{$repo->section}</b> du repo <b>{$repo->name}</b> (distribution {$repo->dist}) en <b>{$repo->env}</b>.</td></tr>";
                echo '<tr><td colspan="100%"><a href="index.php" class="button-submit-large-red">Retour</a></td></tr>';
                return;
            }

            /**
             *  4. Ensuite on vérifie si une section existe déjà dans le nouvel env indiqué. Si c'est le cas, alors son miroir sera archivé si il n'est pas utilisé par un autre environnement
             */
            $repoArchive = 'no';
            if ($repo->section_existsEnv($repo->name, $repo->dist, $repo->section, $repo->newEnv) === true) {
                // du coup on vérifie que le miroir de la section à archiver n'est pas utilisé par un autre environnement :
                // pour cela on récupère sa date de synchro et on regarde si elle est utilisée par un autre env :
                $result = $repo->db->querySingleRow("SELECT Date from repos WHERE Name = '$repo->name' AND Dist = '$repo->dist' AND Section = '$repo->section' AND Env = '$repo->newEnv'");
                $repoArchiveDate = $result['Date'];
                $repoToArchive = $repo->db->countRows("SELECT Name, Env from repos WHERE Name = '$repo->name' AND Dist = '$repo->dist' AND Section = '$repo->section' AND Date = '$repoArchiveDate' AND Env != '$repo->newEnv';");
                if ($repoToArchive == 0) {
                    $repoArchive = "yes"; // si le repo n'est pas utilisé par un autre environnement, alors on pourra indiquer qu'il sera archivé
                }
            }

            /**
             *  5. Si on n'a pas encore reçu la confirmation alors on la demande (et on revalide le formulaire sur cette meme page, en renvoyant toutes les variables nécéssaires grâce aux input hidden)
             */
            if (empty($_GET['confirm'])) {
                echo "<tr><td colspan=\"100%\">L'opération va faire pointer un environnement <b>{$repo->newEnv}</b> sur la section de repo suivante : </td></tr>";
                echo "<tr><td class=\"td-fit\">Section :</td><td><b>{$repo->section}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Nom du repo :</td><td><b>{$repo->name}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Distribution :</td><td><b>{$repo->dist}</b></td></tr>";
                echo "<tr><td class=\"td-fit\">Env. :</td><td><b>{$repo->env}</b></td></tr>";
                if ($repoArchive == "yes") { echo "<tr><td colspan=\"100%\"><br>La section actuellement en <b>{$repo->newEnv}</b> en date du <b>${repoArchiveDate}</b> sera archivée</td></tr>"; } // si il y a un repo à archiver, on l'indique ainsi que sa date de synchro
                echo '<tr><td colspan="100%"><button type="submit" class="button-submit-large-red" name="confirm" value="yes">Confirmer et exécuter</button></td></tr>';
            }

            /**
             *  6. Si on a reçu la confirmation en GET alors on traite
             */
            if (!empty($_GET['confirm']) AND (validateData($_GET['confirm']) == "yes")) {
                require_once('class/Log.php');
                $log = new Log('repomanager');
    
                $title = 'NOUVEL ENVIRONNEMENT DE SECTION';
    
                ob_start();
                $repo->changeEnv();
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