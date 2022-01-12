<!DOCTYPE html>
<html>
<?php
include_once('includes/head.inc.php');
require_once('models/Autoloader.php');
Autoloader::loadAll();
require_once('functions/common-functions.php');

/**
 *  Mise à jour de Repomanager
 */
if (!empty($_GET['action']) AND validateData($_GET['action']) == "update") {
    $error = 0;

    /**
     *  Création d'un fichier 'update-running' afin de mettre une maintenance sur le site
     */
    if (!file_exists(ROOT."/update-running")) touch(ROOT."/update-running");

    /**
     *  Backup avant mise à jour
     */
    if (UPDATE_BACKUP_ENABLED == "yes") {
        $backupName = DATE_YMD."_".TIME."_repomanager_full_backup.tar.gz";
        exec("tar --exclude='".BACKUP_DIR."' -czf /tmp/${backupName} ". ROOT ,$output, $result);
        if ($result != 0) {
            $error++;
            $errorMsg = 'Erreur lors de la sauvegarde de la configuration actuelle de repomanager';
        } else {
            exec("mv /tmp/${backupName} ".BACKUP_DIR."/");
        }
    }
    /**
     *  Création du répertoire du script de mise à jour si n'existe pas
     */
    if ($error == 0) {
        if (!is_dir(ROOT."/update")) {
            if (!mkdir(ROOT."/update", 0770, true)) {
                $error++;
                $errorMsg = "Erreur : impossible de créer le répertoire ".ROOT."/update";
            }
        }
    }
    /**
     *  On récupère la dernière version du script de mise à jour avant de l'exécuter
     */
    if ($error == 0) {
        exec("wget https://raw.githubusercontent.com/lbr38/repomanager/".UPDATE_BRANCH."/www/tools/repomanager-update -O ".ROOT."/tools/repomanager-update", $output, $result);
        if ($result != 0) {
            $error++;
            $errorMsg = 'Erreur pendant le téléchargement de la mise à jour';
        }
    }
    /**
     *  Exécution de la mise à jour
     */
    if ($error == 0) {    
        exec("bash ".ROOT."/tools/repomanager-update", $output, $result);
        if ($result != 0) {
            $error++;
            if ($result == 1) $errorMsg = "Erreur : numéro de version disponible sur github inconnu";
            if ($result == 2) $errorMsg = "Erreur lors du téléchargement de la mise à jour ".GIT_VERSION." (https://github.com/lbr38/repomanager/releases/download/".GIT_VERSION."/repomanager_".GIT_VERSION.".tar.gz)";
            if ($result == 3) $errorMsg = "Erreur lors de l'extraction de la mise à jour";
            if ($result == 4) $errorMsg = "Erreur lors de l'application de la mise à jour";
        }
    }
    /**
     *  Création du message à afficher à l'utilisateur
     */
    if ($error == 0)
        $updateStatus = '<span class="greentext">Mise à jour effectuée avec succès!</span>';
    else
        $updateStatus = '<span class="redtext">'.$errorMsg.'</span>';   
    /**
     *  Suppression du fichier 'update-running' pour lever la maintenance
     */
    if (file_exists(ROOT."/update-running")) unlink(ROOT."/update-running");
}

// Si un des formulaires de la page a été validé alors on entre dans cette condition
if (!empty($_POST['action']) AND validateData($_POST['action']) === "applyConfiguration") {

    // Récupération de tous les paramètres définis dans le fichier repomanager.conf
    $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF, true);

/**
 *  Section PATHS
 */
    /**
     *  Chemin du répertoire des repos sur le serveur
     */
    if (!empty($_POST['reposDir'])) {
        $reposDir = validateData($_POST['reposDir']);
        /**
         *  Le chemin ne doit comporter que des lettres, des chiffres, des tirets et des slashs
         */
        if (is_alphanumdash($reposDir, array('/'))) {
            /**
             *  Suppression du dernier slash si il y en a un
             */
            $repomanager_conf_array['PATHS']['REPOS_DIR'] = rtrim($reposDir, '/');
        }
    }

