<?php

/**
 *  Fonction de vérification des données envoyées par formulaire
 */
function validateData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres
 */
function is_alphanum(string $data, array $additionnalValidCaracters = []) {
    /**
     *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ignore dans le test en les remplacant temporairement par du vide
     */
    if (!empty($additionnalValidCaracters)) {
        if (!ctype_alnum(str_replace($additionnalValidCaracters, '', $data))) {
            printAlert('Vous ne pouvez renseigner que des chiffres ou des lettres', 'error');
            return false;
        }

    /**
     *  Si on n'a pas passé de caractères supplémentaires alors on teste simplement la chaine avec ctype_alnum
     */
    } else {
        if (!ctype_alnum($data)) {
            printAlert('Vous ne pouvez renseigner que des chiffres ou des lettres', 'error');
            return false;
        }
    }

    return true;    
}


/**
 *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres, un underscore ou un tiret
 *  Retire temporairement les tirets et underscore de la chaine passée afin qu'elle soit ensuite testée par la fonction PHP ctype_alnum
 */
function is_alphanumdash(string $data, array $additionnalValidCaracters = []) {
    /**
     *  array contenant quelques exceptions de caractères valides
     */
    $validCaracters = array('-', '_');

    /**
     *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ajoute à l'array $validCaracters
     */
    if (!empty($additionnalValidCaracters)) {
        $validCaracters = array_merge($validCaracters, $additionnalValidCaracters);
    }

    if(!ctype_alnum(str_replace($validCaracters, '', $data))) {
        printAlert('Vous ne pouvez renseigner que des chiffres, des lettres ou des tirets', 'error');
        return false;
    }

    return true;    
}

/**
 *  Suppression du "faux" cache ram du serveur
 *  2 cas possibles : 
 *  il s'agit d'un répertoire classique sur le disque
 *  ou il s'agit d'un lien symbolique vers /dev/smh (en ram)
 */
function clearCache() {
    global $WWW_CACHE;

    if (file_exists("${WWW_CACHE}/repomanager-repos-list.html")) unlink("${WWW_CACHE}/repomanager-repos-list.html");
}

/**
 *  Fonction permettant d'afficher une bulle d'alerte en bas de l'écran
 */
function printAlert(string $message, string $alertType = '') {
    if ($alertType == "error")   echo '<div class="alert-error">';
    if ($alertType == "success") echo '<div class="alert-success">';
    if (empty($alertType))       echo '<div class="alert">';
    echo "<span>$message</span>";
    echo '</div>';

    echo '<script type="text/javascript">';
    echo '$(document).ready(function () {';
    echo 'window.setTimeout(function() {';
    if ($alertType == "error" OR $alertType == "success") {
        echo "$('.alert-${alertType}').fadeTo(1000, 0).slideUp(1000, function(){";
    } else {
        echo "$('.alert').fadeTo(1000, 0).slideUp(1000, function(){";
    }
    echo '$(this).remove();';
    echo '});';
    echo '}, 2500);';
    echo '});';
    echo '</script>';
}

/**
 *  Fonction affichant un message de confirmation avant de supprimer
 *  $message = le message à afficher
 *  $url = lien GET vers la page de suppression
 *  $divID = un id unique du div caché contenant le message et les bouton supprimer ou annuler
 *  $aID = une class avec un ID unique du bouton cliquable permettant d'afficher/fermer la div caché. Attention le bouton d'affichage doit être avant l'appel de cette fonction.
 */
function deleteConfirm(string $message, string $url, $divID, $aID) {
    echo "<div id=\"$divID\" class=\"hide deleteAlert\">";
        echo "<span class=\"deleteAlert-message\">$message</span>";
        echo '<div class="deleteAlert-buttons-container">';
            echo "<a href=\"$url\"><span class=\"deleteAlert-delete\">Supprimer</span></a>";
            echo "<span class=\"$aID deleteAlert-cancel pointer\">Annuler</span>";
        echo '</div>';

    echo "<script>";
    echo "$(document).ready(function(){";
    echo "$(\".$aID\").click(function(){";
    echo "$(\"div#$divID\").slideToggle(150);";
    echo '$(this).toggleClass("open");';
    echo "});";
    echo "});";
    echo "</script>";
    echo '</div>';
    unset($message, $url, $divID, $aID);
}

/**
 *  Colore l'environnement d'une étiquette rouge ou blanche
 */
function envtag($env) {
    global $LAST_ENV;

    if ($env == $LAST_ENV) {
        return "<span class=\"last-env\">$env</span>";
    } else {
        return "<span class=\"env\">$env</span>";
    }
}

