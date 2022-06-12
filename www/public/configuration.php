<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Seuls les admins ont accès à configuration.php
 */
if (!Models\Common::isadmin()) {
    header('Location: index.php');
    exit;
}

/**
 *  Mise à jour de Repomanager
 */
if (!empty($_GET['action']) and \Models\Common::validateData($_GET['action']) == "update") {
    $updateStatus = \Models\Common::repomanagerUpdate();
}

// Si un des formulaires de la page a été validé alors on entre dans cette condition
if (!empty($_POST['action']) and \Models\Common::validateData($_POST['action']) === "applyConfiguration") {
    // Récupération de tous les paramètres définis dans le fichier repomanager.conf
    $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF, true);

/**
 *  Section PATHS
 */
    /**
     *  Chemin du répertoire des repos sur le serveur
     */
    if (!empty($_POST['reposDir'])) {
        $reposDir = \Models\Common::validateData($_POST['reposDir']);
        /**
         *  Le chemin ne doit comporter que des lettres, des chiffres, des tirets et des slashs
         */
        if (Models\Common::isAlphanumDash($reposDir, array('/'))) {
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
        $emailDest = \Models\Common::validateData($_POST['emailDest']);

        if (Models\Common::isAlphanumDash($emailDest, array('@', '.'))) {
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
        $repomanager_conf_array['CONFIGURATION']['REPO_CONF_FILES_PREFIX'] = \Models\Common::validateData($_POST['repoConfPrefix']);
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
        $rpmGpgKeyID = \Models\Common::validateData($_POST['rpmGpgKeyID']);

        if (Models\Common::isAlphanumDash($rpmGpgKeyID, array('@', '.'))) {
            $repomanager_conf_array['RPM']['RPM_SIGN_GPG_KEYID'] = trim($rpmGpgKeyID);
        }
    }

    if (!empty($_POST['releasever']) and is_numeric($_POST['releasever'])) {
        $repomanager_conf_array['RPM']['RELEASEVER'] = $_POST['releasever'];
        file_put_contents('/etc/yum/vars/releasever', $_POST['releasever']);
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
        $debGpgKeyID = \Models\Common::validateData($_POST['debGpgKeyID']);

        if (Models\Common::isAlphanumDash($debGpgKeyID, array('@', '.'))) {
            $repomanager_conf_array['DEB']['DEB_SIGN_GPG_KEYID'] = trim($debGpgKeyID);
        }
    }

/**
 *  Section UPDATE
 */

    /**
     *  Activer / désactiver la mise à jour automatique de repomanager
     */
    if (!empty($_POST['updateAuto']) and \Models\Common::validateData($_POST['updateAuto']) === "yes") {
        $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'yes';
    } else {
        $repomanager_conf_array['UPDATE']['UPDATE_AUTO'] = 'no';
    }

    /**
     *  Activer / désactiver le backup de repomanager avant mise à jour
     */
    if (!empty($_POST['updateBackup']) and \Models\Common::validateData($_POST['updateBackup']) === "yes") {
        $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['UPDATE']['UPDATE_BACKUP_ENABLED'] = 'no';
    }


    /**
     *  Répertoire de destination des backups de repomanager sur le serveur si le paramètre UPDATE_BACKUP_ENABLED est activé
     */
    if (!empty($_POST['updateBackupDir'])) {
        $updateBackupDir = \Models\Common::validateData($_POST['updateBackupDir']);

        if (Models\Common::isAlphanumDash($updateBackupDir, array('/'))) {
            $repomanager_conf_array['UPDATE']['BACKUP_DIR'] = rtrim($updateBackupDir, '/');
        }
    }

    /**
     *  Branche git de mise à jour
     */
    if (!empty($_POST['updateBranch'])) {
        $updateBranch = \Models\Common::validateData($_POST['updateBranch']);

        if (Models\Common::isAlphanum($updateBranch, array('/'))) {
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
        $wwwUser = \Models\Common::validateData($_POST['wwwUser']);

        if (Models\Common::isAlphanumDash($wwwUser)) {
            $repomanager_conf_array['WWW']['WWW_USER'] = trim($wwwUser);
        }
    }

    /**
     *  Adresse web hôte de repomanager (https://xxxx)
     */
    $OLD_WWW_HOSTNAME = WWW_HOSTNAME; // On conserve le hostname actuel car on va s'en servir pour le remplacer dans les fichiers de conf ci dessous
    if (!empty($_POST['wwwHostname']) and $OLD_WWW_HOSTNAME !== \Models\Common::validateData($_POST['wwwHostname']) and \Models\Common::isAlphanumDash(Models\Common::validateData($_POST['wwwHostname']), array('.'))) {
        $NEW_WWW_HOSTNAME = trim(Models\Common::validateData($_POST['wwwHostname']));
        $repomanager_conf_array['WWW']['WWW_HOSTNAME'] = "$NEW_WWW_HOSTNAME";
    }

    /**
     *  URL d'accès aux repos. Exemple : https://xxxxxxx/repo
     */
    if (!empty($_POST['wwwReposDirUrl'])) {
        $wwwReposDirUrl = \Models\Common::validateData($_POST['wwwReposDirUrl']);

        if (Models\Common::isAlphanumDash($wwwReposDirUrl, array('.', '/', ':'))) {
            $repomanager_conf_array['WWW']['WWW_REPOS_DIR_URL'] = rtrim($wwwReposDirUrl, '/');
        }
    }

    /**
     *  Chemin vers le fichier de log d'accès à analyser pour statistiques
     */
    if (!empty($_POST['statsLogPath'])) {
        $statsLogPath = \Models\Common::validateData($_POST['statsLogPath']);

        if (Models\Common::isAlphanumDash($statsLogPath, array('.', '/'))) {
            $repomanager_conf_array['WWW']['WWW_STATS_LOG_PATH'] = $statsLogPath;
        }

        /**
         *  On stoppe le process stats-log-parser.sh actuel, il sera relancé au rechargement de la page
         */
        \Models\Common::killStatsLogParser();
    }
/**
 *  Section AUTOMATISATION
 */

    /**
     *  Activation/désactivation de l'automatisation
     */
    if (!empty($_POST['automatisationEnable']) and \Models\Common::validateData($_POST['automatisationEnable']) === "yes") {
        $repomanager_conf_array['AUTOMATISATION']['AUTOMATISATION_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['AUTOMATISATION']['AUTOMATISATION_ENABLED'] = 'no';
    }

    /**
     *  Autoriser ou non la mise à jour des repos par l'automatisation
     */
    if (!empty($_POST['allowAutoUpdateRepos']) and \Models\Common::validateData($_POST['allowAutoUpdateRepos']) === "yes") {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS'] = 'yes';
    } else {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS'] = 'no';
    }

    /**
     *  Autoriser ou non le changement d'environnement par l'automatisation
     */
    if (!empty($_POST['allowAutoUpdateReposEnv']) and \Models\Common::validateData($_POST['allowAutoUpdateReposEnv']) === "yes") {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'yes';
    } else {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTOUPDATE_REPOS_ENV'] = 'no';
    }

    /**
     *  Autoriser ou non la suppression des repos archivés par l'automatisation
     */
    if (!empty($_POST['allowAutoDeleteArchivedRepos']) and \Models\Common::validateData($_POST['allowAutoDeleteArchivedRepos']) === "yes") {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'yes';
    } else {
        $repomanager_conf_array['AUTOMATISATION']['ALLOW_AUTODELETE_ARCHIVED_REPOS'] = 'no';
    }

    /**
     *  Retention, nombre de repos à conserver avant suppression par l'automatisation
     */
    if (isset($_POST['retention'])) {
        $retention = \Models\Common::validateData($_POST['retention']);

        if (is_numeric($retention)) {
            $repomanager_conf_array['AUTOMATISATION']['RETENTION'] = $retention;
        }
    }

    /**
     *  Activer / désactiver l'envoie de rappels de planifications futures (seul paramètre cron à ne pas être regroupé avec les autres paramètres cron)
     */
    if (!empty($_POST['cronSendReminders']) and \Models\Common::validateData($_POST['cronSendReminders']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_PLAN_REMINDERS_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_PLAN_REMINDERS_ENABLED'] = 'no';
    }

    /**
     *  Activer / désactiver les statistiques
     */
    if (!empty($_POST['cronStatsEnable']) and \Models\Common::validateData($_POST['cronStatsEnable']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_STATS_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_STATS_ENABLED'] = 'no';
    }

    save($repomanager_conf_array);

    /**
     *  Nettoyage du cache de repos-list
     */
    \Models\Common::clearCache();
}

/**
 *  Section CRON
 *  Si un des formulaires de la page a été validé alors on entre dans cette condition
 */
if (!empty($_POST['action']) and \Models\Common::validateData($_POST['action']) === "applyCronConfiguration") {
    // Récupération de tous les paramètres définis dans le fichier repomanager.conf
    $repomanager_conf_array = parse_ini_file(REPOMANAGER_CONF, true);

    /**
     *  Activation des tâches cron
     */
    if (!empty($_POST['cronDailyEnable']) and \Models\Common::validateData($_POST['cronDailyEnable']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_DAILY_ENABLED'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_DAILY_ENABLED'] = 'no';
    }

    /**
     *  Activer / désactiver la ré-application régulière des permissions sur les répertoires de repos
     */
    if (!empty($_POST['cronApplyPerms']) and \Models\Common::validateData($_POST['cronApplyPerms']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_APPLY_PERMS'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_APPLY_PERMS'] = 'no';
    }

    /**
     *  Activer / désactiver la sauvegarde régulière de la DB et des fichiers de configuration
     */
    if (!empty($_POST['cronSaveConf']) and \Models\Common::validateData($_POST['cronSaveConf']) === "yes") {
        $repomanager_conf_array['CRON']['CRON_SAVE_CONF'] = 'yes';
    } else {
        $repomanager_conf_array['CRON']['CRON_SAVE_CONF'] = 'no';
    }

    save($repomanager_conf_array);
}

/**
 *  Enregistrement
 */
function save(array $array)
{
    /**
     *  On écrit toutes les modifications dans le fichier repomanager.conf
     */
    \Models\Common::writeToIni(REPOMANAGER_CONF, $array);

    /**
     *  On appelle enableCron pour qu'il ré-écrive / supprime les lignes de la crontab
     */
    \Models\Common::enableCron();

    /**
     *  Puis rechargement de la page pour appliquer les modifications de configuration
     */
    header('Location: configuration.php');
    exit;
}

/**
 * Deploiement des tâches cron
 */
if (!empty($_GET['action']) and \Models\Common::validateData($_GET['action']) == "enableCron") {
    \Models\Common::enableCron();
}

/**
 *  Gestion des environnements
 *  Récupère la liste des environnements envoyés sous forme de tableau actualEnv[]
 *  Valeurs retournées dans le cas du renommage d'un environnement par exemple
 */
if (!empty($_POST['action']) and \Models\Common::validateData($_POST['action']) === "addNewEnv") {

    /**
     *  Ajout d'un nouvel environnement
     */
    if (!empty($_POST['newEnv'])) {
        $myenv = new \Models\Environnement(array('envName' => \Models\Common::validateData($_POST['newEnv'])));
        $myenv->new();
    }

    /**
     *  Nettoyage du cache de repos-list
     */
    \Models\Common::clearCache();
}

/**
 *  Renommage d'un environnement / changement de sens des environnements
 */
if (!empty($_POST['action']) and \Models\Common::validateData($_POST['action']) === "applyEnvConfiguration") {
    if (!empty($_POST['actualEnv'])) {
        $myenv = new \Models\Environnement();
        $myenv->edit($_POST['actualEnv']);
    }

    /**
     *  Nettoyage du cache de repos-list
     */
    \Models\Common::clearCache();
}

/**
 *  Suppression d'un environnement
 */
if (!empty($_GET['deleteEnv'])) {
    $myenv = new \Models\Environnement(array('envName' => \Models\Common::validateData($_GET['deleteEnv'])));
    $myenv->delete();

    /**
     *  Nettoyage du cache de repos-list
     */
    \Models\Common::clearCache();
}

/**
 *  Création d'un nouvel utilisateur
 */
if (!empty($_POST['action']) and \Models\Common::validateData($_POST['action']) == 'createUser' and !empty($_POST['username']) and !empty($_POST['role'])) {
    $username = \Models\Common::validateData($_POST['username']);
    $role = \Models\Common::validateData($_POST['role']);
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
        <h3>CONFIGURATION GÉNÉRALE</h3>
        <form action="configuration.php" method="post" autocomplete="off">
        <input type="hidden" name="action" value="applyConfiguration" />
        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Famille d'OS
                </td>
                <td>
                    <input type="text" value="<?= OS_FAMILY ?>" readonly />
                </td>
                <td class="td-fit">
                    <?php if (empty(OS_FAMILY)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Nom de l'OS
                </td>
                <td>
                    <input type="text" value="<?= OS_NAME ?>" readonly />
                </td>
                <td class="td-fit">
                    <?php if (empty(OS_NAME)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Version d'OS
                </td>
                <td>
                    <input type="text" value="<?= OS_VERSION ?>" readonly />
                </td>
                <td class="td-fit">
                    <?php if (empty(OS_VERSION)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si activé, repomanager se mettra à jour automatiquement si une mise à jour est disponible" />Mise à jour automatique
                </td>
                <td>
                    <label class="onoff-switch-label">
                    <input name="updateAuto" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (UPDATE_AUTO == "yes") ? 'checked' : ''; ?>>
                    <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(UPDATE_AUTO)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Choisir quelle version de mise à jour recevoir" />Branche de mise à jour
                </td>
                <td>
                    <select name="updateBranch">
                        <option value="beta" <?php echo (UPDATE_BRANCH == "beta") ? 'selected' : ''; ?>>beta</option>
                        <option value="stable" <?php echo (UPDATE_BRANCH == "stable") ? 'selected' : ''; ?>>stable</option>
                    </select>
                </td>
                <?php
                if (UPDATE_AVAILABLE == "yes") {
                    echo '<td class="td-fit">';
                    echo '<input type="button" onclick="location.href=\'configuration.php?action=update\'" class="btn-xxsmall-green" title="Mettre à jour repomanager vers : ' . GIT_VERSION . '" value="↻">';
                    echo '</td>';
                }
                if (empty(UPDATE_BRANCH)) {
                    echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                }
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
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si activé, repomanager créera un backup dans le répertoire indiqué avant de se mettre à jour" />Sauvegarde avant mise à jour
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="updateBackup" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (UPDATE_BACKUP_ENABLED == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(UPDATE_BACKUP_ENABLED)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <?php
            if (UPDATE_BACKUP_ENABLED == "yes") : ?>
                <tr>
                    <td class="td-large">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Répertoire de destination des backups avant mise à jour" />Répertoire de sauvegarde
                    </td>
                    <td>
                        <input type="text" name="updateBackupDir" autocomplete="off" value="<?= BACKUP_DIR ?>">
                    </td>
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(BACKUP_DIR)) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                        } ?>
                    </td>
                </tr>
                <?php
            endif ?>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="L'adresse renseignée recevra les mails d'erreurs et les rappels de planification. Il est possible de renseigner plusieurs adresses séparées par une virgule" />Contact
                </td>
                <td>
                    <input type="text" name="emailDest" autocomplete="off" value="<?= EMAIL_DEST ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(EMAIL_DEST)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
        </table>

        <br><h3>REPOS</h3>

        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Répertoire local de stockage des repos" />Répertoire des repos
                </td>
                <td>
                    <input type="text" autocomplete="off" name="reposDir" value="<?= REPOS_DIR ?>" />
                </td>
                <td class="td-fit">
                    <?php if (empty(REPOS_DIR)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Activer la collecte de statistiques d'accès au repo, sa taille, son nombre de paquets. Ce paramètre nécessite de la configuration supplémentaire dans le vhost de ce serveur, ainsi qu'un accès aux logs d'accès de ce serveur à <?= WWW_USER ?>." />Activer les statistiques
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="cronStatsEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (CRON_STATS_ENABLED == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(CRON_STATS_ENABLED)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <?php
            if (CRON_STATS_ENABLED == "yes") : ?>
                <tr>
                    <td class="td-large">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Chemin vers le fichier de log du serveur web contenant des requêtes d\'accès aux repos. Ce fichier est parsé pour générer des statistiques." />Fichier de log à analyser pour les statistiques
                    </td>
                    <td>
                        <input type="text" autocomplete="off" name="statsLogPath" value="<?= WWW_STATS_LOG_PATH ?>" />
                    </td>
                    <td class="td-fit">
                        <?php
                        if (empty(WWW_STATS_LOG_PATH)) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                        }
                        ?>
                    </td>
                </tr>
            <?php endif ?>
        </table>

        <h4>RPM</h4>

        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title=""> Repo de paquets RPM
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
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Signer les paquets du repo avec GPG après création ou mise à jour d'un miroir de repo."> Signer les paquets avec GPG
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="rpmSignPackages" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (RPM_SIGN_PACKAGES == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <?php if (empty(RPM_SIGN_PACKAGES)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </tr>

                <?php if (RPM_SIGN_PACKAGES == 'yes') : ?>
                    <tr>
                        <td class="td-large">
                            <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Adresse email liée à la clé GPG utilisée pour signer les paquets."> Identifiant email de la clé GPG
                        </td>
                        <td>
                            <input type="email" name="rpmGpgKeyID" autocomplete="off" value="<?= RPM_SIGN_GPG_KEYID ?>">
                        </td>
                        <td>
                            <?php if (empty(RPM_SIGN_GPG_KEYID)) {
                                echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                            } ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="td-large">
                            <img src="ressources/icons/info.png" class="icon-verylowopacity" title=""> Méthode de signature GPG
                        </td>
                        <td>
                            <select name="rpmSignMethod">
                                <option value="rpmsign" <?php echo (RPM_SIGN_METHOD == 'rpmsign' ? 'selected' : '') ?>>rpmsign</option>
                                <option value="rpmresign" <?php echo (RPM_SIGN_METHOD == 'rpmresign' ? 'selected' : '') ?>>rpmresign (RPM4 perl module)</option>
                            </select>
                        </td>
                        <?php if (empty(RPM_SIGN_METHOD)) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                        } ?>
                    </tr>
                <?php endif ?>

                <tr>
                    <td class="td-large">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Ce serveur créera des miroirs de repos pour CentOS <?= RELEASEVER ?>. Attention cette valeur est globale à yum et peut impacter les mises à jour du serveur <?= WWW_HOSTNAME ?> si elle est modifiée." />Version de paquets gérée
                    </td>
                    <td>
                        <input type="text" name="releasever" autocomplete="off" value="<?= RELEASEVER ?>">
                    </td>
                    <td class="td-fit">
                        <?php if (!file_exists('/etc/yum/vars/releasever')) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Le fichier /etc/yum/vars/releaserver n\'existe pas" />';
                        }?>
                        <?php if (!is_readable('/etc/yum/vars/releasever')) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Le fichier /etc/yum/vars/releaserver n\'est pas accessible pour ' . WWW_USER . '" />';
                        }?>
                        <?php if (!is_writable('/etc/yum/vars/releasever')) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Le fichier /etc/yum/vars/releaserver n\'est pas modifiable pour ' . WWW_USER . '" />';
                        }?>
                        <?php if (empty(RELEASEVER)) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                        }?>
                    </td>
                </tr>
            <?php endif ?>
        </table>

        <h4>DEB</h4>

        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title=""> Repo de paquets DEB
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
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Signer les repos avec GPG."> Signer les repos avec GPG
                    </td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="debSignRepo" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (DEB_SIGN_REPO == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <?php if (empty(DEB_SIGN_REPO)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </tr>

                <?php if (DEB_SIGN_REPO == 'yes') : ?>
                    <tr>
                        <td class="td-large">
                            <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Adresse email liée à la clé GPG utilisée pour signer les repos."> Identifiant email de la clé GPG
                        </td>
                        <td>
                            <input type="text" name="debGpgKeyID" autocomplete="off" value="<?= DEB_SIGN_GPG_KEYID ?>">
                        </td>
                        <td>
                            <?php if (empty(DEB_SIGN_GPG_KEYID)) {
                                echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                            } ?>
                        </td>
                    </tr>
                <?php endif ?>
            <?php endif ?>
        </table>

        <br><h3>CONFIGURATION WEB</h3>

        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Utilisateur Linux exécutant le service web de ce serveur" />Utilisateur web
                </td>
                <td>
                    <input type="text" name="wwwUser" autocomplete="off" value="<?= WWW_USER ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(WWW_USER)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Nom Hôte
                </td>
                <td>
                    <input type="text" name="wwwHostname" autocomplete="off" value="<?= WWW_HOSTNAME ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(WWW_HOSTNAME)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="" />Url d'accès aux repos
                </td>
                <td>
                    <input type="text" name="wwwReposDirUrl" autocomplete="off" value="<?= WWW_REPOS_DIR_URL ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(WWW_REPOS_DIR_URL)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
        </table>

        <br><h3>GESTION DU PARC</h3>
        <table class="table-medium">
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Activer la gestion des hôtes utilisant yum-update-auto / apt-update-auto" />Activer la gestion des hôtes
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="manageHosts" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (MANAGE_HOSTS == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(MANAGE_HOSTS)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Activer la gestion des profils pour les clients yum-update-auto / apt-update-auto (en cours de dev)" />Activer la gestion des profils
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="manageProfiles" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (MANAGE_PROFILES == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(MANAGE_PROFILES)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
        <?php   if (MANAGE_PROFILES == "yes") : ?>
                    <td class="td-large">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Préfixe pouvant s'ajouter aux noms de fichiers .repo / .list lors de l'installation sur les hôtes clients" />
                        Préfixe des fichiers de configuration client
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
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Activer les tâches planifiées (planifications)" />
                    Activer les tâches planifiées
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="automatisationEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (AUTOMATISATION_ENABLED == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(AUTOMATISATION_ENABLED)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
    <?php if (AUTOMATISATION_ENABLED == "yes") { ?>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à mettre à jour un repo ou un groupe de repos spécifié par une tâche planifiée" />
                    Autoriser la mise à jour automatique des repos
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="allowAutoUpdateRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (ALLOW_AUTOUPDATE_REPOS == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(ALLOW_AUTOUPDATE_REPOS)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à supprimer les plus vieux snapshots de repo en date en fonction du paramètre de rétention renseigné" />
                    Supprimer automatiquement les plus vieux snapshots de repos
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="allowAutoDeleteArchivedRepos" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (ALLOW_AUTODELETE_ARCHIVED_REPOS == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(ALLOW_AUTODELETE_ARCHIVED_REPOS)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr> 
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Nombre maximal de snapshots à conserver par repos" />
                    Retention
                </td>
                <td>
                    <input type="number" name="retention" autocomplete="off" value="<?= RETENTION ?>">
                </td>
                <td class="td-fit">
                    <?php if (empty(RETENTION)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
                </td>
            </tr>
            <tr>
                <td class="td-large">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Autoriser repomanager à envoyer des rappels par mail des tâches planifiées à venir. Un service d'envoi de mail doit être configuré sur le serveur (e.g. sendmail)." />
                    Recevoir des rappels de planifications
                </td>
                <td>
                    <label class="onoff-switch-label">
                        <input name="cronSendReminders" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (CRON_PLAN_REMINDERS_ENABLED == "yes") ? 'checked' : ''; ?>>
                        <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <td class="td-fit">
                    <?php if (empty(CRON_PLAN_REMINDERS_ENABLED)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                    } ?>
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
                $myenvs = new \Models\Environnement();
                $envs = $myenvs->listAll();

                foreach ($envs as $envName) {
                    echo '<tr>';
                    echo '<td>';
                    echo '<input type="text" name="actualEnv[]" value="' . $envName . '" />';
                    echo '</td>';
                    echo '<td class="td-fit center">';
                    echo "<img src=\"ressources/icons/bin.png\" class=\"envDeleteToggle-${envName} icon-lowopacity\" title=\"Supprimer l'environnement ${envName}\"/>";
                    \Models\Common::deleteConfirm("Êtes-vous sûr de vouloir supprimer l'environnement $envName", "?deleteEnv=${envName}", "envDeleteDiv-${envName}", "envDeleteToggle-${envName}");
                    echo '</td>';
                    if ($envName == DEFAULT_ENV) {
                        echo '<td>(defaut)</td>';
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
                <td class="td-fit">
                    <?php if (empty(ENVS)) {
                        echo '<img src="ressources/icons/warning.png" class="icon" title="Au moins un environnement doit être configuré" />';
                    } ?>
                </td>
                <td></td>
            </tr>
        </form>
        </table>

        <br><h3>BASES DE DONNÉES</h3>
        <table class="table-generic-blue table-large">
            <tr>
                <td class="td-50"><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Base de données principale de repomanager. L'application ne peut fonctionner si la base de données est en erreur." /> Principale</td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier de base de données
                     */
                    if (!is_readable(ROOT . "/db/repomanager.db")) {
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
                    $myconn = new \Models\Connection('main');

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
                <td class="td-50">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Base de données des statistiques des repos." /> Stats
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier
                     */
                    if (!is_readable(ROOT . "/db/repomanager-stats.db")) {
                        echo "Impossible de lire la base de données des statistiques";
                    } else {
                        echo '<span title="OK">Accès</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new \Models\Connection('stats');

                    if (!$myconn->checkStatsTables()) {
                        echo '<span title="Une ou plusieurs tables semblent manquantes">Etat des tables</span><img src="ressources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="Toutes les tables sont présentes">Etat des tables</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>
            <?php   }

            if (MANAGE_HOSTS == "yes") { ?>
            <tr>
                <td class="td-50">
                    <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Base de données des hôtes." /> Hosts
                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la lisibilité du fichier
                     */
                    if (!is_readable(ROOT . "/db/repomanager-hosts.db")) {
                        echo "Impossible de lire la base de données des hôtes";
                    } else {
                        echo '<span title="OK">Accès</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>

                </td>
                <td>
                    <?php
                    /**
                     *  Vérification de la présence des tables
                     */
                    $myconn = new \Models\Connection('hosts');

                    if (!$myconn->checkHostsTables()) {
                        echo '<span title="Une ou plusieurs tables semblent manquantes">Etat des tables</span><img src="ressources/icons/redcircle.png" class="icon-small" />';
                    } else {
                        echo '<span title="Toutes les tables sont présentes">Etat des tables</span><img src="ressources/icons/greencircle.png" class="icon-small" />';
                    } ?>
                </td>
            </tr>
            <?php   } ?>

        </table>

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
                            <input name="cronDailyEnable" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (CRON_DAILY_ENABLED == "yes") ? 'checked' : ''; ?>>
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
                                $cronStatus = exec("grep 'Status=' " . CRON_LOG . " | cut -d'=' -f2 | sed 's/\"//g'");
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
                        <?php if (empty(CRON_DAILY_ENABLED)) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                        } ?>
                    </td>
                </tr>
            <?php
            if (!empty($cronStatus) and $cronStatus === "KO") :
                $cronError = shell_exec("cat " . CRON_LOG . " | grep -v 'Status='"); ?>
                <tr>
                    <td colspan="100%">
                        <div id="cronjobStatusDiv" class="hide"><?=$cronError?></div>
                        <script>
                            $(document).ready(function(){
                                $("#cronjobStatusButton").click(function(){
                                    $("#cronjobStatusDiv").slideToggle(250);
                                    $(this).toggleClass("open");
                                });
                            });
                        </script>
                    </td>
                </tr>
            <?php       endif;

            if (CRON_DAILY_ENABLED == "yes") { ?>
                <tr>
                    <td class="td-fit">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Sauvegarder régulièrement la base de données et les fichiers de configuration de repomanager" />
                    </td>
                    <td class="td-medium">Sauvegarder régulièrement la base de données et les fichiers de configuration</td>
                    <td>
                        <label class="onoff-switch-label">
                            <input name="cronSaveConf" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (CRON_SAVE_CONF == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td></td>
                    <td class="td-fit">
                        <?php if (empty(CRON_SAVE_CONF)) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                        } ?>
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
                            <input name="cronApplyPerms" type="checkbox" class="onoff-switch-input" value="yes" <?php echo (CRON_APPLY_PERMS == "yes") ? 'checked' : ''; ?>>
                            <span class="onoff-switch-slider"></span>
                        </label>
                    </td>
                    <td></td>
                    <td class="td-fit">
                        <?php if (empty(CRON_APPLY_PERMS)) {
                            echo '<img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" />';
                        } ?>
                    </td>
                </tr>
            <?php   }

            if (AUTOMATISATION_ENABLED == "yes" and CRON_PLAN_REMINDERS_ENABLED == "yes") : ?>
                <tr>
                    <td class="td-fit">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Tâche cron envoyant des rappels automatiques des futures planifications" />
                    </td>
                    <td class="td-medium">
                        Rappels de planifications
                    </td>
                    <td></td>
                    <td>
                        <?php
                        /**
                         *  On vérifie la présence d'une ligne dans la crontab
                         */
                        $cronStatus = \Models\Common::checkCronReminder();

                        if ($cronStatus == 'On') {
                            echo '<span title="OK">Status <img src="ressources/icons/greencircle.png" class="icon-small" /></span>';
                        }
                        if ($cronStatus == 'Off') {
                            echo '<span title="Erreur">Status <img src="ressources/icons/redcircle.png" class="icon-small" /></span>';
                        } ?>
                    </td>
                </tr>
            <?php       endif;

            if (CRON_STATS_ENABLED == "yes") { ?>
                <tr>
                    <td class="td-fit">
                        <img src="ressources/icons/info.png" class="icon-verylowopacity" title="Tâche cron générant des statistiques pour chaque repo" />
                    </td>
                    <td class="td-medium">
                        Génération de statistiques
                    </td>
                    <td></td> 
                    <td>

                    <?php
                    /**
                     *  si un fichier de log existe, on récupère l'état
                     */
                    if (file_exists(CRON_STATS_LOG)) {
                        $cronStatus = exec("grep 'Status=' " . CRON_STATS_LOG . " | cut -d'=' -f2 | sed 's/\"//g'");
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

<?php
    /**
     *  Cette section est accessible uniquement pour les utilisateurs dont le role est 'super-administrator'
     */
if ($_SESSION['role'] === 'super-administrator') { ?>
        <section class="right">
            <h3>UTILISATEURS</h3>
                <form action="configuration.php" method="post" autocomplete="off">
                    <input type="hidden" name="action" value="createUser" />

                    <p>Créer un utilisateur :</p>
                    <input class="input-medium" type="text" name="username" placeholder="Nom d'utilisateur" />
                    <select name="role" class="select-medium">
                        <option value="">Sélectionner role...</option>
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
                    echo '<p class="greentext">Mot de passe temporaire généré pour <b>' . $newUserUsername . '</b> : ' . $newUserPassword . '</p>';
                }
                /**
                 *  Cas où un mot de passe a été reset
                 */
                if (!empty($newResetedPwdUsername) and !empty($newResetedPwdPassword)) {
                    echo '<p class="greentext">Un nouveau mot de passe a été généré pour <b>' . $newResetedPwdUsername . '</b> : ' . $newResetedPwdPassword . '</p>';
                }

                echo '<br>';

                /**
                 *  Affichage des utilisateurs existants
                 */
                $myuser = new \Models\Login();
                $users = $myuser->getUsers();

                if (!empty($users)) { ?>
                        <table class="table-generic-blue">
                            <tr class="no-bkg">
                                <td>Nom d'utilisateur</td>
                                <td>Role</td>
                                <td>Type de compte</td>
                                <td></td>
                            </tr>
                    <?php   foreach ($users as $user) { ?>
                            <tr>
                                <td><?php echo $user['Username'];?></td>
                                <td><?php echo $user['Role_name'];?></td>
                                <td><?php echo $user['Type'];?></td>
                                <?php
                                if ($user['Username'] != 'admin') {
                                    echo '<td class="td-fit">';
                                    echo '<a href="?resetPassword&username=' . $user['Username'] . '" title="Réinitialiser le mot de passe de ' . $user['Username'] . '"><img src="ressources/icons/update.png" class="icon-lowopacity" /></a>';
                                    echo '<a href="?deleteUser&username=' . $user['Username'] . '" title="Supprimer l\'utilisateur ' . $user['Username'] . '"><img src="ressources/icons/bin.png" class="icon-lowopacity" /></a>';
                                    echo '</td>';
                                } else {
                                    echo '<td></td>';
                                } ?>
                            </tr>
                    <?php   }
                    echo '</table>';
                }
                ?>
        </section>
<?php } ?>
</section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>
</body>
</html>