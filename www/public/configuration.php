<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Seuls les admins ont accès à configuration.php
 */
if (!Controllers\Common::isadmin()) {
    header('Location: index.php');
    exit;
}

/**
 *  Mise à jour de Repomanager
 */
if (!empty($_GET['action']) and \Controllers\Common::validateData($_GET['action']) == "update") {
    $myupdate = new \Controllers\Update();
    $updateStatus = $myupdate->update();
}

/**
 *  Si un des formulaires de la page a été validé alors on entre dans cette condition
 */
if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) === "applyConfiguration") {
    /**
     *  Récupération de tous les paramètres définis dans le fichier repomanager.conf
     */
    $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF, true);

/**
 *  Section PATHS
 */
    /**
     *  Chemin du répertoire des repos sur le serveur
     */
    if (!empty($_POST['reposDir'])) {
        $reposDir = \Controllers\Common::validateData($_POST['reposDir']);
        /**
         *  Le chemin ne doit comporter que des lettres, des chiffres, des tirets et des slashs
         */
        if (Controllers\Common::isAlphanumDash($reposDir, array('/'))) {
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
        $emailDest = \Controllers\Common::validateData($_POST['emailDest']);

        if (Controllers\Common::isAlphanumDash($emailDest, array('@', '.'))) {
            $repomanager_conf_array['CONFIGURATION']['EMAIL_DEST'] = trim($emailDest);
        }
    }

    /**
     *  Si on souhaite activer ou non la gestion des hôtes
     */
    if (!empty($_POST['manageHosts']) and $_POST['manageHosts'] === "yes") {
        $repomanager_conf_array['CONFIGURATION']['MANAGE_HOSTS'] = 'yes';
    } else {
        $repomanager_conf_array['CONFIGURATION']['MANAGE_HOSTS'] = 'no';
    }

    /**
     *  Si on souhaite activer ou non la gestion des profils
     */
    if (!empty($_POST['manageProfiles']) and $_POST['manageProfiles'] === "yes") {
        $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = 'yes';
    } else {
        $repomanager_conf_array['CONFIGURATION']['MANAGE_PROFILES'] = 'no';
    }

    /**
     *  Modification du préfix des fichiers de conf repos
     */
    if (isset($_POST['repoConfPrefix'])) {
        $repomanager_conf_array['CONFIGURATION']['REPO_CONF_FILES_PREFIX'] = \Controllers\Common::validateData($_POST['repoConfPrefix']);
    }

/**
 *  Section RPM
 */
    /**
     *  Activer/désactiver les repos RPM
     */
    if (!empty($_POST['rpmRepo']) and $_POST['rpmRepo'] === "enabled") {
        $repomanager_conf_array['RPM']['RPM_REPO'] = 'enabled';
    } else {
        $repomanager_conf_array['RPM']['RPM_REPO'] = 'disabled';
    }

    /**
     *  Activer/désactiver la signature des paquets avec GPG
     */
    if (!empty($_POST['rpmSignPackages']) and $_POST['rpmSignPackages'] === "yes") {
        $repomanager_conf_array['RPM']['RPM_SIGN_PACKAGES'] = 'yes';
    } else {
        $repomanager_conf_array['RPM']['RPM_SIGN_PACKAGES'] = 'no';
    }

    /**
     *  Email lié à la clé GPG qui signe les paquets
     */
    if (!empty($_POST['rpmGpgKeyID'])) {
        $rpmGpgKeyID = \Controllers\Common::validateData($_POST['rpmGpgKeyID']);

        if (Controllers\Common::isAlphanumDash($rpmGpgKeyID, array('@', '.'))) {
            $repomanager_conf_array['RPM']['RPM_SIGN_GPG_KEYID'] = trim($rpmGpgKeyID);
        }
    }

    if (!empty($_POST['releasever']) and is_numeric($_POST['releasever'])) {
        $repomanager_conf_array['RPM']['RELEASEVER'] = $_POST['releasever'];
        file_put_contents('/etc/yum/vars/releasever', $_POST['releasever']);
    }

    /**
     *  Rpm mirror: default architecture
     */
    if (!empty($_POST['rpmDefaultArchitecture'])) {
        /**
         *  Convert array to a string with values separated by a comma
         */
        $rpmDefaultArchitecture = \Controllers\Common::validateData(implode(',', $_POST['rpmDefaultArchitecture']));
    } else {
        $rpmDefaultArchitecture = '';
    }
    if (Controllers\Common::isAlphanumDash($rpmDefaultArchitecture, array(','))) {
        $repomanager_conf_array['RPM']['RPM_DEFAULT_ARCH'] = trim($rpmDefaultArchitecture);
    }

    /**
     *  Rpm mirror: include source
     */
    if (!empty($_POST['rpmIncludeSource']) and $_POST['rpmIncludeSource'] === "yes") {
        $repomanager_conf_array['RPM']['RPM_INCLUDE_SOURCE'] = 'yes';
    } else {
        $repomanager_conf_array['RPM']['RPM_INCLUDE_SOURCE'] = 'no';
    }


/**
 *  Section DEB
 */
    /**
     *  Activer/désactiver les repos DEB
     */
    if (!empty($_POST['debRepo']) and $_POST['debRepo'] === "enabled") {
        $repomanager_conf_array['DEB']['DEB_REPO'] = 'enabled';
    } else {
        $repomanager_conf_array['DEB']['DEB_REPO'] = 'disabled';
    }

    /**
     *  Activer/désactiver la signature des repos avec GPG
     */
    if (!empty($_POST['debSignRepo']) and $_POST['debSignRepo'] === "yes") {
        $repomanager_conf_array['DEB']['DEB_SIGN_REPO'] = 'yes';
    } else {
        $repomanager_conf_array['DEB']['DEB_SIGN_REPO'] = 'no';
    }

    /**
     *  Email lié à la clé GPG qui signe les paquets
     */
    if (!empty($_POST['debGpgKeyID'])) {
        $debGpgKeyID = \Controllers\Common::validateData($_POST['debGpgKeyID']);

        if (Controllers\Common::isAlphanumDash($debGpgKeyID, array('@', '.'))) {
            $repomanager_conf_array['DEB']['DEB_SIGN_GPG_KEYID'] = trim($debGpgKeyID);
        }
    }

    /**
     *  Deb mirror: default architecture
     */
    if (!empty($_POST['debDefaultArchitecture'])) {
        /**
         *  Convert array to a string with values separated by a comma
         */
        $debDefaultArchitecture = \Controllers\Common::validateData(implode(',', $_POST['debDefaultArchitecture']));
    } else {
        $debDefaultArchitecture = '';
    }
    if (Controllers\Common::isAlphanumDash($debDefaultArchitecture, array(','))) {
        $repomanager_conf_array['DEB']['DEB_DEFAULT_ARCH'] = trim($debDefaultArchitecture);
    }

    /**
     *  Deb mirror: include source
     */
    if (!empty($_POST['debIncludeSource']) and $_POST['debIncludeSource'] === "yes") {
        $repomanager_conf_array['DEB']['DEB_INCLUDE_SOURCE'] = 'yes';
    } else {
        $repomanager_conf_array['DEB']['DEB_INCLUDE_SOURCE'] = 'no';
    }

    /**
     *  Deb mirror: default translations
     */
    if (!empty($_POST['debDefaultTranslation'])) {
        /**
         *  Convert array to a string with values separated by a comma
         */
        $debDefaultTranslation = \Controllers\Common::validateData(implode(',', $_POST['debDefaultTranslation']));
    } else {
        $debDefaultTranslation = '';
    }
    if (Controllers\Common::isAlphanumDash($debDefaultTranslation, array(','))) {
        $repomanager_conf_array['DEB']['DEB_DEFAULT_TRANSLATION'] = trim($debDefaultTranslation);
    }

/**
 *  Section UPDATE
 */

    /**
     *  Activer / désactiver la mise à jour automatique de repomanager
     */
    if (!empty($_POST['updateAuto']) and \Controllers\Common::validateData($_POST['updateAuto']) === "yes") {
        $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'yes';
    } else {
        $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'no';
    }

    /**
     *  Activer / désactiver le backup de repomanager avant mise à jour
     */
    if (!empty($_POST['updateBackup']) and \Controllers\Common::validateData($_POST['updateBackup']) === "yes") {
        $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'no';
    }


    /**
     *  Répertoire de destination des backups de repomanager sur le serveur si le paramètre UPDATE_BACKUP_ENABLED est activé
     */
    if (!empty($_POST['updateBackupDir'])) {
        $updateBackupDir = \Controllers\Common::validateData($_POST['updateBackupDir']);

        if (Controllers\Common::isAlphanumDash($updateBackupDir, array('/'))) {
            $repomanager_conf_array['UPDATE']['BACKUP_DIR'] = rtrim($updateBackupDir, '/');
        }
    }

    /**
     *  Branche git de mise à jour
     */
    if (!empty($_POST['updateBranch'])) {
        $updateBranch = \Controllers\Common::validateData($_POST['updateBranch']);

        if (Controllers\Common::isAlphanum($updateBranch, array('/'))) {
            $repomanager_conf_array['UPDATE']['UPDATE_BRANCH'] = $updateBranch;
        }
    }

/**
 *  Section WWW
 */

    /**
     *  Utilisateur web exécutant le serveur web
     */
    if (!empty($_POST['wwwUser'])) {
        $wwwUser = \Controllers\Common::validateData($_POST['wwwUser']);

        if (Controllers\Common::isAlphanumDash($wwwUser)) {
            $repomanager_conf_array['WWW']['WWW_USER'] = trim($wwwUser);
        }
    }

    /**
     *  Adresse web hôte de repomanager (https://xxxx)
     */
    $OLD_WWW_HOSTNAME = WWW_HOSTNAME; // On conserve le hostname actuel car on va s'en servir pour le remplacer dans les fichiers de conf ci dessous
    if (!empty($_POST['wwwHostname']) and $OLD_WWW_HOSTNAME !== \Controllers\Common::validateData($_POST['wwwHostname']) and \Controllers\Common::isAlphanumDash(Controllers\Common::validateData($_POST['wwwHostname']), array('.'))) {
        $NEW_WWW_HOSTNAME = trim(Controllers\Common::validateData($_POST['wwwHostname']));
        $repomanager_conf_array['WWW']['WWW_HOSTNAME'] = "$NEW_WWW_HOSTNAME";
    }

    /**
     *  URL d'accès aux repos. Exemple : https://xxxxxxx/repo
     */
    if (!empty($_POST['wwwReposDirUrl'])) {
        $wwwReposDirUrl = \Controllers\Common::validateData($_POST['wwwReposDirUrl']);

        if (Controllers\Common::isAlphanumDash($wwwReposDirUrl, array('.', '/', ':'))) {
            $repomanager_conf_array['WWW']['WWW_REPOS_DIR_URL'] = rtrim($wwwReposDirUrl, '/');
        }
    }

/**
 *  Section PLANS
 */

    /**
     *  Activation/désactivation de l'automatisation
     */
    if (!empty($_POST['automatisationEnable']) and \Controllers\Common::validateData($_POST['automatisationEnable']) === "yes") {
        $repomanager_conf_array['PLANS']['PLANS_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['PLANS']['PLANS_ENABLED'] = 'no';
    }

    /**
     *  Autoriser ou non la mise à jour des repos par l'automatisation
     */
    if (!empty($_POST['allowAutoUpdateRepos']) and \Controllers\Common::validateData($_POST['allowAutoUpdateRepos']) === "yes") {
        $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS'] = 'yes';
    } else {
        $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS'] = 'no';
    }

    /**
     *  Autoriser ou non le changement d'environnement par l'automatisation
     */
    // if (!empty($_POST['allowAutoUpdateReposEnv']) and \Controllers\Common::validateData($_POST['allowAutoUpdateReposEnv']) === "yes") {
    //     $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'yes';
    // } else {
    //     $repomanager_conf_array['PLANS']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'no';
    // }

    /**
     *  Autoriser ou non la suppression des repos archivés par l'automatisation
     */
    if (!empty($_POST['allowAutoDeleteArchivedRepos']) and \Controllers\Common::validateData($_POST['allowAutoDeleteArchivedRepos']) === "yes") {
        $repomanager_conf_array['PLANS']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'yes';
    } else {
        $repomanager_conf_array['PLANS']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'no';
    }

    /**
     *  Retention, nombre de repos à conserver avant suppression par l'automatisation
     */
    if (isset($_POST['retention'])) {
        $retention = \Controllers\Common::validateData($_POST['retention']);

        if (is_numeric($retention)) {
            $repomanager_conf_array['PLANS']['RETENTION'] = $retention;
        }
    }

    /**
     *  Activer / désactiver l'envoie de rappels de planifications futures (seul paramètre cron à ne pas être regroupé avec les autres paramètres cron)
     */
    if (!empty($_POST['cronSendReminders']) and \Controllers\Common::validateData($_POST['cronSendReminders']) === "yes") {
        $repomanager_conf_array['PLANS']['PLAN_REMINDERS_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['PLANS']['PLAN_REMINDERS_ENABLED'] = 'no';
    }

/**
 *  Section STATS
 */

    /**
     *  Activer / désactiver les statistiques
     */
    if (!empty($_POST['cronStatsEnable']) and \Controllers\Common::validateData($_POST['cronStatsEnable']) === "yes") {
        $repomanager_conf_array['STATS']['STATS_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['STATS']['STATS_ENABLED'] = 'no';
    }

    /**
     *  Chemin vers le fichier de log d'accès à analyser pour statistiques
     */
    if (!empty($_POST['statsLogPath'])) {
        $statsLogPath = \Controllers\Common::validateData($_POST['statsLogPath']);

        if (Controllers\Common::isAlphanumDash($statsLogPath, array('.', '/'))) {
            $repomanager_conf_array['STATS']['STATS_LOG_PATH'] = $statsLogPath;
        }

        /**
         *  On stoppe le process stats-log-parser.sh actuel, il sera relancé au rechargement de la page
         */
        \Controllers\Common::killStatsLogParser();
    }

    save($repomanager_conf_array);

    /**
     *  Nettoyage du cache de repos-list
     */
    \Controllers\Common::clearCache();
}

/**
 *  Enregistrement
 */
function save(array $array)
{
    /**
     *  On écrit toutes les modifications dans le fichier repomanager.conf
     */
    \Controllers\Common::writeToIni(REPOMANAGER_CONF, $array);

    /**
     *  Puis rechargement de la page pour appliquer les modifications de configuration
     */
    header('Location: configuration.php');
    exit;
}

/**
 *  Gestion des environnements
 *  Récupère la liste des environnements envoyés sous forme de tableau actualEnv[]
 *  Valeurs retournées dans le cas du renommage d'un environnement par exemple
 */
if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) === "addNewEnv") {

    /**
     *  Ajout d'un nouvel environnement
     */
    if (!empty($_POST['newEnv'])) {
        $myenv = new \Models\Environment(array('envName' => \Controllers\Common::validateData($_POST['newEnv'])));
        $myenv->new();
    }

    /**
     *  Nettoyage du cache de repos-list
     */
    \Controllers\Common::clearCache();
}

/**
 *  Renommage d'un environnement / changement de sens des environnements
 */
if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) === "applyEnvConfiguration") {
    if (!empty($_POST['actualEnv'])) {
        $myenv = new \Models\Environment();
        $myenv->edit($_POST['actualEnv']);
    }

    /**
     *  Nettoyage du cache de repos-list
     */
    \Controllers\Common::clearCache();
}

