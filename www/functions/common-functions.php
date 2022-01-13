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
 *  Fonction de vérification de l'adresse email
 */
function validateMail(string $mail) {
    $mail = trim($mail);

    if (filter_var($mail, FILTER_VALIDATE_EMAIL)) return true;

    return false;
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
     *  Si une chaine vide a été transmise alors c'est valide
     */
    if (empty($data)) {
        return true;
    }

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
        //printAlert('Vous ne pouvez renseigner que des chiffres, des lettres ou des tirets', 'error');
        throw new Exception('Vous ne pouvez renseigner que des chiffres, des lettres ou des tirets');
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
    if (file_exists(WWW_CACHE."/repomanager-repos-list.html")) unlink(WWW_CACHE."/repomanager-repos-list.html");
    if (file_exists(WWW_CACHE."/repomanager-repos-archived-list.html")) unlink(WWW_CACHE."/repomanager-repos-archived-list.html");
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
            echo "<a href=\"$url\"><span class=\"btn-doDelete\">Supprimer</span></a>";
            echo "<span class=\"$aID btn-cancelDelete pointer\">Annuler</span>";
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
    if ($env == LAST_ENV)
        return '<span class="last-env">'.$env.'</span>';
    else
        return '<span class="env">'.$env.'</span>';
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
 *  Inscrit les tâches cron dans la crontab de WWW_USER
 */
function enableCron() {

    // Récupération du contenu de la crontab actuelle dans un fichier temporaire
    shell_exec("crontab -l > ".TEMP_DIR."/".WWW_USER."_crontab.tmp");

    // On supprime toutes les lignes concernant repomanager dans ce fichier pour refaire propre
    exec("sed -i '/cronjob.php/d' ".TEMP_DIR."/".WWW_USER."_crontab.tmp");
    exec("sed -i '/plan.php/d' ".TEMP_DIR."/".WWW_USER."_crontab.tmp");
    exec("sed -i '/stats.php/d' ".TEMP_DIR."/".WWW_USER."_crontab.tmp");

    // Puis on ajoute les tâches cron suivantes au fichier temporaire

    // Tâche cron journalière
    if (CRON_DAILY_ENABLED == "yes") {
        file_put_contents(TEMP_DIR."/".WWW_USER."_crontab.tmp", "*/5 * * * * php ".ROOT."/operations/cronjob.php".PHP_EOL, FILE_APPEND);
    }

    // Statistiques
    if (CRON_STATS_ENABLED == "yes") {
        file_put_contents(TEMP_DIR."/".WWW_USER."_crontab.tmp", "0 0 * * * php ".ROOT."/operations/stats.php".PHP_EOL, FILE_APPEND);
    }

    // si on a activé l'automatisation alors on ajoute la tâche cron d'exécution des planifications
    if (AUTOMATISATION_ENABLED == "yes") {
        file_put_contents(TEMP_DIR."/".WWW_USER."_crontab.tmp", "* * * * * php ".ROOT."/planifications/plan.php exec-plans".PHP_EOL, FILE_APPEND);
    }

    // si on a activé l'automatisation et les envois de rappels de planifications alors on ajoute la tâche cron d'envoi des rappels
    if (AUTOMATISATION_ENABLED == "yes" AND CRON_PLAN_REMINDERS_ENABLED == "yes") {
        file_put_contents(TEMP_DIR."/".WWW_USER."_crontab.tmp", "0 0 * * * php ".ROOT."/planifications/plan.php send-reminders".PHP_EOL, FILE_APPEND);
    }

    // Enfin on reimporte le contenu du fichier temporaire
    exec("crontab ".TEMP_DIR."/".WWW_USER."_crontab.tmp");   // on importe le fichier dans la crontab de WWW_USER
    unlink(TEMP_DIR."/".WWW_USER."_crontab.tmp");         // puis on supprime le fichier temporaire

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
    exec("/usr/bin/pkill -9 -u ".WWW_USER." -f 'tail -n0 -F ".WWW_STATS_LOG_PATH."'");
} ?>