/**
 *  Rechargement d'une div en fournissant sa class css
 */
function refreshdiv_class($divclass) {
    if (!empty($divclass)) {
        echo '<script>';
        echo "$( \".${divclass}\" ).load(window.location.href + \" .${divclass}\" );";
        echo '</script>';
    }
}

/**
 *  Affichage d'une div cachée en fournissant sa class
 */
function showdiv_byclass($divclass) {
    echo '<script>';
    echo "$(document).ready(function() {";
    echo "$('.${divclass}').show(); })";
    echo '</script>';
}

/**
 *  Affichage d'une div cachée en fournissant son id
 */
function showdiv_byid(string $divid) {
    echo '<script>';
    echo "$(document).ready(function() {";
    echo "$('#${divid}').show(); })";
    echo '</script>';
}

/**
 *  Slide et affiche une div en fournissant son id
 *  S'applique notamment aux divs cachées dans le panel droit (gestion des sources, des groupes...)
 */
function slidediv_byid(string $divid) {
    echo "<script>
    $(document).ready(function(){
        $(\"#${divid}\").slideToggle().show('slow');
    });
    </script>";
}

/**
 *  Vérifie que la tâche cron des rappels de planifications est en place
 */
function checkCronReminder() {
    $cronStatus = shell_exec("crontab -l | grep 'send-reminders' | grep -v '#'");
    if (empty($cronStatus))
        return 'Off';
    else
        return 'On';
}

/**
 *  Fonction d'écriture d'un fichier ini
 *  Lui fournir un array contenant le paramètre et sa valeur et éventuellement sa section
 */
if (!function_exists('write_ini_file')) {
    /**
     * Write an ini configuration file
     * 
     * @param string $file
     * @param array  $array
     * @return bool
     */ 
    function write_ini_file($file, $array = []) {
        // check first argument is string
        if (!is_string($file)) {
            throw new \InvalidArgumentException('Function argument 1 must be a string.');
        }

        // check second argument is array
        if (!is_array($array)) {
            throw new \InvalidArgumentException('Function argument 2 must be an array.');
        }

        // process array
        $data = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $data[] = "[$key]";
                foreach ($val as $skey => $sval) {
                    if (is_array($sval)) {
                        foreach ($sval as $_skey => $_sval) {
                            if (is_numeric($_skey)) {
                                $data[] = $skey.'[] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                            } else {
                                $data[] = $skey.'['.$_skey.'] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                            }
                        }
                    } else {
                        $data[] = $skey.' = '.(is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"'.$sval.'"'));
                    }
                }
            } else {
                $data[] = $key.' = '.(is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"'.$val.'"'));
            }
            // empty line
            $data[] = null;
        }

        // open file pointer, init flock options
        $fp = fopen($file, 'w');
        $retries = 0;
        $max_retries = 100;

        if (!$fp) {
            return false;
        }

        // loop until get lock, or reach max retries
        do {
            if ($retries > 0) {
                usleep(rand(1, 5000));
            }
            $retries += 1;
        } while (!flock($fp, LOCK_EX) && $retries <= $max_retries);

        // couldn't get the lock
        if ($retries == $max_retries) {
            return false;
        }

        // got lock, write data
        fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);

        // release lock
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }
}

/**
 *  Inscrit les tâches cron dans la crontab de $WWW_USER
 */
function enableCron() {
    global $WWW_DIR;
    global $WWW_USER;
    global $TEMP_DIR;
    global $CRON_DAILY_ENABLED;
    global $AUTOMATISATION_ENABLED;
    global $CRON_PLAN_REMINDERS_ENABLED;
    global $CRON_STATS_ENABLED;

    // Récupération du contenu de la crontab actuelle dans un fichier temporaire
    shell_exec("crontab -l > ${TEMP_DIR}/${WWW_USER}_crontab.tmp");

    // On supprime toutes les lignes concernant repomanager dans ce fichier pour refaire propre
    exec("sed -i '/cronjob.php/d' ${TEMP_DIR}/${WWW_USER}_crontab.tmp");
    exec("sed -i '/plan.php/d' ${TEMP_DIR}/${WWW_USER}_crontab.tmp");
    exec("sed -i '/stats.php/d' ${TEMP_DIR}/${WWW_USER}_crontab.tmp");

    // Puis on ajoute les tâches cron suivantes au fichier temporaire

    // Tâche cron journalière
    if ($CRON_DAILY_ENABLED == "yes") {
        file_put_contents("${TEMP_DIR}/${WWW_USER}_crontab.tmp", "*/5 * * * * php ${WWW_DIR}/operations/cronjob.php".PHP_EOL, FILE_APPEND);
    }

    // Statistiques
    if ($CRON_STATS_ENABLED == "yes") {
        file_put_contents("${TEMP_DIR}/${WWW_USER}_crontab.tmp", "0 0 * * * php ${WWW_DIR}/operations/stats.php".PHP_EOL, FILE_APPEND);
    }

    // si on a activé l'automatisation alors on ajoute la tâche cron d'exécution des planifications
    if ($AUTOMATISATION_ENABLED == "yes") {
        file_put_contents("${TEMP_DIR}/${WWW_USER}_crontab.tmp", "* * * * * php ${WWW_DIR}/planifications/plan.php exec-plans".PHP_EOL, FILE_APPEND);
    }

    // si on a activé l'automatisation et les envois de rappels de planifications alors on ajoute la tâche cron d'envoi des rappels
    if ($AUTOMATISATION_ENABLED == "yes" AND $CRON_PLAN_REMINDERS_ENABLED == "yes") {
        file_put_contents("${TEMP_DIR}/${WWW_USER}_crontab.tmp", "0 0 * * * php ${WWW_DIR}/planifications/plan.php send-reminders".PHP_EOL, FILE_APPEND);
    }

    // Enfin on reimporte le contenu du fichier temporaire
    exec("crontab ${TEMP_DIR}/${WWW_USER}_crontab.tmp");   // on importe le fichier dans la crontab de $WWW_USER
    unlink("${TEMP_DIR}/${WWW_USER}_crontab.tmp");         // puis on supprime le fichier temporaire

    printAlert('Tâches cron redéployées', 'success');
}

/**
 *  Indique si un répertoire est vide ou non
 */
function dir_is_empty($dir) {
    $handle = opendir($dir);
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            closedir($handle);
            return false;
        }
    }
    closedir($handle);
    return true;
}

