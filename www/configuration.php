<html>
<?php include('common-head.inc.php'); ?>

<?php
/**
 *  Import des variables et fonctions nécessaires
 */
require_once('functions/load_common_variables.php');
require_once('functions/load_display_variables.php');
require_once('functions/common-functions.php');
require_once('common.php');

/**
 *  Mise à jour de Repomanager
 */

if (!empty($_GET['action']) AND validateData($_GET['action']) == "update") {
    $error = 0;

    /**
     *  Backup avant mise à jour
     */
    if ($UPDATE_BACKUP_ENABLED == "yes") {
        if (!is_dir($UPDATE_BACKUP_DIR)) {
            if (!mkdir($UPDATE_BACKUP_DIR)) {
                $error++;
                $errorMsg = "Erreur : impossible de créer le répertoire de sauvegarde $UPDATE_BACKUP_DIR";
            }
        } else {
            exec("tar xzf /tmp/${DATE_AMJ}_${HEURE}_repomanager_backup.tar.gz $WWW_DIR" ,$output, $result);
            if ($result != 0) {
                $error++;
                $errorMsg = 'Erreur lors de la sauvegarde de la configuration actuelle de repomanager';
            }
        }
    }

    if ($error == 0) {
        // On récupère la dernière version du script de mise à jour avant de l'exécuter
        exec("wget https://raw.githubusercontent.com/lbr38/repomanager/${UPDATE_BRANCH}/www/update/repomanager-autoupdate -O ${WWW_DIR}/update/repomanager-autoupdate", $output, $result);
        if ($result != 0) {
            $error++;
            $errorMsg = 'Erreur pendant le téléchargement de la mise à jour';
        }

        exec("bash ${WWW_DIR}/update/repomanager-autoupdate", $output, $result);
        if ($result != 0) {
            $error++;
            $errorMsg = 'Erreur pendant l\'exécution de la mise à jour';
        }
    }
    
    if ($error == 0) {
        $updateStatus = 'OK';
    } else {
        $updateStatus = $errorMsg;   
    }
}

// Si un des formulaires de la page a été validé alors on entre dans cette condition
if (!empty($_POST['action']) AND validateData($_POST['action']) === "applyConfiguration") {

    // Récupération de tous les paramètres définis dans le fichier repomanager.conf
    $repomanager_conf_array = parse_ini_file("$REPOMANAGER_CONF", true);


/**
 *  Section PATHS
 */

    /**
     *  Chemin du répertoire des repos sur le serveur
     */

    if (!empty($_POST['reposDir'])) {
        $reposDir = validateData($_POST['reposDir']);
        $repomanager_conf_array['PATHS']['REPOS_DIR'] = "$reposDir";
    }

/**
 *  Section CONFIGURATION
 */

    /**
     *  Adresse mail destinatrice des alertes
     */

    if (!empty($_POST['emailDest'])) {
        $emailDest = validateData($_POST['emailDest']);
        $repomanager_conf_array['CONFIGURATION']['EMAIL_DEST'] = "$emailDest";
    }

    /**
     *  Si on souhaite activer ou non la gestion des profils
     */

    if (!empty($_POST['manageProfiles'])) {
        $manageProfiles = validateData($_POST['manageProfiles']);
        $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = "$manageProfiles";
    }

    /**
     *  Modification du préfix des fichiers de conf repos
     */

    // On conserve le préfix actuel car on va s'en servir pour renommer les fichiers de conf ci dessous
    $oldRepoFilesPrefix = $REPO_CONF_FILES_PREFIX;

    // on ne traite que si on a renseigné un nouveau préfix :
    if(!empty($_POST['symlinksPrefix']) AND ($oldRepoFilesPrefix !== validateData($_POST['symlinksPrefix']))) {
        $newRepoFilesPrefix = validateData($_POST['symlinksPrefix']);
        $confFiles = scandir($REPOS_PROFILES_CONF_DIR);
        foreach($confFiles as $confFile) {
            if (($confFile != "..") AND ($confFile != ".")) {
                // remplace les occurence de l'ancien préfix par le nouveau à l'intérieur du fichier
                exec("sed -i 's/${oldRepoFilesPrefix}/${newRepoFilesPrefix}/g' $confFile");
                // renomme le fichier en remplacant l'ancien prefix par le nouveau :
                $pattern = "/^${oldRepoFilesPrefix}/";
                $newConfFile = preg_replace($pattern, $newRepoFilesPrefix, $confFile);
                rename("${REPOS_PROFILES_CONF_DIR}/$confFile", "${REPOS_PROFILES_CONF_DIR}/${newConfFile}");
            }
        }

        // renomme les liens symboliques des profils :
        $profilesNames = scandir($PROFILES_MAIN_DIR);
        foreach($profilesNames as $profileName) {
            if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "main")) {
                $profileName_dir = "$PROFILES_MAIN_DIR/$profileName";
                $repoConfFiles = scandir($profileName_dir);
                
                // Pour chaque répertoire de profil sur le serveur, on récupère les noms de fichier de conf (.repo ou .list selon l'OS) :
                foreach($repoConfFiles as $symlink) {
                    if (($symlink != "..") AND ($symlink != ".") AND ($symlink != "config")) {
                        $pattern = "/^${oldRepoFilesPrefix}/";
                        $newSymlinkName = preg_replace($pattern, $newRepoFilesPrefix, $symlink);
                        
                        // suppression du symlink :
                        if (file_exists("${PROFILES_MAIN_DIR}/${profileName}/${symlink}")) {
                            unlink("${PROFILES_MAIN_DIR}/${profileName}/${symlink}");
                        }
                        
                        // création du nouveau avec le nouveau prefix :
                        exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_PROFILES_CONF_DIR}/${newSymlinkName}");
                    }
                }
            }
        }
        
        // enfin, on remplace le préfix dans le fichier de conf repomanager.conf
        $repomanager_conf_array['CONFIGURATION']['REPO_CONF_FILES_PREFIX'] = "$newRepoFilesPrefix";
    }

    /**
     *  Activer ou désactiver le mode debug
     */

    if (!empty($_POST['debugMode'])) {
        $debugMode = validateData($_POST['debugMode']);
        $repomanager_conf_array['CONFIGURATION']['DEBUG_MODE'] = "$debugMode";
    }