/**
 *  Suppression d'un environnement
 */
if (!empty($_GET['deleteEnv'])) {
    $myenv = new \Models\Environment(array('envName' => \Controllers\Common::validateData($_GET['deleteEnv'])));
    $myenv->delete();

    /**
     *  Nettoyage du cache de repos-list
     */
    \Controllers\Common::clearCache();
}

/**
 *  Création d'un nouvel utilisateur
 */
if (!empty($_POST['action']) and \Controllers\Common::validateData($_POST['action']) == 'createUser' and !empty($_POST['username']) and !empty($_POST['role'])) {
    $username = \Controllers\Common::validateData($_POST['username']);
    $role = \Controllers\Common::validateData($_POST['role']);
    $myuser = new \Models\Login();

    $result = $myuser->addUser($username, $role);

    /**
     *  Si la fonction a renvoyé false alors il y a eu une erreur lors de la création de l'utilisateur
     *  Sinon on récupère le mot de passe généré
     */
    if ($result !== false) {
        $newUserUsername = $username;
        $newUserPassword = $result;
    }
}

/**
 *  Réinitialisation du mot de passe d'un utilisateur
 */
if (isset($_GET['resetPassword']) and !empty($_GET['username'])) {
    $mylogin = new \Models\Login();

    $result = $mylogin->resetPassword($_GET['username']);

    /**
     *  Si la fonction a renvoyé false alors il y a eu une erreur lors de la création de l'utilisateur
     *  Sinon on récupère le mot de passe généré
     */
    if ($result !== false) {
        $newResetedPwdUsername = $_GET['username'];
        $newResetedPwdPassword = $result;
    }
}

