<!DOCTYPE html>
<html>
<?php 
require_once('../models/Autoloader.php');
Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Cas où on souhaite modifier la conf serveur
 */
if (!empty($_POST['action']) AND Common::validateData($_POST['action']) === "applyServerConfiguration") {
    if (!empty($_POST['serverConf_manageClientsConf'])) { $serverConf_manageClientsConf = Common::validateData($_POST['serverConf_manageClientsConf']); } else { $serverConf_manageClientsConf = 'no'; }
    if (!empty($_POST['serverConf_manageClients_reposConf'])) { $serverConf_manageClients_reposConf = Common::validateData($_POST['serverConf_manageClients_reposConf']); } else { $serverConf_manageClients_reposConf = 'no'; }

    /**
     *  On forge le bloc de conf qu'on va écrire dans le fichier
     */
    $conf = '[REPOSERVER]'.PHP_EOL;
    $conf .= 'IP="'.__SERVER_IP__.'"'.PHP_EOL;
    $conf .= 'URL="'.__SERVER_URL__.'"'.PHP_EOL;
    $conf .= 'PROFILES_URL="'.WWW_PROFILES_DIR_URL.'"'.PHP_EOL;
    $conf .= 'OS_FAMILY="'.OS_FAMILY.'"'.PHP_EOL;
    $conf .= 'OS_NAME="'.OS_NAME.'"'.PHP_EOL;
    $conf .= 'OS_ID="'.OS_ID.'"'.PHP_EOL;
    $conf .= 'OS_VERSION="'.OS_VERSION.'"'.PHP_EOL;
    $conf .= 'PACKAGE_TYPE="'.PACKAGE_TYPE.'"'.PHP_EOL;

    /**
     *  Sur les systèmes CentOS il est possible de modifier la variable releasever, permettant de faire des miroirs de version de paquets différent de l'OS
     *  Si c'est le cas, (RELEASEVER différent de la version d'OS_VERSION alors il faut indiquer aux serveurs clients que ce serveur gère des paquets de version RELEASEVER)
     */
    if (OS_FAMILY == "Redhat") {
        if (!empty(RELEASEVER) AND RELEASEVER !== OS_VERSION) {
            $conf .= 'PACKAGES_OS_VERSION="'.RELEASEVER.'"'.PHP_EOL;
        }
    }
    $conf .= 'MANAGE_CLIENTS_CONF="'.$serverConf_manageClientsConf.'"'.PHP_EOL;
    $conf .= 'MANAGE_CLIENTS_REPOSCONF="'.$serverConf_manageClients_reposConf.'"'.PHP_EOL;

    /**
     *  Ajout de la conf au fichier de conf serveur
     */
    file_put_contents(PROFILE_SERVER_CONF, $conf);

    /**
     *  Affichage d'un message
     */
    Common::printAlert("La configuration du serveur a été enregistrée", 'success');
}

/**
 *  Récupération de la conf dans le fichier de conf serveur
 */
$serverConf_manageClientsConf = exec("grep '^MANAGE_CLIENTS_CONF=' ".PROFILE_SERVER_CONF." | cut -d'=' -f2 | sed 's/\"//g'");
$serverConf_manageClients_reposConf = exec("grep '^MANAGE_CLIENTS_REPOSCONF=' ".PROFILE_SERVER_CONF." | cut -d'=' -f2 | sed 's/\"//g'");
?>

<body>
<?php include_once('../includes/header.inc.php'); ?>

