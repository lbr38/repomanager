<?php

// Fonction de vérification des données envoyées par formulaire
function validateData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function clearCache($WWW_CACHE) {
    // Suppression du cache serveur
    // 2 cas possibles : 
    // il s'agit d'un répertoire classique sur le disque
    // ou il s'agit d'un lien symbolique vers /dev/smh (en ram)
    if (file_exists("${WWW_CACHE}/repos-list-filter-group.html")) { unlink("${WWW_CACHE}/repos-list-filter-group.html"); }
    if (file_exists("${WWW_CACHE}/repos-list-no-filter.html")) { unlink("${WWW_CACHE}/repos-list-no-filter.html"); }
    if (is_link($WWW_CACHE)) { unlink($WWW_CACHE); }
    if (is_dir($WWW_CACHE)) { rmdir($WWW_CACHE); }

    // Vidage du cache navigateur
    echo "<script>";
    echo "Clear-Site-Data: \"*\";";
    echo "</script>";
}

// Fonction permettant d'afficher une bulle d'alerte au mileu de la page
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

// Fonction affichant un message de confirmation avant de supprimer
// $message = le message à afficher
// $url = lien GET vers la page de suppression
// $divID = un id unique du div caché contenant le message et les bouton supprimer ou annuler
// $aID = une class avec un ID unique du bouton cliquable permettant d'afficher/fermer la div caché. Attention le bouton d'affichage doit être avant l'appel de cette fonction.
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

function operationRunning() {
    global $PID_DIR;

    $datas = array();
    $pidFiles = shell_exec("grep -l 'repomanager_' ${PID_DIR}/*.pid");
    if (!empty($pidFiles)) {
        $pidFiles = explode("\n", trim($pidFiles));
        foreach($pidFiles as $pidFile) {
            $pid     = exec("grep -h '^PID=' $pidFile | sed 's/PID=//g' | sed 's/\"//g'");
            $logFile = exec("grep -h '^LOG=' $pidFile | sed 's/LOG=//g' | sed 's/\"//g'");
            $action  = exec("grep -h '^ACTION=' $pidFile | sed 's/ACTION=//g' | sed 's/\"//g'");
            $name    = exec("grep -h '^NAME=' $pidFile | sed 's/NAME=//g' | sed 's/\"//g'");
            $dist    = exec("grep -h '^DIST=' $pidFile | sed 's/DIST=//g' | sed 's/\"//g'");
            $section = exec("grep -h '^SECTION=' $pidFile | sed 's/SECTION=//g' | sed 's/\"//g'");
            $data = ['pidFile' => $pidFile, 'pid' => $pid , 'logFile' => $logFile, 'action' => $action, 'name' => $name, 'dist' => $dist, 'section' => $section];
            $datas[] = $data;
        }
        return $datas;
    }
    return false;
}

function planificationRunning() {
    global $PID_DIR;

    $datas = array();
    $pidFiles = shell_exec("grep -l 'plan_' ${PID_DIR}/*.pid");
    if (!empty($pidFiles)) {
        $pidFiles = explode("\n", trim($pidFiles));
        foreach($pidFiles as $pidFile) {
            $pid     = exec("grep -h '^PID=' $pidFile | sed 's/PID=//g' | sed 's/\"//g'");
            $logFile = exec("grep -h '^LOG=' $pidFile | sed 's/LOG=//g' | sed 's/\"//g'");
            $action  = exec("grep -h '^ACTION=' $pidFile | sed 's/ACTION=//g' | sed 's/\"//g'");
            $name    = exec("grep -h '^NAME=' $pidFile | sed 's/NAME=//g' | sed 's/\"//g'");
            $dist    = exec("grep -h '^DIST=' $pidFile | sed 's/DIST=//g' | sed 's/\"//g'");
            $section = exec("grep -h '^SECTION=' $pidFile | sed 's/SECTION=//g' | sed 's/\"//g'");
            $data = ['pidFile' => $pidFile, 'pid' => $pid , 'logFile' => $logFile, 'action' => $action, 'name' => $name, 'dist' => $dist, 'section' => $section];
            $datas[] = $data;
        }
        return $datas;
    }

/*  if (!empty(exec("grep 'plan_' ${PID_DIR}/*.pid"))) {
        return true;
    }*/
    return false;
}

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
        if (($logfile != "..") AND ($logfile != ".") AND ($logfile != "lastlog.log") AND preg_match('/^plan_/',$logfile)) {
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

function reloadPage($actual_uri) {
    header("location: $actual_uri");
}

// Rechargement d'une div en fournissant sa class
function refreshdiv_class($divclass) {
    if (!empty($divclass)) {
        echo '<script>';
        echo "$( \".${divclass}\" ).load(window.location.href + \" .${divclass}\" );";
        echo '</script>';
    }
}

// Affichage d'une div cachée
function showdiv_byclass($divclass) {
    echo '<script>';
    echo "$(document).ready(function() {";
    echo "$('.${divclass}').show(); })";
    echo '</script>';
}

function showdiv_byid($divid) {
    echo '<script>';
    echo "$(document).ready(function() {";
    echo "$('#${divid}').show(); })";
    echo '</script>';
}

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


function checkCronReminder() {
    $cronStatus = shell_exec("crontab -l | grep 'planifications/plan.php' | grep -v '#'");
    if (empty($cronStatus)) {
        return 'Off';
    } else {
        return 'On';
    }
}

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

// Ecrit le contenu de la crontab de $WWW_USER
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
?>