/**
 *  Suppression d'un utilisateur
 */
if (isset($_GET['deleteUser']) and !empty($_GET['username'])) {
    $mylogin = new \Models\Login();
    $mylogin->deleteUser($_GET['username']);
}
?>

<body>
<?php include_once('../includes/header.inc.php');?>

<article>
<section class="mainSectionLeft">
    <section class="left">
        <h3>MAIN CONFIGURATION</h3>
        <form action="configuration.php" method="post" autocomplete="off">
        <input type="hidden" name="action" value="applyConfiguration" />
        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="" />OS family
                </td>
                <td>
                    <input type="text" value="<?= OS_FAMILY ?>" readonly />
                </td>
                <td class="td-fit">
                    <?php if (empty(OS_FAMILY)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="" />OS name
                </td>
                <td>
                    <input type="text" value="<?= OS_NAME ?>" readonly />
                </td>
                <td class="td-fit">
                    <?php if (empty(OS_NAME)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="" />OS version
                </td>
                <td>
                    <input type="text" value="<?= OS_VERSION ?>" readonly />
                </td>
                <td class="td-fit">
                    <?php if (empty(OS_VERSION)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="If enabled, repomanager will automatically update to the new available release." />Automatic update
                </td>
                <td>
                    <label class="onoff-switch-label">
                    <input name="updateAuto" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (UPDATE_AUTO == "yes") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(UPDATE_AUTO)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Specify from which target git branch updates must be applied (generally stable)." />Update target branch
                </td>
                <td>
                    <select name="updateBranch">
                        <option value="stable" <?php echo (UPDATE_BRANCH == "stable") ? 'selected' : ''; ?>>stable</option>
                        <option value="dev" <?php echo (UPDATE_BRANCH == "dev") ? 'selected' : ''; ?>>dev</option>
                    </select>
                </td>
                <?php
                if (UPDATE_AVAILABLE == "yes") {
                    echo '<td class="td-fit">';
                    echo '<input type="button" onclick="location.href=\'configuration.php?action=update\'" class="btn-xxsmall-green" title="Update repomanager to: ' . GIT_VERSION . '" value="↻">';
                    echo '</td>';
                }
                if (empty(UPDATE_BRANCH)) {
                    echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                }
                ?>
            </tr>
                <?php
                if (!empty($updateStatus)) : ?>
                    <tr>
                        <td></td>
                        <td colspan="2"><?= $updateStatus ?></td>
                    </tr>
                    <?php
                endif ?>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="If enabled, a backup of repomanager will be created before each update in specified directory." />Backup before update
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="updateBackup" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (UPDATE_BACKUP_ENABLED == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(UPDATE_BACKUP_ENABLED)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <?php
            if (UPDATE_BACKUP_ENABLED == "yes") : ?>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Repomanager backup before update target directory." />Backup before update target directory
                    </td>
                    <td>
                        <input type="text" name="updateBackupDir" autocomplete="off" value="<?= BACKUP_DIR ?>">
                    </td>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(BACKUP_DIR)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </td>
                </tr>
                <?php
            endif ?>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Specify email recipient(s) that will receive plan error/success notifications and plan reminder notifications. You can specify multiple recipients separated by a comma." />Contact
                </td>
                <td>
                    <input type="text" name="emailDest" autocomplete="off" value="<?= EMAIL_DEST ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(EMAIL_DEST)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
        </table>

        <br><h3>REPOSITORIES</h3>

        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Specify storage directory for repositories created by repomanager." /> Repositories storage directory
                </td>
                <td>
                    <input type="text" autocomplete="off" name="reposDir" value="<?= REPOS_DIR ?>" />
                </td>
                <td class="td-fit">
                    <?php if (empty(REPOS_DIR)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Enabled repositories access, size and packages statistics. Require a read-only access to webserver access logs (only nginx access logs supported) for <?= WWW_USER ?>." />Enable statistics
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="cronStatsEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (STATS_ENABLED == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(STATS_ENABLED)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <?php
            if (STATS_ENABLED == "yes") : ?>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Path to webserver access log (containing repomanager access logs). This file will be parsed to retieve repo access and generate statistics." />Path to access log to scan for statistics
                    </td>
                    <td>
                        <input type="text" autocomplete="off" name="statsLogPath" value="<?= STATS_LOG_PATH ?>" />
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(STATS_LOG_PATH)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        }
                        ?>
                    </td>
                </tr>
            <?php endif ?>
        </table>

        <h5>RPM</h5>

        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="If enabled, this server will manage and serve .rpm packages repositories"> Enable RPM repositories
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="rpmRepo" type="checkbox" class="onoff-switch-input" value="enabled" <?php echo (RPM_REPO == "enabled") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>
            <?php if (RPM_REPO == "enabled") : ?>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Sign RPM repos' packages with a GPG key."> Sign packages with GPG
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="rpmSignPackages" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (RPM_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <?php if (empty(RPM_SIGN_PACKAGES)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </tr>

                <?php if (RPM_SIGN_PACKAGES == 'yes') : ?>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.png" class="icon-verylowopacity" title="GPG key email address identifier. Needed to sign packages."> GPG key email address identifier
                        </td>
                        <td>
                            <input type="email" name="rpmGpgKeyID" autocomplete="off" value="<?= RPM_SIGN_GPG_KEYID ?>">
                        </td>
                        <td>
                            <?php if (empty(RPM_SIGN_GPG_KEYID)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.png" class="icon-verylowopacity" title="Specify which tool will be used to sign packages. (Pleas use rpmsign on recent systems. Use rpmresign on old RHEL (version 7)."> GPG signature method
                        </td>
                        <td>
                            <select name="rpmSignMethod">
                                <option value="rpmsign" <?php echo (RPM_SIGN_METHOD == 'rpmsign' ? 'selected' : '') ?>>rpmsign</option>
                                <option value="rpmresign" <?php echo (RPM_SIGN_METHOD == 'rpmresign' ? 'selected' : '') ?>>rpmresign (RPM4 perl module)</option>
                            </select>
                        </td>
                        <?php if (empty(RPM_SIGN_METHOD)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        } ?>
                    </tr>
                <?php endif ?>

                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="This server will create and serve repos for RHEL/CentOS release <?= RELEASEVER ?>. Be careful, if modified, this value will globally affect yum and own local yum updates of this server (if this server is RHEL/CentOS)." /> Release version
                    </td>
                    <td>
                        <input type="text" name="releasever" autocomplete="off" value="<?= RELEASEVER ?>">
                    </td>
                    <td class="td-fit">
                        <?php if (!file_exists('/etc/yum/vars/releasever')) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="/etc/yum/vars/releaserver does not exist." />';
                        }?>
                        <?php if (!is_readable('/etc/yum/vars/releasever')) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="/etc/yum/vars/releaserver is not readable for ' . WWW_USER . '" />';
                        }?>
                        <?php if (!is_writable('/etc/yum/vars/releasever')) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="/etc/yum/vars/releaserver is not writeable for ' . WWW_USER . '" />';
                        }?>
                        <?php if (empty(RELEASEVER)) {
                            echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                        }?>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Select default package architecture to use when creating rpm mirror."> Default package architecture
                    </td>
                    <td>
                        <select id="rpmArchitectureSelect" name="rpmDefaultArchitecture[]" multiple>
                            <option value="x86_64" <?php echo (in_array('x86_64', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>x86_64</option>
                            <option value="noarch" <?php echo (in_array('noarch', RPM_DEFAULT_ARCH)) ? 'selected' : ''; ?>>noarch</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Retrieve and include packages sources when creating rpm mirror."> Include packages sources when creating rpm mirror
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="rpmIncludeSource" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (RPM_INCLUDE_SOURCE == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>
            <?php endif ?>
        </table>

        <h5>DEB</h5>

        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="If enabled, this server will manage and serve .deb packages repositories"> Enable DEB repositories
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="debRepo" type="checkbox" class="onoff-switch-input" value="enabled" <?php echo (DEB_REPO == "enabled") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
            </tr>
        
            <?php if (DEB_REPO == "enabled") : ?>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Sign DEB repos with a GPG key."> Sign repos with GPG
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="debSignRepo" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (DEB_SIGN_REPO == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <?php if (empty(DEB_SIGN_REPO)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </tr>

                <?php if (DEB_SIGN_REPO == 'yes') : ?>
                    <tr>
                        <td class="td-large">
                            <img src="resources/icons/info.png" class="icon-verylowopacity" title="GPG key email address identifier. Needed to sign repositories."> GPG key email address identifier
                        </td>
                        <td>
                            <input type="text" name="debGpgKeyID" autocomplete="off" value="<?= DEB_SIGN_GPG_KEYID ?>">
                        </td>
                        <td>
                            <?php if (empty(DEB_SIGN_GPG_KEYID)) {
                                echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                            } ?>
                        </td>
                    </tr>
                <?php endif ?>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Select default package architecture to use when creating deb mirror."> Default package architecture
                    </td>
                    <td>
                        <select id="debArchitectureSelect" name="debDefaultArchitecture[]" multiple>
                            <option value="i386" <?php echo (in_array('i386', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>i386</option>
                            <option value="amd64" <?php echo (in_array('amd64', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>amd64</option>
                            <option value="armhf" <?php echo (in_array('armhf', DEB_DEFAULT_ARCH)) ? 'selected' : ''; ?>>armhf</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Include packages sources when creating deb mirror."> Include packages sources when creating deb mirror
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="debIncludeSource" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (DEB_INCLUDE_SOURCE == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Include packages specific translation when creating deb mirror."> Include packages translation(s) when creating deb mirror
                    </td>
                    <td>
                        <select id="debTranslationSelect" name="debDefaultTranslation[]" multiple>
                            <option value="en" <?php echo (in_array('en', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>en (english)</option>
                            <option value="fr" <?php echo (in_array('fr', DEB_DEFAULT_TRANSLATION)) ? 'selected' : ''; ?>>fr (french)</option>
                        </select>
                    </td>
                </tr>
            <?php endif ?>
        </table>

        <br><h3>WEB CONFIGURATION</h3>

        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Specify Linux web dedied user that execute this web server. Usually www-data or nginx." /> Web user
                </td>
                <td>
                    <input type="text" name="wwwUser" autocomplete="off" value="<?= WWW_USER ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(WWW_USER)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Repomanager dedied hostname." /> Hostname
                </td>
                <td>
                    <input type="text" name="wwwHostname" autocomplete="off" value="<?= WWW_HOSTNAME ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(WWW_HOSTNAME)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Specify target URL for Repomanager's repos root directory. Usually http://.../repo" /> Repos URL
                </td>
                <td>
                    <input type="text" name="wwwReposDirUrl" autocomplete="off" value="<?= WWW_REPOS_DIR_URL ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(WWW_REPOS_DIR_URL)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
        </table>

        <br><h3>HOSTS MANAGEMENT</h3>
        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Enable hosts managing. For hosts using linupdate." /> Manage hosts
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="manageHosts" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (MANAGE_HOSTS == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(MANAGE_HOSTS)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Enable hosts profiles managing. For hosts using linupdate." /> Manage profiles
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="manageProfiles" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (MANAGE_PROFILES == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(MANAGE_PROFILES)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
        <?php   if (MANAGE_PROFILES == "yes") : ?>
                    <td class="td-large">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Prefix that can be added to repo's configuration file name (e.g. 'myprefix-debian.list')." /> Repo file name prefix
                    </td>
                    <td>
                        <input type="text" name="repoConfPrefix" autocomplete="off" value="<?= REPO_CONF_FILES_PREFIX ?>">
                    </td>
        <?php   endif ?>
            </tr>
        </table>

        <br><h3>PLANIFICATIONS</h3>
        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Enable planifications" /> Enable plans
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="automatisationEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (PLANS_ENABLED == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(PLANS_ENABLED)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
    <?php if (PLANS_ENABLED == "yes") { ?>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="If enabled, plans will be able to update repos by creating new repo snapshot on the planned day and time." /> Allow automatic repos updates by plans
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="allowAutoUpdateRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (ALLOW_AUTOUPDATE_REPOS == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(ALLOW_AUTOUPDATE_REPOS)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="If enabled, plans will be able to delete oldest repos snapshots, depending on the specified retention parameter." /> Allow automatic deletion of old repos snapshots
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="allowAutoDeleteArchivedRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (ALLOW_AUTODELETE_ARCHIVED_REPOS == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(ALLOW_AUTODELETE_ARCHIVED_REPOS)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr> 
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Maximum number of snapshots to keep by repo, before deleting." /> Retention
                </td>
                <td>
                    <input type="number" name="retention" autocomplete="off" value="<?= RETENTION ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(RETENTION)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="If enabled, specified email recipients will receive reminder(s) for each planned tasks to come. A mail configuration must be setted on this server (e.g. sendmail)." /> Enable plan reminders
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="cronSendReminders" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (PLAN_REMINDERS_ENABLED == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(PLAN_REMINDERS_ENABLED)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="This parameter must be specified." />';
                    } ?>
                </td>
            </tr> 
    <?php } ?>
            <tr>
                <td><button type="submit" class="btn-medium-green">Save</button></td>
            </tr>
        </table>
        </form>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
        <h3>ENVIRONMENTS</h3>
        <table class="table-medium">
        <form action="configuration.php" method="post" autocomplete="off">
            <input type="hidden" name="action" value="applyEnvConfiguration" />
                <?php

                /**
                 *  Affichage des environnements actuels
                 */
                $myenvs = new \Models\Environment();
                $envs = $myenvs->listAll();

                foreach ($envs as $envName) : ?>
                    <tr>
                        <td>
                        <input type="text" name="actualEnv[]" value="<?= $envName ?>" />
                    </td>
                    <td class="td-fit center">
                        <img src="resources/icons/bin.png" class="envDeleteToggle-<?= $envName ?> icon-lowopacity" title="Delete environment <?= $envName ?>"/>
                        <?php \Controllers\Common::deleteConfirm("Are you sure you want to delete environment $envName", "?deleteEnv=${envName}", "envDeleteDiv-${envName}", "envDeleteToggle-${envName}"); ?>
                    </td>
                    <?php
                    if ($envName == DEFAULT_ENV) {
                        echo '<td>(default)</td>';
                    } else {
                        echo '<td></td>';
                    }
                    echo '</tr>';
                endforeach ?>
            <input type="submit" class="hide" value="Valider" /> <!-- bouton caché, afin de taper Entrée pour appliquer les modifications -->
        </form>

        <form action="configuration.php" method="post" autocomplete="off">
            <input type="hidden" name="action" value="addNewEnv" />
            <tr>
                <td><input type="text" name="newEnv" placeholder="Add a new environment" /></td>
                <td class="td-fit"><button type="submit" class="btn-xxsmall-blue">+</button></td>
                <td class="td-fit">
                    <?php if (empty(ENVS)) {
                        echo '<img src="resources/icons/warning.png" class="icon" title="At least 1 environment must be configured." />';
                    } ?>
                </td>
                <td></td>
            </tr>
        </form>
        </table>

        <br><h3>DATABASES</h3>
        <table class="table-generic-blue table-large">
            <tr>
                <td class="td-50">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Main database. Repomanager cannot run if database is on error." /> Main
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier de base de données
                     */
                    if (!is_readable(DB) or !is_writable(DB)) {
                        echo "Main database is not readable / writable.";
                    } else {
                        echo '<span title="OK">Access</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new \Models\Connection('main');

                    if (!$myconn->checkMainTables()) {
                        echo '<span title="One or more table are missing.">Tables state</span><img src="resources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="All tables are present.">Tables state</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>

            <?php
            if (STATS_ENABLED == "yes") { ?>
            <tr>
                <td class="td-50">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Repositories' statistics database." /> Statistics
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier
                     */
                    if (!is_readable(STATS_DB) or !is_writable(STATS_DB)) {
                        echo "Stats database is not readable / writable.";
                    } else {
                        echo '<span title="OK">Access</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new \Models\Connection('stats');

                    if (!$myconn->checkStatsTables()) {
                        echo '<span title="One or more table are missing.">Tables state</span><img src="resources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="All tables are present.">Tables state</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>
            <?php   }

            if (MANAGE_HOSTS == "yes") { ?>
            <tr>
                <td class="td-50">
                    <img src="resources/icons/info.png" class="icon-verylowopacity" title="Hosts' database." /> Hosts
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier
                     */
                    if (!is_readable(HOSTS_DB) or !is_writable(HOSTS_DB)) {
                        echo "Hosts database is not readable / writable.";
                    } else {
                        echo '<span title="OK">Access</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>

                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new \Models\Connection('hosts');

                    if (!$myconn->checkHostsTables()) {
                        echo '<span title="One or more table are missing.">Tables state</span><img src="resources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="All tables are present.">Tables state</span><img src="resources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>
            <?php   } ?>
        </table>

        <br><h3>SERVICE</h3>
        <form action="configuration.php" method="post">
            <input type="hidden" name="action" value="applyCronConfiguration" />
            <table class="table-generic-blue table-large">
                <tr>
                    <td class="td-50">
                        <img src="resources/icons/info.png" class="icon-verylowopacity" title="Systemd repomanager service is used to execute regular tasks such as applying permissions on repos dirs, executings plans (if enabled), sending plan reminders (if enabled)." />  Repomanager service state
                    </td>
                    <td>
                        <?php
                        if (SERVICE_RUNNING) {
                            echo '<span title="Service is running">Status <img src="resources/icons/greencircle.png" class="icon-small" /></span>';
                        } else {
                            echo '<span title="Service is not running">Status <img src="resources/icons/redcircle.png" class="icon-small" /></span>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </form>
    </section>

    <?php
    /**
     *  Cette section est accessible uniquement pour les utilisateurs dont le role est 'super-administrator'
     */
    if ($_SESSION['role'] === 'super-administrator') : ?>
        <section class="right">
            <h3>USERS</h3>
            <form action="configuration.php" method="post" autocomplete="off">

                <input type="hidden" name="action" value="createUser" />
                <p>Create an user:</p>
                <input class="input-medium" type="text" name="username" placeholder="Username" />
                <select name="role" class="select-medium">
                    <option value="">Select role...</option>
                    <option value="usage">usage</option>
                    <option value="administrator">administrateur</option>
                </select>
                <button class="btn-xxsmall-blue">+</button>
            </form>
            <?php
            /**
             *  Cas où un nouveau mot de passe a été généré
             */
            if (!empty($newUserUsername) and !empty($newUserPassword)) {
                echo '<p class="greentext">Temporary password generated for <b>' . $newUserUsername . '</b>: ' . $newUserPassword . '</p>';
            }
            /**
             *  Cas où un mot de passe a été reset
             */
            if (!empty($newResetedPwdUsername) and !empty($newResetedPwdPassword)) {
                echo '<p class="greentext">A new password has been generated for <b>' . $newResetedPwdUsername . '</b>: ' . $newResetedPwdPassword . '</p>';
            }

            echo '<br>';

            /**
             *  Affichage des utilisateurs existants
             */
            $myuser = new \Models\Login();
            $users = $myuser->getUsers();

            if (!empty($users)) : ?>
                <table class="table-generic-blue">
                    <tr class="no-bkg">
                        <td>Username</td>
                        <td>Role</td>
                        <td>Account type</td>
                        <td></td>
                    </tr>
                    <?php
                    foreach ($users as $user) : ?>
                        <tr>
                            <td>
                                <?= $user['Username'] ?>
                            </td>
                            <td>
                                <?= $user['Role_name'] ?>
                            </td>
                            <td>
                                <?= $user['Type'] ?>
                            </td>
                            <?php
                            if ($user['Username'] != 'admin') {
                                echo '<td class="td-fit">';
                                echo '<a href="?resetPassword&username=' . $user['Username'] . '" title="Réinitialiser le mot de passe de ' . $user['Username'] . '"><img src="resources/icons/update.png" class="icon-lowopacity" /></a>';
                                echo '<a href="?deleteUser&username=' . $user['Username'] . '" title="Supprimer l\'utilisateur ' . $user['Username'] . '"><img src="resources/icons/bin.png" class="icon-lowopacity" /></a>';
                                echo '</td>';
                            } else {
                                echo '<td></td>';
                            } ?>
                        </tr>
                        <?php
                    endforeach; ?>
                </table>
                <?php
            endif; ?>
        </section>
        <?php
    endif ?>
</section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>
</body>
</html>