/**
 *  Section GPG
 */

    /**
     *  Activer/désactiver la signature des paquets/des repos avec GPG
     */

    if (!empty($_POST['gpgSignPackages'])) {
        $gpgSignPackages = validateData($_POST['gpgSignPackages']);
        $repomanager_conf_array['GPG']['GPG_SIGN_PACKAGES'] = "$gpgSignPackages";
    }
    
    /**
     *  Email lié à la clé GPG qui signe les paquets/les repos
     */

    if (!empty($_POST['gpgKeyID'])) {
        $gpgKeyID = validateData($_POST['gpgKeyID']);
        $repomanager_conf_array['GPG']['GPG_KEYID'] = "$gpgKeyID";
    }

/**
 *  Section UPDATE
 */

    /**
     *  Activer / désactiver la mise à jour automatique de repomanager
     */

    if(!empty($_POST['updateAuto'])) {
        $updateAuto = validateData($_POST['updateAuto']);
        $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = "$updateAuto";
    }

    /**
     *  Activer / désactiver le backup de repomanager avant mise à jour
     */

    if(!empty($_POST['updateBackup'])) {
        $updateBackup = validateData($_POST['updateBackup']);
        $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = "$updateBackup";
    }

    /**
     *  Répertoire de destination des backups de repomanager sur le serveur si le paramètre UPDATE_BACKUP_ENABLED est activé
     */

    if(!empty($_POST['updateBackupDir'])) {
        $updateBackupDir = validateData($_POST['updateBackupDir']);
        $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_DIR'] = "$updateBackupDir";
    }
    
    /**
     *  Branche git de mise à jour 
     */

    if(!empty($_POST['updateBranch'])) {
        $updateBranch = validateData($_POST['updateBranch']);
        $repomanager_conf_array['UPDATE']['UPDATE_BRANCH'] = "$updateBranch";
    }

/**
 *  Section WWW
 */

    /**
     *  Utilisateur web exécutant le serveur web
     */

    if(!empty($_POST['wwwUser'])) {
        $wwwUser = validateData($_POST['wwwUser']);
        $repomanager_conf_array['WWW']['WWW_USER'] = "$wwwUser";
    }

    /**
     *  Adresse web hôte de repomanager (https://xxxx)
     */

    $OLD_WWW_HOSTNAME = $WWW_HOSTNAME; // On conserve le hostname actuel car on va s'en servir pour le remplacer dans les fichiers de conf ci dessous
    if(!empty($_POST['wwwHostname']) AND ($OLD_WWW_HOSTNAME !== validateData($_POST['wwwHostname']))) {
        $NEW_WWW_HOSTNAME = validateData($_POST['wwwHostname']);
        $repomanager_conf_array['WWW']['WWW_HOSTNAME'] = "$NEW_WWW_HOSTNAME";

        // Puis on remplace dans tous les fichier de conf de repo
        if ($OS_FAMILY == "Redhat") {
            exec("find ${REPOS_PROFILES_CONF_DIR}/ -type f -name '*.repo' -print0 | xargs -0 sed -i 's/${OLD_WWW_HOSTNAME}/${NEW_WWW_HOSTNAME}/g'");
        }
        if ($OS_FAMILY == "Debian") {
            exec("find ${REPOS_PROFILES_CONF_DIR}/ -type f -name '*.list' -print0 | xargs -0 sed -i 's/${OLD_WWW_HOSTNAME}/${NEW_WWW_HOSTNAME}/g'");
        }

        // On remplace aussi dans le fichier profils/hostname.conf si existe
        if (file_exists("$PROFILE_SERVER_CONF")) {
            $content = file_get_contents("$PROFILE_SERVER_CONF");
            $content = preg_replace("/${OLD_WWW_HOSTNAME}/", "${NEW_WWW_HOSTNAME}", $content);
            file_put_contents("$PROFILE_SERVER_CONF", $content);
        }
    }

    /**
     *  URL d'accès aux repos. Exemple : https://xxxxxxx/repo
     */

    if(!empty($_POST['wwwReposDirUrl'])) {
        $wwwReposDirUrl = validateData($_POST['wwwReposDirUrl']);
        $repomanager_conf_array['WWW']['WWW_REPOS_DIR_URL'] = "$wwwReposDirUrl";
    }
    
