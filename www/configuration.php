<html>
<?php include('common-head.inc.php'); ?>

<?php
    // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
    require 'vars/common.vars';
    require 'common-functions.php';
    require 'common.php';
    require 'vars/display.vars';
    if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }

    // Comme la page contient un formulaire qui renvoie vers elle meme, on vérifie si des données ont été passées en POST (formulaire validé).
    // Si c'est le cas on récupère ces données et on les écrit dans le fichier de conf
    // Si ce n'est pas le cas c'est parce que la page a seulement été chargée et le formulaire n'a pas encore été validé. On n'écrit rien dans le fichier
    if(!empty($_POST['updateAuto'])) {
        $updateAuto = validateData($_POST['updateAuto']);
        exec("sed -i 's/^UPDATE_AUTO=.*/UPDATE_AUTO=\"${updateAuto}\"/g' $REPOMANAGER_CONF");
    }

    if(!empty($_POST['updateBackup'])) {
        $updateBackup = validateData($_POST['updateBackup']);
        exec("sed -i 's/^UPDATE_BACKUP=.*/UPDATE_BACKUP=\"${updateBackup}\"/g' $REPOMANAGER_CONF");
    }

    if(!empty($_POST['updateBackupDir'])) {
        $updateBackupDir = validateData($_POST['updateBackupDir']);
        exec("sed -i 's|^UPDATE_BACKUP_DIR=.*|UPDATE_BACKUP_DIR=\"${updateBackupDir}\"|g' $REPOMANAGER_CONF");
    }

    if(!empty($_POST['wwwUser'])) {
        $wwwUser = validateData($_POST['wwwUser']);
        exec("sed -i 's/^WWW_USER=.*/WWW_USER=\"${wwwUser}\"/g' $REPOMANAGER_CONF");
    }

    $OLD_WWW_HOSTNAME = $WWW_HOSTNAME; // On conserve le hostname actuel car on va s'en servir pour le remplacer dans les fichiers de conf ci dessous
    if(!empty($_POST['wwwHostname']) AND ($OLD_WWW_HOSTNAME !== validateData($_POST['wwwHostname']))) {
        $NEW_WWW_HOSTNAME = validateData($_POST['wwwHostname']);
        exec("sed -i 's/^WWW_HOSTNAME=.*/WWW_HOSTNAME=\"${NEW_WWW_HOSTNAME}\"/g' $REPOMANAGER_CONF"); // on remplace dans le fichier de conf de repomanager

        // Puis on remplace dans tous les fichier de conf de repo
        if ($OS_FAMILY == "Redhat") {
            exec("find ${REPOS_PROFILES_CONF_DIR}/ -type f -name '*.repo' -print0 | xargs -0 sed -i 's/${OLD_WWW_HOSTNAME}/${NEW_WWW_HOSTNAME}/g'");
        }
        if ($OS_FAMILY == "Debian") {
            exec("find ${REPOS_PROFILES_CONF_DIR}/ -type f -name '*.list' -print0 | xargs -0 sed -i 's/${OLD_WWW_HOSTNAME}/${NEW_WWW_HOSTNAME}/g'");
        }
    }

    // url d'accès aux repos
    if(!empty($_POST['wwwReposDirUrl'])) {
        $wwwReposDirUrl = validateData($_POST['wwwReposDirUrl']);
        $content = file_get_contents("$REPOMANAGER_CONF");
        $content = preg_replace('/WWW_REPOS_DIR_URL.*/', "WWW_REPOS_DIR_URL=\"${wwwReposDirUrl}\"",$content);
        file_put_contents("$REPOMANAGER_CONF", $content);
    }
 
    // adresse mail destinatrice des alertes
    if (!empty($_POST['emailDest'])) {
        $emailDest = validateData($_POST['emailDest']);
        exec("sed -i 's/^EMAIL_DEST=.*/EMAIL_DEST=\"${emailDest}\"/g' $REPOMANAGER_CONF");
    }

    // si on souhaite activer ou non la gestion des profils
    if (!empty($_POST['manageProfiles'])) {
        $manageProfiles = validateData($_POST['manageProfiles']);
        exec("sed -i 's/^MANAGE_PROFILES=.*/MANAGE_PROFILES=\"${manageProfiles}\"/g' $REPOMANAGER_CONF");
    }

    // modification du préfix des fichiers de conf repos
    $oldRepoFilesPrefix = $REPO_CONF_FILES_PREFIX; // On conserve le préfix actuel car on va s'en servir pour renommer les fichiers de conf ci dessous
    if(!empty($_POST['symlinksPrefix']) AND ($oldRepoFilesPrefix !== $_POST['symlinksPrefix'])) { // on ne traite que si on a renseigné un nouveau préfix
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
                foreach($repoConfFiles as $symlink) { // Pour chaque répertoire de profil sur le serveur, on récupère les noms de fichier de conf (.repo ou .list selon l'OS)
                    if (($symlink != "..") AND ($symlink != ".") AND ($symlink != "config")) {
                        $pattern = "/^${oldRepoFilesPrefix}/";
                        $newSymlinkName = preg_replace($pattern, $newRepoFilesPrefix, $symlink);
                        exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && unlink ${symlink}"); // suppression du symlink
                        exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_PROFILES_CONF_DIR}/${newSymlinkName}"); // création du nouveau avec le nouveau prefix
                    }
                }
            }
        }

        // enfin, remplace le préfix dans le fichier de conf repomanager.conf
        exec("sed -i 's/^REPO_CONF_FILES_PREFIX=.*/REPO_CONF_FILES_PREFIX=\"${newRepoFilesPrefix}\"/g' $REPOMANAGER_CONF");
    }   

    // Signer les paquets du repo GPG
    if (!empty($_POST['gpgSignPackages'])) {
        $gpgSignPackages = validateData($_POST['gpgSignPackages']);
        exec("sed -i 's/^GPG_SIGN_PACKAGES=.*/GPG_SIGN_PACKAGES=\"${gpgSignPackages}\"/g' $REPOMANAGER_CONF");
    }
    
    // Email lié à la clé GPG qui signe les paquets
    if (!empty($_POST['gpgKeyID'])) {
        $gpgKeyID = validateData($_POST['gpgKeyID']);
        exec("sed -i 's/^GPG_KEYID=.*/GPG_KEYID=\"${gpgKeyID}\"/g' $REPOMANAGER_CONF");
    }

    // Automatisation
    if(!empty($_POST['automatisationEnable'])) {
        $automatisationEnable = validateData($_POST['automatisationEnable']);
        exec("sed -i 's/^AUTOMATISATION_ENABLED=.*/AUTOMATISATION_ENABLED=\"${automatisationEnable}\"/g' $REPOMANAGER_CONF");
    }

    // Activation des tâches cron
    if (!empty($_POST['enableCron'])) {
        // Récupération du contenu de la crontab actuelle dans un fichier temporaire
        shell_exec("crontab -l > /tmp/repomanager_${WWW_USER}_crontab.tmp");
        // On supprime toutes les lignes concernant repomanager dans ce fichier pour refaire propre
        exec("sed -i '/repomanager/d' /tmp/repomanager_${WWW_USER}_crontab.tmp");

        // Puis on ajoute les tâches cron suivantes au fichier temporaire
        // Tâche cron journalière
        file_put_contents("/tmp/repomanager_${WWW_USER}_crontab.tmp", "*/5 * * * * bash ${REPOMANAGER} --cronjob-daily".PHP_EOL, FILE_APPEND);

        // Tâche cron d'envoi des rappels de planifications
        if ($automatisationEnable === "yes") {
            // si on a activé automatisationEnable === yes, alors on ajoute la tâche cron de rappels de planifications
            file_put_contents("/tmp/repomanager_${WWW_USER}_crontab.tmp", "0 0 * * * bash ${REPOMANAGER} --planReminders".PHP_EOL, FILE_APPEND);
        }

        // Enfin on reimporte le contenu du fichier temporaire
        exec("crontab /tmp/repomanager_${WWW_USER}_crontab.tmp");   // on importe le fichier dans la crontab de $WWW_USER
        //unlink("/tmp/repomanager_${WWW_USER}_crontab.tmp");         // puis on supprime le fichier temporaire
    }

    // Environnements
    // Récupère la liste des environnements envoyés sous forme de tableau actualEnv[]
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
    }

    // Suppression d'un environnement
    if (!empty($_GET['deleteEnv'])) {
        $deleteEnv = validateData($_GET['deleteEnv']);
        // On supprime l'env dans le fichier envs.conf
        exec("sed -i '/^${deleteEnv}/d' $ENV_CONF");
    }

    // Autoriser la mise à jour des repos par l'automatisation
    if(!empty($_POST['allowAutoUpdateRepos'])) {
        $allowAutoUpdateRepos = validateData($_POST['allowAutoUpdateRepos']);
        exec("sed -i 's/^ALLOW_AUTOUPDATE_REPOS=.*/ALLOW_AUTOUPDATE_REPOS=\"${allowAutoUpdateRepos}\"/g' $REPOMANAGER_CONF");
    }

    // Autoriser le changement d'environnement par l'automatisation
    if(!empty($_POST['allowAutoUpdateReposEnv'])) {
        $allowAutoUpdateReposEnv = validateData($_POST['allowAutoUpdateReposEnv']);
        exec("sed -i 's/^ALLOW_AUTOUPDATE_REPOS_ENV=.*/ALLOW_AUTOUPDATE_REPOS_ENV=\"${allowAutoUpdateReposEnv}\"/g' $REPOMANAGER_CONF");
    }

    // Autoriser la suppression des repos archivés par l'automatisation
    if(!empty($_POST['allowAutoDeleteArchivedRepos'])) {
        $allowAutoDeleteArchivedRepos = validateData($_POST['allowAutoDeleteArchivedRepos']);
        exec("sed -i 's/^ALLOW_AUTODELETE_ARCHIVED_REPOS=.*/ALLOW_AUTODELETE_ARCHIVED_REPOS=\"${allowAutoDeleteArchivedRepos}\"/g' $REPOMANAGER_CONF");
    }

    // Retention, nb de repos à conserver avant suppression par l'automatisation
    if(!empty($_POST['retention'])) {
        $retention = validateData($_POST['retention']);
        exec("sed -i 's/^RETENTION=.*/RETENTION=\"${retention}\"/g' $REPOMANAGER_CONF");
    }