/**
 *  Section CONFIGURATION
 */

    /**
     *  Adresse mail destinatrice des alertes
     */
    if (!empty($_POST['emailDest'])) {
        $emailDest = validateData($_POST['emailDest']);

        if (is_alphanumdash($emailDest, array('@', '.'))) {
            $repomanager_conf_array['CONFIGURATION']['EMAIL_DEST'] = trim($emailDest);
        }
    }

    /**
     *  Si on souhaite activer ou non la gestion des hôtes
     */
    if (!empty($_POST['manageHosts']) AND validateData($_POST['manageHosts']) === "yes") {
        $repomanager_conf_array['CONFIGURATION']['MANAGE_HOSTS'] = 'yes';
    } else {
        $repomanager_conf_array['CONFIGURATION']['MANAGE_HOSTS'] = 'no';
    }

    /**
     *  Si on souhaite activer ou non la gestion des profils
     */
    if (!empty($_POST['manageProfiles']) AND validateData($_POST['manageProfiles']) === "yes") {
        $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = 'yes';
    } else {
        $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = 'no';
    }

    /**
     *  Modification du préfix des fichiers de conf repos
     */
    // On conserve le préfix actuel car on va s'en servir pour renommer les fichiers de conf ci dessous
    $oldRepoFilesPrefix = REPO_CONF_FILES_PREFIX;

    // on ne traite que si on a renseigné un nouveau préfix :
    if(!empty($_POST['symlinksPrefix']) AND ($oldRepoFilesPrefix !== validateData($_POST['symlinksPrefix']))) {
        $newRepoFilesPrefix = validateData($_POST['symlinksPrefix']);
        $confFiles = scandir(REPOS_PROFILES_CONF_DIR);
        foreach($confFiles as $confFile) {
            if (($confFile != "..") AND ($confFile != ".")) {
                // remplace les occurence de l'ancien préfix par le nouveau à l'intérieur du fichier
                exec("sed -i 's/${oldRepoFilesPrefix}/${newRepoFilesPrefix}/g' $confFile");
                // renomme le fichier en remplacant l'ancien prefix par le nouveau :
                $pattern = "/^${oldRepoFilesPrefix}/";
                $newConfFile = preg_replace($pattern, $newRepoFilesPrefix, $confFile);
                rename(REPOS_PROFILES_CONF_DIR."/$confFile", REPOS_PROFILES_CONF_DIR."/${newConfFile}");
            }
        }

        // renomme les liens symboliques des profils :
        $profilesNames = scandir(PROFILES_MAIN_DIR);
        foreach($profilesNames as $profileName) {
            if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "main")) {
                $profileName_dir = PROFILES_MAIN_DIR."/$profileName";
                $repoConfFiles = scandir($profileName_dir);
                
                // Pour chaque répertoire de profil sur le serveur, on récupère les noms de fichier de conf (.repo ou .list selon l'OS) :
                foreach($repoConfFiles as $symlink) {
                    if (($symlink != "..") AND ($symlink != ".") AND ($symlink != "config")) {
                        $pattern = "/^${oldRepoFilesPrefix}/";
                        $newSymlinkName = preg_replace($pattern, $newRepoFilesPrefix, $symlink);
                        
                        // suppression du symlink :
                        if (file_exists(PROFILES_MAIN_DIR."/${profileName}/${symlink}")) {
                            unlink(PROFILES_MAIN_DIR."/${profileName}/${symlink}");
                        }
                        
                        // création du nouveau avec le nouveau prefix :
                        exec("cd ".PROFILES_MAIN_DIR."/${profileName}/ && ln -sfn ".REPOS_PROFILES_CONF_DIR."/${newSymlinkName}");
                    }
                }
            }
        }
        
        // enfin, on remplace le préfix dans le fichier de conf repomanager.conf
        $repomanager_conf_array['CONFIGURATION']['REPO_CONF_FILES_PREFIX'] = "$newRepoFilesPrefix";
    }

/**
 *  Section GPG
 */

    /**
     *  Activer/désactiver la signature des paquets/des repos avec GPG
     */
    if (!empty($_POST['gpgSignPackages']) AND validateData($_POST['gpgSignPackages']) === "yes") {
        $repomanager_conf_array['GPG']['GPG_SIGN_PACKAGES'] = 'yes';
    } else {
        $repomanager_conf_array['GPG']['GPG_SIGN_PACKAGES'] = 'no';
    }
    
    /**
     *  Email lié à la clé GPG qui signe les paquets/les repos
     */
    if (!empty($_POST['gpgKeyID'])) {
        $gpgKeyID = validateData($_POST['gpgKeyID']);

        if (is_alphanumdash($gpgKeyID, array('@', '.'))) {
            $repomanager_conf_array['GPG']['GPG_KEYID'] = trim($gpgKeyID);
        }
    }

/**
 *  Section UPDATE
 */

    /**
     *  Activer / désactiver la mise à jour automatique de repomanager
     */
    if (!empty($_POST['updateAuto']) AND validateData($_POST['updateAuto']) === "yes") {
        $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'yes';
    } else {
        $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'no';
    }

    /**
     *  Activer / désactiver le backup de repomanager avant mise à jour
     */
    if (!empty($_POST['updateBackup']) AND validateData($_POST['updateBackup']) === "yes") {
        $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'no';
    }


    /**
     *  Répertoire de destination des backups de repomanager sur le serveur si le paramètre UPDATE_BACKUP_ENABLED est activé
     */
    if(!empty($_POST['updateBackupDir'])) {
        $updateBackupDir = validateData($_POST['updateBackupDir']);

        if (is_alphanumdash($updateBackupDir, array('/'))) {
            $repomanager_conf_array['UPDATE']['BACKUP_DIR'] = rtrim($updateBackupDir, '/');
        }
    }
    
    /**
     *  Branche git de mise à jour 
     */
    if(!empty($_POST['updateBranch'])) {
        $updateBranch = validateData($_POST['updateBranch']);

        if (is_alphanum($updateBranch, array('/'))) {
            $repomanager_conf_array['UPDATE']['UPDATE_BRANCH'] = $updateBranch;
        }
    }