/**
 *  Section AUTOMATISATION
 */

    /**
     *  Activation/désactivation de l'automatisation
     */

    if(!empty($_POST['automatisationEnable'])) {
        $automatisationEnable = validateData($_POST['automatisationEnable']);
        $repomanager_conf_array['AUTOMATISATION']['AUTOMATISATION_ENABLED'] = "$automatisationEnable";
    }

    /**
     *  Autoriser ou non la mise à jour des repos par l'automatisation
     */

    if(!empty($_POST['allowAutoUpdateRepos'])) {
        $allowAutoUpdateRepos = validateData($_POST['allowAutoUpdateRepos']);
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS'] = "$allowAutoUpdateRepos";
    }

    /**
     *  Autoriser ou non le changement d'environnement par l'automatisation
     */

    if(!empty($_POST['allowAutoUpdateReposEnv'])) {
        $allowAutoUpdateReposEnv = validateData($_POST['allowAutoUpdateReposEnv']);
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS_ENV'] = "$allowAutoUpdateReposEnv";
    }

    /**
     *  Autoriser ou non la suppression des repos archivés par l'automatisation
     */

    if(!empty($_POST['allowAutoDeleteArchivedRepos'])) {
        $allowAutoDeleteArchivedRepos = validateData($_POST['allowAutoDeleteArchivedRepos']);
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = "$allowAutoDeleteArchivedRepos";
    }

    /**
     *  Retention, nombre de repos à conserver avant suppression par l'automatisation
     */

    if(!empty($_POST['retention'])) {
        $retention = validateData($_POST['retention']);
        $repomanager_conf_array['AUTOMATISATION']['RETENTION'] = "$retention";
    }

/**
 *  Section CRON
 */

    /**
     *  Activation des tâches cron
     */

    if (!empty($_POST['cronDailyEnable'])) {
        $cronDailyEnable = validateData($_POST['cronDailyEnable']);
        if ($cronDailyEnable == "yes") {
            $repomanager_conf_array['CRON']['CRON_DAILY_ENABLED'] = 'yes';
        } else {
            $repomanager_conf_array['CRON']['CRON_DAILY_ENABLED'] = 'no';
        }
    }

    /**
     *  Activer / désactiver la regénération et le nettoyage régulier des fichiers de configuration des repos (.repo ou .list) téléchargeables par les clients
     */

    if(!empty($_POST['cronGenerateReposConf'])) {
        $cronGenerateReposConf = validateData($_POST['cronGenerateReposConf']);
        $repomanager_conf_array['CRON']['CRON_GENERATE_REPOS_CONF'] = "$cronGenerateReposConf";
    }

    /**
     *  Activer / désactiver la ré-application régulière des permissions sur les répertoires de repos
     */

    if(!empty($_POST['cronApplyPerms'])) {
        $cronApplyPerms = validateData($_POST['cronApplyPerms']);
        $repomanager_conf_array['CRON']['CRON_APPLY_PERMS'] = "$cronApplyPerms";
    }

    /**
     *  Activer / désactiver l'envoie de rappels de planifications futures
     */

    if(!empty($_POST['cronSendReminders'])) {
        $cronSendReminders = validateData($_POST['cronSendReminders']);
        $repomanager_conf_array['CRON']['CRON_PLAN_REMINDERS_ENABLED'] = "$cronSendReminders";
    }

/**
 *  Enregistrement
 */

    /**
     *  On écrit toutes les modifications dans le fichier display.ini
     */

    write_ini_file("$REPOMANAGER_CONF", $repomanager_conf_array);

    /**
     *  On appelle enableCron pour qu'il ré-écrive / supprime les lignes de la crontab
     */

    enableCron();

    /**
     *  Vidage du cache navigateur
     */

    echo "<script>Clear-Site-Data: \"*\";</script>";

    /**
     *  Puis rechargement de la page pour appliquer les modifications de configuration
     */

    header('Location: configuration.php');
}

/**
 * Deploiement des tâches cron
 */

if (!empty($_GET['action']) AND validateData($_GET['action']) == "enableCron") {
    enableCron();
}


/**
 * Gestion des environnements
 */

