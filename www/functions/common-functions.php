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
            printAlert('Vous ne pouvez renseigner que des chiffres, des lettres');
            return false;
        }

    /**
     *  Si on n'a pas passé de caractères supplémentaires alors on teste simplement la chaine avec ctype_alnum
     */
    } else {
        if (!ctype_alnum($data)) {
            printAlert('Vous ne pouvez renseigner que des chiffres, des lettres');
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
        printAlert('Vous ne pouvez renseigner que des chiffres, des lettres ou des tirets');
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

    if (file_exists("${WWW_CACHE}/repomanager-repos-list.html")) { unlink("${WWW_CACHE}/repomanager-repos-list.html"); }
}

/**
 *  Fonction permettant d'afficher une bulle d'alerte au milieu de la page
 */
function printAlert($message) {
    echo '<div class="alert">';
    echo "<p>${message}</p>";
    echo '</div>';
    echo '<script type="text/javascript">';
    echo '$(document).ready(function () {';
    echo 'window.setTimeout(function() {';
    echo '$(".alert").fadeTo(1000, 0).slideUp(1000, function(){';
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
function deleteConfirm($message, $url, $divID, $aID) {
    echo "<div id=\"${divID}\" class=\"hide deleteAlert\">";
    echo "<p>${message}</p>";
    echo '<br>';
    echo "<a href=\"${url}\" class=\"deleteButton\">Supprimer</a>";
    echo "<span class=\"$aID pointer\">Annuler</span>";
    echo "<script>";
    echo "$(document).ready(function(){";
    echo "$(\".$aID\").click(function(){";
    echo "$(\"div#${divID}\").slideToggle(150);";
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
 *  Récupère les fichiers de logs d'opérations passées ou en cours et les affiche dans un select
 */
function selectlogs() {
    global $MAIN_LOGS_DIR;

    // Si un fichier de log est actuellement sélectionné (en GET) alors on récupère son nom afin qu'il soit sélectionné dans la liste déroulante (s'il apparait)
    if (!empty($_GET['logfile'])) {
        $currentLogfile = validateData($_GET['logfile']);
    } else {
        $currentLogfile = '';
    }

    // On récupère la liste des fichiers de logs en les triant sur la date
    $logfiles = explode("\n", shell_exec("cd $MAIN_LOGS_DIR && ls -A1 'repomanager_'* | sort -t _ -k3 -r"));
    echo '<form action="run.php" method="get" class="is-inline-block">';
    echo '<select name="logfile" class="select-xxlarge">';
    echo "<option value=\"\">Historique de traitements</option>";
    foreach($logfiles as $logfile) {
        // on ne souhaite pas afficher les répertoires '..' '.' ni le fichier lastlog.log (déjà affiché en premier ci-dessus) et on souhaite uniquement afficher les fichier commencant par repomanager_
        if (($logfile != "..") AND ($logfile != ".") AND ($logfile != "lastlog.log") AND preg_match('/^repomanager_/',$logfile)) {
            // Formatage du nom du fichier afin d'afficher quelque chose de plus propre dans la liste
            $logfileDate = exec("echo $logfile | awk -F '_' '{print $3}'");
            $logfileDate = DateTime::createFromFormat('Y-m-d', $logfileDate)->format('d-m-Y');
            $logfileTime = exec("echo $logfile | awk -F '_' '{print $4}' | sed 's/.log//g'");
            $logfileTime = DateTime::createFromFormat('H-i-s', $logfileTime)->format('H:i:s');
            if ($logfile === $currentLogfile) {
                echo "<option value=\"${logfile}\" selected>Repomanager : traitement du $logfileDate à $logfileTime</option>";
            } else {
                echo "<option value=\"${logfile}\">Repomanager : traitement du $logfileDate à $logfileTime</option>";
            }
        }
    }
    echo '</select>';
    echo '<button type="submit" class="button-submit-xsmall-blue">Afficher</button>';
    echo '</form>';
    unset($logfiles, $logfile, $logfileDate, $logfileTime);
}

/**
 *  Récupère les fichiers de logs de planifications passées ou en cours et les affiche dans un select
 */
function selectPlanlogs() {
    global $MAIN_LOGS_DIR;

    // Si un fichier de log est actuellement sélectionné (en GET) alors on récupère son nom afin qu'il soit sélectionné dans la liste déroulante (s'il apparait)
    if (!empty($_GET['logfile'])) {
        $currentLogfile = validateData($_GET['logfile']);
    } else {
        $currentLogfile = '';
    }

    // On récupère la liste des fichiers de logs en les triant 
    $logfiles = explode("\n", shell_exec("cd $MAIN_LOGS_DIR && ls -A1 'plan_'* | sort -t _ -k3 -r"));
    echo '<form action="run.php" method="get" class="is-inline-block">';
    echo '<select name="logfile" class="select-xxlarge">';
    echo "<option value=\"\">Historique de planifications</option>";
	foreach($logfiles as $logfile) {
        // on ne souhaite pas afficher les répertoires '..' '.' ni le fichier lastlog.log (déjà affiché en premier ci-dessus) et on souhaite uniquement afficher les fichier commencant par repomanager_
        if (($logfile != "..") AND ($logfile != ".") AND ($logfile != "lastlog.log") AND preg_match('/^plan_/', $logfile)) {
            // Formatage du nom du fichier afin d'afficher quelque chose de plus propre dans la liste
            $logfileDate = exec("echo $logfile | awk -F '_' '{print $3}'");
            $logfileDate = DateTime::createFromFormat('Y-m-d', $logfileDate)->format('d-m-Y');
            $logfileTime = exec("echo $logfile | awk -F '_' '{print $4}' | sed 's/.log//g'");
            $logfileTime = DateTime::createFromFormat('H-i-s', $logfileTime)->format('H:i:s');
            if ($logfile === $currentLogfile) {
                echo "<option value=\"${logfile}\" selected>Planification : traitement du $logfileDate à $logfileTime</option>";
            } else {
                echo "<option value=\"${logfile}\">Planification : traitement du $logfileDate à $logfileTime</option>";
            }
        }
	}
	echo '</select>';
	echo '<button type="submit" class="button-submit-xsmall-blue">Afficher</button>';
    echo '</form>';
    unset($logfiles, $logfile, $logfileDate, $logfileTime);
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
function showdiv_byid($divid) {
    echo '<script>';
    echo "$(document).ready(function() {";
    echo "$('#${divid}').show(); })";
    echo '</script>';
}

/**
 *  Anime (affiche ou ferme) une div en fournissant son id
 *  S'applique aux divs cachées dans le panel droit (gestion des sources, des groupes...)
 */
function animatediv_byid($divid) {
    echo "<script>
    $(document).ready(function(){
        $(\"#${divid}\").animate({
            width: '97%',
            padding: '10px',
            opacity: 1
        });
    });
    </script>";
}

/**
 *  Vérifie que la tâche cron des rappels de planifications est en place
 */
function checkCronReminder() {
    $cronStatus = shell_exec("crontab -l | grep 'send-reminders' | grep -v '#'");
    if (empty($cronStatus)) {
        return 'Off';
    } else {
        return 'On';
    }
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

    // Récupération du contenu de la crontab actuelle dans un fichier temporaire
    shell_exec("crontab -l > ${TEMP_DIR}/${WWW_USER}_crontab.tmp");

    // On supprime toutes les lignes concernant repomanager dans ce fichier pour refaire propre
    exec("sed -i '/cronjob_daily.php/d' ${TEMP_DIR}/${WWW_USER}_crontab.tmp");
    exec("sed -i '/plan.php/d' ${TEMP_DIR}/${WWW_USER}_crontab.tmp");

    // Puis on ajoute les tâches cron suivantes au fichier temporaire

    // Tâche cron journalière
    if ($CRON_DAILY_ENABLED == "yes") {
        file_put_contents("${TEMP_DIR}/${WWW_USER}_crontab.tmp", "*/5 * * * * php ${WWW_DIR}/operations/cronjob_daily.php".PHP_EOL, FILE_APPEND);
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

    printAlert('Tâches cron redéployées');
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
        echo '<td class="rl-30"></td>';
        echo '<td class="rl-30">Repo</td>';
        if ($OS_FAMILY == "Debian") {
            if ($repoListType == 'active') echo '<td class="rl-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
            echo '<td class="rl-30">Distribution</td>';
            if ($repoListType == 'active') echo '<td class="rl-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
            echo '<td class="rl-30">Section</td>';
        }
        if ($repoListType == 'active') { 
            echo '<td class="rl-30">Env</td>'; // On affiche l'env uniquement pour les repos actifs
            echo '<td class="rl-fit"></td>'; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
        }
        echo '<td class="rl-30">Date</td>';
        if ($printRepoSize == "yes") { // On affiche la taille des repos seulement si souhaité
            echo '<td class="rl-30">Taille</td>';
        }
        echo '<td class="rl-desc">Description</td>';
        echo '<td class="rl-fit"></td>';
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
            if ($OS_FAMILY == "Redhat") {
                printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
            }
            if ($OS_FAMILY == "Debian") {
                printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoEnv', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
            }
        }
        if ($repoListType == 'archived') {
            if ($OS_FAMILY == "Redhat") {
                printRepoLine(compact('repoId', 'repoName', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName'));
            }
            if ($OS_FAMILY == "Debian") {
                printRepoLine(compact('repoId', 'repoName', 'repoDist', 'repoSection', 'repoSource', 'repoDate', 'repoTime', 'repoDescription', 'repoType', 'repoSigned', 'repoLastName', 'repoLastDist', 'repoLastSection'));
            }
        }
        if (!empty($repoName)) { $repoLastName = $repoName; }
        if ($OS_FAMILY == "Debian") {
            if (!empty($repoDist)) { $repoLastDist = $repoDist; }
            if (!empty($repoSection)) { $repoLastSection = $repoSection; }
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
        echo '<td class="rl-30">';
            if ($repoListType == 'active') {
                /**
                 *  Affichage de l'icone "corbeille" pour supprimer le repo
                 */
                if ($OS_FAMILY == "Redhat") { // si rpm on doit présicer repoEnv dans l'url
                    echo "<a href=\"operation.php?action=delete&repoName=${repoName}&repoEnv=${repoEnv}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName} (${repoEnv})\" /></a>";
                }
                if ($OS_FAMILY == "Debian") {
                    echo "<a href=\"operation.php?action=delete&repoName=${repoName}\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo ${repoName}\" /></a>";
                }
                /**
                 *  Affichage de l'icone "dupliquer" pour dupliquer le repo
                 */
                if ($OS_FAMILY == "Redhat") {
                    echo "<a href=\"operation.php?action=duplicate&repoName=${repoName}&repoEnv=${repoEnv}&repoGroup=ask&repoDescription=ask\"><img class=\"icon-lowopacity\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} (${repoEnv})\" /></a>";
                }
                if ($OS_FAMILY == "Debian") {
                    echo "<a href=\"operation.php?action=duplicate&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}&repoGroup=ask&repoDescription=ask\"><img class=\"icon-lowopacity\" src=\"icons/duplicate.png\" title=\"Dupliquer le repo ${repoName} avec sa distribution ${repoDist} et sa section ${repoSection} (${repoEnv})\" /></a>";
                }
                /**
                 *  Affichage de l'icone "terminal" pour afficher la conf repo à mettre en place sur les serveurs
                 */
                echo "<img id=\"clientConfToggle${repoId}\" class=\"icon-lowopacity\" src=\"icons/code.png\" title=\"Afficher la configuration client\" />";
                /**
                 *  Affichage de l'icone 'update' pour mettre à jour le repo/section. On affiche seulement si l'env du repo/section = $DEFAULT_ENV et si il s'agit d'un miroir
                 */
                if ($repoType === "mirror" AND $repoEnv === $DEFAULT_ENV) {
                    if ($OS_FAMILY == "Redhat") {
                        echo "<a href=\"operation.php?action=update&repoName=${repoName}&repoGpgCheck=ask&repoGpgResign=ask\"><img class=\"icon-lowopacity\" src=\"icons/update.png\" title=\"Mettre à jour le repo ${repoName} (${repoEnv})\" /></a>";
                    }
                    if ($OS_FAMILY == "Debian") {
                        echo "<a href=\"operation.php?action=update&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoGpgCheck=ask&repoGpgResign=ask\"><img class=\"icon-lowopacity\" src=\"icons/update.png\" title=\"Mettre à jour la section ${repoSection} (${repoEnv})\" /></a>";
                    }
                }
            }
            if ($repoListType == 'archived') {
                if ($OS_FAMILY == "Redhat") { // si rpm on doit présicer repoEnv dans l'url
                    echo "<a href=\"operation.php?action=deleteArchive&repoName=${repoName}&repoDate=".DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d')."\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer le repo archivé ${repoName}\" /></a>";
                }
                if ($OS_FAMILY == "Debian") {
                    echo "<a href=\"operation.php?action=deleteArchive&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoDate=".DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d')."\"><img class=\"icon-lowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section archivée ${repoSection}\" /></a>";
                }
                // Affichage de l'icone "remise en production du repo"
                if ($OS_FAMILY == "Redhat") { // si rpm on doit présicer repoEnv dans l'url
                    echo "<a href=\"operation.php?action=restore&repoName=${repoName}&repoDate=".DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d')."&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"icons/arrow-up.png\" title=\"Remettre en production le repo archivé ${repoName} en date du ${repoDate}\" /></a>";
                }
                if ($OS_FAMILY == "Debian") {
                    echo "<a href=\"operation.php?action=restore&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoDate=".DateTime::createFromFormat('d-m-Y', $repoDate)->format('Y-m-d')."&repoDescription=${repoDescription}&repoNewEnv=ask\"><img class=\"icon-lowopacity-red\" src=\"icons/arrow-up.png\" title=\"Remettre en production la section archivée ${repoSection} en date du ${repoDate}\" /></a>";
                }
            }
        echo '</td>';

    /**
     *  Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent)
     */
    if ($concatenateReposName == "yes" AND $repoName === $repoLastName) {
        echo '<td class="rl-30"></td>';
    } else {
        echo "<td class=\"rl-30\">$repoName</td>";
    }
    if ($OS_FAMILY == "Debian") {
        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
        if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist) {
            if ($repoListType == 'active') echo '<td class="rl-fit"></td>';
            echo '<td class="rl-30"></td>';
        } else {
            if ($repoListType == 'active') echo "<td class=\"rl-fit\"><a href=\"operation.php?action=deleteDist&repoName=${repoName}&repoDist=${repoDist}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la distribution ${repoDist}\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque distribution
            echo "<td class=\"rl-30\">$repoDist</td>";
        }
        // Si la vue simplifiée est activée (masquage du nom de repo si similaire au précédent) :
        if ($concatenateReposName == "yes" AND $repoName === $repoLastName AND $repoDist === $repoLastDist AND $repoSection === $repoLastSection) {
            if ($repoListType == 'active') echo "<td class=\"rl-fit\"><a href=\"operation.php?action=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
            echo '<td class="rl-30"></td>';
        } else {
            if ($repoListType == 'active') echo "<td class=\"rl-fit\"><a href=\"operation.php?action=deleteSection&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}\"><img class=\"icon-verylowopacity-red\" src=\"icons/bin.png\" title=\"Supprimer la section ${repoSection} (${repoEnv})\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'corbeille' avant chaque section
            echo "<td class=\"rl-30\">$repoSection</td>";
        }
    }

    /**
     *  Affichage de l'env en couleur
     *  On regarde d'abord combien d'environnements sont configurés. Si il n'y a qu'un environement, l'env restera blanc.
     */
    if ($repoListType == 'active') {
        if ($DEFAULT_ENV === $LAST_ENV) { // Cas où il n'y a qu'un seul env
            echo "<td class=\"rl-red-bckg rl-30\"><span>$repoEnv</span></td>";
        } elseif ($repoEnv === $DEFAULT_ENV) {
            echo "<td class=\"rl-white-bckg rl-30\"><span>$repoEnv</span></td>";
        } elseif ($repoEnv === $LAST_ENV) {
            echo "<td class=\"rl-red-bckg rl-30\"><span>$repoEnv</span></td>";
        } else {
            echo "<td class=\"rl-white-bckg rl-30\"><span>$repoEnv</span></td>";
        }
        if ($ENVS_TOTAL > 1) {
            // Icone permettant d'ajouter un nouvel environnement, placée juste avant la date
            if ($OS_FAMILY == "Redhat") {
                echo "<td class=\"rl-fit\"><a href=\"operation.php?action=changeEnv&repoName=${repoName}&repoEnv=${repoEnv}&repoNewEnv=ask&repoDescription=ask\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur le repo $repoName du $repoDate\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
            }
            if ($OS_FAMILY == "Debian") {
                echo "<td class=\"rl-fit\"><a href=\"operation.php?action=changeEnv&repoName=${repoName}&repoDist=${repoDist}&repoSection=${repoSection}&repoEnv=${repoEnv}&repoNewEnv=ask&repoDescription=ask\"><img class=\"icon-verylowopacity-red\" src=\"icons/link.png\" title=\"Faire pointer un nouvel environnement sur la section $repoSection du $repoDate\" /></a></td>"; // td de toute petite taille, permettra d'afficher une icone 'link' avant chaque date
            }
        }
    }

    /**
     *  Affichage de la date
     */
    echo "<td class=\"rl-30\" title=\"$repoDate $repoTime\">$repoDate</td>";

    /**
     *  Affichage de la taille
     */
    if ($printRepoSize == "yes") {
        if ($repoListType == 'active') {
            if ($OS_FAMILY == "Redhat") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoDate}_${repoName} | awk '{print $1}'");
            }
            if ($OS_FAMILY == "Debian") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/${repoDate}_${repoSection} | awk '{print $1}'");
            }
        }

        if ($repoListType == 'archived') {
            if ($OS_FAMILY == "Redhat" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/archived_${repoDate}_${repoName} | awk '{print $1}'");
            }
            if ($OS_FAMILY == "Debian" AND $printRepoSize == "yes") {
                $repoSize = exec("du -hs ${REPOS_DIR}/${repoName}/${repoDist}/archived_${repoDate}_${repoSection} | awk '{print $1}'");
            }
        }

        echo "<td class=\"rl-30\">$repoSize</td>";
    }

    /**
     *  Affichage de la description
     */
    echo '<td class="rl-desc">';
    echo "<input type=\"text\" class=\"invisibleInput\" name=\"repoDescription\" value=\"$repoDescription\" />";
    echo '</td>';
    echo '<td class="rl-fit">';
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
         *  Affichage de l'icone "explorer"
         */
        if ($repoListType == 'active') {
            if ($OS_FAMILY == "Redhat") {
                echo "<a href=\"explore.php?id=${repoId}&state=active\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer le repo $repoName (${repoEnv})\" /></a>";
            }
            if ($OS_FAMILY == "Debian") {
                echo "<a href=\"explore.php?id=${repoId}&state=active\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer la section ${repoSection} (${repoEnv})\" /></a>";
            }
        }
        if ($repoListType == 'archived') {
            if ($OS_FAMILY == "Redhat") {
                echo "<a href=\"explore.php?id=${repoId}&state=archived\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer le repo $repoName archivé (${repoDate})\" /></a>";
            }
            if ($OS_FAMILY == "Debian") {
                echo "<a href=\"explore.php?id=${repoId}&state=archived\"><img class=\"icon-lowopacity\" src=\"icons/search.png\" title=\"Explorer la section archivée ${repoSection} (${repoDate})\" /></a>";
            }
        }
        /**
         *  Affichage de l'icone "warning" si le répertoire du repo n'existe plus sur le serveur
         */
        if ($repoListType == 'active') {
            if ($OS_FAMILY == "Redhat") {
                if (!is_dir("$REPOS_DIR/${repoDate}_${repoName}")) {
                    echo '<img class="icon-lowopacity" src="icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
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
                    echo '<img class="icon-lowopacity" src="icons/warning.png" title="Le répertoire de ce repo semble inexistant sur le serveur" />';
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

    /**
     *  Affichage de la configuration installable sur les serveurs clients, placée dans une div cachée
     */
    if ($repoListType == 'active') {
        echo '<tr>';
            echo '<td colspan="100%">';
                echo "<div id=\"clientConfDiv${repoId}\" class=\"divReposConf\">";
                echo '<h3>INSTALLATION</h3>';
                echo '<p>Exécuter ces commandes directement dans le terminal de la machine cliente :</p>';
                echo '<pre>';
                if ($OS_FAMILY == "Redhat") {
                    echo "echo -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\n[${REPO_CONF_FILES_PREFIX}${repoName}_${repoEnv}]\nname=Repo ${repoName} sur ${WWW_HOSTNAME}\ncomment=Repo ${repoName} sur ${WWW_HOSTNAME}\nbaseurl=${WWW_REPOS_DIR_URL}/${repoName}_${repoEnv}\nenabled=1\ngpgkey=${WWW_REPOS_DIR_URL}/gpgkeys/${WWW_HOSTNAME}.pub\ngpgcheck=1' > /etc/yum.repos.d/${REPO_CONF_FILES_PREFIX}${repoName}.repo";
                }
                if ($OS_FAMILY == "Debian") {
                    echo "wget -qO ${WWW_REPOS_DIR_URL}/gpgkeys/${WWW_HOSTNAME}.pub | sudo apt-key add -\n\necho -e '# Repo ${repoName} (${repoEnv}) sur ${WWW_HOSTNAME}\ndeb ${WWW_REPOS_DIR_URL}/${repoName}/${repoDist}/${repoSection}_${repoEnv} ${repoDist} ${repoSection}' > /etc/apt/sources.list.d/${REPO_CONF_FILES_PREFIX}${repoName}_${repoDist}_${repoSection}.list";
                }
                echo '</pre>';
                echo '</div>';
                /**
                 *  Script JS pour afficher ou masquer la div
                 */
                echo '<script>';
                echo '$(document).ready(function(){';
                echo "$(\"#clientConfToggle${repoId}\").click(function(){";
                echo "$(\"#clientConfDiv${repoId}\").slideToggle(250);";
                echo '$(this).toggleClass("open");';
                echo '});';
                echo '});';
                echo '</script>';
            echo '</td>';
        echo '</tr>';
    }
}
?>