<article>
<section class="mainSectionLeft">
    <!-- REPOS ACTIFS -->
    <section id="profilesDiv" class="left">
        <h3>PROFILS</h3>
        <p>Vous pouvez créer des profils de configuration pour vos serveurs clients utilisant <a href="https://github.com/lbr38/linupdate">linupdate</a>.<br>A chaque exécution d'une mise à jour, les clients récupèreront automatiquement leur configuration et leurs fichiers de repo depuis ce serveur de repo.</p>
        <br>
        <p>Créer un nouveau profil :</p>
        <form id="newProfileForm" action="profiles.php" method="post" autocomplete="off">
            <input id="newProfileInput" type="text" class="input-medium" />
            <button type="submit" class="btn-xxsmall-blue" title="Ajouter">+</button>
        </form>
        <br>
        <?php
        /**
         *  Récupération de tous les noms de profils
         */
        $profilesNames = scandir(PROFILES_MAIN_DIR); // 

        /**
         *  Tri des profils afin de les afficher dans l'ordre alpha
         */
        sort($profilesNames);

        if (!empty($profilesNames)) {
            $repo = new Repo();

            echo '<p><b>PROFILS ACTIFS</b></p>';
            echo '<div class="profileDivContainer">';

            /**
             *  Affichage des profils et leur configuration
             */
            foreach($profilesNames as $profileName) {
                if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "_reposerver") AND ($profileName != PROFILE_SERVER_CONF)) { ?>
                    <div class="profileDiv">
                        <form class="profileForm" profilename="<?php echo $profileName;?>" autocomplete="off">
                            <table class="table-large">
                                <tr>
                                    <td>
                                        <input type="text" class="invisibleInput-blue profileFormInput" profilename="<?php echo $profileName;?>" value="<?php echo $profileName;?>" />
                                    </td>
                                    <td class="td-fit">
                                        <img src="ressources/icons/cog.png" class="profileConfigurationBtn icon-mediumopacity" profilename="<?php echo $profileName;?>" title="Configuration de <?php echo $profileName;?>" />
                                        <img src="ressources/icons/duplicate.png" class="duplicateProfileBtn icon-mediumopacity" profilename="<?php echo $profileName;?>" title="Créer un nouveau profil en dupliquant la configuration de <?php echo $profileName;?>" />
                                        <img src="ressources/icons/bin.png" class="deleteProfileBtn icon-mediumopacity" profilename="<?php echo $profileName;?>" title="Supprimer le profil <?php echo $profileName;?>" />
                                    </td>
                                </tr>
                            </table>
                        </form>
         
                        <div id="profileConfigurationDiv-<?php echo $profileName;?>" class="hide profileDivConf">
                            <form class="profileConfigurationForm" profilename="<?php echo $profileName;?>" autocomplete="off">
                                <?php
                                if ($serverConf_manageClients_reposConf == "yes") {
                                    if (OS_FAMILY == "Redhat") echo '<p>Repos :</p>';
                                    if (OS_FAMILY == "Debian") echo '<p>Sections de repos :</p>'; ?>
                                    <table class="table-large">
                                        <tr>
                                            <td colspan="100%">
                                                <select class="reposSelectList" profilename="<?php echo $profileName;?>" name="profileRepos[]" multiple>
                                                    <?php
                                                    /**
                                                     *  On récupère la liste des repos actifs
                                                     *  Puis pour chaque repos, on regarde si celui-ci est déjà présent dans le profil, si c'est le cas il sera affiché sélectionné dans la liste déroulante, si ce n'est pas le cas il sera disponible dans la liste déroulante 
                                                     */
                                                    $reposList = $repo->listAll_distinct();
                                                    foreach($reposList as $myrepo) {
                                                        $repoName = $myrepo['Name'];
                                                        if (OS_FAMILY == "Debian") {
                                                            $repoDist = $myrepo['Dist'];
                                                            $repoSection = $myrepo['Section'];
                                                        }
                                                        if (OS_FAMILY == "Redhat") {
                                                            // Si un fichier de repo existe dans ce profil, alors on génère une option "selected" pour indiquer que le repo est déjà présent dans ce profil
                                                            if (file_exists(PROFILES_MAIN_DIR."/${profileName}/".REPO_CONF_FILES_PREFIX."${repoName}.repo")) {
                                                                echo "<option value=\"${repoName}\" selected>${repoName}</option>";
                                                            } else {
                                                                echo "<option value=\"${repoName}\">${repoName}</option>";
                                                            }
                                                        }
                                                        if (OS_FAMILY == "Debian") {
                                                            /**
                                                             *  Si le nom de la distribution comporte un slash, alors on remplace '/' par '--slash--' car c'est comme cela qu'il sera écrit dans le nom du fichier
                                                             */
                                                            if (preg_match('#/#', $repoDist)) {
                                                                $repoDistFormatted = str_replace('/', '--slash--', $repoDist);

                                                                // Si un fichier de repo existe dans ce profil, alors on génère une option "selected" pour indiquer que le repo est déjà présent dans ce profil
                                                                if (file_exists(PROFILES_MAIN_DIR."/${profileName}/".REPO_CONF_FILES_PREFIX."${repoName}_${repoDistFormatted}_${repoSection}.list")) {
                                                                    echo "<option value=\"${repoName}|${repoDist}|${repoSection}\" selected>${repoName} - ${repoDist} - ${repoSection}</option>";
                                                                } else {
                                                                    echo "<option value=\"${repoName}|${repoDist}|${repoSection}\">${repoName} - ${repoDist} - ${repoSection}</option>";
                                                                }
                                                            } else {
                                                                // Si un fichier de repo existe dans ce profil, alors on génère une option "selected" pour indiquer que le repo est déjà présent dans ce profil
                                                                if (file_exists(PROFILES_MAIN_DIR."/${profileName}/".REPO_CONF_FILES_PREFIX."${repoName}_${repoDist}_${repoSection}.list")) {
                                                                    echo "<option value=\"${repoName}|${repoDist}|${repoSection}\" selected>${repoName} - ${repoDist} - ${repoSection}</option>";
                                                                } else {
                                                                    echo "<option value=\"${repoName}|${repoDist}|${repoSection}\">${repoName} - ${repoDist} - ${repoSection}</option>";
                                                                }
                                                            }
                                                        }
                                                    } ?>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                    <br>
                                    <hr>
                                    <br>
                        <?php   }

                                /**
                                 *  Si le serveur est configuré pour gérer la conf des serveurs clients alors on affiche la configuration pour chaque profil
                                 */
                                if ($serverConf_manageClientsConf == "yes") {
                                    $myProfile = new Profile();

                                    /**
                                     *  On récupére la conf du profil contenue dans le fichier "config"
                                     */
                                    $profileConf_excludeMajor = exec("grep '^EXCLUDE_MAJOR=' ".PROFILES_MAIN_DIR."/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                                    $profileConf_exclude = exec("grep '^EXCLUDE=' ".PROFILES_MAIN_DIR."/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                                    $profileConf_needRestart = exec("grep '^NEED_RESTART=' ".PROFILES_MAIN_DIR."/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                                    $profileConf_keepCron = exec("grep '^KEEP_CRON=' ".PROFILES_MAIN_DIR."/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                                    $profileConf_allowOverwrite = exec("grep '^ALLOW_OVERWRITE=' ".PROFILES_MAIN_DIR."/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");
                                    $profileConf_allowReposFilesOverwrite = exec("grep '^ALLOW_REPOSFILES_OVERWRITE=' ".PROFILES_MAIN_DIR."/${profileName}/config | cut -d'=' -f2 | sed 's/\"//g'");

                                    echo '<p>Paquets à exclure en cas de version majeure :</p>';
                                    $profileConf_excludeMajor = explode(',', $profileConf_excludeMajor);
                                    $profileConf_exclude = explode(',', $profileConf_exclude);
                                    $profileConf_needRestart = explode(',', $profileConf_needRestart);

                                    /**
                                     *  Liste des paquets sélectionnables dans la liste des paquets à exclure
                                     *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                                     */
                                    $listPackages = $myProfile->db_getPackages();
                                    sort($listPackages);

                                    /**
                                     *  Pour chaque paquet de cette liste, si celui-ci apparait dans $profileConf_excludeMajor alors on l'affiche comme sélectionné "selected"
                                     */ ?>
                                    <select class="excludeMajorSelectList" profilename="<?php echo $profileName;?>" name="profileConf_excludeMajor[]" multiple>
                            <?php       foreach($listPackages as $package) {
                                            if (in_array($package, $profileConf_excludeMajor)) {
                                                echo "<option value=\"$package\" selected>${package}</option>";
                                            } else {
                                                echo "<option value=\"$package\">${package}</option>";
                                            }
                                            /**
                                             *  On vérifie la même chose pour ce même paquet suivi d'un wildcard (ex: apache.*)
                                             */
                                            if (in_array("${package}.*", $profileConf_excludeMajor)) {
                                                echo "<option value=\"${package}.*\" selected>${package}.*</option>";
                                            } else {
                                                echo "<option value=\"${package}.*\">${package}.*</option>";
                                            }
                                        } ?>
                                    </select>
                                    <br>
                                    <p>Paquets à exclure (toute version) :</p>
                                    <select class="excludeSelectList" profilename="<?php echo $profileName;?>" name="profileConf_exclude[]" multiple>
                            <?php       foreach($listPackages as $package) {
                                            if (in_array($package, $profileConf_exclude)) {
                                                echo "<option value=\"$package\" selected>${package}</option>";
                                            } else {
                                                echo "<option value=\"$package\">${package}</option>";
                                            }
                                            /**
                                             *  On fait la même chose pour ce même paquet suivi d'un wildcard (ex: apache.*)
                                             */
                                            if (in_array("${package}.*", $profileConf_exclude)) {
                                                echo "<option value=\"${package}.*\" selected>${package}.*</option>";
                                            } else {
                                                echo "<option value=\"${package}.*\">${package}.*</option>";
                                            }
                                        } ?>
                                    </select>
                                    <br>
                                    <p>Services à redémarrer en cas de mise à jour :</p>
                                    <?php
                                    /**
                                     *  Liste des services sélectionnables dans la liste des services à redémarrer
                                     *  explode cette liste pour retourner un tableau, puis tri par ordre alpha
                                     */
                                    $listServices = $myProfile->db_getServices();
                                    sort($listServices); ?>

                                    <select class="needRestartSelectList" profilename="<?php echo $profileName;?>" name="profileConf_needRestart[]" multiple>
                            <?php       foreach($listServices as $service) {
                                            if (in_array($service, $profileConf_needRestart)) {
                                                echo "<option value=\"$service\" selected>${service}</option>";
                                            } else {
                                                echo "<option value=\"$service\">${service}</option>";
                                            }
                                        } ?>
                                    </select>
                                    <br>
                                    <table class="table-large">
                                        <tr>
                                            <td class="td-fit" title="Conserver ou non la tâche cron après exécution de la mise à jour">Conserver la tâche cron</td>
                                            <td>
                                                <label class="onoff-switch-label">
                                                    <input id="profileConf_keepCron" name="profileConf_keepCron" profilename="<?php echo $profileName;?>" type="checkbox" class="onoff-switch-input" <?php if ($profileConf_keepCron == "yes") echo 'checked';?> />
                                                    <span class="onoff-switch-slider"></span>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="td-fit" title="Autoriser linux-autoupdate à récupérer et écraser sa conf à chaque exécution">Autoriser la mise à jour auto. de la configuration</td>
                                            <td>
                                                <label class="onoff-switch-label">
                                                    <input id="profileConf_allowOverwrite" name="profileConf_allowOverwrite" profilename="<?php echo $profileName;?>" type="checkbox" class="onoff-switch-input" <?php if ($profileConf_allowOverwrite == "yes") echo 'checked';?> />
                                                    <span class="onoff-switch-slider"></span>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="td-fit" title="Autoriser linux-autoupdate à récupérer automatiquement les fichiers .list ou .repo de son profil">Autoriser la mise à jour auto. des fichiers de repo</td>
                                            <td>
                                                <label class="onoff-switch-label">
                                                    <input id="profileConf_allowReposFilesOverwrite" name="profileConf_allowReposFilesOverwrite" profilename="<?php echo $profileName;?>" type="checkbox" class="onoff-switch-input" <?php if ($profileConf_allowReposFilesOverwrite == "yes") echo 'checked';?> />
                                                    <span class="onoff-switch-slider"></span>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                        <?php   }
                                /**
                                 *  On n'affiche pas le bouton Enregistrer si les 2 paramètres ci-dessous sont tous les 2 à no
                                 */
                                if ($serverConf_manageClients_reposConf == "yes" OR $serverConf_manageClientsConf == "yes") {
                                    echo '<button type="submit" class="btn-large-green">Enregistrer</button>';
                                } ?>
                            </form>
                        </div>
                    </div>
    <?php       }
            }
            echo '</div>';
        } ?>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
        <h3>CONFIGURATION DE CE SERVEUR</h3>
        <form action="profiles.php" method="post" class="actionform" autocomplete="off">
            <input type="hidden" name="action" value="applyServerConfiguration" />
            <table class="table-large">
            <tr>
                <td><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Permet aux serveurs clients de récupérer la configuration de leur profil avec http. Sous-répertoire du répertoire des repos. Non-modifiable." />URL d'accès aux profils</td>
                <td><input type="text" class="td-medium" value="<?php echo WWW_PROFILES_DIR_URL;?>" readonly /></td>
            </tr>
            <tr>
                <td><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Famille d'OS que ce client gère. Défini en fonction de l'OS de ce serveur (non-modifiable). Seuls des serveurs clients de la même famille pourront récupérer leur configuration auprès de ce serveur." />Famille d'OS</td>
                <td><input type="text" class="td-medium" value="<?php echo OS_FAMILY;?>" readonly /></td>
            </tr>
            <tr>
                <td><img src="ressources/icons/info.png" class="icon-verylowopacity" title="OS de ce serveur. Les serveurs clients appartenant à la même famille que ce serveur mais pas au même OS pourront tout de même récupérer leur configuration auprès de ce serveur si les repos sont compatibles." />Nom de l'OS</td>
                <td><input type="text" class="td-medium" value="<?php echo OS_NAME;?>" readonly /></td>
            </tr>
            <tr>
                <td><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Version d'OS de ce serveur" />Version d'OS</td>
                <td><input type="text" class="td-medium" value="<?php echo OS_VERSION;?>" readonly /></td>
            </tr>
            <?php
            if (OS_FAMILY == "Redhat" AND defined('RELEASEVER') AND RELEASEVER !== OS_VERSION) {
                echo '<tr>';
                echo '<td><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Version d\'OS des paquets récupérés lors de la création de miroirs." />Version de paquets gérée</td>';
                echo '<td><input type="text" class="td-medium" value="'.RELEASEVER.'" readonly /></td>';
                echo '</tr>';
            }
            ?>
            <tr>
                <td><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si activé, ce serveur pourra choisir les paquets à exclure ou quels service redémarrer pour chaque profil de configuration. Cependant les clients qui téléchargeront la configuration de leur profil resteront en droit d'accepter ou non que ce serveur gère leur configuration." />Gérer la configuration des clients</td>
                <td class="td-medium">
                    <label class="onoff-switch-label">
                    <input name="serverConf_manageClientsConf" type="checkbox" class="onoff-switch-input" value="yes" <?php if ($serverConf_manageClientsConf == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <?php if (empty($serverConf_manageClientsConf)) {
                echo '<td class="td-fit"><img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" /></td>';
                } ?>
            </tr>
            <tr>
                <td><img src="ressources/icons/info.png" class="icon-verylowopacity" title="Si activé, ce serveur pourra choisir les repos à déployer pour chaque profil de configuration. Cependant les clients qui téléchargeront la configuration de leur profil resteront en droit d'accepter ou non que ce serveur gère leur configuration." />Gérer la configuration des repos clients</td>
                <td class="td-medium">
                    <label class="onoff-switch-label">
                    <input name="serverConf_manageClients_reposConf" type="checkbox" class="onoff-switch-input" value="yes" <?php if ($serverConf_manageClients_reposConf == "yes") { echo 'checked'; }?> />
                    <span class="onoff-switch-slider"></span>
                    </label>
                </td>
                <?php if (empty($serverConf_manageClients_reposConf)) {
                echo '<td class="td-fit"><img src="ressources/icons/warning.png" class="icon" title="Ce paramètre doit prendre une valeur" /></td>';
                } ?>
            </tr>
            <tr>
                <td colspan="100%"><button type="submit" class="btn-large-green">Enregistrer</button></td>
            </tr>
            </table>
        </form>
    </section>
</section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>
</body>
</html>