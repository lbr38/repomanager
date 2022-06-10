<?php

namespace Models;

use Exception;

/**
 *  Classe regroupant quelques fonctions communes / génériques
 */

class Common
{
    /**
     *  Fonction de vérification / conversion des données envoyées par formulaire
     */
    public static function validateData($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /**
     *  Fonction de vérification du format d'une adresse email
     */
    public static function validateMail(string $mail)
    {
        $mail = trim($mail);

        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }

    /**
     *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres
     */
    public static function isAlphanum(string $data, array $additionnalValidCaracters = [])
    {
        /**
         *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ignore dans le test en les remplacant temporairement par du vide
         */
        if (!empty($additionnalValidCaracters)) {
            if (!ctype_alnum(str_replace($additionnalValidCaracters, '', $data))) {
                return false;
            }

        /**
         *  Si on n'a pas passé de caractères supplémentaires alors on teste simplement la chaine avec ctype_alnum
         */
        } else {
            if (!ctype_alnum($data)) {
                ;
                return false;
            }
        }

        return true;
    }

    /**
     *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres, un underscore ou un tiret
     *  Retire temporairement les tirets et underscore de la chaine passée afin qu'elle soit ensuite testée par la fonction PHP ctype_alnum
     */
    public static function isAlphanumDash(string $data, array $additionnalValidCaracters = [])
    {
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

        if (!ctype_alnum(str_replace($validCaracters, '', $data))) {
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
    public static function clearCache()
    {
        /**
         *  Suppression de tous les fichiers commencant par 'repomanager-repos-list' dans le répertoire de cache
         */
        $files = glob(WWW_CACHE . '/repomanager-repos-*');

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public static function generateCache(string $role)
    {
        ob_start();
        include(ROOT . '/includes/repos-active-list.inc.php');
        $content = ob_get_clean();
        file_put_contents(WWW_CACHE . '/repomanager-repos-list-' . $role . '.html', $content);

        // if (empty($role)) {

        //     $roles = array('super-administrator', 'administrator', 'usage');

        //     foreach ($roles as $role) {
        //         define('GENERATE_CACHE_ROLE', $role);
        //         ob_start();
        //         include(ROOT . '/includes/repos-active-list.inc.php');
        //         $content = ob_get_clean();
        //         file_put_contents(WWW_CACHE . '/repomanager-repos-list-' . $role . '.html', $content);
        //     }
        // } else {
        //     define('GENERATE_CACHE_ROLE', $role);

        //     ob_start();
        //     include(ROOT . '/includes/repos-active-list.inc.php');
        //     $content = ob_get_clean();
        //     file_put_contents(WWW_CACHE . '/repomanager-repos-list-' . $role . '.html', $content);
        // }
    }

    /**
     *  Fonction permettant d'afficher une bulle d'alerte en bas de l'écran
     */
    public static function printAlert(string $message, string $alertType = null)
    {
        if ($alertType == "error") {
            echo '<div class="alert-error">';
        }
        if ($alertType == "success") {
            echo '<div class="alert-success">';
        }
        if (empty($alertType)) {
            echo '<div class="alert">';
        }

        echo "<span>$message</span>";
        echo '</div>';

        echo '<script type="text/javascript">';
        echo '$(document).ready(function () {';
        echo 'window.setTimeout(function() {';
        if ($alertType == "error" or $alertType == "success") {
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
    public static function deleteConfirm(string $message, string $url, $divID, $aID)
    {
        echo "<div id=\"$divID\" class=\"hide confirmAlert\">";
            echo "<span class=\"confirmAlert-message\">$message</span>";
            echo '<div class="confirmAlert-buttons-container">';
                echo "<a href=\"$url\"><span class=\"btn-doConfirm\">Supprimer</span></a>";
                echo "<span class=\"$aID btn-doCancel pointer\">Annuler</span>";
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
     *  Affiche une erreur générique ou personnalisée lorsqu'il y a eu une erreur d'exécution d'une requête dans la base de données
     *  Ajoute une copie de l'erreur dans le fichier de logs 'exceptions'
     */
    public static function dbError(string $exception = null)
    {
        /**
         *  Date et heure de l'évènement
         */
        $content = PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL;
        /**
         *  Récupération du nom du fichier ayant fait appel à cette fonction
         */
        $content .= 'File : ' . debug_backtrace()[0]['file'] . PHP_EOL;

        /**
         *  Si une exception a été catchée, on va l'ajouter au contenu
         */
        if (!empty($exception)) {
            $content .= 'Error catched ¬' . PHP_EOL . $exception . PHP_EOL;
        }
        /**
         *  Ajout du contenu au fichier de log
         */
        $content .= '___________________________' . PHP_EOL;
        file_put_contents(EXCEPTIONS_LOG, $content, FILE_APPEND);

        /**
         *  Lancement d'une exception qui sera catchée par printAlert
         *  Si le mode debug est activé alors on affiche l'exception dans le message d'erreur
         */
        if (!empty($exception) and DEBUG_MODE == 'enabled') {
            throw new Exception('Une erreur est survenue lors de l\'exécution de la requête en base de données <br>' . $exception . '<br>');
        } else {
            throw new Exception('Une erreur est survenue lors de l\'exécution de la requête en base de données <br>');
        }
    }

    /**
     *  Colore l'environnement d'une étiquette rouge ou blanche
     */
    public static function envtag($env, $css = null)
    {
        if ($env == LAST_ENV) {
            $class = 'last-env';
        } else {
            $class = 'env';
        }

        if ($css == 'fit') {
            $class .= '-fit';
        }

        return '<span class="' . $class . '">' . $env . '</span>';
    }

    /**
     *  Vérifie que la tâche cron des rappels de planifications est en place
     */
    public static function checkCronReminder()
    {
        $cronStatus = shell_exec("crontab -l | grep 'send-reminders' | grep -v '#'");
        if (empty($cronStatus)) {
            return 'Off';
        }

        return 'On';
    }

    public static function writeToIni($file, $array = [])
    {
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
                                $data[] = $skey . '[] = ' . (is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"' . $_sval . '"'));
                            } else {
                                $data[] = $skey . '[' . $_skey . '] = ' . (is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"' . $_sval . '"'));
                            }
                        }
                    } else {
                        $data[] = $skey . ' = ' . (is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"' . $sval . '"'));
                    }
                }
            } else {
                $data[] = $key . ' = ' . (is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"' . $val . '"'));
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
        fwrite($fp, implode(PHP_EOL, $data) . PHP_EOL);
        // release lock
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    /**
     *  Inscrit les tâches cron dans la crontab de WWW_USER
     */
    public static function enableCron()
    {
        /**
         *  Récupération du contenu de la crontab actuelle dans un fichier temporaire
         */
        shell_exec("crontab -l > " . TEMP_DIR . "/" . WWW_USER . "_crontab.tmp");

        /**
         *  On supprime toutes les lignes concernant repomanager dans ce fichier pour refaire propre
         */
        exec("sed -i '/cronjob.php/d' " . TEMP_DIR . "/" . WWW_USER . "_crontab.tmp");
        exec("sed -i '/plan.php/d' " . TEMP_DIR . "/" . WWW_USER . "_crontab.tmp");
        exec("sed -i '/stats-generator.php/d' " . TEMP_DIR . "/" . WWW_USER . "_crontab.tmp");

        /**
         *  Puis on ajoute les tâches cron suivantes au fichier temporaire
         */

        // Tâche cron journalière
        if (CRON_DAILY_ENABLED == "yes") {
            file_put_contents(TEMP_DIR . "/" . WWW_USER . "_crontab.tmp", "*/5 * * * * php " . ROOT . "/operations/cronjob.php" . PHP_EOL, FILE_APPEND);
        }

        // Statistiques
        if (CRON_STATS_ENABLED == "yes") {
            file_put_contents(TEMP_DIR . "/" . WWW_USER . "_crontab.tmp", "0 0 * * * php " . ROOT . "/tools/stats-generator.php" . PHP_EOL, FILE_APPEND);
        }

        // si on a activé l'automatisation alors on ajoute la tâche cron d'exécution des planifications
        if (AUTOMATISATION_ENABLED == "yes") {
            file_put_contents(TEMP_DIR . "/" . WWW_USER . "_crontab.tmp", "* * * * * php " . ROOT . "/operations/plan.php exec" . PHP_EOL, FILE_APPEND);
        }

        // si on a activé l'automatisation et les envois de rappels de planifications alors on ajoute la tâche cron d'envoi des rappels
        if (AUTOMATISATION_ENABLED == "yes" and CRON_PLAN_REMINDERS_ENABLED == "yes") {
            file_put_contents(TEMP_DIR . "/" . WWW_USER . "_crontab.tmp", "0 0 * * * php " . ROOT . "/operations/plan.php send-reminders" . PHP_EOL, FILE_APPEND);
        }

        /**
         *  Enfin on reimporte le contenu du fichier temporaire
         */
        exec("crontab " . TEMP_DIR . "/" . WWW_USER . "_crontab.tmp");   // on importe le fichier dans la crontab de WWW_USER
        unlink(TEMP_DIR . "/" . WWW_USER . "_crontab.tmp");         // puis on supprime le fichier temporaire

        Common::printAlert('Tâches cron redéployées', 'success');
    }

    /**
     *  Indique si un répertoire est vide ou non
     */
    public static function dirIsEmpty($dir)
    {
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
     *  Stoppe le process bash de génération des statistiques
     */
    public static function killStatsLogParser()
    {
        exec("/usr/bin/pkill -9 -u " . WWW_USER . " -f 'tail -n0 -F " . WWW_STATS_LOG_PATH . "'");
    }

    /**
     *  Renvoi true si le role spécifié ou la session utilisateur en cours est administrateur
     */
    public static function isadmin()
    {
        if (defined('GENERATE_CACHE_ROLE')) {
            if (GENERATE_CACHE_ROLE === 'super-administrator' or GENERATE_CACHE_ROLE === 'administrator') {
                return true;
            }

            return false;
        }

        if ($_SESSION['role'] === 'super-administrator' or $_SESSION['role'] === 'administrator') {
            return true;
        }

        return false;
    }

    /**
     *  Génère un nombre aléatoire en 1000 et 99999
     */
    public static function generateRandom()
    {
        return mt_rand(1000, 99999);
    }

    /**
     *  Mise à jour de repomanager
     */
    public static function repomanagerUpdate()
    {
        $error = 0;

        /**
         *  Création d'un fichier 'update-running' afin de mettre une maintenance sur le site
         */
        if (!file_exists(ROOT . "/update-running")) {
            touch(ROOT . "/update-running");
        }

        /**
         *  Backup avant mise à jour
         */
        if (UPDATE_BACKUP_ENABLED == "yes") {
            $backupName = DATE_YMD . '_' . TIME . '_repomanager_full_backup.tar.gz';
            exec("tar --exclude='" . BACKUP_DIR . "' --exclude='" . ROOT . "/db' -czf /tmp/${backupName} " . ROOT, $output, $result);
            if ($result != 0) {
                $error++;
                $errorMsg = 'Erreur lors de la sauvegarde de la configuration actuelle de repomanager';
            } else {
                exec("mv /tmp/${backupName} " . BACKUP_DIR . "/");
            }
        }
        /**
         *  Création du répertoire du script de mise à jour si n'existe pas
         */
        if ($error == 0) {
            if (!is_dir(ROOT . "/update")) {
                if (!mkdir(ROOT . "/update", 0770, true)) {
                    $error++;
                    $errorMsg = "Erreur : impossible de créer le répertoire " . ROOT . "/update";
                }
            }
        }
        /**
         *  On récupère la dernière version du script de mise à jour avant de l'exécuter
         */
        if ($error == 0) {
            exec("wget https://raw.githubusercontent.com/lbr38/repomanager/" . UPDATE_BRANCH . "/www/tools/repomanager-update -O " . ROOT . "/tools/repomanager-update", $output, $result);
            if ($result != 0) {
                $error++;
                $errorMsg = 'Erreur pendant le téléchargement de la mise à jour';
            }
        }
        /**
         *  Exécution de la mise à jour
         */
        if ($error == 0) {
            exec("bash " . ROOT . "/tools/repomanager-update", $output, $result);
            if ($result != 0) {
                $error++;
                if ($result == 1) {
                    $errorMsg = "Erreur : numéro de version disponible sur github inconnu";
                }
                if ($result == 2) {
                    $errorMsg = "Erreur lors du téléchargement de la mise à jour " . GIT_VERSION . " (https://github.com/lbr38/repomanager/releases/download/" . GIT_VERSION . "/repomanager_" . GIT_VERSION . ".tar.gz)";
                }
                if ($result == 3) {
                    $errorMsg = "Erreur lors de l'extraction de la mise à jour";
                }
                if ($result == 4) {
                    $errorMsg = "Erreur lors de l'application de la mise à jour";
                }
            }
        }
        /**
         *  Création du message à afficher à l'utilisateur
         */
        if ($error == 0) {
            $updateStatus = '<span class="greentext">Mise à jour effectuée avec succès!</span>';
        } else {
            $updateStatus = '<span class="redtext">' . $errorMsg . '</span>';
        }
        /**
         *  Suppression du fichier 'update-running' pour lever la maintenance
         */
        if (file_exists(ROOT . "/update-running")) {
            unlink(ROOT . "/update-running");
        }

        return $updateStatus;
    }

    /**
     *  Modification des paramètres d'affichage de la liste des repos
     */
    public static function configureReposListDisplay(string $printRepoSize, string $printRepoType, string $printRepoSignature, string $cacheReposList)
    {
        /**
         *  On vérifie que la valeur des paramètres est 'yes' ou 'no'
         */
        if ($printRepoSize != 'yes' and $printRepoSize != 'no') {
            throw new Exception("Le paramètre d'affichage de la taille du repo est invalide");
        }

        if ($printRepoType != 'yes' and $printRepoType != 'no') {
            throw new Exception("Le paramètre d'affichage du type du repo est invalide");
        }

        if ($printRepoSignature != 'yes' and $printRepoSignature != 'no') {
            throw new Exception("Le paramètre d'affichage de la signature du repo est invalide");
        }

        if ($cacheReposList != 'yes' and $cacheReposList != 'no') {
            throw new Exception('Le paramètre de mise en cache est invalide');
        }

        /**
         *  Ouverture d'une connexion à la base de données
         */
        $myconn = new Connection('main');

        /**
         *  Modification des paramètres en base de données
         */
        try {
            $stmt = $myconn->prepare("UPDATE repos_list_settings SET print_repo_size = :printRepoSize, print_repo_type = :printRepoType, print_repo_signature = :printRepoSignature, cache_repos_list = :cacheReposList");
            $stmt->bindValue(':printRepoSize', $printRepoSize);
            $stmt->bindValue(':printRepoType', $printRepoType);
            $stmt->bindValue(':printRepoSignature', $printRepoSignature);
            $stmt->bindValue(':cacheReposList', $cacheReposList);
            $stmt->execute();
        } catch (\Exception $e) {
            Common::dbError($e);
        }

        $myconn->close();

        /**
         *  On supprime le cache
         */
        Common::clearCache();

        return true;
    }
}