function kill_stats_log_parser() {
    global $WWW_DIR;
    global $WWW_USER;
    global $WWW_STATS_LOG_PATH;

    exec("/usr/bin/pkill -9 -u $WWW_USER -f 'tail -n0 -F $WWW_STATS_LOG_PATH'");
}

/**
 * 	Fonctions liées à l'affichage des listes de repos
 */

/**
 *  Affiche l'en-tête du tableau
 */
function printHead() {
    global $OS_FAMILY;
    global $printRepoSize;
    global $repoListType;

    /**
     *  Affichage de l'entête (Repo, Distrib, Section, Env, Date...)
     */
    echo '<tr class="reposListHead">';
        //echo '<td class="td-30"></td>';
        echo '<td class="td-10"></td>';
        echo '<td class="td-30">Repo</td>';
        if ($OS_FAMILY == "Debian") {
            if ($repoListType == 'active') echo '<td class="td-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
            echo '<td class="td-30">Distribution</td>';
            if ($repoListType == 'active') echo '<td class="td-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
            echo '<td class="td-30">Section</td>';
        }
        if ($repoListType == 'active') { 
            echo '<td class="td-30">Env</td>'; // On affiche l'env uniquement pour les repos actifs
            echo '<td class="td-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
        }
        echo '<td class="td-30">Date</td>';
        if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
            echo '<td class="td-30">Taille</td>';
        }
        echo '<td class="td-desc">Description</td>';
        echo '<td class="td-fit"></td>';
    echo '</tr>';
}

function processList(array $reposList) {
    global $OS_FAMILY;
    global $repoListType;

    $repoLastName = '';
    $repoLastDist = '';
    $repoLastSection = '';
    $repoLastEnv = '';

    foreach($reposList as $repo) {
        $repoId = $repo['Id'];
        $repoName = $repo['Name'];
        $repoSource = $repo['Source'];
        if ($OS_FAMILY == "Debian") {
            $repoDist = $repo['Dist'];
            $repoSection = $repo['Section'];
        }
        if ($repoListType == 'active') $repoEnv = $repo['Env'];
        $repoDate = DateTime::createFromFormat('Y-m-d', $repo['Date'])->format('d-m-Y');
        $repoTime = $repo['Time'];
        $repoDescription = $repo['Description'];
        $repoType = $repo['Type'];
        $repoSigned = $repo['Signed'];
    
        /**
         *  On transmets ces infos à la fonction printRepo qui va se charger d'afficher la ligne du repo
         */
        if ($repoListType == 'active') {
            if ($OS_FAMILY == "Redhat") printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
            if ($OS_FAMILY == "Debian") printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
        }
        if ($repoListType == 'archived') {
            if ($OS_FAMILY == "Redhat") printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
            if ($OS_FAMILY == "Debian") printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
        }
        if (!empty($repoName)) { $repoLastName = $repoName; }
        if ($OS_FAMILY == "Debian") {
            if (!empty($repoDist)) $repoLastDist = $repoDist;
            if (!empty($repoSection)) $repoLastSection = $repoSection;
        }
    }
}