// Récupère la liste des environnements envoyés sous forme de tableau actualEnv[]
// Valeurs retournées dans le cas du renommage d'un environnement par exemple
if (!empty($_POST['actualEnv'])) {
    $actualEnvTotal = '';
    foreach ($_POST['actualEnv'] as $actualEnvName) {
        $actualEnvName = validateData($actualEnvName);
        $actualEnvTotal = "${actualEnvTotal}\n${actualEnvName}";
    }
    // On ré-écrit le tout dans le fichier envs.conf
    file_put_contents("$ENV_CONF", "[ENVIRONNEMENTS]${actualEnvTotal}".PHP_EOL);   
}

// Ajout d'un nouvel environnement
if (!empty($_POST['newEnv'])) {
    $newEnv = validateData($_POST['newEnv']);
    // On écrit le nouvel env dans le fichier envs.conf, avant 'prod'
    file_put_contents("$ENV_CONF", "${newEnv}".PHP_EOL,FILE_APPEND);
    // Puis rechargement de la page pour appliquer les modifications de configuration
    header('Location: configuration.php');
}

// Suppression d'un environnement
if (!empty($_GET['deleteEnv'])) {
    $deleteEnv = validateData($_GET['deleteEnv']);
    // On supprime l'env dans le fichier envs.conf
    exec("sed -i '/^${deleteEnv}/d' $ENV_CONF");
    // Puis rechargement de la page pour appliquer les modifications de configuration
    header('Location: configuration.php');
}
?>