/**
 *  Section WWW
 */

    /**
     *  Utilisateur web exécutant le serveur web
     */
    if(!empty($_POST['wwwUser'])) {
        $wwwUser = validateData($_POST['wwwUser']);

        if (is_alphanumdash($wwwUser)) {
            $repomanager_conf_array['WWW']['WWW_USER'] = trim($wwwUser);
        }
    }

    /**
     *  Adresse web hôte de repomanager (https://xxxx)
     */
    $OLD_WWW_HOSTNAME = WWW_HOSTNAME; // On conserve le hostname actuel car on va s'en servir pour le remplacer dans les fichiers de conf ci dessous
    if(!empty($_POST['wwwHostname']) AND $OLD_WWW_HOSTNAME !== validateData($_POST['wwwHostname']) AND is_alphanumdash(validateData($_POST['wwwHostname']), array('.'))) {

        $NEW_WWW_HOSTNAME = trim(validateData($_POST['wwwHostname']));
        $repomanager_conf_array['WWW']['WWW_HOSTNAME'] = "$NEW_WWW_HOSTNAME";

        // Puis on remplace dans tous les fichier de conf de repo
        if (OS_FAMILY == "Redhat") {
            exec("find ".REPOS_PROFILES_CONF_DIR."/ -type f -name '*.repo' -print0 | xargs -0 sed -i 's/${OLD_WWW_HOSTNAME}/${NEW_WWW_HOSTNAME}/g'");
        }
        if (OS_FAMILY == "Debian") {
            exec("find ".REPOS_PROFILES_CONF_DIR."/ -type f -name '*.list' -print0 | xargs -0 sed -i 's/${OLD_WWW_HOSTNAME}/${NEW_WWW_HOSTNAME}/g'");
        }

        // On remplace aussi dans le fichier profils/hostname.conf si existe
        if (file_exists("PROFILE_SERVER_CONF")) {
            $content = file_get_contents(PROFILE_SERVER_CONF);
            $content = preg_replace("/${OLD_WWW_HOSTNAME}/", $NEW_WWW_HOSTNAME, $content);
            file_put_contents(PROFILE_SERVER_CONF, $content);
        }
    }

    /**
     *  URL d'accès aux repos. Exemple : https://xxxxxxx/repo
     */
    if(!empty($_POST['wwwReposDirUrl'])) {
        $wwwReposDirUrl = validateData($_POST['wwwReposDirUrl']);

        if (is_alphanumdash($wwwReposDirUrl, array('.', '/', ':'))) {
            $repomanager_conf_array['WWW']['WWW_REPOS_DIR_URL'] = rtrim($wwwReposDirUrl, '/');
        }
    }

    /**
     *  Chemin vers le fichier de log d'accès à analyser pour statistiques
     */
    if(!empty($_POST['statsLogPath'])) {
        $statsLogPath = validateData($_POST['statsLogPath']);
        
        if (is_alphanumdash($statsLogPath, array('.', '/'))) {
            $repomanager_conf_array['WWW']['WWW_STATS_LOG_PATH'] = $statsLogPath;
        }

        /**
         *  On stoppe le process stats-log-parser.sh actuel, il sera relancé au rechargement de la page
         */
        kill_stats_log_parser();
    }