// D'autres paramètres enregistrés dans display.vars
    if (!empty($_POST['debugMode'])) {
        $debugMode = validateData($_POST['debugMode']);
        exec("sed -i 's/^\$debugMode.*/\$debugMode = \"${debugMode}\";/g' ${WWW_DIR}/vars/display.vars");
    }


// Puis on récupère les infos du fichier de conf pour les afficher
    $PACKAGE_TYPE = exec("grep '^PACKAGE_TYPE=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    if ($PACKAGE_TYPE === "deb") {
        $OS_FAMILY = "Debian";
    }
    if ($PACKAGE_TYPE === "rpm") {
        $OS_FAMILY = "Redhat";
    }
    $EMAIL_DEST = exec("grep '^EMAIL_DEST=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $MANAGE_PROFILES = exec("grep '^MANAGE_PROFILES=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $REPO_CONF_FILES_PREFIX = exec("grep '^REPO_CONF_FILES_PREFIX=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    // Paramètres de maj
    $UPDATE_AUTO = exec("grep '^UPDATE_AUTO=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $UPDATE_BACKUP = exec("grep '^UPDATE_BACKUP=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $UPDATE_BACKUP_DIR = exec("grep '^UPDATE_BACKUP_DIR=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    // Paramètres WWW
    $WWW_USER = exec("grep '^WWW_USER=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $WWW_HOSTNAME = exec("grep '^WWW_HOSTNAME=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $WWW_REPOS_DIR_URL = exec("grep '^WWW_REPOS_DIR_URL=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    // Environnements
    $ENVS = shell_exec("cat $ENV_CONF | grep -v '[ENVIRONNEMENTS]'"); // récupération de tous les env dans un tableau
    $ENVS = explode("\n", $ENVS);
    $ENVS = array_filter($ENVS); // on supprime les lignes vides du tableau si il y en a
    // Paramètres automatisation    
    $AUTOMATISATION_ENABLED = exec("grep '^AUTOMATISATION_ENABLED=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    if ($AUTOMATISATION_ENABLED == "yes" ) {
        $ALLOW_AUTOUPDATE_REPOS = exec("grep '^ALLOW_AUTOUPDATE_REPOS=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
        $ALLOW_AUTOUPDATE_REPOS_ENV = exec("grep '^ALLOW_AUTOUPDATE_REPOS_ENV=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
        $ALLOW_AUTODELETE_ARCHIVED_REPOS = exec("grep '^ALLOW_AUTODELETE_ARCHIVED_REPOS=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
        $RETENTION = exec("grep '^RETENTION=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    }
    
    # D'autres paramètres spécifiques à rpm :
    if ($OS_FAMILY == "Redhat") {   $RELEASEVER = exec("grep '^RELEASEVER=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
                                    $GPG_SIGN_PACKAGES = exec("grep '^GPG_SIGN_PACKAGES=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
                                    $GPG_KEYID = exec("grep '^GPG_KEYID=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    }
?>

<body>
<?php include('common-header.inc.php');?>
<section class="mainSectionLeft">
    <section class="left">
        <form action="configuration.php" method="post" autocomplete="off">
        <table class="table-medium">
            <tr>
                <td colspan="100%"><h4>CONFIGURATION GÉNÉRALE</h4</td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Famille d'OS</td>
                <td><input type="text" value="<?php echo "$OS_FAMILY";?>" readonly /></td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Nom de l'OS</td>
                <td><input type="text" value="<?php echo "$OS_INFO[name]";?>" readonly /></td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Version d'OS</td>
                <td><input type="text" value="<?php echo "$OS_INFO[version_id]";?>" readonly /></td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Si activé, repomanager se mettra à jour lors de sa prochaine exécution si une mise à jour est disponible" />Mise à jour automatique</td>
                <td>
                    <input type="radio" id="updateAuto_radio_yes" name="updateAuto" value="yes" <?php if ($UPDATE_AUTO == "yes" ) { echo 'checked'; }?>>
                    <label for="updateAuto_radio_yes">Yes</label>
                    <input type="radio" id="updateAuto_radio_no" name="updateAuto" value="no" <?php if ($UPDATE_AUTO == "no" ) { echo 'checked'; }?>>
                    <label for="updateAuto_radio_no">No</label>
                </td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Si activé, repomanager créera un backup dans le répertoire indiqué avant de se mettre à jour" />Sauvegarde avant mise à jour</td>
                <td>
                    <input type="radio" id="updateBackup_radio_yes" name="updateBackup" value="yes" <?php if ($UPDATE_BACKUP == "yes" ) { echo 'checked'; }?>>
                    <label for="updateBackup_radio_yes">Yes</label>
                    <input type="radio" id="updateBackup_radio_no" name="updateBackup" value="no" <?php if ($UPDATE_BACKUP == "no" ) { echo 'checked'; }?>>
                    <label for="updateBackup_radio_no">No</label>
                </td>
            <?php if ($UPDATE_BACKUP == "yes") {
            echo "<tr>";
            echo "<td class=\"td-large\"><img src=\"icons/info.png\" class=\"icon-verylowopacity\" title=\"Répertoire de destination des backups avant mise à jour\" />Répertoire de sauvegarde</td>";
            echo "<td><input type=\"text\" name=\"updateBackupDir\" autocomplete=\"off\" value=\"${UPDATE_BACKUP_DIR}\"></td>";
            echo "</td>";
            echo "</tr>";
            } ?>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="L'adresse renseignée recevra les mails d'erreurs et les rappels de planification. Il est possible de renseigner plusieurs adresses séparées par un espace" />Adresse mail</td>
                <td><input type="text" name="emailDest" autocomplete="off" value="<?php echo $EMAIL_DEST; ?>"></td>
            </tr>
            <tr>
                <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
            </tr>
        </table>
        </form>

        <form action="configuration.php" method="post" autocomplete="off">
        <table class="table-medium">
            <tr>
                <td><br><h4>REPOS</h4</td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Ce serveur gère des repos de paquets <?php if ($OS_FAMILY == "Redhat") { echo 'rpm'; } if ($OS_FAMILY == "Debian") { echo 'deb'; }?>" />Type de paquets</td>
                <td><input type="text" value=".<?php echo $PACKAGE_TYPE; ?>" readonly /></td>
            </tr>
            <?php
            if ($OS_FAMILY == "Redhat") {
                echo '<tr>';
                echo "<td class=\"td-large\"><img src=\"icons/info.png\" class=\"icon-verylowopacity\" title=\"Ce serveur créera des miroirs de repos pour CentOS $RELEASEVER uniquement\" />Version de paquets gérée</td>";
                echo "<td><input type=\"text\" name=\"releasever\" autocomplete=\"off\" value=\"${RELEASEVER}\"></td>";
                echo '</tr>';
                echo '<tr>';
                echo '<td class=\"td-large\"><img src="icons/info.png" class="icon-verylowopacity" title="Resigner les paquets du repo avec GPG après création ou mise à jour d\'un miroir de repo" />Signer les paquets avec GPG</td>';
                echo '<td>';
                if ($GPG_SIGN_PACKAGES == "yes") {
                    echo '<input type="radio" id="gpgSignPackages_yes" name="gpgSignPackages" value="yes" checked="yes" />';
                    echo '<label for="gpgSignPackages_yes">Yes</label>';
                    echo '<input type="radio" id="gpgSignPackages_no" name="gpgSignPackages" value="no" />';
                    echo '<label for="gpgSignPackages_no">No</label>';
                    echo '</td>';
                    echo '<tr>';
                    echo '<td class=\"td-large\"><img src="icons/info.png" class="icon-verylowopacity" title="Adresse mail liée au trousseau de clé GPG servant à resigner les paquets" />GPG Key ID (pour signature des paquets)</td>';
                    echo "<td><input type=\"text\" name=\"gpgKeyID\" autocomplete=\"off\" value=\"$GPG_KEYID\"></td>";
                    echo '</tr>'; 
                } else {
                    echo '<input type="radio" id="gpgSignPackages_yes" name="gpgSignPackages" value="yes"/>';
                    echo '<label for="gpgSignPackages_yes">Yes</label>';
                    echo '<input type="radio" id="gpgSignPackages_no" name="gpgSignPackages" value="no" checked="yes" />';
                    echo '<label for="gpgSignPackages_no">No</label>';
                    echo '</td>';
                }        
            }?>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Répertoire local de stockage des repos" />Répertoire des repos</td>
                <td><input type="text" autocomplete="off" value="<?php echo $REPOS_DIR; ?>" readonly /></td>
            <tr>
            <tr>
                <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
            </tr>
        </table>
        </form>

        <form action="configuration.php" method="post" autocomplete="off">
        <table class="table-medium">
            <tr>
                <td><br><h4>CONFIGURATION WEB</h4></td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Utilisateur Linux exécutant le service web de ce serveur" />Utilisateur web</td>
                <td><input type="text" name="wwwUser" autocomplete="off" value="<?php echo $WWW_USER; ?>"></td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Nom Hôte</td>
                <td><input type="text" name="wwwHostname" autocomplete="off" value="<?php echo $WWW_HOSTNAME; ?>"></td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="" />Url d'accès aux repos</td>
                <td><input type="text" name="wwwReposDirUrl" autocomplete="off" value="<?php echo $WWW_REPOS_DIR_URL; ?>"></td>
            </tr>
            <tr>
                <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Activer la gestion des profils pour les clients yum-update-auto / apt-update-auto (en cours de dev)" />Activer la gestion des profils</td>
                <td>
                    <input type="radio" id="manageProfiles_radio_yes" name="manageProfiles" value="yes" <?php if ($MANAGE_PROFILES == "yes") { echo 'checked'; }?>>
                    <label for="manageProfiles_radio_yes">Yes</label> 
                    <input type="radio" id="manageProfiles_radio_no" name="manageProfiles" value="no" <?php if ($MANAGE_PROFILES == "no") { echo 'checked'; }?>>
                    <label for="manageProfiles_radio_no">No</label> 
                </td>
            </tr>
            <tr>
                <?php
                if ($MANAGE_PROFILES == "yes") {
                    if ($OS_FAMILY == "Debian") {
                        echo '<td class=\"td-large\"><img src="icons/info.png" class="icon-verylowopacity" title="Préfixe s\'ajoutant au nom de fichiers .list générés par repomanager, ex : repomanager-debian.list" />Préfixe des fichiers de repo \'.list\'</td>';
                    }
                    if ($OS_FAMILY == "Redhat") {
                        echo '<td class=\"td-large\"><img src="icons/info.png" class="icon-verylowopacity" title="Préfixe s\'ajoutant au nom de fichiers .repo générés par repomanager, ex : repomanager-BaseOS.repo" />Préfixe des fichiers de repo \'.repo\'</td>';
                    }
                    echo "<td><input type=\"text\" name=\"symlinksPrefix\" autocomplete=\"off\" value=\"${REPO_CONF_FILES_PREFIX}\"></td>";
                }?>
            </tr>
            <tr>
                <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
            </tr>
        </table>
        </form>

        <form action="configuration.php" method="post" autocomplete="off">
        <table class="table-medium">
            <tr>
                <td><br><h4>ENVIRONNEMENTS</h4></td>
            </tr>
                <?php // Affichage des envs actuels
                foreach ($ENVS as $env) {
                    echo '<tr>';
                    if ($env === $DEFAULT_ENV) {
                        echo '<td class="td-large">Defaut</td>';
                    } else {
                        echo '<td class="td-large"></td>';
                    }
                    echo '<td>';
                    echo "<input type=\"text\" class=\"input-large\" name=\"actualEnv[]\" value=\"${env}\" />";
                    echo "<a href=\"configuration.php?deleteEnv=${env}\" title=\"Supprimer l'environnement ${env}\"><img src=\"icons/bin.png\" class=\"icon-lowopacity\"/></a>";
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
                <tr>
                    <td></td>
                    <td><input type="text" class="input-large" name="newEnv" placeholder="Ajouter un nouvel environnement" /><button type="submit" class="button-submit-xxsmall-blue">+</button></td></td>
                </tr>
                <tr>
                    <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
                </tr>
            </table>
            </form>

            <form action="configuration.php" method="post" autocomplete="off">
            <table class="table-medium"> 
                <tr>
                    <td><br><h4>PLANIFICATIONS</h4></td>
                </tr>
                <tr>
                    <td class="td-large"><img src="icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à exécuter des opérations automatiquement à des dates et heures spécifiques" />Activer les planifications</td>
                    <td>
                        <input type="radio" id="automatisation_radio_yes" name="automatisationEnable" value="yes" <?php if ($AUTOMATISATION_ENABLED == "yes" ) { echo 'checked'; }?>>
                        <label for="automatisation_radio_yes">Yes</label> 
                        <input type="radio" id="automatisation_radio_no" name="automatisationEnable" value="no" <?php if ($AUTOMATISATION_ENABLED == "no" ) { echo 'checked'; }?>>
                        <label for="automatisation_radio_no">No</label> 
                    </td>
                </tr>

            <?php if ($AUTOMATISATION_ENABLED == "yes") { 
            echo "<tr>";
            echo "<td class=\"td-large\"><img src=\"icons/info.png\" class=\"icon-verylowopacity\" title=\"Autoriser repomanager à mettre à jour un repo ou un groupe de repos spécifié\" />Autoriser la mise à jour automatique des repos</td>";
            echo "<td>";
            echo "<input type=\"radio\" id=\"allow_autoupdate_repos_radio_yes\" name=\"allowAutoUpdateRepos\" value=\"yes\""; if ($ALLOW_AUTOUPDATE_REPOS == "yes") { echo "checked >"; } else { echo " >"; }
            echo "<label for=\"allow_autoupdate_repos_radio_yes\">Yes</label>";
            echo "<input type=\"radio\" id=\"allow_autoupdate_repos_radio_no\" name=\"allowAutoUpdateRepos\" value=\"no\""; if ($ALLOW_AUTOUPDATE_REPOS == "no" ) { echo "checked >"; } else { echo " >"; }
            echo "<label for=\"allow_autoupdate_repos_radio_no\">No</label>";
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td class=\"td-large\"><img src=\"icons/info.png\" class=\"icon-verylowopacity\" title=\"Autoriser repomanager à modifier l'environnement d'un repo ou d'un groupe de repos spécifié\" />Autoriser la mise à jour automatique de l'env des repos</td>";
            echo "<td>";
            echo "<input type=\"radio\" id=\"allow_autoupdate_repos_env_radio_yes\" name=\"allowAutoUpdateReposEnv\" value=\"yes\""; if ($ALLOW_AUTOUPDATE_REPOS_ENV == "yes") { echo "checked >"; } else { echo " >"; }
            echo "<label for=\"allow_autoupdate_repos_env_radio_yes\">Yes</label>";
            echo "<input type=\"radio\" id=\"allow_autoupdate_repos_env_radio_no\" name=\"allowAutoUpdateReposEnv\" value=\"no\""; if ($ALLOW_AUTOUPDATE_REPOS_ENV == "no" ) { echo "checked >"; } else { echo " >"; }
            echo "<label for=\"allow_autoupdate_repos_env_radio_no\">No</label>";
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td class=\"td-large\"><img src=\"icons/info.png\" class=\"icon-verylowopacity\" title=\"Autoriser repomanager à supprimer les repos archivés (en fonction de la retention renseignée)\" />Autoriser la suppression automatique des anciens repos archivés</td>";
            echo "<td>";
            echo "<input type=\"radio\" id=\"allow_autodelete_old_repos_radio_yes\" name=\"allowAutoDeleteArchivedRepos\" value=\"yes\""; if ($ALLOW_AUTODELETE_ARCHIVED_REPOS == "yes") { echo "checked >"; } else { echo " >"; } 
            echo "<label for=\"allow_autodelete_old_repos_radio_yes\">Yes</label>";
            echo "<input type=\"radio\" id=\"allow_autodelete_old_repos_radio_no\" name=\"allowAutoDeleteArchivedRepos\" value=\"no\""; if ($ALLOW_AUTODELETE_ARCHIVED_REPOS == "no" ) { echo "checked >"; } else { echo " >"; }
            echo "<label for=\"allow_autodelete_old_repos_radio_no\">No</label>";
            echo "</td>";
            echo "</tr>"; 
            echo "<tr>";
            echo "<td class=\"td-large\"><img src=\"icons/info.png\" class=\"icon-verylowopacity\" title=\"Nombre de repos archivés du même nom à conserver avant suppression\" />Retention</td>";
            echo "<td><input type=\"number\" name=\"retention\" autocomplete=\"off\" value=\"${RETENTION}\"></td>";
            echo "</tr>";
            } ?>
                <tr>
                    <td>
                        <input type="hidden" name="enableCron" value="yes" />
                        <button type="submit" class="button-submit-medium-green">Enregistrer</button>
                    </td>
                </tr>
            </table>
            </form>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
    <form action="configuration.php" method="post">
        <table class="table-medium">
            <tr>
                <td><h4>MODE DEBUG</h4></td>
            </tr>
            <tr>
                <td>
                    <select name="debugMode" class="select-small">
                        <option value="enabled" <?php if ($debugMode == "enabled") { echo "selected"; }?>>enabled</option>
                        <option value="disabled" <?php if ($debugMode == "disabled") { echo "selected"; }?>>disabled</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
            </tr>
        </table>
    </form>

    <table class="table-medium">
        <tr>
            <td><br><h4>ETAT DES CRON</h4></td>
        </tr>
        <tr>
            <td><img src="icons/info.png" class="icon-verylowopacity" title="Tâche cron exécutant des actions régulières tels que vérifier la disponibilité d'une nouvelle mise à jour, remettre en ordre les permissions sur les répertoires de repos. Tâche journalière s'exécutant toutes les 5min." />Tâche cron journalière</td>
            <td>
            <?php
            // si un fichier de log existe, on récupère l'état
            if (file_exists("$CRON_LOG")) {
                $cronStatus = exec("grep 'Status=' $CRON_LOG | cut -d'=' -f2 | sed 's/\"//g'");
                if ($cronStatus === "OK") {
                    echo "Status : <span class=\"greentext\">${cronStatus}</span>";
                }
                if ($cronStatus === "KO") {
                    echo "Status : <span class=\"redtext\">${cronStatus}</span>";
                }
            }
            if (!file_exists("$CRON_LOG")) {
                echo "Status : inconnu";
            }
            ?>
            </td>
        </tr>
        <?php
        if ($AUTOMATISATION_ENABLED == "yes") {
            echo '<tr>';
            echo '<td><img src="icons/info.png" class="icon-verylowopacity" title="Tâche cron envoyant des rappels automatiques des futures planifications à venir" />Rappels automatique de planifications</td>';
            // On vérifie la présence d'une ligne contenant planReminders dans la crontab
            $cronStatus = shell_exec("crontab -l | grep 'planReminders'");
            if (empty($cronStatus)) {
                echo '<td>Status : <span class="redtext">Inactif</span></td>';
            } else {
                echo '<td>Status : <span class="greentext">Actif</span></td>';
            }
        }
        ?>

        <?php 
        /* Si un fichier de log cron existe c'est qu'il y a eu un problème lors de l'exécution de la tâche 
        On affiche donc une pastille rouge et le contenu du fichier de logs. 
        On affiche un bouton pour relancer la tâche manuellement 
        if (file_exists("$CRON_LOG")) {
            echo "<td>";
            echo "Etat des cron <img src=\"icons/red_circle.png\" class=\"cronStatus\">";
            echo "</td>";
            echo "<td>";
            echo "Relancer";
            echo "</td>";
            echo "</tr>";
            $content = file_get_contents("$CRON_LOG");
            echo "<td>";
            echo "<pre>";
            echo "$content";
            echo "</pre>";
            echo "</td>";
        } else {
            echo "<td>";
            echo "Etat des cron <img src=\"icons/green_circle.png\" class=\"cronStatus\">";
            echo "</td>";
        }*/
        ?>
    </table>
    </section>
</section>
<?php include('common-footer.inc.php'); ?>
</body>
</html>