<body>
<?php include('common-header.inc.php');?>
<section class="mainSectionLeft">
    <section class="left">
        <h5>CONFIGURATION GÉNÉRALE</h5>
        <form action="configuration.php" method="post" autocomplete="off">
        <input type="hidden" name="action" value="applyConfiguration" />
        <table class="table-medium">
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Famille d'OS</td>
                <td><input type="text" value="<?php echo $OS_FAMILY;?>" readonly /></td>
                <td class="td-fit">
                <?php if (empty($OS_FAMILY)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Nom de l'OS</td>
                <td><input type="text" value="<?php echo "$OS_INFO[name]";?>" readonly /></td>
                <td class="td-fit">
                <?php if (empty($OS_INFO['name'])) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Version d'OS</td>
                <td><input type="text" value="<?php echo "$OS_INFO[version_id]";?>" readonly /></td>
                <td class="td-fit">
                <?php if (empty($OS_INFO['version_id'])) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Si activé, repomanager se mettra à jour lors de sa prochaine exécution si une mise à jour est disponible" />Mise à jour automatique</td>
                <td>
                    <input type="radio" id="updateAuto_radio_yes" name="updateAuto" value="yes" <?php if ($UPDATE_AUTO == "yes") { echo 'checked'; }?>>
                    <label for="updateAuto_radio_yes">Yes</label>
                    <input type="radio" id="updateAuto_radio_no" name="updateAuto" value="no" <?php if ($UPDATE_AUTO == "no") { echo 'checked'; }?>>
                    <label for="updateAuto_radio_no">No</label>
                </td>
                <td class="td-fit">
                <?php if (empty($UPDATE_AUTO)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Choisir quelle version de mise à jour recevoir" />Branche de mise à jour</td>
                <td>
                <select name="updateBranch">
                <option value="alpha" <?php if ($UPDATE_BRANCH == "alpha") { echo 'selected'; } ?>>alpha</option>
                <option value="beta" <?php if ($UPDATE_BRANCH == "beta") { echo 'selected'; } ?>>beta</option>
                </td>
                <td class="td-fit">
                <?php
                    if ($UPDATE_AVAILABLE == "yes") {
                        echo '<input type="button" onclick="location.href=\'configuration.php?action=update\'" class="button-submit-xxsmall-green" title="Mettre à jour repomanager" value="↻">';
                    }
                    if (!empty($updateStatus)) { echo $updateStatus; }
                    if (empty($UPDATE_BRANCH)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } 
                ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Si activé, repomanager créera un backup dans le répertoire indiqué avant de se mettre à jour" />Sauvegarde avant mise à jour</td>
                <td>
                    <input type="radio" id="updateBackup_radio_yes" name="updateBackup" value="yes" <?php if ($UPDATE_BACKUP_ENABLED == "yes") { echo 'checked'; }?>>
                    <label for="updateBackup_radio_yes">Yes</label>
                    <input type="radio" id="updateBackup_radio_no" name="updateBackup" value="no" <?php if ($UPDATE_BACKUP_ENABLED == "no") { echo 'checked'; }?>>
                    <label for="updateBackup_radio_no">No</label>
                </td>
                <td class="td-fit">
                <?php if (empty($UPDATE_BACKUP_ENABLED)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <?php
            if ($UPDATE_BACKUP_ENABLED == "yes") {
            echo '<tr>';
            echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Répertoire de destination des backups avant mise à jour" />Répertoire de sauvegarde</td>';
            echo "<td><input type=\"text\" name=\"updateBackupDir\" autocomplete=\"off\" value=\"${UPDATE_BACKUP_DIR}\"></td>";
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty($UPDATE_BACKUP_DIR)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';
            echo '</tr>';
            } ?>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="L'adresse renseignée recevra les mails d'erreurs et les rappels de planification. Il est possible de renseigner plusieurs adresses séparées par une virgule" />Adresse mail</td>
                <td><input type="text" name="emailDest" autocomplete="off" value="<?php echo $EMAIL_DEST; ?>"></td>
                <td class="td-fit">
                <?php if (empty($EMAIL_DEST)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
        </table>

        <br><h5>REPOS</h5>
        <table class="table-medium">
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Ce serveur gère des repos de paquets <?php if ($OS_FAMILY == "Redhat") { echo 'rpm'; } if ($OS_FAMILY == "Debian") { echo 'deb'; }?>" />Type de paquets</td>
                <td><input type="text" value=".<?php echo $PACKAGE_TYPE; ?>" readonly /></td>
                <td class="td-fit">
                <?php if (empty($PACKAGE_TYPE)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <?php
            if ($OS_FAMILY == "Redhat") {
                echo '<tr>';
                echo "<td class=\"td-large\"><img src=\"icons/info.png\" class=\"icon-verylowopacity\" title=\"Ce serveur créera des miroirs de repos pour CentOS $RELEASEVER uniquement\" />Version de paquets gérée</td>";
                echo "<td><input type=\"number\" name=\"releasever\" autocomplete=\"off\" value=\"${RELEASEVER}\"></td>";
                echo '<td class="td-fit">';
                if (empty($RELEASEVER)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
                echo '</td>';
                echo '</tr>';
            }
            echo '<tr>';
            echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Resigner les paquets du repo avec GPG après création ou mise à jour d\'un miroir de repo" />Signer les paquets avec GPG</td>';    
            echo '<td>';
            echo '<input type="radio" id="gpgSignPackages_yes" name="gpgSignPackages" value="yes"'; if ($GPG_SIGN_PACKAGES == "yes") { echo "checked >"; } else { echo " >"; }
            echo '<label for="gpgSignPackages_yes">Yes</label>';
            echo '<input type="radio" id="gpgSignPackages_no" name="gpgSignPackages" value="no"'; if ($GPG_SIGN_PACKAGES == "no") { echo "checked >"; } else { echo " >"; }
            echo '<label for="gpgSignPackages_no">No</label>';
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty($GPG_SIGN_PACKAGES)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';

            if ($GPG_SIGN_PACKAGES == "yes") {
                echo '<tr>';
                echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Adresse mail liée au trousseau de clé GPG servant à resigner les paquets" />GPG Key ID (pour signature des paquets)</td>';
                echo "<td><input type=\"text\" name=\"gpgKeyID\" autocomplete=\"off\" value=\"$GPG_KEYID\"></td>";
                echo '<td class="td-fit">';
                if (empty($GPG_KEYID)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
                echo '</td>';
                echo '</tr>'; 
            } 
            ?>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Répertoire local de stockage des repos" />Répertoire des repos</td>
                <td><input type="text" autocomplete="off" name="reposDir" value="<?php echo $REPOS_DIR; ?>" /></td>
                <td class="td-fit">
                <?php if (empty($REPOS_DIR)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
        </table>

        <br><h5>CONFIGURATION WEB</h5>
        <table class="table-medium">
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Utilisateur Linux exécutant le service web de ce serveur" />Utilisateur web</td>
                <td><input type="text" name="wwwUser" autocomplete="off" value="<?php echo $WWW_USER; ?>"></td>
                <td class="td-fit">
                <?php if (empty($WWW_USER)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Nom Hôte</td>
                <td><input type="text" name="wwwHostname" autocomplete="off" value="<?php echo $WWW_HOSTNAME; ?>"></td>
                <td class="td-fit">
                <?php if (empty($WWW_HOSTNAME)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Url d'accès aux repos</td>
                <td><input type="text" name="wwwReposDirUrl" autocomplete="off" value="<?php echo $WWW_REPOS_DIR_URL; ?>"></td>
                <td class="td-fit">
                <?php if (empty($WWW_REPOS_DIR_URL)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Activer la gestion des profils pour les clients yum-update-auto / apt-update-auto (en cours de dev)" />Activer la gestion des profils</td>
                <td>
                    <input type="radio" id="manageProfiles_radio_yes" name="manageProfiles" value="yes" <?php if ($MANAGE_PROFILES == "yes") { echo 'checked'; }?>>
                    <label for="manageProfiles_radio_yes">Yes</label> 
                    <input type="radio" id="manageProfiles_radio_no" name="manageProfiles" value="no" <?php if ($MANAGE_PROFILES == "no") { echo 'checked'; }?>>
                    <label for="manageProfiles_radio_no">No</label> 
                </td>
                <td class="td-fit">
                <?php if (empty($MANAGE_PROFILES)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                </td>
            </tr>
            <tr>
                <?php
                if ($MANAGE_PROFILES == "yes") {
                    if ($OS_FAMILY == "Debian") {
                        echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Préfixe s\'ajoutant au nom de fichiers .list générés par repomanager, ex : repomanager-debian.list" />Préfixe des fichiers de repo \'.list\'</td>';
                    }
                    if ($OS_FAMILY == "Redhat") {
                        echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Préfixe s\'ajoutant au nom de fichiers .repo générés par repomanager, ex : repomanager-BaseOS.repo" />Préfixe des fichiers de repo \'.repo\'</td>';
                    }
                    echo "<td><input type=\"text\" name=\"symlinksPrefix\" autocomplete=\"off\" value=\"${REPO_CONF_FILES_PREFIX}\"></td>";
                }?>
            </tr>
        </table>

        <br><h5>PLANIFICATIONS</h5>
        <table class="table-medium"> 
            <?php
            echo '<tr>';
            echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à exécuter des opérations automatiquement à des dates et heures spécifiques" />Activer les planifications</td>';
            echo '<td>';
            echo "<input type=\"radio\" id=\"automatisation_radio_yes\" name=\"automatisationEnable\" value=\"yes\""; if ($AUTOMATISATION_ENABLED == "yes") { echo 'checked >'; } else { echo " >"; }
            echo '<label for="automatisation_radio_yes">Yes</label>';
            echo "<input type=\"radio\" id=\"automatisation_radio_yes\" name=\"automatisationEnable\" value=\"no\""; if ($AUTOMATISATION_ENABLED == "no") { echo 'checked >'; } else { echo " >"; }
            echo '<label for="automatisation_radio_no">No</label>';
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty($AUTOMATISATION_ENABLED)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';
            echo '</tr>';
        
            if ($AUTOMATISATION_ENABLED == "yes") { 
            echo '<tr>';
            echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à mettre à jour un repo ou un groupe de repos spécifié" />Autoriser la mise à jour automatique des repos</td>';
            echo '<td>';
            echo "<input type=\"radio\" id=\"allow_autoupdate_repos_radio_yes\" name=\"allowAutoUpdateRepos\" value=\"yes\""; if ($ALLOW_AUTOUPDATE_REPOS == "yes") { echo "checked >"; } else { echo " >"; }
            echo '<label for="allow_autoupdate_repos_radio_yes">Yes</label>';
            echo "<input type=\"radio\" id=\"allow_autoupdate_repos_radio_no\" name=\"allowAutoUpdateRepos\" value=\"no\""; if ($ALLOW_AUTOUPDATE_REPOS == "no") { echo "checked >"; } else { echo " >"; }
            echo '<label for="allow_autoupdate_repos_radio_no">No</label>';
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty($ALLOW_AUTOUPDATE_REPOS)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à modifier l\'environnement d\'un repo ou d\'un groupe de repos spécifié" />Autoriser la mise à jour automatique de l\'env des repos</td>';
            echo '<td>';
            echo "<input type=\"radio\" id=\"allow_autoupdate_repos_env_radio_yes\" name=\"allowAutoUpdateReposEnv\" value=\"yes\""; if ($ALLOW_AUTOUPDATE_REPOS_ENV == "yes") { echo "checked >"; } else { echo " >"; }
            echo '<label for="allow_autoupdate_repos_env_radio_yes">Yes</label>';
            echo "<input type=\"radio\" id=\"allow_autoupdate_repos_env_radio_no\" name=\"allowAutoUpdateReposEnv\" value=\"no\""; if ($ALLOW_AUTOUPDATE_REPOS_ENV == "no") { echo "checked >"; } else { echo " >"; }
            echo '<label for="allow_autoupdate_repos_env_radio_no">No</label>';
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty($ALLOW_AUTOUPDATE_REPOS_ENV)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à supprimer les repos archivés (en fonction de la retention renseignée)" />Autoriser la suppression automatique des anciens repos archivés</td>';
            echo '<td>';
            echo "<input type=\"radio\" id=\"allow_autodelete_old_repos_radio_yes\" name=\"allowAutoDeleteArchivedRepos\" value=\"yes\""; if ($ALLOW_AUTODELETE_ARCHIVED_REPOS == "yes") { echo "checked >"; } else { echo " >"; } 
            echo '<label for="allow_autodelete_old_repos_radio_yes">Yes</label>';
            echo "<input type=\"radio\" id=\"allow_autodelete_old_repos_radio_no\" name=\"allowAutoDeleteArchivedRepos\" value=\"no\""; if ($ALLOW_AUTODELETE_ARCHIVED_REPOS == "no") { echo "checked >"; } else { echo " >"; }
            echo '<label for="allow_autodelete_old_repos_radio_no">No</label>';
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty($ALLOW_AUTODELETE_ARCHIVED_REPOS)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';
            echo '</tr>'; 
            echo '<tr>';
            echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Nombre de repos archivés du même nom à conserver avant suppression" />Retention</td>';
            echo "<td><input type=\"number\" name=\"retention\" autocomplete=\"off\" value=\"${RETENTION}\"></td>";
            echo '<td class="td-fit">';
            if (empty($RETENTION)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Autorise repomanager à envoyer des rappels par mail des futures planifications. Un service d\'envoi de mail doit être configuré sur le serveur (sendmail)." />Recevoir des rappels de planifications</td>';
            echo '<td>';
            echo "<input type=\"radio\" id=\"cronSendReminders_yes\" name=\"cronSendReminders\" value=\"yes\""; if ($CRON_PLAN_REMINDERS_ENABLED == "yes") { echo "checked >"; } else { echo " >"; } 
            echo '<label for="cronSendReminders_yes">Yes</label>';
            echo "<input type=\"radio\" id=\"cronSendReminders_no\" name=\"cronSendReminders\" value=\"no\""; if ($CRON_PLAN_REMINDERS_ENABLED == "no") { echo "checked >"; } else { echo " >"; }
            echo '<label for="cronSendReminders_no">No</label>';
            echo '</td>';
            echo '<td class="td-fit">';
            if (empty($CRON_PLAN_REMINDERS_ENABLED)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
            echo '</td>';
            echo '</tr>'; 
            } ?>
            <tr>
                <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
            </tr>
        </table>
        </form>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
        <h5>ENVIRONNEMENTS</h5>
        <form action="configuration.php" method="post" autocomplete="off">
            <input type="hidden" name="action" value="applyConfiguration" />
            <table class="table-large">
                <?php // Affichage des envs actuels
                $i=0;
                foreach ($ENVS as $env) {
                    echo '<tr>';
                    if ($env === $DEFAULT_ENV) {
                        echo '<td>Defaut</td>';
                    } else {
                        echo '<td></td>';
                    }
                    echo '<td>';
                    echo "<input type=\"text\" class=\"input-large\" name=\"actualEnv[]\" value=\"${env}\" />";
                    echo "<img src=\"icons/bin.png\" class=\"envDeleteToggle${i} icon-lowopacity\" title=\"Supprimer l'environnement ${env}\"/>";
                    deleteConfirm("Êtes-vous sûr de vouloir supprimer l'environnement $env", "?deleteEnv=${env}", "envDeleteDiv${i}", "envDeleteToggle${i}");
                    echo '</td>';
                    echo '</tr>';
                    ++$i;
                } ?>
                <tr>
                    <td></td>
                    <td><input type="text" class="input-large" name="newEnv" placeholder="Ajouter un nouvel environnement" /><button type="submit" class="button-submit-xxsmall-blue">+</button></td></td>
                    <td class="td-fit">
                    <?php if (empty($ENVS)) { echo '<img src="icons/warning.png" class="icon" title="Au moins un environnement doit être configuré" />'; } ?>
                    </td>
                </tr>
                <tr>
                    <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
                </tr>
            </table>
        </form>

        <br><h5>CRONS</h5>
        <form action="configuration.php" method="post">
        <input type="hidden" name="action" value="applyConfiguration" />
            <table class="table-large">
                <tr>
                    <td><img src="icons/info.png" class="icon-verylowopacity" title="Tâche cron exécutant des actions régulières telles que vérifier la disponibilité d'une nouvelle mise à jour, remettre en ordre les permissions sur les répertoires de repos. Tâche journalière s'exécutant toutes les 5min." />Activer la tâche cron journalière</td>        
                    <td>
                        <input type="radio" id="cron_daily_enabled_yes" name="cronDailyEnable" value="yes" <?php if ($CRON_DAILY_ENABLED == "yes") { echo 'checked'; }?>>
                        <label for="cron_daily_enabled_yes">Yes</label> 
                        <input type="radio" id="cron_daily_enabled_no" name="cronDailyEnable" value="no" <?php if ($CRON_DAILY_ENABLED == "no") { echo 'checked'; }?>>
                        <label for="cron_daily_enabled_no">No</label> 
                    </td>
                    <td>
                        <?php
                        if ($CRON_DAILY_ENABLED == "yes") {
                            // si un fichier de log existe, on récupère l'état
                            if (file_exists("$CRON_LOG")) {
                                $cronStatus = exec("grep 'Status=' $CRON_LOG | cut -d'=' -f2 | sed 's/\"//g'");
                                if ($cronStatus === "OK") {
                                    echo 'Status : <span class="greentext">OK</span>';
                                }
                                if ($cronStatus === "KO") {
                                    echo 'Status : <span class="redtext">KO</span>';
                                    echo '<img id="cronjobStatusButton" src="icons/search.png" class="icon-lowopacity pointer" title="Afficher les détails" />';
                                }
                            }
                            if (!file_exists("$CRON_LOG")) {
                                echo "Status : inconnu";
                            }
                        } ?>
                    </td>
                    <td class="td-fit">
                    <?php if (empty($CRON_DAILY_ENABLED)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; } ?>
                    </td>
                </tr>
            <?php
            if (!empty($cronStatus) AND $cronStatus === "KO") {
                $cronError = shell_exec("cat $CRON_LOG | grep -v 'Status='");
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

            if ($CRON_DAILY_ENABLED == "yes" AND $MANAGE_PROFILES == "yes") {
                echo '<tr>';
                echo '<td><img src="icons/info.png" class="icon-verylowopacity" title="Si la gestion des profils est activée. Regénère et nettoie les fichiers de configurations '; if ($OS_FAMILY == "Redhat") { echo '.repo'; } if ($OS_FAMILY == "Debian") { echo '.list'; } echo ' téléchargés par les serveurs clients." />Re-générer les fichiers de configurations de repos</td>';
                echo '<td>';
                echo '<input type="radio" id="cron_generate_repos_conf_yes" name="cronGenerateReposConf" value="yes"'; if ($CRON_GENERATE_REPOS_CONF == "yes") { echo "checked >"; } else { echo " >"; }
                echo '<label for="cron_generate_repos_conf_yes">Yes</label>';
                echo '<input type="radio" id="cron_generate_repos_conf_no" name="cronGenerateReposConf" value="no"'; if ($CRON_GENERATE_REPOS_CONF == "no") { echo "checked >"; } else { echo " >"; }
                echo '<label for="cron_generate_repos_conf_no">No</label> ';
                echo '</td>';
                echo '<td></td>'; // comble le td affichant le statut de la précédente ligne
                echo '<td class="td-fit">';
                if (empty($CRON_GENERATE_REPOS_CONF)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
                echo '</td>';
                echo '</tr>';
            } ?>

            <?php
            if ($CRON_DAILY_ENABLED == "yes") {
                echo '<tr>';
                echo '<td><img src="icons/info.png" class="icon-verylowopacity" title="" />Re-appliquer les permissions sur les miroirs</td>';
                echo '<td>';
                echo '<input type="radio" id="cron_apply_perms_yes" name="cronApplyPerms" value="yes"'; if ($CRON_APPLY_PERMS == "yes") { echo "checked >"; } else { echo " >"; }
                echo '<label for="cron_apply_perms_yes">Yes</label>';
                echo '<input type="radio" id="cron_apply_perms_no" name="cronApplyPerms" value="no"'; if ($CRON_APPLY_PERMS == "no") { echo "checked >"; } else { echo " >"; }
                echo '<label for="cron_apply_perms_no">No</label> ';
                echo '</td>';
                echo '<td></td>'; // comble le td affichant le statut de la précédente ligne
                echo '<td class="td-fit">';
                if (empty($CRON_APPLY_PERMS)) { echo '<img src="icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />'; }
                echo '</td>';
                echo '</tr>';
            }
            
            if ($AUTOMATISATION_ENABLED == "yes" AND $CRON_PLAN_REMINDERS_ENABLED == "yes") {
                echo '<tr>';
                echo '<td><img src="icons/info.png" class="icon-verylowopacity" title="Tâche cron envoyant des rappels automatiques des futures planifications" />Rappels de planifications</td>';
                echo '<td></td>'; // comble les td affichant des boutons radio de la précédente ligne

                // On vérifie la présence d'une ligne contenant 'planifications/plan.php' dans la crontab
                $cronStatus = checkCronReminder();
                if ($cronStatus == 'On') {
                    echo '<td>Status : <span class="greentext">Actif</span></td>';
                }
                if ($cronStatus == 'Off') {
                    echo '<td>Status : <span class="redtext">Inactif</span></td>';
                }
                echo '</tr>';
            } ?>
            <tr>
                <td>
                    <button type="submit" class="button-submit-medium-green">Enregistrer</button>
                    <input type="button" onclick="location.href='configuration.php?action=enableCron'" class="button-submit-xxsmall-green" title="Re-déployer les tâches dans la crontab" value="↻">
                </td>
            </tr>
            </table>
        </form>

        <br><h5>MODE DEBUG</h5>
        <form action="configuration.php" method="post">
        <input type="hidden" name="action" value="applyConfiguration" />
            <table class="table-medium">
                <tr>
                    <td>
                        <select name="debugMode" class="select-small">
                            <option value="enabled" <?php if ($DEBUG_MODE == "enabled") { echo "selected"; }?>>enabled</option>
                            <option value="disabled" <?php if ($DEBUG_MODE == "disabled") { echo "selected"; }?>>disabled</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
                </tr>
            </table>
        </form>
    </section>
</section>
<?php include('common-footer.inc.php'); ?>
</body>
</html>