/**
 *  Affiche la ligne d'un repo
 */
function printRepoLine($variables = []) {
    global $OS_FAMILY;
    global $DEFAULT_ENV;
    global $LAST_ENV;
    global $ENVS_TOTAL;
    global $REPOS_DIR;
    global $WWW_REPOS_DIR_URL;
    global $WWW_HOSTNAME;
    global $REPO_CONF_FILES_PREFIX;
    global $CRON_STATS_ENABLED;
    global $alternateColors;
    global $listColor;
    global $dividingLine;
    global $concatenateReposName;
    global $printRepoSize;
    global $printRepoType;
    global $printRepoSignature;
    global $repoLastName;
    global $repoLastDist;
    global $repoLastSection;
    global $repoListType;

	/**
	 * 	Récupère les infos concernant le repo passées en argument
	 */
    extract($variables);

    /**
     *  Affichage des données
     *  On souhaite afficher des couleurs identiques si le nom du repo est identique avec le précédent affiché. Si ce n'est pas le cas alors on affiche une couleur différente afin de différencier les repos dans la liste
     */
    if ($alternateColors == "yes" AND $repoName !== $repoLastName) {
        if ($listColor == "color1") { $listColor = 'color2'; }
        elseif ($listColor == "color2") { $listColor = 'color1'; }
    }

    /**
     *  Affichage ou non d'une ligne séparatrice entre chaque repo/section
     */
    if ($dividingLine === "yes") {
        if (!empty($repoLastName) AND $repoName !== $repoLastName) {
            echo '<tr><td colspan="100%"><hr></td></tr>';
        }
    }

    /**
     *  Début du form de modification de la ligne du repo
     */
    echo '<form action="" method="post" autocomplete="off">';
    echo '<input type="hidden" name="action" value="repoListEditRepo" />';
    echo "<input type=\"hidden\" name=\"repoListEditRepo_repoId\" value=\"$repoId\" />";
    if ($repoListType == 'active')   echo '<input type="hidden" name="repoListEditRepo_repoStatus" value="active" />';
    if ($repoListType == 'archived') echo '<input type="hidden" name="repoListEditRepo_repoStatus" value="archived" />';
    echo "<tr class=\"$listColor\">";
        /**
         *  Affichage des icones d'opérations
         */
        echo '<td class="td-10">';
            if ($repoListType == 'active') {
                /**
                 *  Affichage de l'icone "corbeille" pour supprimer le repo
                 *  Pour Redhat, on précise l'id du repo à supprimer
                 *  Pour Debian, on précise le nom du repo puisque celui-ci n'a pas d'id directement (ce sont les sections qui ont des id en BDD)
                 */
                if ($OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=delete&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
                if ($OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=delete&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";

                /**
                 *  Affichage de l'icone "dupliquer" pour dupliquer le repo
                 */
                if ($OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=duplicate&id=${repoId}&repoGroup=ask&repoDescription=ask\"><img class=\"icon-lowopacity\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
                if ($OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=duplicate&id=${repoId}&repoGroup=ask&repoDescription=ask\"><img class=\"icon-lowopacity\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";

                /**
                 *  Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
                 */
                if ($OS_FAMILY == "Redhat") echo "<img class=\"client-configuration-button icon-lowopacity\" os_family=\"Redhat\" repo=\"$repoName\" env=\"$repoEnv\" repo_dir_url=\"$WWW_REPOS_DIR_URL\" repo_conf_files_prefix=\"$REPO_CONF_FILES_PREFIX\" www_hostname=\"$WWW_HOSTNAME\" src=\"icons/code.png\" title=\"Afficher la configuration client\" />";
                if ($OS_FAMILY == "Debian") echo "<img class=\"client-configuration-button icon-lowopacity\" os_family=\"Debian\" repo=\"$repoName\" dist=\"$repoDist\" section=\"$repoSection\" env=\"$repoEnv\" repo_dir_url=\"$WWW_REPOS_DIR_URL\" repo_conf_files_prefix=\"$REPO_CONF_FILES_PREFIX\" www_hostname=\"$WWW_HOSTNAME\" src=\"icons/code.png\" title=\"Afficher la configuration client\" />";
                
                /**
                 *  Affichage de l'icone 'update' pour mettre à jour le repo/section. On affiche seulement si l'env du repo/section = $DEFAULT_ENV et si il s'agit d'un miroir
                 */
                if ($repoType === "mirror" AND $repoEnv === $DEFAULT_ENV) {
                    if ($OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=update&id=${repoId}&repoGpgCheck=ask&repoGpgResign=ask\"><img class=\"icon-lowopacity\" src=\"icons/update.png\" title=\"Mettre à jour le repo ${repoName} (${repoEnv})\" /></a>";
                    if ($OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=update&id=${repoId}&repoGpgCheck=ask&repoGpgResign=ask\"><img class=\"icon-lowopacity\" src=\"icons/update.png\" title=\"Mettre à jour la section ${repoSection} (${repoEnv})\" /></a>";
                }
            }
            if ($repoListType == 'archived') {
                if ($OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=deleteArchive&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo archivé ${repoName}\" /></a>";
                if ($OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=deleteArchive&id=${repoId}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section archivée ${repoSection}\" /></a>";
                
                /**
                 *  Affichage de l'icone "remise en production du repo"
                 */
                if ($OS_FAMILY == "Redhat") echo "<a href=\"operation.php?action=restore&id=${repoId}&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"icons/arrow-circle-up.png\" title=\"Restaurer le repo archivé ${repoName} en date du ${repoDate}\" /></a>";
                if ($OS_FAMILY == "Debian") echo "<a href=\"operation.php?action=restore&id=${repoId}&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"icons/arrow-circle-up.png\" title=\"Restaurer la section archivée ${repoSection} en date du ${repoDate}\" /></a>";
            }
        echo '</td>';

    /**
     *  Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent)
     */
    if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
        echo '<td class="td-30"></td>';
    } else {
        echo "<td class=\"td-30\">$repoName</td>";
    }
    if ($OS_FAMILY == "Debian") {
        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
        if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
            if ($repoListType == 'active') echo '<td class="td-fit"></td>';
            echo '<td class="td-30"></td>';
        } else {
            if ($repoListType == 'active') echo "<td class=\"td-fit\"><a href=\"operation.php?action=deleteDist&id=${repoId}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
            echo "<td class=\"td-30\">$repoDist</td>";
        }

        if ($repoListType == 'active') echo "<td class=\"td-fit\"><a href=\"operation.php?action=deleteSection&id=${repoId}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :    
        if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
            echo '<td class="td-30"></td>';
        } else {
            echo "<td class=\"td-30\">$repoSection</td>";
        }
    }

    /**
     *  Affichage de l'env en couleur
     *  On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
     */
    if ($repoListType == 'active') {
        if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
            echo "<td class=\"td-red-bckg td-30\"><span>$repoEnv</span></td>";
        } elseif ($repoEnv === $DEFAULT_ENV) {
            echo "<td class=\"td-white-bckg td-30\"><span>$repoEnv</span></td>";
        } elseif ($repoEnv === $LAST_ENV) {
            echo "<td class=\"td-red-bckg td-30\"><span>$repoEnv</span></td>";
        } else {
            echo "<td class=\"td-white-bckg td-30\"><span>$repoEnv</span></td>";
        }
        if ($ENVS_TOTAL > 1) {
            /**
             *  Icone permettant d'ajouter un nouvel environnement, placée juste avant la date
             */           
            echo "<td class=\"td-fit\"><a href=\"operation.php?action=changeEnv&id=${repoId}&repoNewEnv=ask&repoDescription=ask\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur le repo $repoName du $repoDate\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
        }
    }

    /**
     *  Affichage de la date
     */
    echo "<td class=\"td-30\" title=\"$repoDate $repoTime\">$repoDate</td>";

    /**
     *  Affichage de la taille
     */
    if ($printRepoSize == "yes") {
        if ($repoListType == 'active') {
            if ($OS_FAMILY == "Redhat") $repoSize = exec("du -hs ${REPOS_DIR}/${repoDate}_${repoName} | awk '{print $1}'");
            if ($OS_FAMILY == "Debian") $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
        }

        if ($repoListType == 'archived') {
            if ($OS_FAMILY == "Redhat" AND $printRepoSize == "yes") $repoSize = exec("du -hs ${REPOS_DIR}/archived_${repoDate}_${repoName} | awk '{print $1}'");
            if ($OS_FAMILY == "Debian" AND $printRepoSize == "yes") $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoDate}_${repoSection} | awk '{print $1}'");
        }

        echo "<td class=\"td-30\">$repoSize</td>";
    }

    /**
     *  Affichage de la description
     */
    echo '<td class="td-desc">';
    echo "<input type=\"text\" class=\"invisibleInput\" name=\"repoDescription\" value=\"$repoDescription\" />";
    echo '</td>';
    echo '<td class="td-fit">';
        /**
         *  Affichage de l'icone du type de repo (miroir ou local)
         */
        if ($printRepoType == "yes") {
            if ($repoType == "mirror") {
                echo "<img class=\"icon-lowopacity\" src=\"icons/world.png\" title=\"Type : miroir ($repoSource)\" />";
            } elseif ($repoType == "local") {
                echo '<img class="icon-lowopacity" src="icons/pin.png" title="Type : local" />';
            } else {
                echo '<span title="Type : inconnu">?</span>';
            }
        }
        /**
         *  Affichage de l'icone de signature GPG du repo
         */
        if ($printRepoSignature == "yes") {
            if ($repoSigned == "yes") {
                echo '<img class="icon-lowopacity" src="icons/key.png" title="Repo signé avec GPG" />';
            } elseif ($repoSigned == "no") {
                echo '<img class="icon-lowopacity" src="icons/key2.png" title="Repo non-signé avec GPG" />';
            } else {
                echo '<img class="icon-lowopacity" src="icons/unknow.png" title="Signature GPG : inconnue" />';
            }
        }
        /**
         *  Affichage de l'icone "statistiques"
         */
        if ($CRON_STATS_ENABLED == "yes" AND $repoListType == 'active') {
            if ($OS_FAMILY == "Redhat") echo "<a href=\"stats.php?id=${repoId}\"><img class=\"icon-lowopacity\" src=\"icons/stats.png\" title=\"Voir les stats du repo $repoName (${repoEnv})\" /></a>";
            if ($OS_FAMILY == "Debian") echo "<a href=\"stats.php?id=${repoId}\"><img class=\"icon-lowopacity\" src=\"icons/stats.png\" title=\"Voir les stats de la section $repoSection (${repoEnv})\" /></a>";
        }
        /**
         *  Affichage de l'icone "explorer"
         */
        if ($repoListType == 'active') {
            if ($OS_FAMILY == "Redhat") echo "<a href=\"explore.php?id=${repoId}&state=active\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer le repo $repoName (${repoEnv})\" /></a>";
            if ($OS_FAMILY == "Debian") echo "<a href=\"explore.php?id=${repoId}&state=active\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer la section ${repoSection} (${repoEnv})\" /></a>";
        }
        if ($repoListType == 'archived') {
            if ($OS_FAMILY == "Redhat") echo "<a href=\"explore.php?id=${repoId}&state=archived\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer le repo $repoName archivé (${repoDate})\" /></a>";
            if ($OS_FAMILY == "Debian") echo "<a href=\"explore.php?id=${repoId}&state=archived\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer la section archivée ${repoSection} (${repoDate})\" /></a>";
        }
        /**
         *  Affichage de l'icone "warning" si le répertoire du repo n'existe plus sur le serveur
         */
        if ($repoListType == 'active') {
            if ($OS_FAMILY == "Redhat") {
                if (!is_dir("$REPOS_DIR/${repoDate}_${repoName}")) {
                    echo '<img class="icon" src="icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
                }
            }
            if ($OS_FAMILY == "Debian") {
                if (!is_dir("$REPOS_DIR/$repoName/$repoDist/${repoDate}_${repoSection}")) {
                    echo '<img class="icon" src="icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
                }
            }
        }
        if ($repoListType == 'archived') {
            if ($OS_FAMILY == "Redhat") {
                if (!is_dir("$REPOS_DIR/archived_${repoDate}_${repoName}")) {
                    echo '<img class="icon" src="icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
                }
            }
            if ($OS_FAMILY == "Debian") {
                if (!is_dir("$REPOS_DIR/$repoName/$repoDist/archived_${repoDate}_${repoSection}")) {
                    echo '<img class="icon" src="icons/warning.png" title="Le répertoire de cette section semble inexistant sur le serveur" />';
                }
            }
        }
        echo '</td>';
    echo '</tr>';
    echo '</form>';
}
?>