/**
 *  Section AUTOMATISATION
 */

    /**
     *  Activation/désactivation de l'automatisation
     */
    if (!empty($_POST['automatisationEnable']) AND validateData($_POST['automatisationEnable']) === "yes") {
        $repomanager_conf_array['AUTOMATISATION']['AUTOMATISATION_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['AUTOMATISATION']['AUTOMATISATION_ENABLED'] = 'no';
    }

    /**
     *  Autoriser ou non la mise à jour des repos par l'automatisation
     */
    if (!empty($_POST['allowAutoUpdateRepos']) AND validateData($_POST['allowAutoUpdateRepos']) === "yes") {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS'] = 'yes';
    } else {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS'] = 'no';
    }

    /**
     *  Autoriser ou non le changement d'environnement par l'automatisation
     */
    if (!empty($_POST['allowAutoUpdateReposEnv']) AND validateData($_POST['allowAutoUpdateReposEnv']) === "yes") {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'yes';
    } else {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'no';
    }

    /**
     *  Autoriser ou non la suppression des repos archivés par l'automatisation
     */
    if (!empty($_POST['allowAutoDeleteArchivedRepos']) AND validateData($_POST['allowAutoDeleteArchivedRepos']) === "yes") {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'yes';
    } else {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'no';
    }

    /**
     *  Retention, nombre de repos à conserver avant suppression par l'automatisation
     */
    if(!empty($_POST['retention'])) {
        $retention = validateData($_POST['retention']);

        if (is_numeric($retention)) {
            $repomanager_conf_array['AUTOMATISATION']['RETENTION'] = $retention;
        }
    }

    /**
     *  Activer / désactiver l'envoie de rappels de planifications futures (seul paramètre cron à ne pas être regroupé avec les autres paramètres cron)
     */
    if (!empty($_POST['cronSendReminders']) AND validateData($_POST['cronSendReminders']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_PLAN_REMINDERS_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_PLAN_REMINDERS_ENABLED'] = 'no';
    }

    /**
     *  Activer / désactiver les statistiques
     */
    if (!empty($_POST['cronStatsEnable']) AND validateData($_POST['cronStatsEnable']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_STATS_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_STATS_ENABLED'] = 'no';
    }

    save($repomanager_conf_array);

    /**
     *  Nettoyage du cache de repos-list
     */
    clearCache();
}

/**
 *  Section CRON
 *  Si un des formulaires de la page a été validé alors on entre dans cette condition
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) === "applyCronConfiguration") {

    // Récupération de tous les paramètres définis dans le fichier repomanager.conf
    $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF, true);

    /**
     *  Activation des tâches cron
     */
    if (!empty($_POST['cronDailyEnable']) AND validateData($_POST['cronDailyEnable']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_DAILY_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_DAILY_ENABLED'] = 'no';
    }

    /**
     *  Activer / désactiver la regénération et le nettoyage régulier des fichiers de configuration des repos (.repo ou .list) téléchargeables par les clients
     */
    if (!empty($_POST['cronGenerateReposConf']) AND validateData($_POST['cronGenerateReposConf']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_GENERATE_REPOS_CONF'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_GENERATE_REPOS_CONF'] = 'no';
    }

    /**
     *  Activer / désactiver la ré-application régulière des permissions sur les répertoires de repos
     */
    if (!empty($_POST['cronApplyPerms']) AND validateData($_POST['cronApplyPerms']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_APPLY_PERMS'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_APPLY_PERMS'] = 'no';
    }

    /**
     *  Activer / désactiver la sauvegarde régulière de la DB et des fichiers de configuration
     */
    if (!empty($_POST['cronSaveConf']) AND validateData($_POST['cronSaveConf']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_SAVE_CONF'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_SAVE_CONF'] = 'no';
    }

    save($repomanager_conf_array);
}

/**
 *  Enregistrement
 */
function save(array $array) {
    /**
     *  On écrit toutes les modifications dans le fichier display.ini
     */
    write_ini_file(REPOMANAGER_CONF, $array);

    /**
     *  On appelle enableCron pour qu'il ré-écrive / supprime les lignes de la crontab
     */
    enableCron();

    /**
     *  Puis rechargement de la page pour appliquer les modifications de configuration
     */
    header('Location: configuration.php');
    exit;
}

/**
 * Deploiement des tâches cron
 */
if (!empty($_GET['action']) AND validateData($_GET['action']) == "enableCron") {
    enableCron();
}

/**
 *  Gestion des environnements
 *  Récupère la liste des environnements envoyés sous forme de tableau actualEnv[]
 *  Valeurs retournées dans le cas du renommage d'un environnement par exemple
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) === "addNewEnv") {
    
    /**
     *  Ajout d'un nouvel environnement
     */
    if (!empty($_POST['newEnv'])) {
        $myenv = new Environnement(array('envName' => validateData($_POST['newEnv'])));
        $myenv->new();
    }

    /**
     *  Nettoyage du cache de repos-list
     */
    clearCache();
}

/**
 *  Renommage d'un environnement / changement de sens des environnements
 */
if (!empty($_POST['action']) AND validateData($_POST['action']) === "applyEnvConfiguration") {

    if (!empty($_POST['actualEnv'])) {
        $myenv = new Environnement();
        $myenv->edit($_POST['actualEnv']);
    }

    /**
     *  Nettoyage du cache de repos-list
     */
    clearCache();
} 

/**
 *  Suppression d'un environnement
 */
if (!empty($_GET['deleteEnv'])) {
    $myenv = new Environnement(array('envName' => validateData($_GET['deleteEnv'])));
    $myenv->delete();

    /**
     *  Nettoyage du cache de repos-list
     */
    clearCache();
}
?>

<body>
<?php include('includes/header.inc.php');?>

<article>
<section class="mainSectionLeft">
    <section class="left">
        <h3>CONFIGURATION GÉNÉRALE</h3>
        <form action="configuration.php" method="post" autocomplete="off">
        <input type="hidden" name="action" value="applyConfiguration" />
        <table class="table-medium">
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Famille d'OS</td>
                <td><input type="text" value="<?php echo OS_FAMILY;?>" readonly /></td>
                <td class="td-fit">
                <?php if (empty(OS_FAMILY)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Nom de l'OS</td>
                <td><input type="text" value="<?php echo OS_INFO['name'];?>" readonly /></td>
                <td class="td-fit">
                <?php if (empty(OS_INFO['name'])) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Version d'OS</td>
                <td><input type="text" value="<?php echo OS_INFO['version_id'];?>" readonly /></td>
                <td class="td-fit">
                <?php if (empty(OS_INFO['version_id'])) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si activé, repomanager se mettra à jour automatiquement si une mise à jour est disponible" />Mise à jour automatique</td>
                <td>
                    <label class="onoff-switch-label">
                    <input name="updateAuto" type="checkbox" class="onoff-switch-input" value="yes" <?php if (UPDATE_AUTO == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                <?php if (empty(UPDATE_AUTO)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Choisir quelle version de mise à jour recevoir" />Branche de mise à jour</td>
                <td>
                <select name="updateBranch">
                <option value="alpha" <?php if (UPDATE_BRANCH == "alpha") { echo 'selected'; } ?>>alpha</option>
                <option value="beta" <?php if (UPDATE_BRANCH == "beta") { echo 'selected'; } ?>>beta</option>
                </td>
                <?php
                if (UPDATE_AVAILABLE == "yes") {
                    echo '<td class="td-fit">';
                    echo '<input type="button" onclick="location.href=\'configuration.php?action=update\'" class="btn-xxsmall-green" title="Mettre à jour repomanager" value="↻">';
                    echo '</td>';
                }
                if (empty(UPDATE_BRANCH)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } 
                ?>
            </tr>
                <?php
                if (!empty($updateStatus)) {
                    echo '<tr>';
                    echo '<td></td>';
                    echo "<td colspan=\"2\">$updateStatus</td>";
                    echo '</tr>';
                }
                ?>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si activé, repomanager créera un backup dans le répertoire indiqué avant de se mettre à jour" />Sauvegarde avant mise à jour</td>
                <td>
                    <label class="onoff-switch-label">
                    <input name="updateBackup" type="checkbox" class="onoff-switch-input" value="yes" <?php if (UPDATE_BACKUP_ENABLED == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                <?php if (empty(UPDATE_BACKUP_ENABLED)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <?php
            if (UPDATE_BACKUP_ENABLED == "yes") {
            echo '<tr>';
            echo '<td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Répertoire de destination des backups avant mise à jour" />Répertoire de sauvegarde</td>';
            echo '<td><input type="text" name="updateBackupDir" autocomplete="off" value="'.BACKUP_DIR.'"></td>';
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty(BACKUP_DIR)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';
            echo '</tr>';
            } ?>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="L'adresse renseignée recevra les mails d'erreurs et les rappels de planification. Il est possible de renseigner plusieurs adresses séparées par une virgule" />Contact</td>
                <td><input type="text" name="emailDest" autocomplete="off" value="<?php echo EMAIL_DEST; ?>"></td>
                <td class="td-fit">
                <?php if (empty(EMAIL_DEST)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
        </table>

        <br><h3>REPOS</h3>
        <table class="table-medium">
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Ce serveur gère des repos de paquets <?php if (OS_FAMILY == "Redhat") { echo 'rpm'; } if (OS_FAMILY == "Debian") { echo 'deb'; }?>" />Type de paquets</td>
                <td><input type="text" value=".<?php echo PACKAGE_TYPE; ?>" readonly /></td>
                <td class="td-fit">
                <?php if (empty(PACKAGE_TYPE)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <?php
            if (OS_FAMILY == "Redhat") {
                echo '<tr>';
                echo "<td class=\"td-large\"><img src=\"ressources/icons/info.png\" class=\"icon-verylowopacity\" title=\"Ce serveur créera des miroirs de repos pour CentOS ".RELEASEVER." uniquement\" />Version de paquets gérée</td>";
                echo '<td><input type="number" name="releasever" autocomplete="off" value="'.RELEASEVER.'"></td>';
                echo '<td class="td-fit">';
                if (empty(RELEASEVER)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
                echo '</td>';
                echo '</tr>';
            }
            echo '<tr>';
            echo '<td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Resigner les paquets du repo avec GPG après création ou mise à jour d\'un miroir de repo" />Signer les paquets avec GPG</td>';    
            echo '<td>';
            echo '<label class="onoff-switch-label">';
            echo '<input name="gpgSignPackages" type="checkbox" class="onoff-switch-input" value="yes"'; if (GPG_SIGN_PACKAGES == "yes") { echo 'checked'; } echo ' />';
            echo '<span class="onoff-switch-slider"></span>';
            echo '</label>';
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty(GPG_SIGN_PACKAGES)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';

            if (GPG_SIGN_PACKAGES == "yes") {
                echo '<tr>';
                if (OS_FAMILY == "Redhat") {
                    echo '<td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Adresse mail liée à la clé GPG servant à resigner les paquets" />GPG Key ID (pour signature des paquets)</td>';
                }
                if (OS_FAMILY == "Debian") {
                    echo '<td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Adresse mail liée à la clé GPG servant à signer les repos" />GPG Key ID (pour signature des repos)</td>';
                }
                echo '<td><input type="text" name="gpgKeyID" autocomplete="off" value="'.GPG_KEYID.'"></td>';
                echo '<td class="td-fit">';
                if (empty(GPG_KEYID)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
                echo '</td>';
                echo '</tr>'; 
            } 
            ?>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Répertoire local de stockage des repos" />Répertoire des repos</td>
                <td><input type="text" autocomplete="off" name="reposDir" value="<?php echo REPOS_DIR; ?>" /></td>
                <td class="td-fit">
                <?php if (empty(REPOS_DIR)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Activer la collecte de statistiques d'accès au repo, sa taille, son nombre de paquets. Ce paramètre nécessite de la configuration supplémentaire dans le vhost de ce serveur, ainsi qu'un accès aux logs d'accès de ce serveur à <?php echo WWW_USER; ?>." />Activer les statistiques</td>
                <td>
                    <label class="onoff-switch-label">
                    <input name="cronStatsEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php if (CRON_STATS_ENABLED == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>

                </td>
                <td class="td-fit">
                <?php if (empty(CRON_STATS_ENABLED)) echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; ?>
                </td>
            </tr>
            <?php
                if (CRON_STATS_ENABLED == "yes") {
                    echo '<tr>';
                    echo '<td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Chemin vers le fichier de log du serveur web contenant des requêtes d\'accès aux repos. Ce fichier est parsé pour générer des statistiques." />Fichier de log à analyser pour les statistiques</td>';
                    echo '<td><input type="text" autocomplete="off" name="statsLogPath" value="'.WWW_STATS_LOG_PATH.'" /></td>';
                    echo '<td class="td-fit">';
                    if (empty(WWW_STATS_LOG_PATH)) echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    echo '</td>';
                    echo '</tr>';
                }
            ?>
        </table>

        <br><h3>CONFIGURATION WEB</h3>
        <table class="table-medium">
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Utilisateur Linux exécutant le service web de ce serveur" />Utilisateur web</td>
                <td><input type="text" name="wwwUser" autocomplete="off" value="<?php echo WWW_USER; ?>"></td>
                <td class="td-fit">
                <?php if (empty(WWW_USER)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Nom Hôte</td>
                <td><input type="text" name="wwwHostname" autocomplete="off" value="<?php echo WWW_HOSTNAME; ?>"></td>
                <td class="td-fit">
                <?php if (empty(WWW_HOSTNAME)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Url d'accès aux repos</td>
                <td><input type="text" name="wwwReposDirUrl" autocomplete="off" value="<?php echo WWW_REPOS_DIR_URL; ?>"></td>
                <td class="td-fit">
                <?php if (empty(WWW_REPOS_DIR_URL)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            
        </table>

        <br><h3>GESTION DU PARC</h3>
        <table class="table-medium">
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Activer la gestion des hôtes utilisant yum-update-auto / apt-update-auto" />Activer la gestion des hôtes</td>
                <td>
                    <label class="onoff-switch-label">
                    <input name="manageHosts" type="checkbox" class="onoff-switch-input" value="yes" <?php if (MANAGE_HOSTS == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>

                </td>
                <td class="td-fit">
                <?php if (empty(MANAGE_HOSTS)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Activer la gestion des profils pour les clients yum-update-auto / apt-update-auto (en cours de dev)" />Activer la gestion des profils</td>
                <td>
                    <label class="onoff-switch-label">
                    <input name="manageProfiles" type="checkbox" class="onoff-switch-input" value="yes" <?php if (MANAGE_PROFILES == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>

                </td>
                <td class="td-fit">
                <?php if (empty(MANAGE_PROFILES)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <?php
                if (MANAGE_PROFILES == "yes") {
                    if (OS_FAMILY == "Debian") {
                        echo '<td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Préfixe s\'ajoutant au nom de fichiers .list générés par repomanager, ex : repomanager-debian.list" />Préfixe des fichiers de repo \'.list\'</td>';
                    }
                    if (OS_FAMILY == "Redhat") {
                        echo '<td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Préfixe s\'ajoutant au nom de fichiers .repo générés par repomanager, ex : repomanager-BaseOS.repo" />Préfixe des fichiers de repo \'.repo\'</td>';
                    }
                    echo '<td><input type="text" name="symlinksPrefix" autocomplete="off" value="'.REPO_CONF_FILES_PREFIX.'"></td>';
                }?>
            </tr>
        </table>

        <br><h3>PLANIFICATIONS</h3>
        <table class="table-medium">
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à exécuter des opérations automatiquement à des dates et heures spécifiques" />Activer les planifications</td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="automatisationEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php if (AUTOMATISATION_ENABLED == "yes") { echo 'checked'; }?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(AUTOMATISATION_ENABLED)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
    <?php if (AUTOMATISATION_ENABLED == "yes") { ?>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à mettre à jour un repo ou un groupe de repos spécifié" />Autoriser la mise à jour automatique des repos</td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="allowAutoUpdateRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php if (ALLOW_AUTOUPDATE_REPOS == "yes") { echo 'checked'; } ?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(ALLOW_AUTOUPDATE_REPOS)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à modifier l'environnement d'un repo ou d'un groupe de repos spécifié" />Autoriser la mise à jour automatique de l'env des repos</td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="allowAutoUpdateReposEnv" type="checkbox" class="onoff-switch-input" value="yes" <?php if (ALLOW_AUTOUPDATE_REPOS_ENV == "yes") { echo 'checked'; } ?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(ALLOW_AUTOUPDATE_REPOS_ENV)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à supprimer les repos archivés (en fonction de la retention renseignée)" />Autoriser la suppression automatique des anciens repos archivés</td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="allowAutoDeleteArchivedRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php if (ALLOW_AUTODELETE_ARCHIVED_REPOS == "yes") { echo 'checked'; } ?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(ALLOW_AUTODELETE_ARCHIVED_REPOS)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr> 
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Nombre de repos archivés du même nom à conserver avant suppression" />Retention</td>
                <td>
                    <input type="number" name="retention" autocomplete="off" value="<?php echo RETENTION;?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(RETENTION)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Autorise repomanager à envoyer des rappels par mail des futures planifications. Un service d'envoi de mail doit être configuré sur le serveur (sendmail)." />Recevoir des rappels de planifications</td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="cronSendReminders" type="checkbox" class="onoff-switch-input" value="yes" <?php if (CRON_PLAN_REMINDERS_ENABLED == "yes") { echo 'checked'; } ?> />
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(CRON_PLAN_REMINDERS_ENABLED)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr> 
        <?php } ?>
            <tr>
                <td><button type="submit" class="btn-medium-green">Enregistrer</button></td>
            </tr>
        </table>
        </form>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
        <h3>ENVIRONNEMENTS</h3>
        <table class="table-medium">
        <form action="configuration.php" method="post" autocomplete="off">
            <input type="hidden" name="action" value="applyEnvConfiguration" />
                <?php
                /**
                 *  Affichage des environnements actuels
                 */
                $myenvs = new Environnement();
                $envs = $myenvs->listAll();

                foreach ($envs as $envName) {
                    echo '<tr>';
                    echo '<td>';
                    echo '<input type="text" name="actualEnv[]" value="'.$envName.'" />';
                    echo '</td>';
                    echo '<td class="td-fit center">';
                    echo "<img src=\"ressources/icons/bin.png\" class=\"envDeleteToggle-${envName} icon-lowopacity\" title=\"Supprimer l'environnement ${envName}\"/>";
                    deleteConfirm("Êtes-vous sûr de vouloir supprimer l'environnement $envName", "?deleteEnv=${envName}", "envDeleteDiv-${envName}", "envDeleteToggle-${envName}");
                    echo '</td>';
                    if ($envName == DEFAULT_ENV) {
                        echo '<td>Defaut</td>';
                    } else {
                        echo '<td></td>';
                    }
                    echo '</tr>';
                } ?>
            <input type="submit" class="hide" value="Valider" /> <!-- bouton caché, afin de taper Entrée pour appliquer les modifications -->
        </form>
        <form action="configuration.php" method="post" autocomplete="off">
            <input type="hidden" name="action" value="addNewEnv" />
            <tr>
                <td><input type="text" name="newEnv" placeholder="Ajouter un nouvel environnement" /></td>
                <td class="td-fit"><button type="submit" class="btn-xxsmall-blue">+</button></td>
                <td class="td-fit"><?php if (empty(ENVS)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Au moins un environnement doit être configuré" />'; } ?></td>
                <td></td>
            </tr>
        </form>
        </table>

        <br><h3>BASES DE DONNÉES</h3>
        <table class="table-large">
            <tr>
                <td class="td-fit">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Base de données principale de repomanager. L'application ne peut fonctionner si la base de données est en erreur." />
                </td>
                <td class="td-50">Principale</td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier de base de données
                     */
                    if (!is_readable(ROOT."/db/repomanager.db")) {
                        echo "Impossible de lire la base principale";
                    } else {
                        echo '<span title="OK">Accès</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new Connection('main', 'rw');

                    if (!$myconn->checkMainTables()) {
                        echo '<span title="Une ou plusieurs tables semblent manquantes">Etat des tables</span><img src="ressources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="Toutes les tables sont présentes">Etat des tables</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>

            <?php
            if (CRON_STATS_ENABLED == "yes") { ?>
            <tr>
                <td class="td-fit">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Base de données des statistiques des repos." />
                </td>
                <td class="td-50">Stats</td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier
                     */
                    if (!is_readable(ROOT."/db/repomanager-stats.db")) {
                        echo "Impossible de lire la base de données des statistiques";
                    } else {
                        echo '<span title="OK">Status</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new Connection('stats', 'rw');

                    if (!$myconn->checkStatsTables()) {
                        echo '<span title="Une ou plusieurs tables semblent manquantes">Etat des tables</span><img src="ressources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="Toutes les tables sont présentes">Etat des tables</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>
        <?php }
            if (MANAGE_HOSTS == "yes") { ?>
            <tr>
                <td class="td-fit">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Base de données des hôtes." />
                </td>
                <td class="td-50">Hosts</td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier
                     */
                    if (!is_readable(ROOT."/db/repomanager-hosts.db")) {
                        echo "Impossible de lire la base de données des hôtes";
                    } else {
                        echo '<span title="OK">Status</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>

                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new Connection('hosts', 'rw');

                    if (!$myconn->checkHostsTables()) {
                        echo '<span title="Une ou plusieurs tables semblent manquantes">Etat des tables</span><img src="ressources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="Toutes les tables sont présentes">Etat des tables</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>
        <?php } ?>

        </table>

        <!--<form action="configuration.php" method="post">
            <input type="hidden" name="action" value="deployDatabases" />
        </form>-->

        <br><h3>CRONS</h3>
        <form action="configuration.php" method="post">
            <input type="hidden" name="action" value="applyCronConfiguration" />
            <table class="table-large">
                <tr>
                    <td class="td-fit">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Tâche cron exécutant des actions régulières telles que vérifier la disponibilité d'une nouvelle mise à jour, remettre en ordre les permissions sur les répertoires de repos. Tâche journalière s'exécutant toutes les 5min." />
                    </td>
                    <td class="td-medium">
                        Activer la tâche cron journalière
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="cronDailyEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php if (CRON_DAILY_ENABLED == "yes") { echo 'checked'; } ?> />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td>
                    <?php
                    if (CRON_DAILY_ENABLED == "yes") {
                        /**
                         *  Si un fichier de log existe, on récupère l'état
                         */
                        if (file_exists(CRON_LOG)) {
                            $cronStatus = exec("grep 'Status=' ".CRON_LOG." | cut -d'=' -f2 | sed 's/\"//g'");
                            if ($cronStatus === "OK") {
                                echo '<span title="OK">Status <img src="ressources/icons/greencircle.png" class="icon-small" /></span>';
                            }
                            if ($cronStatus === "KO") {
                                echo '<span title="Erreur">Status <img src="ressources/icons/redcircle.png" class="icon-small" /></span>';
                                echo '<img id="cronjobStatusButton" src="ressources/icons/search.png" class="icon-lowopacity pointer" title="Afficher les détails" />';
                            }
                        }
                        if (!file_exists(CRON_LOG)) {
                            echo "<span>Status : inconnu</span>";
                        }
                    } ?>
                    </td>
                    <td class="td-fit">
                        <?php if (empty(CRON_DAILY_ENABLED)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                    </td>
                </tr>
            <?php
            if (!empty($cronStatus) AND $cronStatus === "KO") {
                $cronError = shell_exec("cat ".CRON_LOG." | grep -v 'Status='");
                echo '<tr>';
                echo '<td colspan="100%">';
                echo "<div id=\"cronjobStatusDiv\" class=\"hide background-gray\">$cronError</div>";
                echo '<script>';
                echo '$(document).ready(function(){';
                echo "$(\"#cronjobStatusButton\").click(function(){";
                echo "$(\"#cronjobStatusDiv\").slideToggle(250);";
                echo '$(this).toggleClass("open");';
                echo '});';
                echo '});';
                echo '</script>';
                echo '</td>';
                echo '</tr>';
            }

            if (CRON_DAILY_ENABLED == "yes" AND MANAGE_PROFILES == "yes") { ?>
                <tr>
                    <td class="td-fit">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si la gestion des profils est activée. Regénère et nettoie les fichiers de configurations  if (OS_FAMILY == "Redhat") { .repo } if (OS_FAMILY == "Debian") { .list }  téléchargés par les serveurs clients." />
                    </td>
                    <td class="td-medium">Re-générer les fichiers de configurations de repos</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="cronGenerateReposConf" type="checkbox" class="onoff-switch-input" value="yes" <?php if (CRON_GENERATE_REPOS_CONF == "yes") { echo 'checked'; } ?> />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td></td>
                    <td class="td-fit">
                        <?php if (empty(CRON_GENERATE_REPOS_CONF)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                    </td>
                </tr>
    <?php   }

            if (CRON_DAILY_ENABLED == "yes") { ?>
                <tr>
                    <td class="td-fit">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Sauvegarder régulièrement la base de données et les fichiers de configuration de repomanager" />
                    </td>
                    <td class="td-medium">Sauvegarder régulièrement la base de données et les fichiers de configuration</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="cronSaveConf" type="checkbox" class="onoff-switch-input" value="yes" <?php if (CRON_SAVE_CONF == "yes") { echo 'checked'; } ?> />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td></td>
                    <td class="td-fit">
                        <?php if (empty(CRON_SAVE_CONF)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                    </td>
                </tr>
    <?php   }

            if (CRON_DAILY_ENABLED == "yes") { ?>
                <tr>
                    <td class="td-fit">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />
                    </td>
                    <td class="td-medium">Re-appliquer les permissions sur les miroirs</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="cronApplyPerms" type="checkbox" class="onoff-switch-input" value="yes" <?php if (CRON_APPLY_PERMS == "yes") { echo 'checked'; } ?> />
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td></td>
                    <td class="td-fit">
                        <?php if (empty(CRON_APPLY_PERMS)) { echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                    </td>
                </tr>
    <?php   }
            
            if (AUTOMATISATION_ENABLED == "yes" AND CRON_PLAN_REMINDERS_ENABLED == "yes") {
                echo '<tr>';
                echo '<td class="td-fit">';
                echo '<img src="ressources/icons/info.png" class="icon-verylowopacity" title="Tâche cron envoyant des rappels automatiques des futures planifications" />';
                echo '</td>';
                echo '<td class="td-medium">Rappels de planifications</td>';
                echo '<td></td>'; // comble les td affichant des boutons radio de la précédente ligne
                echo '<td>';
                // On vérifie la présence d'une ligne contenant 'planifications/plan.php' dans la crontab
                $cronStatus = checkCronReminder();
                if ($cronStatus == 'On')  echo '<span title="OK">Status <img src="ressources/icons/greencircle.png" class="icon-small" /></span>';
                if ($cronStatus == 'Off') echo '<span title="Erreur">Status <img src="ressources/icons/redcircle.png" class="icon-small" /></span>';
                echo '</td>';
                echo '</tr>';
            } 
            
            if (CRON_STATS_ENABLED == "yes") { ?>
                <tr>
                    <td class="td-fit">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Tâche cron générant des statistiques pour chaque repo" />
                    </td>
                    <td class="td-medium">Génération de statistiques</td>
                    <td></td> 
                    <td>

                    <?php
                    /**
                     *  si un fichier de log existe, on récupère l'état
                     */
                    if (file_exists(CRON_STATS_LOG)) {
                        $cronStatus = exec("grep 'Status=' ".CRON_STATS_LOG." | cut -d'=' -f2 | sed 's/\"//g'");
                        if ($cronStatus === "OK") {
                            echo '<span title="OK">Status <img src="ressources/icons/greencircle.png" class="icon-small" /></span>';
                        }
                        if ($cronStatus === "KO") {
                            echo '<span title="Erreur">Status <img src="ressources/icons/redcircle.png" class="icon-small" /></span>';
                        }
                    }
                    if (!file_exists(CRON_STATS_LOG)) {
                        echo "<span>Status : inconnu</span>";
                    }
                    echo '</td>';
                echo '</tr>';
            } ?>
            <tr>
                <td colspan="100%">
                    <button type="submit" class="btn-medium-green">Enregistrer</button>
                    <input type="button" onclick="location.href='configuration.php?action=enableCron'" class="btn-xxsmall-green" title="Re-déployer les tâches dans la crontab" value="↻">
                </td>
            </tr>
            </table>
        </form>
    </section>
</section>
</article>

<?php include('includes/footer.inc.php'); ?>
</body>
</html>